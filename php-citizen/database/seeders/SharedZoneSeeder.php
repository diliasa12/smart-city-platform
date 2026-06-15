<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SharedZoneSeeder extends Seeder
{
  public function run(): void
  {
    // FIX: tabel shared_zones harus ada datanya sebelum citizen/report bisa di-insert
    // karena ada foreign key zone_id → shared_zones.id
    // Zona harus sesuai dengan ZONE_PROFILES di iot/simulator.py (zone1–zone5)
    $zones = [
      [
        'id'            => 1,
        'name'          => 'zone1',
        'city_district' => 'Pusat',
        'coordinates'   => json_encode(['type' => 'Point', 'coordinates' => [106.8272, -6.1751]]),
        'area_km2'      => 12.50,
        'created_at'    => now(),
        'updated_at'    => now(),
      ],
      [
        'id'            => 2,
        'name'          => 'zone2',
        'city_district' => 'Utara',
        'coordinates'   => json_encode(['type' => 'Point', 'coordinates' => [106.8300, -6.1200]]),
        'area_km2'      => 18.20,
        'created_at'    => now(),
        'updated_at'    => now(),
      ],
      [
        'id'            => 3,
        'name'          => 'zone3',
        'city_district' => 'Selatan',
        'coordinates'   => json_encode(['type' => 'Point', 'coordinates' => [106.8200, -6.2500]]),
        'area_km2'      => 22.80,
        'created_at'    => now(),
        'updated_at'    => now(),
      ],
      [
        'id'            => 4,
        'name'          => 'zone4',
        'city_district' => 'Timur',
        'coordinates'   => json_encode(['type' => 'Point', 'coordinates' => [106.9000, -6.1900]]),
        'area_km2'      => 16.40,
        'created_at'    => now(),
        'updated_at'    => now(),
      ],
      [
        'id'            => 5,
        'name'          => 'zone5',
        'city_district' => 'Barat',
        'coordinates'   => json_encode(['type' => 'Point', 'coordinates' => [106.7500, -6.1800]]),
        'area_km2'      => 14.10,
        'created_at'    => now(),
        'updated_at'    => now(),
      ],
    ];

    // upsert agar idempotent — aman dijalankan berulang kali
    DB::table('shared_zones')->upsert(
      $zones,
      ['id'],
      ['name', 'city_district', 'coordinates', 'area_km2', 'updated_at']
    );
  }
}
