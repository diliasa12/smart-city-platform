<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('env_rooms', function (Blueprint $table) {
        $table->id();
        // Relasi ke tabel shared_zones
        $table->foreignId('zone_id')->constrained('shared_zones')->onDelete('cascade');
        $table->string('room_name');
        $table->integer('capacity');
        $table->string('device_token')->unique()->nullable(); // Token untuk Wokwi/MicroPython
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('env_rooms');
    }
};
