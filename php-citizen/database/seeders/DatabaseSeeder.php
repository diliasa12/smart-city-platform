<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\SharedZoneSeeder;
use Database\Seeders\CitizenSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SharedZoneSeeder::class, // FIX: harus jalan dulu sebelum CitizenSeeder
            CitizenSeeder::class,
        ]);
    }
}
