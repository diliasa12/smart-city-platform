<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('shared_zones', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('city_district');
      $table->json('coordinates'); // Untuk data GeoJSON Point Anda
      $table->float('area_km2');
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('citizen_notifications');
  }
};
