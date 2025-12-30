<?php

namespace App\Http\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CabletvHelper
{
    protected static $baseUrl;

    protected static $apiKey;

    protected static $publicKey;

    protected static $secretKey;

    public static function init()
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
     * Fetch variations for any TV service (DSTV, GOTV, Startimes, etc.)
     */
    public static function getVariations($serviceId)
    {
        self::init();
        $url = self::$baseUrl."/service-variations?serviceID={$serviceId}";
        // dd(self::$baseUrl, env('VTPASS_API_URL'), config('services.vtpass.url'));

        try {
            $response = Http::withHeaders(self::headers('GET'))->get($url);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("VTPass {$serviceId} variations error: ".$e->getMessage());

            return ['error' => true, 'message' => 'Unable to fetch service variations.'];
        }
    }

    /**
     * Verify a smartcard number for any TV service.
     */
    public static function verifySmartCard($serviceId, $billersCode)
    {
        self::init();

        $url = self::$baseUrl.'/merchant-verify';
        $payload = [
            'serviceID' => $serviceId,
            'billersCode' => $billersCode,
        ];

        try {
            $response = Http::withHeaders(self::headers('POSR'))->post($url, $payload);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("VTPass {$serviceId} smartcard verification failed: ".$e->getMessage());

            return ['error' => true, 'message' => 'Verification failed.'];
        }
    }

    /**
     * Purchase or Renew any TV subscription.
     */
    public static function purchaseTV($serviceId, $data)
    {
        self::init();

        $url = self::$baseUrl.'/pay';

        $payload = [
            'request_id' => Str::uuid(),
            'serviceID' => $serviceId,
            'billersCode' => $data['smartcard_number'] ?? $data['phone'],
            'variation_code' => $data['variation_code'] ?? null,
            'amount' => $data['amount'] ?? null,
            'phone' => $data['phone'],
            'subscription_type' => $data['subscription_type'] ?? 'renew',
            'quantity' => $data['quantity'] ?? 1,
        ];
        logger()->info(json_encode($payload));
        try {
            $response = Http::withHeaders(self::headers('POST'))->post($url, $payload);
            logger()->info($response);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("VTPass {$serviceId} purchase error: ".$e->getMessage());

            return ['error' => true, 'message' => 'Purchase failed.'];
        }
    }

    /**
     * Query a transaction status.
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
            Log::error('VTPass requery failed: '.$e->getMessage());

            return ['error' => true, 'message' => 'Unable to requery transaction.'];
        }
    }
}
