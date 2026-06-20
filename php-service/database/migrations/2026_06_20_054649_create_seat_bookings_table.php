<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('seat_bookings', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('user_id');
        $table->unsignedInteger('room_id');
        $table->string('seat_number', 10);
        $table->date('booking_date');
        $table->time('start_time');
        $table->time('end_time');
        $table->enum('status', ['pending', 'approved', 'cancelled'])->default('pending');
        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('room_id')->references('id')->on('env_rooms')->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::dropIfExists('seat_bookings');
}
};
