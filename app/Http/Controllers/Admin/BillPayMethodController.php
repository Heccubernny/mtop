<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use App\Models\Admin\ReloadlyApi;
use App\Models\BillPayCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BillPayMethodController extends Controller
{
    // ==============================================Bill Pay Manual Start===============================================
    public function billPayList()
    {
        $page_title = __('Bill Pay Method').' ( '.__('Manual').' )';
        $allCategory = BillPayCategory::orderByDesc('id')->paginate(10);

        return view('admin.sections.bill-pay.category', compact(
            'page_title',
            'allCategory',
        ));
    }

    public function storeCategory(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200|unique:bill_pay_categories,name',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal', 'category-add');
        }
        $validated = $validator->validate();
        $slugData = Str::slug($request->name);
        $makeUnique = BillPayCategory::where('slug', $slugData)->first();
        if ($makeUnique) {
            return back()->with(['error' => [__('Method Already Exists!')]]);
        }
        $admin = Auth::user();

        $validated['admin_id'] = $admin->id;
        $validated['name'] = $request->name;
        $validated['slug'] = $slugData;
        try {
            BillPayCategory::create($validated);

            return back()->with(['success' => [__('Method Saved Successfully!')]]);
        } catch (Exception $e) {
            return back()->withErrors($validator)->withInput()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }
    }

    public function categoryUpdate(Request $request)
    {
        $target = $request->target;
        $category = BillPayCategory::where('id', $target)->first();
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal', 'edit-category');
        }
        $validated = $validator->validate();

        $slugData = Str::slug($request->name);
        $makeUnique = BillPayCategory::where('id', '!=', $category->id)->where('slug', $slugData)->first();
        if ($makeUnique) {
            return back()->with(['error' => [__('Method Already Exists!')]]);
        }
        $admin = Auth::user();
        $validated['admin_id'] = $admin->id;
        $validated['name'] = $request->name;
        $validated['slug'] = $slugData;

        try {
            $category->fill($validated)->save();

            return back()->with(['success' => [__('Method Updated Successfully!')]]);
        } catch (Exception $e) {
            return back()->withErrors($validator)->withInput()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }
    }

    public function categoryStatusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
            'data_target' => 'required|string',
        ]);
        if ($validator->stopOnFirstFailure()->fails()) {
            $error = ['error' => $validator->errors()];

            return BillPayCategory::error($error, null, 400);
        }
        $validated = $validator->safe()->all();
        $category_id = $validated['data_target'];

        $category = BillPayCategory::where('id', $category_id)->first();
        if (! $category) {
            $error = ['error' => [__('Method record not found in our system.')]];

            return Response::error($error, null, 404);
        }

        try {
            $category->update([
                'status' => ($validated['status'] == true) ? false : true,
            ]);
        } catch (Exception $e) {
            $error = ['error' => [__('Something went wrong! Please try again.')]];

            return Response::error($error, null, 500);
        }

        $success = ['success' => [__('Method status updated successfully!')]];

        return Response::success($success, null, 200);
    }

    public function categoryDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target' => 'required|string|exists:bill_pay_categories,id',
        ]);
        $validated = $validator->validate();
        $category = BillPayCategory::where('id', $validated['target'])->first();

        try {
            $category->delete();
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Method deleted successfully!')]]);
    }

    public function categorySearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string',
        ]);

        if ($validator->fails()) {
            $error = ['error' => $validator->errors()];

            return Response::error($error, null, 400);
        }

        $validated = $validator->validate();

        $allCategory = BillPayCategory::search($validated['text'])->select()->limit(10)->get();

        return view('admin.components.search.bill-category-search', compact(
            'allCategory',
        ));
    }
    // ================================================Bill Pay Manual End======================================================

    // ================================================Bill Pay Automatic Start=================================================
    public function manageBillPayApi()
    {
        $page_title = __('Setup Bill Pay Api');
        $reloadlyApi = ReloadlyApi::reloadly()->utilityPayment()->first();
        $clubkonnectApi = ReloadlyApi::clubkonnect()->utility()->first();

        return view('admin.sections.bill-pay.reloadly.api', compact(
            'page_title',
            'reloadlyApi',
            'clubkonnectApi',
        ));
    }

    public function updateCredentials(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|string',
            'secret_key' => 'required|string',
            'production_base_url' => 'required|url',
            'sandbox_base_url' => 'required|url',
            'env' => 'required|string',
            'status' => 'required|boolean'
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $validated = $validator->validate();
        $api = ReloadlyApi::reloadly()->utilityPayment()->first();
        $credentials = array_filter($request->except('_token', 'env', '_method'));
        $data['credentials'] = $credentials;
        $data['env'] = $validated['env'];
        $data['status'] = $validated['status'];
        $data['provider'] = ReloadlyApi::PROVIDER_RELOADLY;
        $data['type'] = ReloadlyApi::UTILITY_PAYMENT;
        if (! $api) {
            ReloadlyApi::create($data);
        } else {
            $api->fill($data)->save();
        }

        return back()->with(['success' => [__('Bill Pay API Has Been Updated.')]]);
    }
    
    // ================================================Bill Pay Automatic End===================================================

   public function updateCKCredentials(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */
    $validator = Validator::make($request->all(), [
        'api_key'      => 'required|string',
        'api_url'      => 'required|string',
        'callback_url' => 'required|url',
        'user_id'      => 'required|string',
        'env'          => 'required|string',
        'status'       => 'required|boolean',

        // Charges (NEW STRUCTURE)
        'charges.data.fixed'        => 'required|numeric|min:0',
        'charges.data.percentage'   => 'required|numeric|min:0',

        'charges.airtime.fixed'     => 'required|numeric|min:0',
        'charges.airtime.percentage'=> 'required|numeric|min:0',

        'charges.cabletv.fixed'     => 'required|numeric|min:0',
        'charges.cabletv.percentage'=> 'required|numeric|min:0',

        // Network configuration
        'networks' => 'required|array',
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    $validated = $validator->validated();

    /*
    |--------------------------------------------------------------------------
    | FETCH EXISTING RECORD
    |--------------------------------------------------------------------------
    */
    $api = ReloadlyApi::clubkonnect()->utility()->first();

    /*
    |--------------------------------------------------------------------------
    | BUILD CREDENTIALS JSON
    |--------------------------------------------------------------------------
    */
    $credentials = [
        'api_key'      => $validated['api_key'],
        'api_url'      => $validated['api_url'],
        'user_id'      => $validated['user_id'],
        'callback_url' => $validated['callback_url'],

        'charges' => [
            'data' => [
                'fixed'      => (float) $validated['charges']['data']['fixed'],
                'percentage' => (float) $validated['charges']['data']['percentage'],
            ],
            'airtime' => [
                'fixed'      => (float) $validated['charges']['airtime']['fixed'],
                'percentage' => (float) $validated['charges']['airtime']['percentage'],
            ],
            'cabletv' => [
                'fixed'      => (float) $validated['charges']['cabletv']['fixed'],
                'percentage' => (float) $validated['charges']['cabletv']['percentage'],
            ],
        ],

        'networks' => [],
    ];

    /*
    |--------------------------------------------------------------------------
    | NETWORK DATA CATEGORIES
    |--------------------------------------------------------------------------
    */
    $networks   = ['mtn', 'glo', 'airtel', '9mobile'];
    $categories = ['sme' => 'SME Data', 'direct' => 'Direct Data', 'awoof' => 'Awoof Data'];

    foreach ($networks as $networkKey) {
        $credentials['networks'][$networkKey] = [
            'data_categories' => [],
        ];

        foreach ($categories as $catKey => $catLabel) {
            $status =
                $validated['networks'][$networkKey]['data_categories'][$catKey]['status']
                ?? 0;

            $credentials['networks'][$networkKey]['data_categories'][$catKey] = [
                'label'  => $catLabel,
                'status' => (bool) $status,
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE DATA
    |--------------------------------------------------------------------------
    */
    $data = [
        'credentials' => $credentials,
        'env'         => $validated['env'],
        'status'      => $validated['status'],
        'provider'    => ReloadlyApi::PROVIDER_CLUBKONNECT,
        'type'        => ReloadlyApi::UTILITY,
    ];

    if (! $api) {
        ReloadlyApi::create($data);
    } else {
        $api->update($data);
    }

    return back()->with([
        'success' => ['ClubKonnect API settings updated successfully.']
    ]);
}

}
