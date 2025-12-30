<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\ReferralSetting;
use Illuminate\Database\Seeder;

class ReferralSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (! ReferralSetting::first()) {
            ReferralSetting::create(['id' => '1', 'bonus' => '0.50000000', 'wallet_type' => 'c_balance', 'mail' => '1', 'sms' => '1', 'status' => '1', 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
