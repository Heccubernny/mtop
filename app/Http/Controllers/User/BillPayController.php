<?php

namespace App\Http\Controllers\User;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\CabletvHelper;
use App\Http\Helpers\Clubkonnect\CkHelper;
use App\Http\Helpers\DataSubscriptionHelper;
use App\Http\Helpers\NotificationHelper;
use App\Http\Helpers\PushNotificationHelper;
use App\Http\Helpers\TransactionLimit;
use App\Http\Helpers\UtilityHelper;
use App\Jobs\ProcessBillPayment;
use App\Models\Admin\Currency;
use App\Models\Admin\ReloadlyApi;
use App\Models\Admin\TransactionSetting;
use App\Models\BillPayCategory;
use App\Models\CkCableTv;
use App\Models\CkCableTvPackages;
use App\Models\CkDataPlan;
use App\Models\CkMobileNetwork;
use App\Models\CkTransaction;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Notifications\Admin\ActivityNotification;
use App\Notifications\User\BillPay\BillPayMail;
use App\Notifications\User\BillPay\BillPayMailAutomatic;
use App\Notifications\User\ClubKonnect\AirtimePurchaseMail;
use App\Notifications\User\ClubKonnect\CableTvPurchaseMail;
use App\Notifications\User\ClubKonnect\DataPurchaseMail;
use App\Providers\Admin\BasicSettingsProvider;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BillPayController extends Controller
{
    protected $basic_settings;
    protected $services = [
        'dstv' => 'DSTV',
        'gotv' => 'GOTV',
        'startimes' => 'Startimes',
        'showmax' => 'Showmax',
    ];

    protected $dataServices = [
        'mtn-data' => 'MTN',
        'glo-data' => 'GLO',
        'glo-sme-data' => 'GLO SME',
        'airtel-data' => 'Airtel',
        'etisalat-data' => '9mobile',
        'smile-direct' => 'Smile',
        'spectranet' => 'Spectranet'
    ];

    public function __construct()
    {
        $this->basic_settings = BasicSettingsProvider::get();
    }
    public function index(Request $request)
    {
        // try {
        //     // $billers =  (new UtilityHelper())->getBillers([
        //     //     'size' => 500,
        //     //     'page' =>0
        //     // ], false);



        // } catch (Exception $e) {
        //     // return back()->with(['error' => [__("Something went wrong, please try again later.")]]);
        // }
        $billers = (new UtilityHelper())->getBillers(
            $request->input('type').'_BILL_PAYMENT',
            $request->input('country', 'NG'),
            $request->input('serviceType'),
            $request->input('page', 1),
            $request->input('size', 20)
        );
        Log::info("Billers info: ".json_encode($billers));
        // if ($request->ajax()) {
        // return view('user.partials.billers-list', compact('billers'))->render();
        // }
        // logger()->info(json_encode($request->all(), $request->ajax()));

        $contentArray = $billers["content"] ?? [];
        $billType = BillPayCategory::active()->orderByDesc('id')->get();
        $billTypeArray = $billType->toArray();
        foreach ($billTypeArray as &$item) {
            $item['item_type'] = 'MANUAL';
        }
        foreach ($contentArray as &$item) {
            $item['item_type'] = 'AUTOMATIC';
        }
        $billTypeCollection = collect($billTypeArray);
        $mergedCollection = collect($contentArray)->merge($billTypeCollection);
        $billType = $mergedCollection;
        $billPayCharge = TransactionSetting::where('slug', 'bill_pay')->where('status', 1)->first();
        $page_title = __("Bill Pay");
        $sender_wallets = Currency::sender()->active()->get();
        $transactions = Transaction::auth()->billPay()->latest()->take(10)->get();
        return view('user.sections.bill-pay.index', compact("page_title", 'billPayCharge', 'transactions', 'billType', 'sender_wallets'));
    }
    public function payConfirm(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'bill_type' => 'required|string',
            'bill_month' => 'required|string',
            'bill_number' => 'required|min:8',
            'amount' => 'required|numeric|gt:0',
            'currency' => 'required|exists:currencies,code',
        ])->validate();
        if ($request->biller_item_type === "AUTOMATIC") {
            return $this->automaticBillPay($request->all());
        }

        $user = userGuard()['user'];
        $amount = $validated['amount'];
        $billType = $validated['bill_type'];
        $bill_type = BillPayCategory::where('id', $billType)->first();
        $bill_number = $validated['bill_number'];

        $billPayCharge = TransactionSetting::where('slug', 'bill_pay')->where('status', 1)->first();

        $userWallet = UserWallet::where('user_id', $user->id)->whereHas("currency", function ($q) use ($validated) {
            $q->where("code", $validated['currency'])->active();
        })->active()->first();

        if (!$userWallet) {
            return back()->with(['error' => [__('User wallet not found!')]]);
        }
        $baseCurrency = Currency::default();
        if (!$baseCurrency) {
            return back()->with(['error' => [__('Default currency not found')]]);
        }
        $charges = $this->manualBillPayCharge($amount, $billPayCharge, $userWallet);


        $min_amount = $billPayCharge->min_limit / $charges['exchange_rate'];
        $max_amount = $billPayCharge->max_limit / $charges['exchange_rate'];



        if ($charges['sender_amount'] < $min_amount || $charges['sender_amount'] > $max_amount) {
            return back()->with(['error' => [__("Please follow the transaction limit")]]);
        }

        //daily and monthly
        try {
            (new TransactionLimit())->trxLimit('user_id', $userWallet->user->id, PaymentGatewayConst::BILLPAY, $userWallet->currency, $validated['amount'], $billPayCharge, PaymentGatewayConst::SEND);
        } catch (Exception $e) {
            $errorData = json_decode($e->getMessage(), true);
            return back()->with(['error' => [__($errorData['message'] ?? __("Something went wrong! Please try again.7"))]]);
        }

        //charge calculations
        if ($charges['payable'] > $userWallet->balance) {
            return back()->with(['error' => [__("Sorry, insufficient balance")]]);
        }
        try {
            $trx_id = 'BP'.getTrxNum();

            $sender = $this->insertSender($trx_id, $user, $userWallet, $amount, $bill_type, $bill_number, $charges, $request->biller_item_type, $request->bill_month);
            $this->insertSenderCharges($charges, $amount, $user, $sender);
            try {
                if ($this->basic_settings->email_notification == true) {
                    $notifyData = [
                        'trx_id' => $trx_id,
                        'bill_type' => @$bill_type->name,
                        'bill_number' => $bill_number,
                        'request_amount' => $amount,
                        'charges' => $charges['total_charge'],
                        'payable' => $charges['payable'],
                        'current_balance' => get_amount($userWallet->balance, null, $charges['precision_digit']),
                        'status' => __("Pending"),
                    ];
                    //send notifications
                    $user->notify(new BillPayMail($user, (object) $notifyData, $charges));
                }
            } catch (Exception $e) {
            }
            //sms notification
            if ($this->basic_settings->sms_notification == true) {
                try {
                    sendSms($user, 'BILL_PAY', [
                        'amount' => get_amount($charges['conversion_amount'], $charges['sender_currency'], $charges['precision_digit']),
                        'type' => $request->biller_item_type ?? '',
                        'bill_type' => $bill_type->name ?? '',
                        'bill_number' => $request->bill_number,
                        'month' => $request->bill_month,
                        'trx' => $trx_id,
                        'time' => now()->format('Y-m-d h:i:s A')
                    ]);
                } catch (Exception $e) {
                }
            }

            //admin notification
            $this->adminNotificationManual($trx_id, $charges, $bill_type, $user, $request->all());
            return redirect()->route("user.bill.pay.index")->with(['success' => [__('Bill pay request sent to admin successful')]]);
        } catch (Exception $e) {
            return back()->with(['error' => [__("Something went wrong! Please try again.8")]]);
        }

    }
    public function insertSender($trx_id, $user, $userWallet, $amount, $bill_type, $bill_number, $charges, $biller_item_type, $bill_month, $type = PaymentGatewayConst::BILLPAY)
    {
        $trx_id = $trx_id;
        $authWallet = $userWallet;
        $afterCharge = ($authWallet->balance - $charges['payable']);
        $details = [
            'bill_type_id' => $bill_type->id ?? '',
            'bill_type_name' => $bill_type->name ?? '',
            'bill_number' => $bill_number ?? "",
            'sender_amount' => $amount ?? "",
            'bill_month' => $bill_month ?? '',
            'bill_type' => $biller_item_type ?? '',
            'biller_info' => [],
            'api_response' => [],
            'charges' => $charges ?? [],
        ];
        DB::beginTransaction();
        try {
            $id = DB::table("transactions")->insertGetId([
                'user_id' => $user->id,
                'user_wallet_id' => $authWallet->id,
                'payment_gateway_currency_id' => null,
                'type' => $type,
                'trx_id' => $trx_id,
                'request_amount' => $amount,
                'payable' => $charges['payable'],
                'available_balance' => $afterCharge,
                'remark' => ucwords(remove_speacial_char($type, " "))." Request To Admin",
                'details' => json_encode($details),
                'attribute' => PaymentGatewayConst::SEND,
                'status' => 2,
                'created_at' => now(),
            ]);
            $this->updateSenderWalletBalance($authWallet, $afterCharge);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again.19"));
        }
        return $id;
    }

    public function insertDataSender($trx_id, $user, $userWallet, $amount, $bill_type, $bill_number, $charges, $biller_item_type, $bill_month)
    {
        $trx_id = $trx_id;
        $authWallet = $userWallet;
        $afterCharge = ($authWallet->balance - $charges['payable']);
        $details = [
            'bill_type_id' => $bill_type->id ?? '',
            'bill_type_name' => $bill_type->name ?? '',
            'bill_number' => $bill_number ?? "",
            'sender_amount' => $amount ?? "",
            'bill_month' => $bill_month ?? '',
            'bill_type' => $biller_item_type ?? '',
            'biller_info' => [],
            'api_response' => [],
            'charges' => $charges ?? [],
        ];
        DB::beginTransaction();
        try {
            $id = DB::table("transactions")->insertGetId([
                'user_id' => $user->id,
                'user_wallet_id' => $authWallet->id,
                'payment_gateway_currency_id' => null,
                'type' => PaymentGatewayConst::BILLPAY,
                'trx_id' => $trx_id,
                'request_amount' => $amount,
                'payable' => $charges['payable'],
                'available_balance' => $afterCharge,
                'remark' => ucwords(remove_speacial_char(PaymentGatewayConst::BILLPAY, " "))." Request To Admin",
                'details' => json_encode($details),
                'attribute' => PaymentGatewayConst::SEND,
                'status' => 2,
                'created_at' => now(),
            ]);
            $this->updateSenderWalletBalance($authWallet, $afterCharge);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again.20"));
        }
        return $id;
    }

    public function updateSenderWalletBalance($authWallet, $afterCharge)
    {
        $authWallet->update([
            'balance' => $afterCharge,
        ]);
    }
    public function insertSenderCharges($charges, $amount, $user, $id)
    {
        DB::beginTransaction();
        try {
            DB::table('transaction_charges')->insert([
                'transaction_id' => $id,
                'percent_charge' => $charges['percent_charge'],
                'fixed_charge' => $charges['fixed_charge'],
                'total_charge' => $charges['total_charge'],
                'created_at' => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title' => __("Bill Pay"),
                'message' => __("Bill pay request send to admin")." ".get_amount($amount, $charges['wallet_currency'], $charges['precision_digit'])." ".__("Successful"),
                'image' => get_image($user->image, 'user-profile'),
            ];

            UserNotification::create([
                'type' => NotificationConst::BILL_PAY,
                'user_id' => $user->id,
                'message' => $notification_content,
            ]);
            //Push Notifications
            if ($this->basic_settings->push_notification == true) {
                try {
                    (new PushNotificationHelper())->prepare([$user->id], [
                        'title' => $notification_content['title'],
                        'desc' => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                } catch (Exception $e) {
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again.21"));
        }
    }
    public function manualBillPayCharge($sender_amount, $charges, $userWallet)
    {
        $sPrecision = get_wallet_precision($userWallet->currency);
        $exchange_rate = get_default_currency_rate() / $userWallet->currency->rate;


        $data['exchange_rate'] = $exchange_rate;
        $data['sender_amount'] = $sender_amount;

        $data['sender_currency'] = get_default_currency_code();
        $data['sender_currency_rate'] = get_default_currency_rate();

        $data['wallet_currency'] = $userWallet->currency->code;
        $data['wallet_currency_rate'] = $userWallet->currency->rate;

        $data['percent_charge'] = ($sender_amount / 100) * $charges->percent_charge ?? 0;
        $data['fixed_charge'] = ($charges->fixed_charge * $userWallet->currency->rate) ?? 0;
        $data['total_charge'] = $data['percent_charge'] + $data['fixed_charge'];

        $data['sender_wallet_balance'] = $userWallet->balance;
        $data['conversion_amount'] = $sender_amount * $exchange_rate;
        $data['payable'] = $data['sender_amount'] + $data['total_charge'];
        $data['precision_digit'] = $sPrecision;

        logger()->info("Manual Bill Pay Charge: ".json_encode($data));

        return $data;
    }
    //start automatic bill pay
    public function automaticBillPay($request_data)
    {

        $user = userGuard()['user'];
        try {
            $biller = (new UtilityHelper())->getSingleBiller($request_data['bill_type']);
        } catch (Exception $e) {
            $biller = [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

        if (isset($biller['status']) && $biller['status'] == false) {
            return back()->with(['error' => [__("Something went wrong! Please try again.11")]]);
        } elseif (isset($biller['content']) && empty($biller['content'])) {
            return back()->with(['error' => [__("Something went wrong! Please try again.12")]]);
        }
        $biller = $biller['content'][0];

        // $referenceId = remove_special_char($user->username.getFirstChar($biller['name']).$request_data['bill_month']).rand(1323, 5666);
        $referenceBase = Str::slug(
            "{$user->username}-{$biller['name']}-{$request_data['bill_month']}-bill"
        );

        $referenceBase = substr($referenceBase, 0, 24);

        $referenceId = $referenceBase . '-' . substr(md5(uniqid()), 0, 8);
        $bill_amount = $request_data['amount'];
        $bill_number = $request_data['bill_number'];

        $userWallet = UserWallet::where('user_id', $user->id)->whereHas("currency", function ($q) use ($request_data) {
            $q->where("code", $request_data['currency'])->active();
        })->active()->first();

        if (!$userWallet) {
            return back()->with(['error' => [__('User wallet not found!')]]);
        }
        $baseCurrency = Currency::default();
        if (!$baseCurrency) {
            return back()->with(['error' => [__('Default currency not found')]]);
        }
        $billPayCharge = TransactionSetting::where('slug', 'bill_pay')->where('status', 1)->first();
        $charges = $this->automaticBillPayCharge($userWallet, $biller, $bill_amount, $billPayCharge);

        $minLimit = $biller['minLocalTransactionAmount'] / $charges['exchange_rate'];
        $maxLimit = $biller['maxLocalTransactionAmount'] / $charges['exchange_rate'];


        if ($charges['sender_amount'] < $minLimit || $charges['sender_amount'] > $maxLimit) {
            return back()->with(['error' => [__("Please follow the transaction limit")]]);
        }

        //daily and monthly
        try {
            (new TransactionLimit())->trxLimit('user_id', $userWallet->user->id, PaymentGatewayConst::BILLPAY, $userWallet->currency, $bill_amount, $billPayCharge, PaymentGatewayConst::SEND);
        } catch (Exception $e) {
            $errorData = json_decode($e->getMessage(), true);
            return back()->with(['error' => [__($errorData['message'] ?? __("Something went wrong! Please try again.13"))]]);
        }


        if ($charges['payable'] > $userWallet->balance) {
            return back()->with(['error' => [__('Sorry, insufficient balance')]]);
        }

        logger()->info("Biller Infomation: ".json_encode($biller));

        $payBillData = [
            'subscriberAccountNumber' => $bill_number,
            'amount' => getAmount($charges['conversion_amount'], 2),
            'amountId' => null,
            'billerId' => $biller['id'],
            'useLocalAmount' => $biller['localAmountSupported'],
            'referenceId' => $referenceId,
            'additionalInfo' => [
                'invoiceId' => null,
            ],

        ];
        $payBill = (new UtilityHelper())->payUtilityBill($payBillData);
        logger()->info("Pay Bill ".json_encode($payBill));
        if (isset($payBill['status']) && $payBill['status'] === false) {
            if ($payBill['message'] === "The provided reference ID has already been used. Please provide another one.") {
                $errorMessage = __("Bill payment already taken for")." ".$biller['name']." ".$request_data['bill_month'];
            } else {
                $errorMessage = $payBill['message'];
            }
            return back()->with(['error' => [$errorMessage]]);
        }
        try {
            $trx_id = 'BP'.getTrxNum();
            $transaction = $this->insertTransactionAutomatic($trx_id, $user, $userWallet, $charges, $request_data, $payBill ?? [], $biller);
            $this->insertAutomaticCharges($transaction, $charges, $biller, $request_data, $user);
            try {
                if ($this->basic_settings->email_notification == true) {
                    $notifyData = [
                        'trx_id' => $trx_id,
                        'biller_name' => $biller['name'],
                        'bill_month' => $request_data['bill_month'],
                        'bill_number' => $bill_number,
                        'request_amount' => get_amount($charges['sender_amount'], $charges['wallet_currency'], $charges['precision_digit']),
                        'exchange_rate' => get_amount(1, $charges['wallet_currency'])." = ".get_amount($charges['exchange_rate'], $charges['sender_currency'], $charges['precision_digit']),
                        'bill_amount' => get_amount($charges['conversion_amount'], $charges['sender_currency'], $charges['precision_digit']),
                        'charges' => get_amount($charges['total_charge'], $charges['wallet_currency'], $charges['precision_digit']),
                        'payable' => get_amount($charges['payable'], $charges['wallet_currency'], $charges['precision_digit']),
                        'current_balance' => get_amount($userWallet->balance, null, $charges['precision_digit']),
                        'status' => $payBill['status'] ?? __("Successful"),
                    ];
                    //send notifications
                    $user->notify(new BillPayMailAutomatic($user, (object) $notifyData));
                }

            } catch (Exception $e) {
            }
            if ($this->basic_settings->sms_notification == true) {
                try {
                    sendSms($user, 'BILL_PAY', [
                        'amount' => get_amount($charges['conversion_amount'], $charges['sender_currency'], $charges['precision_digit']),
                        'type' => $request_data['biller_item_type'] ?? '',
                        'bill_type' => $biller['name'] ?? '',
                        'bill_number' => $request_data['bill_number'],
                        'month' => $request_data['bill_month'],
                        'trx' => $trx_id,
                        'time' => now()->format('Y-m-d h:i:s A')
                    ]);
                } catch (Exception $e) {
                }
            }
            //admin notification
            $this->adminNotificationAutomatic($trx_id, $charges, $biller, $request_data, $user, $payBill);
            // Dispatch the job to process the payment status
            ProcessBillPayment::dispatch($transaction)->delay(now()->addSeconds(scheduleBillPayApiCall($payBill)));
            // ProcessBillPayment::dispatch($transaction)->delay(now()->addSeconds(10));
            return redirect()->route("user.bill.pay.index")->with(['success' => [__('Bill Pay Request Successful')]]);
        } catch (Exception $e) {
            return back()->with(['error' => [__("Something went wrong! Please try again.14")]]);
        }



    }
    public function insertTransactionAutomatic($trx_id, $user, $userWallet, $charges, $request_data, $payBill, $biller)
    {
        if ($payBill['status'] === "PROCESSING") {
            $status = PaymentGatewayConst::STATUSPROCESSING;
        } elseif ($payBill['status'] === "SUCCESSFUL") {
            $status = PaymentGatewayConst::STATUSSUCCESS;
        } else {
            $status = PaymentGatewayConst::STATUSFAILD;
        }
        $authWallet = $userWallet;
        $afterCharge = ($authWallet->balance - $charges['payable']);
        $details = [
            'bill_type_id' => $request_data['bill_type'] ?? '',
            'bill_type_name' => $biller['name'] ?? '',
            'bill_number' => $request_data['bill_number'] ?? '',
            'sender_amount' => $request_data['amount'] ?? 0,
            'bill_month' => $request_data['bill_month'] ?? '',
            'bill_type' => $request_data['biller_item_type'] ?? '',
            'biller_info' => $biller ?? [],
            'api_response' => $payBill ?? [],
            'charges' => $charges ?? [],
        ];
        DB::beginTransaction();
        try {
            $id = DB::table("transactions")->insertGetId([
                'user_id' => $user->id,
                'user_wallet_id' => $authWallet->id,
                'payment_gateway_currency_id' => null,
                'type' => PaymentGatewayConst::BILLPAY,
                'trx_id' => $trx_id,
                'request_amount' => $charges['sender_amount'],
                'payable' => $charges['payable'],
                'available_balance' => $afterCharge,
                'remark' => ucwords(remove_speacial_char(PaymentGatewayConst::BILLPAY, " "))." Request Successful",
                'details' => json_encode($details),
                'attribute' => PaymentGatewayConst::SEND,
                'status' => $status,
                'created_at' => now(),
            ]);
            $this->updateSenderWalletBalance($authWallet, $afterCharge);
            DB::commit();
        } catch (Exception $e) {

            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again.15"));
        }
        return $id;

    }
    public function insertAutomaticCharges($id, $charges, $biller, $request_data, $user)
    {
        DB::beginTransaction();
        try {
            DB::table('transaction_charges')->insert([
                'transaction_id' => $id,
                'percent_charge' => $charges['percent_charge'],
                'fixed_charge' => $charges['fixed_charge'],
                'total_charge' => $charges['total_charge'],
                'created_at' => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title' => __("Bill Pay"),
                'message' => __("Bill Pay For")." (".$biller['name']." ".$request_data['bill_month'].") ".get_amount($charges['sender_amount'], $charges['wallet_currency'], $charges['precision_digit'])." ".__("Successful"),
                'image' => get_image($user->image, 'user-profile'),
            ];

            UserNotification::create([
                'type' => NotificationConst::BILL_PAY,
                'user_id' => $user->id,
                'message' => $notification_content,
            ]);
            //Push Notifications
            if ($this->basic_settings->push_notification == true) {
                try {
                    (new PushNotificationHelper())->prepare([$user->id], [
                        'title' => $notification_content['title'],
                        'desc' => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                } catch (Exception $e) {
                }
            }
        } catch (Exception $e) {

            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again.16"));
        }
    }
    public function automaticBillPayCharge($userWallet, $biller, $amount, $charges)
    {
        $sPrecision = get_wallet_precision($userWallet->currency);

        $sender_currency = Currency::where(['code' => $biller['localTransactionCurrencyCode']])->first();
        $exchange_rate = $sender_currency->rate / $userWallet->currency->rate;


        $data['exchange_rate'] = $exchange_rate;
        $data['sender_amount'] = $amount;

        $data['sender_currency'] = $sender_currency->code;
        $data['sender_currency_rate'] = $sender_currency->rate;

        $data['wallet_currency'] = $userWallet->currency->code;
        $data['wallet_currency_rate'] = $userWallet->currency->rate;

        $data['percent_charge'] = ($amount / 100) * $charges->percent_charge ?? 0;
        $data['fixed_charge'] = ($charges->fixed_charge * $userWallet->currency->rate) ?? 0;
        $data['total_charge'] = $data['percent_charge'] + $data['fixed_charge'];

        $data['sender_wallet_balance'] = $userWallet->balance;
        $data['conversion_amount'] = $amount * $exchange_rate;
        $data['payable'] = $data['sender_amount'] + $data['total_charge'];
        $data['precision_digit'] = $sPrecision;

        return $data;

    }
    //admin notification
    public function adminNotificationManual($trx_id, $charges, $bill_type, $user, $request_data)
    {
        $exchange_rate = get_amount(1, $charges['wallet_currency'])." = ".get_amount($charges['exchange_rate'], $charges['sender_currency'], $charges['precision_digit']);
        $notification_content = [
            //email notification
            'subject' => __("Bill Pay For")." ".$bill_type->name.' ('.$request_data['bill_number'].' )',
            'greeting' => __("Bill pay request sent to admin successful")." (".$request_data['bill_month'].")",
            'email_content' => __("web_trx_id")." : ".$trx_id."<br>".__("Bill Number")." : ".$request_data['bill_number']."<br>".__("bill Type")." : ".$bill_type->name."<br>".__("Bill Amount")." : ".get_amount($charges['sender_amount'], $charges['wallet_currency'], $charges['precision_digit'])."<br>".__("Exchange Rate")." : ".$exchange_rate."<br>".__("Conversion Amount")." : ".get_amount($charges['conversion_amount'], $charges['sender_currency'], $charges['precision_digit'])."<br>".__("Fees & Charges")." : ".get_amount($charges['total_charge'], $charges['wallet_currency'], $charges['precision_digit'])."<br>".__("Total Payable Amount")." : ".get_amount($charges['payable'], $charges['wallet_currency'], $charges['precision_digit'])."<br>".__("Status")." : ".__("Pending"),

            //push notification
            'push_title' => __("Bill pay request sent to admin successful")." (".userGuard()['type'].")",
            'push_content' => __('web_trx_id')." : ".$trx_id.",".__("Bill Amount")." : ".get_amount($charges['sender_amount'], $charges['wallet_currency'], $charges['precision_digit']).",".__("Bill Month")." : ".$request_data['bill_month'].",".__("Bill Number")." : ".$request_data['bill_number'],

            //admin db notification
            'notification_type' => NotificationConst::BILL_PAY,
            'admin_db_title' => "Bill pay request sent to admin successful"." (".userGuard()['type'].")",
            'admin_db_message' => "Transaction ID"." : ".$trx_id.","."Bill Amount"." : ".get_amount($charges['sender_amount'], $charges['wallet_currency'], $charges['precision_digit']).",".__("Bill Month")." : ".$request_data['bill_month'].",".__("Bill Number")." : ".$request_data['bill_number'].","."Total Payable Amount"." : ".get_amount($charges['payable'], $charges['wallet_currency'], $charges['precision_digit'])." (".$user->email.")"
        ];
        try {
            //notification
            (new NotificationHelper())->admin(['admin.bill.pay.index', 'admin.bill.pay.pending', 'admin.bill.pay.processing', 'admin.bill.pay.complete', 'admin.bill.pay.canceled', 'admin.bill.pay.details', 'admin.bill.pay.approved', 'admin.bill.pay.rejected', 'admin.bill.pay.export.data'])
                ->mail(ActivityNotification::class, [
                    'subject' => $notification_content['subject'],
                    'greeting' => $notification_content['greeting'],
                    'content' => $notification_content['email_content'],
                ])
                ->push([
                    'user_type' => "admin",
                    'title' => $notification_content['push_title'],
                    'desc' => $notification_content['push_content'],
                ])
                ->adminDbContent([
                    'type' => $notification_content['notification_type'],
                    'title' => $notification_content['admin_db_title'],
                    'message' => $notification_content['admin_db_message'],
                ])
                ->send();


        } catch (Exception $e) {
        }

    }
    public function adminNotificationAutomatic($trx_id, $charges, $biller, $request_data, $user, $payBill)
    {
        $exchange_rate = get_amount(1, $charges['wallet_currency'])." = ".get_amount($charges['exchange_rate'], $charges['sender_currency'], $charges['precision_digit']);
        if ($payBill['status'] === "PROCESSING") {
            $status = "Processing";
        } elseif ($payBill['status'] === "SUCCESSFUL") {
            $status = "success";
        } else {
            $status = "Failed";
        }
        $notification_content = [
            //email notification
            'subject' => __("Bill Pay For")." ".$biller['name'].' ('.$request_data['bill_number'].' )',
            'greeting' => __("Bill pay successful")." (".$request_data['bill_month'].")",
            'email_content' => __("web_trx_id")." : ".$trx_id."<br>".__("Bill Number")." : ".$request_data['bill_number']."<br>".__("bill Type")." : ".$biller['name']."<br>".__("Bill Amount")." : ".get_amount($charges['sender_amount'], $charges['wallet_currency'], $charges['precision_digit'])."<br>".__("Exchange Rate")." : ".$exchange_rate."<br>".__("Fees & Charges")." : ".get_amount($charges['total_charge'], $charges['wallet_currency'], $charges['precision_digit'])."<br>".__("Total Payable Amount")." : ".get_amount($charges['payable'], $charges['wallet_currency'], $charges['precision_digit'])."<br>".__("Status")." : ".__($status),

            //push notification
            'push_title' => __("Bill pay successful")." (".userGuard()['type'].")",
            'push_content' => __('web_trx_id')." : ".$trx_id.",".__("Bill Amount")." : ".get_amount($charges['sender_amount'], $charges['wallet_currency'], $charges['precision_digit']).",".__("Bill Month")." : ".$request_data['bill_month'].",".__("Bill Number")." : ".$request_data['bill_number'].",".__("Biller Name")." : ".$biller['name'],

            //admin db notification
            'notification_type' => NotificationConst::BILL_PAY,
            'admin_db_title' => "Bill pay successful"." (".userGuard()['type'].")",
            'admin_db_message' => "Transaction ID"." : ".$trx_id.","."Bill Amount"." : ".get_amount($charges['sender_amount'], $charges['wallet_currency'], $charges['precision_digit']).",".__("Bill Month")." : ".$request_data['bill_month'].",".__("Bill Number")." : ".$request_data['bill_number'].","."Total Payable Amount"." : ".get_amount($charges['payable'], $charges['wallet_currency'], $charges['precision_digit']).",".__("Biller Name")." : ".$biller['name']." (".$user->email.")"
        ];
        try {
            //notification
            (new NotificationHelper())->admin(['admin.make.payment.index', 'admin.make.payment.export.data'])
                ->mail(ActivityNotification::class, [
                    'subject' => $notification_content['subject'],
                    'greeting' => $notification_content['greeting'],
                    'content' => $notification_content['email_content'],
                ])
                ->push([
                    'user_type' => "admin",
                    'title' => $notification_content['push_title'],
                    'desc' => $notification_content['push_content'],
                ])
                ->adminDbContent([
                    'type' => $notification_content['notification_type'],
                    'title' => $notification_content['admin_db_title'],
                    'message' => $notification_content['admin_db_message'],
                ])
                ->send();


        } catch (Exception $e) {
        }

    }

    public function cableServices()
    {
        $services = [
            'dstv' => [
                'name' => 'DSTV',
                'icon' => asset('images/tv/dstv.png'),
                'description' => 'Watch your favorite DSTV channels with flexible plans.',
            ],
            'gotv' => [
                'name' => 'GOTV',
                'icon' => asset('images/tv/gotv.png'),
                'description' => 'Enjoy affordable GOTV entertainment packages.',
            ],
            'startimes' => [
                'name' => 'Startimes',
                'icon' => asset('images/tv/startimes.png'),
                'description' => 'Digital TV with lots of exciting channels.',
            ],
            'showmax' => [
                'name' => 'Showmax',
                'icon' => asset('images/tv/showmax.png'),
                'description' => 'Stream top movies and series on-demand.',
            ],
        ];

        $page_title = __("Data Services");
        $transactions = Transaction::auth()->cableTv()->latest()->take(10)->get();


        return view('tv.services', compact('services', 'page_title', 'transactions'));
    }

    /**
     * Display variations (plans) for selected TV service.
     */
    public function showPlans($service)
    {
        $service = strtolower($service);

        if (!array_key_exists($service, $this->services)) {
            abort(404, 'Invalid TV service.');
        }


        $table = strtolower($service).'_plans';

        // Create table dynamically if not existing
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            DB::statement("
                CREATE TABLE {$table} (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    variation_code VARCHAR(255) UNIQUE,
                    name VARCHAR(255),
                    amount DECIMAL(10,2) NULL,
                    fixed_price VARCHAR(10) NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ");
        }
        $sender_wallets = Currency::sender()->active()->get();
        $billPayCharge = TransactionSetting::where('slug', 'cable_tv')->where('status', 1)->first();
        $transactions = Transaction::auth()->cableTv()->latest()->take(10)->get();

        // Fetch from DB or API
        $plans = DB::table($table)->get();


        if ($plans->isEmpty()) {
            $response = CabletvHelper::getVariations($service);

            // dd($response);

            // if (!isset($response['content']['variations'])) {
            //     return back()->with('error', 'Unable to fetch ' . strtoupper($service) . ' plans from VTpass.');
            // }
            if (!isset($response['content']['variations'])) {
                return view('tv.error', [
                    'message' => 'Unable to fetch '.strtoupper($service).' plans at the moment.',
                    'service' => $service,
                ]);
            }


            foreach ($response['content']['variations'] as $plan) {
                DB::table($table)->updateOrInsert(
                    ['variation_code' => $plan['variation_code']],
                    [
                        'name' => $plan['name'],
                        'amount' => $plan['variation_amount'],
                        'fixed_price' => $plan['fixedPrice'] ?? 'Yes',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $plans = DB::table($table)->get();
        }

        return view('tv.plans', [
            'plans' => $plans,
            'service' => $service,
            'title' => strtoupper($this->services[$service]).' Plans',
            'sender_wallets' => $sender_wallets,
            'billPayCharge' => $billPayCharge,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Verify Smartcard (for DSTV, GOTV, Startimes)
     */
    public function verifySmartCard(Request $request, $service)
    {
        $request->validate(['smartcard_number' => 'required|string']);

        $response = CabletvHelper::verifySmartCard($service, $request->smartcard_number);
        // dd($response);
        if (!isset($response['code']) || $response['code'] !== '000' || isset($response['content']['error'])) {
            return response()->json([
                'success' => false,
                'message' => $response['content']['error'] ?? 'Verification failed. Please try again.17',
            ]);
        }

        $content = $response['content'];

        return response()->json([
            'success' => true,
            'message' => 'Smartcard verified successfully.',
            'data' => [
                'customer_name' => $content['Customer_Name'] ?? '',
                'status' => $content['Status'] ?? '',
                'due_date' => $content['Due_Date'] ?? '',
                'customer_number' => $content['Customer_Number'] ?? '',
                'customer_type' => $content['Customer_Type'] ?? '',
            ],
        ]);
    }



    /**
     * Purchase subscription (for all services)
     */
    public function buySubscription(Request $request, $service)
    {
        $request->validate([
            'smartcard_number' => 'nullable|string',
            'phone' => 'required|string',
            'variation_code' => 'required|string',
            'amount' => 'required|numeric',
            'currency' => 'required|exists:currencies,code',
        ]);

        $user = userGuard()['user'];
        $amount = $request->amount;
        $bill_type = BillPayCategory::where('id', 8)->first();

        logger()->info(json_encode($amount));

        // Pass service and form data to helper
        $response = CabletvHelper::purchaseTV($service, [
            'smartcard_number' => $request->smartcard_number,
            'variation_code' => $request->variation_code,
            'amount' => $request->amount,
            'phone' => $request->phone,
        ]);

        logger("response: ".json_encode($response));


        // Handle response
        if (!isset($response['content']['transactions'])) {
            return back()->with('error', $response['response_description'] ?? 'Transaction failed.');
        }

        $billPayCharge = TransactionSetting::where('slug', 'cable_tv')->where('status', 1)->first();
        // $billType = $mergedCollection;
        $page_title = __("Cable TV Subscription");
        $sender_wallets = Currency::sender()->active()->get();
        $transactions = Transaction::auth()->cableTv()->latest()->take(10)->get();
        $userWallet = UserWallet::where('user_id', $user->id)->whereHas("currency", function ($q) use ($request) {
            $q->where("code", $request->currency)->active();
        })->active()->first();

        $bill_number = $request->smartcard_number ?? 'N/A';

        logger()->info(json_encode([$userWallet, $billPayCharge, $sender_wallets, $transactions]));

        if (!$userWallet) {
            return back()->with(['error' => [__('User wallet not found!')]]);
        }
        $baseCurrency = Currency::default();
        if (!$baseCurrency) {
            return back()->with(['error' => [__('Default currency not found')]]);
        }

        $charges = $this->manualBillPayCharge(
            $amount,
            $billPayCharge,
            $userWallet,
        );
        $min_amount = $billPayCharge->min_limit / $charges['exchange_rate'];
        $max_amount = $billPayCharge->max_limit / $charges['exchange_rate'];
        if ($charges['sender_amount'] < $min_amount || $charges['sender_amount'] > $max_amount) {
            return back()->with(['error' => [__("Please follow the transaction limit")]]);
        }
        logger()->info(json_encode([$charges, $min_amount, $max_amount, $baseCurrency]));
        //daily and monthly
        try {
            logger()->info("Checking transaction limit");
            (new TransactionLimit())->trxLimit('user_id', $userWallet->user->id, PaymentGatewayConst::CABLETV, $userWallet->currency, $request->amount, $billPayCharge, PaymentGatewayConst::SEND);
            logger()->info("Transaction limit check passed");
        } catch (Exception $e) {
            $errorData = json_decode($e->getMessage(), true);
            return back()->with(['error' => [__($errorData['message'] ?? __("Transaction Something went wrong! Please try again.18"))]]);
        }

        //charge calculations
        if ($charges['payable'] > $userWallet->balance) {
            return back()->with(['error' => [__("Sorry, insufficient balance")]]);
        }
        try {
            logger()->info("Inserting transaction record");
            $trx_id = 'BP'.getTrxNum();

            $sender = $this->insertSender($trx_id, $user, $userWallet, $amount, $bill_type, $bill_number, $charges, $request->biller_item_type, $request->bill_month, PaymentGatewayConst::CABLETV);
            $this->insertSenderCharges($charges, $amount, $user, $sender);
            logger()->info(json_encode($sender));
            try {
                logger()->info("Sending email notification");
                if ($this->basic_settings->email_notification == true) {
                    $notifyData = [
                        'trx_id' => $trx_id,
                        'bill_type' => @$bill_type->name,
                        'bill_number' => $bill_number,
                        'request_amount' => $amount,
                        'charges' => $charges['total_charge'],
                        'payable' => $charges['payable'],
                        'current_balance' => get_amount($userWallet->balance, null, $charges['precision_digit']),
                        'status' => __("Pending"),
                    ];
                    //send notifications
                    $user->notify(new BillPayMail($user, (object) $notifyData, $charges));
                }
            } catch (Exception $e) {
            }
            //sms notification
            if ($this->basic_settings->sms_notification == true) {
                try {
                    sendSms($user, 'BILL_PAY', [
                        'amount' => get_amount($charges['conversion_amount'], $charges['sender_currency'], $charges['precision_digit']),
                        'type' => $request->biller_item_type ?? '',
                        'bill_type' => $bill_type->name ?? '',
                        'bill_number' => $request->bill_number,
                        'month' => $request->bill_month,
                        'trx' => $trx_id,
                        'time' => now()->format('Y-m-d h:i:s A')
                    ]);
                } catch (Exception $e) {
                }
            }
            logger()->info("Sending admin notification");
            //admin notification
            try {
                $this->adminNotificationManual($trx_id, $charges, $bill_type, $user, $request->all());
                logger()->info("Admin notification sent successfully");
            } catch (Exception $ex) {
                logger()->error("Admin notification failed: ".$ex->getMessage());
            }

            // Validate response structure before rendering view
            if (!isset($response['content']['transactions'])) {
                logger()->error("Missing transactions in response: ".json_encode($response));
                return back()->with('error', 'Invalid response structure from service.');
            }

            return view('tv.success', [
                'transaction' => $response['content']['transactions'],
                'voucher' => $response['Voucher'] ?? null,
                'request_id' => $response['requestId'] ?? null,
                'service' => $service,
                'billPayCharge' => $billPayCharge,
                'sender_wallets' => $sender_wallets,
                'page_title' => $page_title,
                // 'billType' => $billType,
            ]);
        } catch (Exception $e) {
            logger()->error("Main exception: ".$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
            return back()->with(['error' => [__("Main Something went wrong! Please try again.19")]]);
        }
    }

    /**
     * Query transaction status
     */
    public function queryTransaction(Request $request, $service)
    {
        $request->validate(['request_id' => 'required|string']);

        $response = CabletvHelper::queryTransaction($request->request_id);

        if (!isset($response['content']['transactions'])) {
            return back()->with('error', 'Transaction not found or still processing.');
        }

        return view('tv.status', [
            'transaction' => $response['content']['transactions'],
            'voucher' => $response['Voucher'] ?? null,
            'service' => $service,
        ]);
    }


    public function dataServices()
    {
        $services = [
            'mtn-data' => [
                'name' => 'MTN Data',
                'icon' => asset('images/data/mtn.png'),
                'description' => 'Reliable MTN data bundles for all devices.',
            ],
            'airtel-data' => [
                'name' => 'Airtel Data',
                'icon' => asset('images/data/airtel.png'),
                'description' => 'Stay connected with Airtel’s flexible data plans.',
            ],
            'glo-data' => [
                'name' => 'GLO Data',
                'icon' => asset('images/data/glo.png'),
                'description' => 'Enjoy fast browsing with GLO data.',
            ],
            'glo-sme-data' => [
                'name' => 'GLO SME Data',
                'icon' => asset('images/data/glo.png'),
                'description' => 'Enjoy fast browsing with GLO SME data.',
            ],
            'etisalat-data' => [
                'name' => '9Mobile Data',
                'icon' => asset('images/data/9mobile.png'),
                'description' => 'Affordable data packages for 9mobile users.',
            ],
            // 'smile-direct' => [
            //     'name' => 'Smile Data',
            //     'icon' => asset('images/data/smile.png'),
            //     'description' => 'High-speed internet with Smile data plans.',
            // ],
            // 'spectranet' => [
            //     'name' => 'Spectranet',
            //     'icon' => asset('images/data/spectranet.png'),
            //     'description' => 'Reliable internet via Spectranet.',
            // ],
        ];

        $page_title = __("Data Services");
        $transactions = Transaction::auth()->data()->latest()->take(10)->get();

        return view('data.services', compact('services', 'page_title', 'transactions'));
    }

    public function showDataPlans($service)
    {
        $service = strtolower($service);

        if (!array_key_exists($service, $this->dataServices)) {
            abort(404, 'Invalid Data service.');
        }

        $table = strtolower(str_replace('-', '_', $service)).'_plans';

        // Create table dynamically if not existing
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            DB::statement("
                CREATE TABLE {$table} (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    variation_code VARCHAR(255) UNIQUE,
                    name VARCHAR(255),
                    amount DECIMAL(10,2) NULL,
                    data_size VARCHAR(50) NULL,
                    validity VARCHAR(50) NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ");
        }

        $sender_wallets = Currency::sender()->active()->get();
        $billPayCharge = TransactionSetting::where('slug', 'data')->where('status', 1)->first();
        $transactions = Transaction::auth()->data()->latest()->take(10)->get();
        $plans = DB::table($table)->get();

        // Fetch from DB or API
        if ($plans->isEmpty()) {
            $response = DataSubscriptionHelper::getVariations($service);



            if (!isset($response['content']['variations'])) {
                return view('data.error', [
                    'message' => 'Unable to fetch '.strtoupper($service).' data plans at the moment.',
                    'service' => $service,
                ]);
            }

            foreach ($response['content']['variations'] as $plan) {
                DB::table($table)->updateOrInsert(
                    ['variation_code' => $plan['variation_code']],
                    [
                        'name' => $plan['name'],
                        'amount' => $plan['variation_amount'],
                        'data_size' => $plan['data_size'] ?? null,
                        'validity' => $plan['validity'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $plans = DB::table($table)->get();
        }


        return view('data.plans', [
            'plans' => $plans,
            'service' => $service,
            'title' => strtoupper($this->dataServices[$service]).' Data Plans',
            'transactions' => $transactions,
            'billPayCharge' => $billPayCharge,
            'sender_wallets' => $sender_wallets
        ]);
    }

    public function verifyDataNumber(Request $request, $service)
    {
        $request->validate(['phone_number' => 'required|string']);

        $response = DataSubscriptionHelper::verifyNumber($service, $request->phone_number);

        if (!isset($response['code']) || $response['code'] !== '000' || isset($response['content']['error'])) {
            return response()->json([
                'success' => false,
                'message' => $response['content']['error'] ?? 'Verification failed. Please try again.20',
            ]);
        }

        $content = $response['content'];

        return response()->json([
            'success' => true,
            'message' => 'Phone number verified successfully.',
            'data' => [
                'customer_name' => $content['Customer_Name'] ?? '',
                'status' => $content['Status'] ?? '',
                'customer_number' => $content['Customer_Number'] ?? '',
            ],
        ]);
    }

    public function buyDataSubscription(Request $request, $service)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'variation_code' => 'required|string',
            'amount' => 'required|numeric',
            'currency' => 'required|exists:currencies,code',
        ]);

        $user = userGuard()['user'];
        $amount = $request->amount;
        $bill_type = BillPayCategory::where('id', 9)->first();

        logger()->info(json_encode($amount));

        $response = DataSubscriptionHelper::purchaseData($service, [
            'phone_number' => $request->phone_number,
            'variation_code' => $request->variation_code,
            'amount' => $request->amount,
        ]);

        logger("response: ".json_encode($response));

        if (!isset($response['content']['transactions'])) {
            return back()->with('error', $response['response_description'] ?? 'Transaction failed.');
        }

        $billPayCharge = TransactionSetting::where('slug', 'data')->where('status', 1)->first();
        // $billType = $mergedCollection;
        $page_title = __("Data Subscription");
        $sender_wallets = Currency::sender()->active()->get();
        $transactions = Transaction::auth()->data()->latest()->take(10)->get();
        $userWallet = UserWallet::where('user_id', $user->id)->whereHas("currency", function ($q) use ($request) {
            $q->where("code", $request->currency)->active();
        })->active()->first();

        $bill_number = $request->smartcard_number ?? 'N/A';

        logger()->info(json_encode([$userWallet, $billPayCharge, $sender_wallets, $transactions]));

        if (!$userWallet) {
            return back()->with(['error' => [__('User wallet not found!')]]);
        }
        $baseCurrency = Currency::default();
        if (!$baseCurrency) {
            return back()->with(['error' => [__('Default currency not found')]]);
        }

        $charges = $this->manualBillPayCharge(
            $amount,
            $billPayCharge,
            $userWallet,
        );
        $min_amount = $billPayCharge->min_limit / $charges['exchange_rate'];
        $max_amount = $billPayCharge->max_limit / $charges['exchange_rate'];
        if ($charges['sender_amount'] < $min_amount || $charges['sender_amount'] > $max_amount) {
            return back()->with(['error' => [__("Please follow the transaction limit")]]);
        }
        logger()->info(json_encode([$charges, $min_amount, $max_amount, $baseCurrency]));
        //daily and monthly
        try {
            logger()->info("Checking transaction limit");
            (new TransactionLimit())->trxLimit('user_id', $userWallet->user->id, PaymentGatewayConst::DATA, $userWallet->currency, $request->amount, $billPayCharge, PaymentGatewayConst::SEND);
            logger()->info("Transaction limit check passed");
        } catch (Exception $e) {
            $errorData = json_decode($e->getMessage(), true);
            return back()->with(['error' => [__($errorData['message'] ?? __("Transaction Something went wrong! Please try again."))]]);
        }

        //charge calculations
        if ($charges['payable'] > $userWallet->balance) {
            return back()->with(['error' => [__("Sorry, insufficient balance")]]);
        }
        try {
            logger()->info("Inserting transaction record");
            $trx_id = 'BP'.getTrxNum();

            $sender = $this->insertSender($trx_id, $user, $userWallet, $amount, $bill_type, $bill_number, $charges, $request->biller_item_type, $request->bill_month, PaymentGatewayConst::DATA);
            $this->insertSenderCharges($charges, $amount, $user, $sender);
            logger()->info(json_encode($sender));
            try {
                logger()->info("Sending email notification");
                if ($this->basic_settings->email_notification == true) {
                    $notifyData = [
                        'trx_id' => $trx_id,
                        'bill_type' => @$bill_type->name,
                        'bill_number' => $bill_number,
                        'request_amount' => $amount,
                        'charges' => $charges['total_charge'],
                        'payable' => $charges['payable'],
                        'current_balance' => get_amount($userWallet->balance, null, $charges['precision_digit']),
                        'status' => __("Pending"),
                    ];
                    //send notifications
                    $user->notify(new BillPayMail($user, (object) $notifyData, $charges));
                }
            } catch (Exception $e) {
            }
            //sms notification
            if ($this->basic_settings->sms_notification == true) {
                try {
                    sendSms($user, 'DATA', [
                        'amount' => get_amount($charges['conversion_amount'], $charges['sender_currency'], $charges['precision_digit']),
                        'type' => $request->biller_item_type ?? '',
                        'bill_type' => $bill_type->name ?? '',
                        'bill_number' => $request->bill_number,
                        'month' => $request->bill_month,
                        'trx' => $trx_id,
                        'time' => now()->format('Y-m-d h:i:s A')
                    ]);
                } catch (Exception $e) {
                }
            }
            logger()->info("Sending admin notification");
            //admin notification
            try {
                $this->adminNotificationManual($trx_id, $charges, $bill_type, $user, $request->all());
                logger()->info("Admin notification sent successfully");
            } catch (Exception $ex) {
                logger()->error("Admin notification failed: ".$ex->getMessage());
            }

            // Validate response structure before rendering view
            if (!isset($response['content']['transactions'])) {
                logger()->error("Missing transactions in response: ".json_encode($response));
                return back()->with('error', 'Invalid response structure from service.');
            }


            return view('data.success', [
                'transaction' => $response['content']['transactions'],
                'service' => $service,
                'billPayCharge' => $billPayCharge,
                'sender_wallets' => $sender_wallets,
                'page_title' => $page_title,
            ]);
        } catch (Exception $e) {
            logger()->error("Main exception: ".$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
            return back()->with(['error' => [__("Main Something went wrong! Please try again.")]]);
        }
    }

    public function queryDataTransaction(Request $request, $service)
    {
        $request->validate(['request_id' => 'required|string']);

        $response = DataSubscriptionHelper::queryTransaction($request->request_id);

        if (!isset($response['content']['transactions'])) {
            return back()->with('error', 'Transaction not found or still processing.');
        }

        return view('data.status', [
            'transaction' => $response['content']['transactions'],
            'service' => $service,
        ]);
    }

    public function success()
    {
        return view('tv.success');
    }


    // public function buyDataForm(){
    //     $networks = CkMobileNetwork::with('plans')->get();

    //     return view('data.buy', compact('networks'));
    // }

    // Show dropdown with networks

    public function ckAirtime()
    {
        $networks = (new CkHelper())->getMobileNetworks();
        $user = userGuard()['user'];
        $transactions = CkTransaction::where('user_id', $user->id)
            ->where('type', 'airtime')
            ->latest()
            ->get();

        $ckSetting = ReloadlyApi::clubkonnect()->utility()->first();

        $fixedCharge = $ckSetting->credentials->charges->airtime->fixed ?? 0;
        $percentageCharge = $ckSetting->credentials->charges->airtime->percentage ?? 0;
        return view('user.sections.clubkonnect.buy-airtime', compact('networks', 'transactions', 'fixedCharge', 'percentageCharge'));
    }


    // Get plans by network (AJAX)
    public function getPlans($networkId)
    {
        $plans = (new CkHelper())->getDataPlansByNetwork($networkId);

        return $plans;
    }

    // public function buyData(Request $request)
    // {
    //     $request->validate([
    //         'network_id' => 'required|integer',
    //         'plan_id'    => 'required|integer',
    //         'mobile'     => 'required|string',
    //     ]);

    //     $network = CkMobileNetwork::findOrFail($request->network_id);
    //     $plan = CkDataPlan::findOrFail($request->plan_id);
    //     $user = userGuard()['user'];


    //     $response = (new CkHelper)->buyData(
    //         $network->code,        // MobileNetwork
    //         $plan->plan_code,      // DataPlan
    //         $request->mobile,      // mobileNumber
    //         null,                   // RequestID auto-generated
    //     );

    //     // Save transaction
    //     CkTransaction::create([
    //         'user_id'       => $user->id,
    //         'request_id'    => $response['RequestID'] ?? null,
    //         'order_id'      => $response['OrderID'] ?? null,
    //         'provider'      => "ClubKonnect",
    //         'type'          => 'data',
    //         'network'       => $network->name,
    //         'plan'          => $plan->description,
    //         'amount'        => $plan->price,
    //         'mobile'        => $request->mobile,
    //         'status'        => $response['status'] ?? 'pending',
    //         'additional_info' => "",
    //         'response_body' => json_encode($response)
    //     ]);

    //     return back()->with('success', 'Data purchase initiated successfully.');
    // }

    public function ckData()
    {
        $networks = (new CkHelper())->getMobileNetworks();
        $user = userGuard()['user'];
        $transactions = CkTransaction::where('user_id', $user->id)
            ->where('type', 'data')
            ->latest()
            ->get();
        $ckSetting = ReloadlyApi::clubkonnect()->utility()->first();

        $fixedCharge = $ckSetting->credentials->charges->data->fixed ?? 0;
        $percentageCharge = $ckSetting->credentials->charges->data->percentage ?? 0;

        return view('user.sections.clubkonnect.buy-data', compact('networks', 'transactions', 'fixedCharge', 'percentageCharge'));
    }

    public function buyData(Request $request)
    {
        $request->validate([
            'network_id' => 'required|integer',
            'plan_id'    => 'required|integer',
            'mobile'     => 'required|string',
        ]);

        $user = userGuard()['user'];

        /*
        |--------------------------------------------------------------------------
        | FETCH USER WALLET (NGN WALLET)
        |--------------------------------------------------------------------------
        */
        $userWallet = UserWallet::where('user_id', $user->id)
            ->whereHas("currency", function ($q) {
                $q->where("code", "NGN")->active();
            })
            ->active()
            ->lockForUpdate()
            ->first();

        if (!$userWallet) {
            return back()->with('error', 'User wallet not found!');
        }

        /*
        |--------------------------------------------------------------------------
        | GET NETWORK & DATA PLAN
        |--------------------------------------------------------------------------
        */
        $network = CkMobileNetwork::findOrFail($request->network_id);
        $plan    = CkDataPlan::findOrFail($request->plan_id);
        $ckSetting = ReloadlyApi::clubkonnect()->utility()->first();
        $amount  = $plan->price;
        $fixedCharge = $ckSetting->credentials->charges->data->fixed ?? 0;
        $percentageCharge = $ckSetting->credentials->charges->data->percentage ?? 0;

        $payable = $amount + $fixedCharge + ($amount * ($percentageCharge / 100));
        /*
        |--------------------------------------------------------------------------
        | CHECK WALLET BALANCE
        |--------------------------------------------------------------------------
        */
        if ($payable > $userWallet->balance) {
            return back()->with('error', 'Sorry, insufficient balance');
        }

        try {
            DB::beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | FIRST — Debit User Wallet Before API Call
            |--------------------------------------------------------------------------
            */
            $userWallet->balance -= $payable;
            $userWallet->save();

            /*
            |--------------------------------------------------------------------------
            | CALL CLUB KONNECT API
            |--------------------------------------------------------------------------
            */
            $response = (new CkHelper())->buyData(
                $network->code,      // networkCode
                $plan->plan_code,    // Data Plan code
                $request->mobile,
                null                 // auto RequestID
            );


            /*
            |--------------------------------------------------------------------------
            | API FAILURE → REFUND WALLET
            |--------------------------------------------------------------------------

            */

            $failedStatuses = [
                        'FAILED',
                        'INSUFFICIENT_BALANCE',
                        'CANCELLED',
                        'ERROR'
                    ];

            if (
                empty($response) ||
                isset($response['error']) ||
                in_array($response['status'] ?? null, $failedStatuses)
            ) {

                // Refund wallet
                $userWallet->balance += $payable;
                $userWallet->save();

                DB::commit();

                return back()->with('error', $response['message'] ?? 'Transaction failed bro');
            }

            /*
            |--------------------------------------------------------------------------
            | SAVE TRANSACTION
            |--------------------------------------------------------------------------
            */
            $transaction = CkTransaction::create([
                'user_id'       => $user->id,
                'request_id'    => $response['RequestID'] ?? null,
                'order_id'      => $response['OrderID'] ?? null,
                'provider'      => "ClubKonnect",
                'type'          => 'data',
                'network'       => $network->name,
                'plan'          => $plan->description,
                'amount'        => $payable,
                'mobile'        => $request->mobile,
                'status'        => $response['status'],
                'response_body' => json_encode($response)
            ]);

            /*
            |--------------------------------------------------------------------------
            | EMAIL NOTIFICATION
            |--------------------------------------------------------------------------
            */
            if ($this->basic_settings->email_notification == true) {
                try {
                    $notifyData = [
                        'trx_id'          => $transaction->id,
                        'network'         => $network->name,
                        'plan'            => $plan->description,
                        'amount'          => get_amount($payable),
                        'mobile'          => $request->mobile,
                        'current_balance' => get_amount($userWallet->balance),
                        'status'          => $transaction->status,
                    ];

                    $user->notify(new DataPurchaseMail($user, (object) $notifyData));
                } catch (\Exception $e) {
                }
            }

            /*
            |--------------------------------------------------------------------------
            | SMS NOTIFICATION
            |--------------------------------------------------------------------------
            */
            if ($this->basic_settings->sms_notification == true) {
                try {
                    sendSms($user, 'DATA_PURCHASE', [
                        'amount'  => get_amount($payable, 'NGN'),
                        'plan'    => $plan->description,
                        'mobile'  => $request->mobile,
                        'network' => $network->name,
                        'trx'     => $transaction->id,
                        'time'    => now()->format('Y-m-d h:i:s A')
                    ]);
                } catch (\Exception $e) {
                }
            }
            DB::commit();

            // return back()->with('success', 'Data purchase successful.');
            return redirect()->route('user.data.success', $transaction->id);

        } catch (\Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | UNEXPECTED FAILURE → REFUND AND ERROR LOG
            |-------------------------------------------------------------------------- */
            DB::rollBack();

            logger()->error("Data Purchase Error: " . $e->getMessage());
            return back()->with('error', 'Something went wrong while processing your request.');
        }
    }

    // public function buyAirtime(Request $request){
    //     $request->validate([
    //         'network_id' => 'required|integer',
    //         'mobile' => 'required|string',
    //         'amount' => 'required|numeric|min:50|max:200000',
    //     ]);

    //     $user = userGuard()['user'];

    //     $network = CkMobileNetwork::findOrFail($request->network_id);

    //     try {
    //         $response = (new CkHelper)->buyAirtime($network->code, $request->amount, $request->mobile, null);

    //         if(!empty($response['error'])) {
    //             return back()->with('error', $response['message']);
    //         }

    //         // Save transaction
    //         CKTransaction::create([
    //             'user_id' => $user->id,
    //             'request_id' => $response['RequestID'] ?? null,
    //             'order_id' => $response['OrderID'] ?? null,
    //             'provider' => 'ClubKonnect',
    //             'network' => $network->name,
    //             'mobile' => $request->mobile,
    //             'amount' => $request->amount,
    //             'type' => 'airtime',
    //             'plan' => 'Purchase '.$request->amount.' '.$network->name.' Airtime for '.$request->mobile,
    //             'status' => $response['status'] ?? 'pending',
    //             'response_body' => json_encode($response),
    //         ]);



    //         return back()->with('success', 'Airtime purchase request sent. Check transactions below.');
    //     } catch (\Exception $e) {
    //         logger()->error('Airtime Purchase Error: ' . $e->getMessage());
    //         return back()->with('error', 'Something went wrong while processing your request.');
    //     }
    // }



    public function buyAirtime(Request $request)
    {
        $request->validate([
            'network_id' => 'required|integer',
            'mobile'    => 'required|string',
            'amount'    => 'required|numeric|min:50|max:200000',
        ]);

        $user = userGuard()['user'];

        // Get user wallet (NGN or dynamic currency)
        $userWallet = UserWallet::where('user_id', $user->id)
            ->whereHas("currency", function ($q) {
                $q->where("code", "NGN")->active();
            })
            ->active()
            -> lockForUpdate()
            ->first();

        if (!$userWallet) {
            return back()->with('error', 'User wallet not found!');
        }

        $ckSetting = ReloadlyApi::clubkonnect()->utility()->first();
        $amount = $request->amount;
        $fixedCharge = $ckSetting->credentials->charges->airtime->fixed ?? 0;
        $percentageCharge = $ckSetting->credentials->charges->airtime->percentage ?? 0;

        $payable = $amount + $fixedCharge + ($amount * ($percentageCharge / 100));
        // Check wallet balance
        if ($payable > $userWallet->balance) {
            return back()->with('error', 'Sorry, insufficient balance');
        }

        // Get selected network
        $network = CkMobileNetwork::findOrFail($request->network_id);

        try {
            DB::beginTransaction();
            // FIRST — debit wallet to avoid fraud
            $userWallet->balance -= $payable;
            $userWallet->save();

            // THEN call the provider
            $response = (new CkHelper())->buyAirtime(
                $network->code,
                $amount,
                $request->mobile,
                null
            );

            $failedStatuses = [
            'FAILED',
            'INSUFFICIENT_BALANCE',
            'CANCELLED',
            'ERROR'
        ];

            if (
                empty($response) ||
                isset($response['error']) ||
                in_array($response['status'] ?? null, $failedStatuses)
            ) {

                // Refund Wallet on Failure
                $userWallet->balance += $payable;
                $userWallet->save();

                DB::commit();

                return back()->with('error', $response['message']);
            }

            // Save transaction
            $transaction = CKTransaction::create([
                'user_id'       => $user->id,
                'request_id'    => $response['RequestID'] ?? null,
                'order_id'      => $response['OrderID'] ?? null,
                'provider'      => 'ClubKonnect',
                'network'       => $network->name,
                'mobile'        => $request->mobile,
                'amount'        => $payable,
                'type'          => 'airtime',
                'plan'          => "Purchase {$amount} {$network->name} Airtime for {$request->mobile}",
                'status'        => $response['status'] ?? 'pending',
                'response_body' => json_encode($response),
            ]);

            /*
            |--------------------------------------------------------------------------
            | EMAIL NOTIFICATION
            |--------------------------------------------------------------------------
            */
            if ($this->basic_settings->email_notification == true) {
                try {
                    $notifyData = [
                        'trx_id'           => $transaction->id,
                        'amount'           => $payable,
                        'network'          => $network->name,
                        'mobile'           => $request->mobile,
                        'current_balance'  => get_amount($userWallet->balance),
                        'status'           => $transaction->status,
                    ];

                    $user->notify(new AirtimePurchaseMail($user, (object) $notifyData));
                } catch (\Exception $e) {
                }
            }

            /*
            |--------------------------------------------------------------------------
            | SMS NOTIFICATION
            |--------------------------------------------------------------------------
            */
            if ($this->basic_settings->sms_notification == true) {
                try {
                    sendSms($user, 'AIRTIME_PURCHASE', [
                        'amount'  => get_amount($payable, 'NGN'),
                        'mobile'  => $request->mobile,
                        'network' => $network->name,
                        'trx'     => $transaction->id,
                        'time'    => now()->format('Y-m-d h:i:s A')
                    ]);
                } catch (\Exception $e) {
                }
            }

            /*
            |--------------------------------------------------------------------------
            | ADMIN NOTIFICATION (Optional)
            |--------------------------------------------------------------------------
            */
            // $this->adminNotificationAirtime($transaction, $user);
            DB::commit();


            return back()->with('success', 'Airtime purchase successful.');

        } catch (\Exception $e) {

            // Refund wallet on any unexpected crash
            DB::rollBack();

            logger()->error('Airtime Purchase Error: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while processing your request.');
        }
    }


    public function ckHome()
    {
        return view('user.sections.clubkonnect.index');
    }

    public function ckCableTv()
    {
        $providers = (new CkHelper())->getCableTvProviders();
        $user = userGuard()['user'];
        $transactions = CkTransaction::where('user_id', $user->id)
            ->where('type', 'cabletv')
            ->latest()
            ->get();

        $ckSetting = ReloadlyApi::clubkonnect()->utility()->first();

        $fixedCharge = $ckSetting->credentials->charges->cabletv->fixed ?? 0;
        $percentageCharge = $ckSetting->credentials->charges->cabletv->percentage ?? 0;

        return view('user.sections.clubkonnect.buy-cable', compact('providers', 'transactions', 'fixedCharge', 'percentageCharge'));
    }

    // Get packages by provider (AJAX)
    public function getPackages($providerId)
    {
        $plans = (new CkHelper())->getCableTvPacakges($providerId);

        return response()->json($plans);
    }

    public function verify(Request $request)
    {
        logger()->info("Hitting the verify endpoint");
        $request->validate([
            'cable' => 'required',
            'smartcard' => 'required'
        ]);

        logger()->info("Verifying Cable", [
            'cable' => $request->cable,
            'smartcard' => $request->smartcard
        ]);


        $verify = (new CkHelper())->verifySmartcard($request->cable, $request->smartcard);
        logger()->error("Verify Smart Card: ".json_encode($verify));


        return response()->json($verify);
    }

    public function buyCableTv(Request $request)
    {
        $request->validate([
            'cable' => 'required',
            'package' => 'required',
            'smartcard' => 'required',
            'phone' => 'required'
        ]);

        $user = userGuard()['user'];

        $userWallet = UserWallet::where('user_id', $user->id)
        ->whereHas('currency', fn ($q) => $q->where('code', 'NGN')->active())
        ->active()
        ->lockForUpdate()
        ->first();

        if (!$userWallet) {
            return back()->with('error', 'User wallet not found!');
        }

        $provider = CkCableTv::where('code', $request->cable)->first();

        // $packages = CkCableTvPackages::where('package_');
        $packages = CkCableTvPackages::where('package_code', $request->package)->first();

        // try
        $ckSetting = ReloadlyApi::clubkonnect()->utility()->first();


        $fixedCharge = $ckSetting->credentials->charges->cabletv->fixed ?? 0;
        $percentageCharge = $ckSetting->credentials->charges->cabletv->percentage ?? 0;
        $payable = $packages->price + $fixedCharge + ($packages->price * ($percentageCharge / 100));


        try {
            DB::beginTransaction();

            $userWallet->balance -= $payable;
            $userWallet->save();

            $response = (new CkHelper())->buyCableTv(
                $request->cable,
                $request->package,
                $request->smartcard,
                $request->phone
            );


            $failedStatuses = [
                'FAILED',
                'INSUFFICIENT_BALANCE',
                'CANCELLED',
                'ERROR'
            ];

            if (
                empty($response) ||
                isset($response['error']) ||
                in_array($response['status'] ?? null, $failedStatuses)
            ) {
                $userWallet->balance += $payable;
                $userWallet->save();

                DB::commit();
                return back()->with('error', $response['message'] ?? 'Transaction failed');
            }

            // Save transaction
            $transaction = CkTransaction::create([
                'user_id'     => $user->id,
                'request_id'  => $response['RequestID'] ?? null,
                'order_id'    => $response['OrderID'] ?? null,
                'provider'    => 'ClubKonnect',
                'network'       => $provider->name,
                'mobile'       => $request->phone,
                'type'        => 'cabletv',
                'plan'        => $request->package,
                'amount'      => $payable,
                'status'      => $response['status'] ?? 'pending',
                'status_code' => $response['statuscode'] ?? null,
                'additional_info' => "{}",
                'response_body'    => json_encode($response)
            ]);

            /*
            |--------------------------------------------------------------------------
            | EMAIL NOTIFICATION
            |--------------------------------------------------------------------------
            */
            if ($this->basic_settings->email_notification == true) {
                try {
                    $notifyData = [
                        'trx_id'           => $transaction->id,
                        'provider'        => $provider->name,
            'package'         => $packages->name ?? $request->package,
            'amount'          => get_amount($payable, 'NGN'),
            'smartcard'       => $request->smartcard,
            'status'          => $response['status'] ?? 'pending',
            'current_balance' => get_amount($userWallet->balance),
                    ];

                    $user->notify(new CableTvPurchaseMail($user, (object) $notifyData));
                } catch (\Exception $e) {
                }
            }

            /*
            |--------------------------------------------------------------------------
            | SMS NOTIFICATION
            |--------------------------------------------------------------------------
            */
            if ($this->basic_settings->sms_notification == true) {
                try {
                    sendSms($user, 'CABLETV_PURCHASE', [
                        'amount'  => get_amount($payable, 'NGN'),
                        'package' => $packages->name ?? $request->package,
    'provider' => $provider->name,
    'smartcard' => $request->smartcard,
    'trx'     => $transaction->id,
    'time'    => now()->format('Y-m-d h:i:s A'),
                    ]);
                } catch (\Exception $e) {
                }
            }


            if (isset($response['status']) && $response['status'] === "ORDER_RECEIVED") {
                DB::commit();
                return back()->with('success', 'Subscription request submitted successfully.');
            }

            return back()->with('error', 'Error: ' . ($response['status'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            DB::rollBack();

            logger()->error('Airtime Purchase Error: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while processing your request.');

        }
    }

    

}
