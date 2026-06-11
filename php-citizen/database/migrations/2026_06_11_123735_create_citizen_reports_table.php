<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('citizen_id');
            $table->enum('category', [
                'infrastructure',
                'environment',
                'traffic',
                'public_safety',
                'other'
            ])->default('other');
            $table->text('description');
            $table->unsignedInteger('zone_id');
            $table->enum('status', [
                'pending',
                'in_progress',
                'resolved',
                'rejected'
            ])->default('pending');
            $table->string('attachment_url', 512)->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index('citizen_id');
            $table->index('zone_id');
            $table->index('status');
            $table->index('category');
            $table->index('created_at');

            $table->foreign('citizen_id')
                  ->references('id')
                  ->on('citizen_citizens')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_reports');
    }
};