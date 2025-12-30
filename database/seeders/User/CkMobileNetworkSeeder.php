<?php

namespace Database\Seeders\User;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CkMobileNetworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $networks = [
            ['name' => 'MTN', 'code' => '01', 'slug' => 'mtn'],
            ['name' => 'Glo', 'code' => '02', 'slug' => 'glo'],
            ['name' => 'm_9mobile', 'code' => '03', 'slug' => '9mobile'],
            ['name' => 'Airtel', 'code' => '04', 'slug' => 'airtel'],
        ];

        foreach ($networks as $net) {
            \App\Models\CkMobileNetwork::create($net);
        }
    }
}
