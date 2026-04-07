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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'ultra hard']);
            $table->string('image_path')->nullable();
            $table->unsignedInteger('min_players')->default(2);
            $table->unsignedInteger('max_players')->default(10);
            $table->unsignedInteger('weekday_price');
            $table->unsignedInteger('weekend_price');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
