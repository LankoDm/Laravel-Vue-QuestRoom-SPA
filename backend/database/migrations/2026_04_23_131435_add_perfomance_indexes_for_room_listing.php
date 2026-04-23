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
        Schema::table('rooms', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('difficulty');
            $table->index('genre');
            $table->index('created_at');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['room_id', 'is_approved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['room_id', 'is_approved']);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['difficulty']);
            $table->dropIndex(['genre']);
            $table->dropIndex(['created_at']);
        });
    }
};
