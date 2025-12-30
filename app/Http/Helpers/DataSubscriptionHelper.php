<?php

namespace App\Http\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataSubscriptionHelper
{
    protected static $baseUrl;

    protected static $apiKey;

    protected static $publicKey;

    protected static $secretKey;

    /**
     * Initialize configuration values.
     */
    protected static function init()
    {
        self::$baseUrl = config('services.vtpass.url');
        self::$apiKey = config('services.vtpass.api_key');
        self::$publicKey = config('services.vtpass.public_key');
        self::$secretKey = config('services.vtpass.secret_key');

        // defensive fallback if someone put trailing slash in env
        if (is_string(self::$baseUrl)) {
            self::$baseUrl = rtrim(self::$baseUrl, '/');
        }
    }

    /**
     * Common header builder for GET/POST requests.
     */
    protected static function headers($method = 'GET')
    {
        self::init();

        $headers = [
            'api-key' => self::$apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($method === 'GET') {
            $headers['public-key'] = self::$publicKey;
        } else {
            $headers['secret-key'] = self::$secretKey;
        }

        return $headers;
    }

    /**
     * Fetch available data plans (variations) for any provider (MTN, GLO, etc.).
     *
     * @param  string  $serviceId  e.g. "mtn-data", "glo-data", "airtel-data"
     * @return array
     */
    public static function getVariations($serviceId)
    {
        self::init();

        $url = self::$baseUrl."/service-variations?serviceID={$serviceId}";

        // $response = Http::withHeaders(self::headers('GET'))->get($url);
        //     dd($response);
        try {
            $response = Http::withHeaders(self::headers('GET'))->get($url);

            // dd($response);
            return $response->json();
        } catch (\Exception $e) {
            Log::error("VTPass {$serviceId} data plan fetch error: ".$e->getMessage());

            return ['error' => true, 'message' => 'Unable to fetch data plans.'];
        }
    }

    /**
     * Verify customer phone number or account number for data services.
     *
     * @param  string  $serviceId
     * @param  string  $billersCode
     * @return array
     */
    public static function verifyNumber($serviceId, $billersCode)
    {
        self::init();

        $url = self::$baseUrl.'/merchant-verify';
        $payload = [
            'serviceID' => $serviceId,
            'billersCode' => $billersCode,
        ];

        try {
            $response = Http::withHeaders(self::headers('POST'))->post($url, $payload);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("VTPass {$serviceId} verification failed: ".$e->getMessage());

            return ['error' => true, 'message' => 'Verification failed.'];
        }
    }

    /**
     * Purchase any data bundle.
     *
     * @param  string  $serviceId
     * @return array
     */
    public static function purchaseData($serviceId, array $data)
    {
        self::init();

        $url = self::$baseUrl.'/pay';

        $payload = [
            'request_id' => Str::uuid(),
            'serviceID' => $serviceId,
            'billersCode' => $data['phone_number'], // For data, billersCode is the phone number
            'variation_code' => $data['variation_code'] ?? null,
            'amount' => $data['amount'] ?? null,
            'phone' => $data['phone_number'],
        ];

        try {
            $response = Http::withHeaders(self::headers('POST'))->post($url, $payload);
            logger()->info('Buy Data Subscription response body: '.json_encode($response));

            return $response->json();
        } catch (\Exception $e) {
            Log::error("VTPass {$serviceId} data purchase error: ".$e->getMessage());

            return ['error' => true, 'message' => 'Purchase failed.'];
        }
    }

    /**
     * Query transaction status for any data purchase.
     *
     * @param  string  $requestId
     * @return array
     */
    public static function queryTransaction($requestId)
    {
        self::init();

        $url = self::$baseUrl.'/requery';
        $payload = ['request_id' => $requestId];

        try {
            $response = Http::withHeaders(self::headers('POST'))->post($url, $payload);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('VTPass data requery failed: '.$e->getMessage());

            return ['error' => true, 'message' => 'Unable to requery transaction.'];
        }
    }
}
