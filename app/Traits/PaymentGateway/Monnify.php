<?php

namespace App\Traits\PaymentGateway;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait Monnify
{
    protected $baseUrl;

    protected $apiKey;

    protected $secretKey;

    // public function __construct()
    // {
    //     // $this->baseUrl = config('monnify.base_url');
    //     // $this->apiKey = config('monnify.api_key');
    //     // $this->secretKey = config('monnify.secret_key');
    //     $this->apiKey="MK_TEST_NT397FLRCY";
    //     $this->baseUrl="https://api.monnify.com";
    //     $this->secretKey = "5UAW8BSC1LYM6VQM0TWWVN61PESVJBQ8";
    // }
    public function initMonnify()
    {
        $this->baseUrl = config('monnify.base_url', 'https://sandbox.monnify.com');
        $this->apiKey = config('monnify.api_key');
        $this->secretKey = config('monnify.secret_key');
    }

    public function getAccessToken()
    {
        // Cache the token for 55 minutes to avoid requesting every time
        return Cache::remember('monnify_token', 55 * 60, function () {
            $credentials = base64_encode($this->apiKey.':'.$this->secretKey);
            // dd($this->baseUrl, $this->apiKey, $this->secretKey, $credentials);

            $response = Http::withHeaders([
                'Authorization' => 'Basic '.$credentials,
            ])->post("{$this->baseUrl}/api/v1/auth/login");

            if ($response->successful()) {
                return $response->json('responseBody.accessToken');
            }
            throw new \Exception('Failed to authenticate with Monnify: '.$response->body());
        });

    }

    public function verifyIdentityType(string $type, array $data)
    {
        // try {
        $token = $this->getAccessToken();
        Log::info('token: '.$token);

        // $type = strlen($data['bvn']) === 11 ? 'bvn' : 'nin';

        if ($type === 'bvn') {
            logger()->info('Verifying BVN with data: '.json_encode($data));
            $response = Http::withToken($token)->acceptJson()->post("{$this->baseUrl}/api/v1/vas/bvn-details-match", $data); // bvn, name, dateOfBirth, mobileNo
        } else {
            $response = Http::withToken($token)->acceptJson()->post("{$this->baseUrl}/api/v1/vas/nin-details", [
                'nin' => $data['nin'],
            ]);
        }

        if ($response->successful() && isset($response['responseBody'])) {
            return [
                'success' => true,
                'data' => $response['responseBody'],
            ];
        }

        // } catch (\Exception $e) {
        // Log::error("Monnify verification error: ".$e->getMessage());

        return [
            'success' => false,
            'message' => 'Error Message From Monnify: '.$response->json('responseMessage') ?? 'Verification failed.',
            'code' => $response->status(),

        ];
        // }
    }
}
