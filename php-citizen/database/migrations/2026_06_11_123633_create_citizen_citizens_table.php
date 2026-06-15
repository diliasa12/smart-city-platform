<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_citizens', function (Blueprint $table) {
            $table->increments('id');
            $table->char('nik', 16)->unique()->comment('Nomor Induk Kependudukan');
            $table->string('name', 150);
            $table->string('email', 255)->unique();
            $table->string('phone', 20)->nullable();
            $table->unsignedInteger('zone_id');
            $table->enum('role', ['citizen', 'admin', 'officer'])->default('citizen');
            $table->string('password', 255)->comment('bcrypt hash');
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->index('zone_id');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_citizens');
    }
};