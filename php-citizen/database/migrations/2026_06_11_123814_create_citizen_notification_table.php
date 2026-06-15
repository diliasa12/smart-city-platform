<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('citizen_id');
            $table->string('title', 255);
            $table->text('body');
            $table->tinyInteger('is_read')->default(0);
            $table->dateTime('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('citizen_id');
            $table->index('is_read');
            $table->index('created_at');

            $table->foreign('citizen_id')
                  ->references('id')
                  ->on('citizen_citizens')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_notifications');
    }
};