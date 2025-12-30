<?php

namespace Database\Seeders\User;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CkCableTvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $packages = [
            ['name' => 'DStv', 'code' => 'dstv', 'slug' => 'dstv'],
            ['name' => 'GOtv', 'code' => 'gotv', 'slug' => 'gotv'],
            ['name' => 'Startimes', 'code' => 'startimes', 'slug' => 'startimes'],
        ];

        foreach ($packages as $package) {
            \App\Models\CkCableTv::create($package);
        }
    }
}
