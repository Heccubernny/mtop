<?php

namespace Database\Seeders\User;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class CkCableTvPackagesSeeder extends Seeder
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
        

        $url = $apiUrl."APICableTVPackagesV2.asp?UserID=".$userId;

        $response = Http::get($url);
        if (!$response->successful()) {
            return;
        }

        $packages = $response->json();

        // dd($packages['TV_ID']);


        $tvs = $packages['TV_ID'];

        foreach ($tvs as $tv => $entries) {

            // Create or find the network
            $cable = \App\Models\CkCableTv::firstOrCreate(
                ['name' => $tv]
            );

            // Each entry contains ID + Package array
            foreach ($entries as $entry) {

                // Validate expected structure
                if (!isset($entry['PRODUCT'])) {
                    continue;
                }

                foreach ($entry['PRODUCT'] as $item) {

                    \App\Models\CkCableTvPackages::updateOrCreate(
                        [
                            'ck_cable_tv_id' => $cable->id,
                            'package_code' => $item['PACKAGE_ID'],
                        ],
                        [
                            'description' => $item['PACKAGE_NAME'],
                            'price' => floatval($item['PACKAGE_AMOUNT']),
                            'cable_tv' => $entry['ID'],
                        ]
                    );
                }
            }
        }

    }
}
