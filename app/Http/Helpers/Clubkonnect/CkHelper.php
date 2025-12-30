<?php

namespace App\Http\Helpers\Clubkonnect;

use App\Models\Admin\ReloadlyApi;
use App\Models\CkDataPlan;
use App\Models\CkMobileNetwork;
use Illuminate\Support\Facades\Http;

class CkHelper
{
    protected $apiUrl;
    protected $apiKey;
    protected $userId;
    protected $callbackUrl;

    public function __construct()
    {
        // $this->apiUrl = config('clubkonnect.api_url');
        // $this->apiKey = config('clubkonnect.api_key');
        // $this->userId = config('clubkonnect.user_id');
        // $this->callbackUrl = config('clubkonnect.callback_url');

        $clubkonnectApi = ReloadlyApi::clubkonnect()->utility()->first();

        $this->apiUrl      = $clubkonnectApi->credentials->api_url;
        $this->apiKey      = $clubkonnectApi->credentials->api_key;
        $this->userId      = $clubkonnectApi->credentials->user_id;
        $this->callbackUrl = $clubkonnectApi->credentials->callback_url;

    }

    public function buyData($network, $dataPlan, $mobileNumber, $requestId = null)
    {

        $requestId = $requestId ?? uniqid('ck_airtime_');
        $callbackUrl = $this->callbackUrl;

        $url = $this->apiUrl . 'APIDatabundleV1.asp';

        $params = [
            'UserID' => $this->userId,
            'APIKey' => $this->apiKey,
            'MobileNetwork' => $network,
            'DataPlan' => $dataPlan,
            'MobileNumber' => $mobileNumber,
            'RequestID' => $requestId,
            'CallBackUrl' => $callbackUrl,
        ];

        // Send GET request to Clubkonnect API
        try {
            $response = Http::get($url, $params);
            return $response->json();
        } catch (\Exception $e) {
            logger()->error('Clubkonnect Data Subscription Error: ' . $e->getMessage());
            return ['error' => true, 'message' => 'Something went wrong while processing your request.'];
        }
    }

    public function buyAirtime($network, $amount, $mobileNumber, $requestId = null)
    {

        $requestId = $requestId ?? uniqid('ck_data_');
        $callbackUrl = $this->callbackUrl;

        $url = $this->apiUrl . 'APIAirtimeV1.asp';


        if ($amount < 50 || $amount > 200000) {
            return ['error' => true, 'message' => 'Invalid amount specified.'];
        }

        $params = [
            'UserID' => $this->userId,
            'APIKey' => $this->apiKey,
            'MobileNetwork' => $network,
            'Amount' => $amount,
            'MobileNumber' => $mobileNumber,
            'RequestID' => $requestId,
            'CallBackUrl' => $callbackUrl,
        ];

        // Send GET request to Clubkonnect API
        try {
            $response = Http::get($url, $params);
            return $response->json();
        } catch (\Exception $e) {
            logger()->error('Clubkonnect Data Subscription Error: ' . $e->getMessage());
            return ['error' => true, 'message' => 'Something went wrong while processing your request.'];
        }
    }

    public function buyCableTv($cable, $package, $smartcard, $phone, $requestId = null)
    {
        $requestId = $requestId ?? uniqid('ck_cable_');


        $url = $this->apiUrl . "APICableTVV1.asp";

        $params = [
            'UserID'       => $this->userId,
            'APIKey'       => $this->apiKey,
            'CableTV'      => $cable,
            'Package'      => $package,
            'SmartCardNo'  => $smartcard,
            'PhoneNo'      => $phone,
            'RequestID'    => $requestId,
            'CallBackURL'  => $this->callbackUrl,
        ];

        logger()->info(json_encode($params));

        try {
            $response = Http::get($url, $params);

            return $response->json();
        } catch (\Exception $e) {
            logger()->error("CableTV Subscription Error: " . $e->getMessage());
            return ['error' => true, 'message' => 'Connection error'];
        }
    }

