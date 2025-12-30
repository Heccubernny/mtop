<?php

namespace App\Http\Controllers\User\Auth;

use App\Constants\ExtensionConst;
use App\Constants\GlobalConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\ReferralSetting;
use App\Models\Admin\SetupKyc;
use App\Models\Agent;
use App\Models\Merchants\Merchant;
use App\Models\User;
use App\Models\UserAuthorization;
use App\Notifications\User\Auth\SendVerifyCode;
use App\Providers\Admin\BasicSettingsProvider;
use App\Providers\Admin\ExtensionProvider;
use App\Traits\AdminNotifications\AuthNotifications;
use App\Traits\ControlDynamicInputFields;
use App\Traits\PaymentGateway\Monnify;
use App\Traits\User\RegisteredUsers;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use AuthNotifications, ControlDynamicInputFields, Monnify, RegisteredUsers, RegistersUsers;

    protected $basic_settings;

    public function __construct()
    {
        $this->basic_settings = BasicSettingsProvider::get();
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        $client_ip = request()->ip() ?? false;
        $user_country = geoip()->getLocation($client_ip)['country'] ?? '';

        $page_title = __('User Registration');

        return view('user.auth.register', compact(
            'page_title',
            'user_country',
        ));
    }
    // ========================before registration======================================

    public function sendVerifyCode(Request $request)
    {
        $basic_settings = $this->basic_settings;
        if ($basic_settings->agree_policy) {
            $agree = 'required';
        } else {
            $agree = '';
        }
        if ($request->register_type == global_const()::PHONE) {
            $mobile_code = 'required';
        } else {
            $mobile_code = 'nullable';
        }

        $extension = ExtensionProvider::get()->where('slug', ExtensionConst::GOOGLE_RECAPTCHA_SLUG)->first();
        $captcha_rules = 'nullable';
        if ($extension && $extension->status == true) {
            $captcha_rules = 'required|string|g_recaptcha_verify';
        }

        $validated = Validator::make($request->all(), [
            'register_type' => 'required|in:'.global_const()::PHONE.','.global_const()::EMAIL,
            'credentials' => ['required', function ($attribute, $value, $fail) use ($request) {
                if ($request->type == global_const()::PHONE && ! preg_match('/^0?[0-9]{9,14}$/', $value)) {
                    $fail('The '.$attribute.' must be a valid phone number.');
                }
                if ($request->register_type == global_const()::EMAIL && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $fail('The '.$attribute.' must be a valid email address.');
                }
            }],
            'mobile_code' => $mobile_code,
            'agree' => $agree,
            'g-recaptcha-response' => $captcha_rules,

        ])->validate();

        if ($validated['register_type'] == GlobalConst::PHONE) {

            $code = generate_random_code();

            $country = select_country($validated['mobile_code']);
            $mobile_code = remove_special_char($country->mobile_code);
            $mobile = $mobile_code == '880' ? (int) $validated['credentials'] : $validated['credentials'];
            $full_mobile = $mobile_code.$mobile;

            $sms_verify_status = ($basic_settings->sms_verification == true) ? false : true;

            $exist = User::where('full_mobile', $full_mobile)->first();
            $exists_agent = Agent::where('full_mobile', $full_mobile)->first();
            $exists_merchant = Merchant::where('full_mobile', $full_mobile)->first();
            if ($exist || $exists_agent || $exists_merchant) {
                return back()->with(['error' => [__('User already  exists, please try with another phone or email address.')]]);
            }

            $data = [
                'user_id' => 0,
                'phone' => $full_mobile,
                'code' => $code,
                'token' => generate_unique_string('user_authorizations', 'token', 200),
                'created_at' => now(),
            ];

            DB::beginTransaction();
            try {
                if ($basic_settings->sms_verification == false) {
                    Session::put('register_data', [
                        'credentials' => $mobile,
                        'mobile_code' => $mobile_code,
                        'country_name' => $country->name,
                        'register_type' => $validated['register_type'],
                        'sms_verified' => $sms_verify_status,
                    ]);

                    return redirect()->route('user.register.kyc');

                }

                DB::table('user_authorizations')->insert($data);
                Session::put('register_data', [
                    'credentials' => $mobile,
                    'mobile_code' => $mobile_code,
                    'country_name' => $country->name,
                    'register_type' => $validated['register_type'],
                    'sms_verified' => $sms_verify_status,
                ]);
                if ($basic_settings->sms_notification == true && $basic_settings->sms_verification == true) {
                    try {
                        sendSmsNotAuthUser($full_mobile, 'SVER_CODE', [
                            'code' => $code,
                        ]);
                    } catch (Exception $e) {
                    }
                }
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();

                return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
            }

            return redirect()->route('user.sms.verify', $data['token'])->with(['success' => [__('SMS Verification Code Send')]]);
        } else {
            $exist = User::where('email', $validated['credentials'])->first();
            $exists_agent = Agent::where('email', $validated['credentials'])->first();
            $exists_merchant = Merchant::where('email', $validated['credentials'])->first();
            if ($exist || $exists_agent || $exists_merchant) {
                return back()->with(['error' => [__('User already  exists, please try with another phone or email address.')]]);
            }
            $code = generate_random_code();
            // dd($basic_settings->email_verification);
            logger()->info('Email verification setting: '.($basic_settings->email_verification ? 'Enabled' : 'Disabled'));
            $email_verify_status = ($basic_settings->email_verification == true) ? false : true;

            logger()->info('Email verification status: '.($email_verify_status ? 'Verified' : 'Not Verified. '.$email_verify_status));

            $data = [
                'user_id' => 0,
                'email' => $validated['credentials'],
                'code' => $code,
                'token' => generate_unique_string('user_authorizations', 'token', 200),
                'created_at' => now(),
            ];

            try {
                if ($basic_settings->email_verification == false) {
                    Session::put('register_data', [
                        'credentials' => $validated['credentials'],
                        'register_type' => $validated['register_type'],
                        'email_verified' => $email_verify_status,
                    ]);

                    return redirect()->route('user.register.kyc');
                }
                DB::table('user_authorizations')->insert($data);
                Session::put('register_data', [
                    'credentials' => $validated['credentials'],
                    'register_type' => $validated['register_type'],
                    'email_verified' => $email_verify_status,
                ]);
                if ($basic_settings->email_notification == true && $basic_settings->email_verification == true) {
                    try {
                        Log::info('Attempting to send verification email.', [
                            'to' => $validated['credentials'],
                            'code' => $code,
                        ]);
                        Notification::route('mail', $validated['credentials'])->notify(new SendVerifyCode($validated['credentials'], $code));
                        Log::info('Verification email notification triggered successfully.', [
                            'to' => $validated['credentials'],
                        ]);
                        Log::info('Mail is been sent');
                    } catch (Exception $e) {
                    }
                }
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();

                return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
            }

            return redirect()->route('user.email.verify', $data['token'])->with(['success' => [__('Verification code sent to your email address.')]]);
        }

    }

    /**
     * Method for sms verify code
     */
    public function verifyCode(Request $request, $token)
    {
        $request->merge(['token' => $token]);
        $request->validate([
            'token' => 'required|string|exists:user_authorizations,token',
            'code' => 'required|array',
            'code.*' => 'required|numeric',
        ]);
        $code = $request->code;
        $code = implode('', $code);
        $otp_exp_sec = BasicSettingsProvider::get()->otp_exp_seconds ?? GlobalConst::DEFAULT_TOKEN_EXP_SEC;
        $auth_column = UserAuthorization::where('token', $request->token)->where('code', $code)->first();
        if (! $auth_column) {
            return back()->with(['error' => [__('The verification code does not match')]]);
        }
        if ($auth_column->created_at->addSeconds($otp_exp_sec) < now()) {
            $auth_column->delete();

            return redirect()->route('user.register')->with(['error' => [__('Session expired. Please try again')]]);
        }

        try {
            $auth_column->delete();
        } catch (Exception $e) {
            return redirect()->route('user.register')->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return redirect()->route('user.register.kyc')->with(['success' => [__('Otp successfully verified')]]);
    }

    /**
     * Method for email verify code
     */
    public function EmailVerifyCode(Request $request, $token)
    {

        $request->merge(['token' => $token]);
        $request->validate([
            'token' => 'required|string|exists:user_authorizations,token',
            'code' => 'required|array',
            'code.*' => 'required|numeric',
        ]);
        $code = $request->code;
        $code = implode('', $code);
        $otp_exp_sec = BasicSettingsProvider::get()->otp_exp_seconds ?? GlobalConst::DEFAULT_TOKEN_EXP_SEC;
        $auth_column = UserAuthorization::where('token', $request->token)->where('code', $code)->first();
        if (! $auth_column) {
            return back()->with(['error' => [__('The verification code does not match')]]);
        }
        if ($auth_column->created_at->addSeconds($otp_exp_sec) < now()) {
            $auth_column->delete();

            return redirect()->route('user.register')->with(['error' => [__('Session expired. Please try again')]]);
        }
        try {
            $auth_column->delete();
        } catch (Exception $e) {
            return redirect()->route('user.register')->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return redirect()->route('user.register.kyc')->with(['success' => [__('Otp successfully verified')]]);
    }

    /**
     * Method for sms resend code
     */
    public function resendCode()
    {
        $mobile_code = remove_special_char(session()->get('register_data.mobile_code'));
        $phone = $mobile_code.session()->get('register_data.credentials');

        $resend = UserAuthorization::where('phone', $phone)->first();

        if ($resend) {
            if (Carbon::now() <= $resend->created_at->addMinutes(GlobalConst::USER_VERIFY_RESEND_TIME_MINUTE)) {
                throw ValidationException::withMessages([
                    'code' => __('You can resend the verification code after').' '.Carbon::now()->diffInSeconds($resend->created_at->addMinutes(GlobalConst::USER_PASS_RESEND_TIME_MINUTE)).' '.__('seconds'),
                ]);
            }
        }

        $code = generate_random_code();
        $data = [
            'user_id' => 0,
            'phone' => $phone,
            'code' => $code,
            'token' => generate_unique_string('user_authorizations', 'token', 200),
            'created_at' => now(),
        ];
        DB::beginTransaction();
        try {
            $oldToken = UserAuthorization::where('phone', $phone)->get();
            if ($oldToken) {
                foreach ($oldToken as $token) {
                    $token->delete();
                }
            }
            DB::table('user_authorizations')->insert($data);
            if ($this->basic_settings->sms_notification == true) {
                try {
                    sendSmsNotAuthUser($phone, 'SVER_CODE', [
                        'code' => $code,
                    ]);
                } catch (Exception $e) {
                }
            }
            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();

            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return redirect()->route('user.sms.verify', $data['token'])->with(['success' => [__('Verification code resend success')]]);
    }

    /**
     * Method for email resend code
     */
    public function emailResendCode()
    {
        $email = session()->get('register_data.credentials');
        $resend = UserAuthorization::where('email', $email)->first();
        if ($resend) {
            if (Carbon::now() <= $resend->created_at->addMinutes(GlobalConst::USER_VERIFY_RESEND_TIME_MINUTE)) {
                throw ValidationException::withMessages([
                    'code' => __('You can resend the verification code after').' '.Carbon::now()->diffInSeconds($resend->created_at->addMinutes(GlobalConst::USER_PASS_RESEND_TIME_MINUTE)).' '.__('seconds'),
                ]);
            }
        }

        $code = generate_random_code();
        $data = [
            'user_id' => 0,
            'email' => $email,
            'code' => $code,
            'token' => generate_unique_string('user_authorizations', 'token', 200),
            'created_at' => now(),
        ];
        DB::beginTransaction();
        try {
            $oldToken = UserAuthorization::where('email', $email)->get();
            if ($oldToken) {
                foreach ($oldToken as $token) {
                    $token->delete();
                }
            }
            DB::table('user_authorizations')->insert($data);
            if ($this->basic_settings->email_notification == true) {
                try {
                    Notification::route('mail', $email)->notify(new SendVerifyCode($email, $code));
                } catch (Exception $e) {
                }
            }
            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();

            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return redirect()->route('user.email.verify', $data['token'])->with(['success' => [__('Verification code resend success')]]);
    }

    /**
     * Method for view register kyc page
     */
    public function registerKyc($refer = null)
    {
        $basic_settings = $this->basic_settings;
        $mobile_code = session()->get('register_data.mobile_code') ?? null;
        $country_name = session()->get('register_data.country_name') ?? null;
        $credentials = session()->get('register_data.credentials');
        $register_type = session()->get('register_data.register_type');
        $full_name = session()->get('verified_identity.fullName');
        $first_name = session()->get('verified_identity.first_name') ?? '';
        $last_name = session()->get('verified_identity.last_name') ?? '';
        $middle_name = session()->get('verified_identity.middle_name') ?? '';
        $phone = session()->get('verified_identity.phone') ?? '';
        logger()->info(json_encode(session()->all()));
        //  if ($verifiedIdentity) {
        // $validated['firstname'] = explode(' ', $verifiedIdentity['name'])[0] ?? $request->firstname;
        // $validated['lastname']  = explode(' ', $verifiedIdentity['name'])[1] ?? $request->lastname;
        // }
        // ;

        if ($credentials == null) {
            return redirect()->route('user.register');
        }
        // $kyc_fields =[];
        // if($basic_settings->kyc_verification == true ){
        //     $user_kyc = SetupKyc::userKyc()->first();
        //     if($user_kyc != null){
        //         $kyc_data = $user_kyc->fields;
        //         $kyc_fields = [];
        //         if($kyc_data) {
        //             $kyc_fields = array_reverse($kyc_data);
        //         }
        //     }else{
        //         $kyc_fields =[];
        //     }
        // }

        $page_title = __('User Registration KYC');
        if ($refer && ! User::where('referral_id', $refer)->exists()) {
            $refer = '';
        }

        return view('user.auth.register-kyc', compact(
            'page_title',
            'mobile_code',
            'country_name',
            'credentials',
            'register_type',
            // 'kyc_fields',
            'refer',
            'full_name',
            'first_name',
            'last_name',
            'phone',
            'middle_name',
        ));
    }
    // ========================before registration======================================

    /**
     * Handle a registration request for the application.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $basic_settings = $this->basic_settings;

        $register_data = session()->get('register_data', []);
        logger()->info('Register data from session: '.json_encode($register_data));
        $verified_identity = session()->get('verified_identity', []);

        $mergedData = array_merge($request->all(), [
            'firstname' => $verified_identity['first_name'] ?? ($request->input('firstname') ?? ''),
            'lastname' => $verified_identity['last_name'] ?? ($request->input('lastname') ?? ''),
            'middlename' => $verified_identity['middle_name'] ?? '',
            'phone' => $verified_identity['phone'] ?? ($request->input('phone') ?? ''),
            'email' => $register_data['credentials'] ?? ($request->input('email') ?? ''),
        ]);

        $validated = $this->validator($mergedData)->validate();

        $mobile_code = $register_data['mobile_code'] ?? null;
        $country_name = $register_data['country_name'] ?? null;
        $register_type = $register_data['register_type'] ?? null;

        // if ($basic_settings->kyc_verification == true) {
        //     $user_kyc_fields = SetupKyc::userKyc()->first()->fields ?? [];
        //     $validation_rules = $this->generateValidationRules($user_kyc_fields);
        //     $kyc_validated = Validator::make($request->all(), $validation_rules)->validate();
        //     $get_values = $this->registerPlaceValueWithFields($user_kyc_fields, $kyc_validated);
        // }

        // Format phone
        // $validated['mobile_code'] = remove_speacial_char($validated['phone_code']);
        // $validated['mobile'] = get_mobile_number($validated['mobile_code'], $validated['phone']);
        $validated['mobile_code'] = remove_speacial_char(
            $mergedData['phone_code'] ?? ($register_data['mobile_code'] ?? '234')
        );

        $validated['mobile'] = get_mobile_number(
            $validated['mobile_code'],
            $validated['phone']
        );
        $complete_phone = $validated['mobile_code'].$validated['mobile'];

        // ✅ Prevent duplicates
        if (User::where('full_mobile', $complete_phone)->orWhere('email', $validated['email'])->exists() ||
            Agent::where('full_mobile', $complete_phone)->orWhere('email', $validated['email'])->exists() ||
            Merchant::where('full_mobile', $complete_phone)->orWhere('email', $validated['email'])->exists()) {
            throw ValidationException::withMessages([
                'phone' => __('The phone number or email address you have provided is already in use.'),
            ]);
        }

        // ✅ Generate username
        $userName = make_username($validated['firstname'], $validated['lastname']);
        if (User::where('username', $userName)->exists()) {
            $userName .= '-'.rand(123, 456);
        }

        // ✅ Determine SMS & Email Verification Status
        // $sms_verified = false;
        // $email_verified = false;
        logger()->info('Register type: '.$register_type);

        if ($register_type == GlobalConst::PHONE) {
            // $sms_verified = ($register_data['sms_verified'] ?? false) || !$basic_settings->sms_verification;
            if ($register_data['sms_verified'] == true && $basic_settings->sms_verification == false) {
                $sms_verified = true;
            } elseif ($register_data['sms_verified'] == false && $basic_settings->sms_verification == true) {
                $sms_verified = true;
            } else {
                $sms_verified = false;
            }
        } elseif ($basic_settings->sms_verification == false) {
            $sms_verified = true;
        } else {
            $sms_verified = false;
        }

        if ($register_type == GlobalConst::EMAIL) {
            logger()->info('Email verified in session: '.(($register_data['email_verified'] ? 'true' : 'false')));
            logger()->info('Email verification required: '.($basic_settings->email_verification ? 'true' : 'false'));
            logger()->info('Final email verified status: '.(($register_data['email_verified'])));
            // $email_verified = ($register_data['email_verified'] ?? false) || !$basic_settings->email_verification;
            if ($register_data['email_verified'] == true && $basic_settings->email_verification == false) {
                $email_verified = true;
            } elseif ($register_data['email_verified'] == false && $basic_settings->email_verification == true) {
                $email_verified = true;
            } else {
                $email_verified = false;
            }
        } elseif ($basic_settings->email_verification == false) {
            $email_verified = true;
        } else {
            $email_verified = false;
        }

        // ✅ Prepare user data for creation
        $validated['full_mobile'] = $complete_phone;
        $validated = Arr::except($validated, ['agree', 'phone_code', 'phone']);
        $validated['email_verified'] = $email_verified;
        $validated['sms_verified'] = $sms_verified;
        // $validated['kyc_verified'] = ($basic_settings->kyc_verification == true) ? false : true;
        $validated['password'] = Hash::make($validated['password']);
        $validated['username'] = $userName;
        $validated['address'] = [
            'country' => $validated['country'],
            'city' => $validated['city'],
            'zip' => $validated['zip_code'],
            'state' => '',
            'address' => '',
        ];
        $validated['registered_by'] = $register_type ?? GlobalConst::EMAIL;
        $validated['referral_id'] = generate_unique_string('users', 'referral_id', 8, 'number');

        // ✅ Create user
        $data = event(new Registered($user = $this->create($validated)));

        // ✅ If KYC enabled, save KYC data
        // if ($data && $basic_settings->kyc_verification == true) {
        //     $create = [
        //         'user_id' => $user->id,
        //         'data' => json_encode($get_values),
        //         'created_at' => now(),
        //     ];

        //     DB::beginTransaction();
        //     try {
        //         DB::table('user_kyc_data')->updateOrInsert(['user_id' => $user->id], $create);
        //         $user->update(['kyc_verified' => GlobalConst::PENDING]);
        //         DB::commit();
        //     } catch (\Exception $e) {
        //         DB::rollBack();
        //         $user->update(['kyc_verified' => GlobalConst::DEFAULT]);
        //         return back()->with('error', __('Something went wrong! Please try again.'));
        //     }
        // }

        // ✅ Clear session and log user in
        $request->session()->forget('register_data');
        $this->guard()->login($user);

        return $this->registered($request, $user);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator(array $data)
    {

        $basic_settings = $this->basic_settings;
        $passowrd_rule = 'required|string|min:6|confirmed';
        if ($basic_settings->secure_password) {
            $passowrd_rule = ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised()];
        }
        if ($basic_settings->agree_policy) {
            $agree = 'required';
        } else {
            $agree = '';
        }
        if ($basic_settings->email_verification) {
            $email_field = 'required|string|email|max:150|unique:users,email';
        } else {
            $email_field = 'nullable';
        }
        if ($basic_settings->sms_verification) {
            $mobile_code_field = 'required|string|max:10';
            $mobile_field = 'required|string|max:20|unique:users,mobile';
        } else {
            $mobile_code_field = 'nullable';
            $mobile_field = 'nullable';
        }

        return Validator::make($data, [
            'firstname' => 'required|string|max:60',
            'lastname' => 'required|string|max:60',
            'email' => $email_field,
            'password' => $passowrd_rule,
            'country' => 'required|string|max:150',
            'city' => 'required|string|max:150',
            'phone_code' => $mobile_code_field,
            'phone' => $mobile_field,
            'zip_code' => 'required|string|max:8',
            'agree' => $agree,
            'refer' => 'nullable|string|exists:users,referral_id',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return User::create($data);
    }

    /**
     * The user has been registered.
     *
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        $refer_system = ReferralSetting::first();

        try {
            $this->createUserWallets($user);
            if (isset($refer_system) && $refer_system->status == true) {
                $this->createAsReferUserIfExists($request, $user);
                $this->createNewUserRegisterBonus($user);
                $this->assignReferralLevelToNewUser($user);
            }

            $user->createQr();
            $this->registerNotificationToAdmin($user);
        } catch (Exception $e) {

            $this->guard()->logout();
            $user->delete();

            return redirect()->back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return redirect()->intended(route('user.dashboard'));
    }

    public function showForm()
    {
        return view('test.monnify');
    }

    public function verifyIdentity(Request $request)
    {
        $request->validate([
            'type' => 'required|in:bvn,nin',
            'bvn' => 'nullable|required_if:type,bvn',
            'nin' => 'nullable|required_if:type,nin',
            'name' => 'nullable|string',
            'dateOfBirth' => 'nullable|string',
            'mobileNo' => 'nullable|string',
        ]);

        try {
            $type = $request->input('type');
            $data = $request->only(['bvn', 'nin', 'name', 'dateOfBirth', 'mobileNo']);
            logger()->info('Verification data: '.json_encode($data));
            if (! empty($data['dateOfBirth'])) {
                try {
                    $data['dateOfBirth'] = \Carbon\Carbon::parse($data['dateOfBirth'])->format('d-M-Y');
                } catch (\Exception $e) {
                    // In case date parse fails
                    return back()->with('error', 'Invalid date format. Please use a valid date.');
                }
            }

            $this->initMonnify();
            $result = $this->verifyIdentityType($type, $data);
            // logger()->info('Verification result: ' . $result);
            logger()->info($result['data'] ?? 'No data in result');

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['responseMessage'] ?? 'Verification failed.',
                ]);
            }

            if ($type === 'nin') {

                $content = $result['data'] ?? [];
                $first_name = $content['firstName'];
                $last_name = $content['lastName'];
                $middle_name = $content['middleName'];
                $fullName = trim(($first_name ?? '').' '.($last_name ?? '').' '.($middle_name ?? ''));
                $dob = $content['dateOfBirth'] ?? null;
                $gender = $content['gender'] ?? null;
                $phone = $content['mobileNumber'] ?? null;

                // Save verified identity info to session for registration flow
                Session::put('verified_identity', [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'middle_name' => $middle_name,
                    'full_name' => $fullName,
                    'gender' => $gender,
                    'dob' => $dob,
                    'phone' => $phone,
                ]);

                alert()->success('Nin verification', 'Verify successfully');

                return response()->json([
                    'success' => true,
                    'message' => 'Verification successful!',
                    'data' => [
                        'fullName' => $fullName,
                        'dateOfBirth' => $dob,
                        'phoneNumber' => $phone,
                    ],
                ]);
            } else {

                $content = $result['data'] ?? [];
                $fullName = $data['name'] ?? '';
                $nameParts = preg_split('/\s+/', trim($fullName));
                $last_name = $nameParts[0] ?? '';
                $first_name = $nameParts[count($nameParts) - 1] ?? '';
                $middle_name = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 1, -1)) : '';

                $nameMatch = $content['name']['matchPercentage'] ?? 0;
                $dobMatch = $content['dateOfBirth'] ?? '';
                $mobileMatch = $content['mobileNo'] ?? '';

                // Only accept if name match >= 66%
                if ($nameMatch >= 66) {
                    // Store verified data in session
                    Session::put('verified_identity', [
                        'bvn' => $data['bvn'],
                        'name_match_percentage' => $nameMatch,
                        'dob_match' => $dobMatch,
                        'mobile_match' => $mobileMatch,
                        'full_name' => $data['name'] ?? null,
                        'first_name' => $first_name ?? null,
                        'last_name' => $last_name ?? null,
                        'middle_name' => $middle_name ?? null,

                        'date_of_birth' => $data['dateOfBirth'] ?? null,
                        'phone' => $data['mobileNo'] ?? null,
                    ]);

                    alert()->success('BVN Verification', 'Bro BVN verified successfully.');

                    return response()->json([
                        'success' => true,
                        'message' => 'BVN verification successful!',
                        'data' => [
                            'nameMatch' => $nameMatch,
                            'dobMatch' => $dobMatch,
                            'mobileMatch' => $mobileMatch,
                        ],
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'BVN verification failed — name match below acceptable threshold.',
                        'data' => [
                            'nameMatch' => $nameMatch,
                            'dobMatch' => $dobMatch,
                            'mobileMatch' => $mobileMatch,
                        ],
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Identity Verification Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during verification.',
            ]);
        }
    }
}
