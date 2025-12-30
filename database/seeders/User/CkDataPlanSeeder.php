<?php

namespace Database\Seeders\User;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class CkDataPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $apiUrl = config('clubkonnect.api_url');
        $apiKey = config('clubkonnect.api_key');
        $userId = config('clubkonnect.user_id');
        

        $url = $apiUrl."APIDatabundlePlansV2.asp?UserID=".$userId;

        $response = Http::get($url);
        if (!$response->successful()) {
            return;
        }

        $plans = $response->json();

        // dd($plans);


        $networks = $plans['MOBILE_NETWORK']; // Get MTN, Glo, 9mobile

        foreach ($networks as $networkName => $entries) {

            // Create or find the network
            $network = \App\Models\CkMobileNetwork::firstOrCreate(
                ['name' => $networkName]
            );

            // Each entry contains ID + PRODUCT array
            foreach ($entries as $entry) {

                // Validate expected structure
                if (!isset($entry['PRODUCT'])) {
                    continue;
                }

                foreach ($entry['PRODUCT'] as $item) {

                    \App\Models\CkDataPlan::updateOrCreate(
                        [
                            'ck_mobile_network_id' => $entry['ID'],
                            'plan_code' => $item['PRODUCT_ID'],
                        ],
                        [
                            'description' => $item['PRODUCT_NAME'],
                            'price' => floatval($item['PRODUCT_AMOUNT']),
                        ]
                    );
                }
            }
        }

    }
}