    /** Verify Smartcard/IUC number */
    public function verifySmartcard($cable, $smartcard)
    {
        $url = $this->apiUrl . "APIVerifyCableTVV1.0.asp";

        $params = [
            'UserID'      => $this->userId,
            'APIKey'      => $this->apiKey,
            'CableTV'     => $cable,
            'SmartCardNo' => $smartcard,
        ];

        try {
            $response = Http::get($url, $params);
            logger()->error("Response to Verify Smart Card: ".$response);
            return $response->json();
        } catch (\Exception $e) {
            logger()->error("Unable to verify smart card no error: " . $e->getMessage());
            return ['error' => true, 'message' => 'Connection error'];
        }
    }

    public function queryTransaction($queryId, $queryType = 'RequestID')
    {
        $url = $this->apiUrl.'APIQueryV1.asp';

        // QueryId can be either RequestID or OrderID
        $queryType = strtoupper($queryType);
        if (!in_array($queryType, ['REQUESTID', 'ORDERID'])) {
            $queryType = 'REQUESTID';
        }
        //  if ($orderId) $params['OrderID'] = $orderId;
        // if ($requestId) $params['RequestID'] = $requestId;

        $params = [
            'UserID' => $this->userId,
            'APIKey' => $this->apiKey,
            $queryType => $queryId,
        ];
        // usage will now be like:
        // $result = $helper->queryTransaction('ck_data_123'); // by RequestID
        // $result = $helper->queryTransaction('order_456', 'OrderID'); // by OrderID

        try {
            $response = Http::get($url, $params);
            return $response->json();
        } catch (\Exception $e) {
            logger()->error('Clubkonnect Query Transaction Error: ' . $e->getMessage());
            return ['error' => true, 'message' => 'Something went wrong while querying the transaction.'];
        }

    }

    public function cancelTransactions($orderId)
    {
        $url = $this->apiUrl.'APICancelV1.asp';

        $params = [
            'UserID' => $this->userId,
            'APIKey' => $this->apiKey,
            'OrderID' => $orderId,
        ];

        try {
            $response = Http::get($url, $params);
            return $response->json();
        } catch (\Exception $e) {
            logger()->error('Clubkonnect Cancel Transaction Error: '.$e->getMessage());
            return ['error' => true, 'message' => 'Something went wrong while cancelling the transaction.'];
        }
    }

    public function getMobileNetworks()
    {
        return CkMobileNetwork::orderBy('name')->get();
    }

    // public function getDataPlansByNetwork($networkId)
    // {
    //     return CkDataPlan::where('ck_mobile_network_id', $networkId)
    //         ->orderBy('price')
    //         ->get();
    // }
public function getDataPlansByNetwork($networkId)
{
    // Fetch network
    $network = CkMobileNetwork::findOrFail($networkId);

    // Fetch ClubKonnect settings
    $api = ReloadlyApi::clubkonnect()->utility()->first();
    if (!$api) {
        return response()->json([]);
    }
    // Get network-specific data categories from credentials
    $enabledCategories = [];
    $networks = $api->credentials->networks ?? [];

if ($networks && isset($networks->{$network->slug}->data_categories)) {
        foreach ($networks->{$network->slug}->data_categories as $cat) {
            if (!empty($cat->status)) {
                $enabledCategories[] = strtolower($cat->label);
            }
        }
    }

    // Fetch plans for the network
    $plans = CkDataPlan::where('ck_mobile_network_id', $networkId)->orderBy('price')->get();

    // Filter plans by enabled data categories

    $filteredPlans = $plans->filter(function ($plan) use ($enabledCategories) {
    // Check if plan description matches any enabled category
    foreach ($enabledCategories as $category) {
        if (stripos($plan->description, $category) !== false) {
            return true; // show this plan
        }
    }

    return false; // hide this plan
})->values();


    return response()->json($filteredPlans);
}

    public function getCableTvProviders()
    {
        return \App\Models\CkCableTv::orderBy('name')->get();
    }

    public function getCableTvPacakges($cableId)
    {
        return \App\Models\CkCableTvPackages::where('cable_tv', $cableId)->orderBy('price')->get();
    }

}
