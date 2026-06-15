<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CitizenSeeder extends Seeder
{
    public function run(): void
    {
        // Data warga dummy
        $citizens = [];
        for ($i = 1; $i <= 50; $i++) {
            $citizens[] = [
                'nik'        => str_pad($i, 16, '0', STR_PAD_LEFT),
                'name'       => 'Warga ' . $i,
                'email'      => 'warga' . $i . '@smartcity.id',
                'phone'      => '08' . rand(100000000, 999999999),
                'zone_id'    => rand(1, 5),
                'role'       => $i <= 2 ? 'admin' : 'citizen',
                'password'   => Hash::make('password123'),
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('citizen_citizens')->insert($citizens);

        // Data laporan dummy
        $reports = [];
        $categories = ['infrastructure', 'environment', 'traffic', 'public_safety', 'other'];
        $statuses   = ['pending', 'in_progress', 'resolved', 'rejected'];
        for ($i = 0; $i < 20; $i++) {
            $reports[] = [
                'citizen_id'  => rand(3, 50),
                'category'    => $categories[array_rand($categories)],
                'description' => 'Laporan dummy nomor ' . ($i + 1),
                'zone_id'     => rand(1, 5),
                'status'      => $statuses[array_rand($statuses)],
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }
        DB::table('citizen_reports')->insert($reports);
    }
}