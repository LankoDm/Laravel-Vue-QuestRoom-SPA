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
            $table->string('age')->default('12+')->after('difficulty');
            $table->string('hint_phrase')->nullable()->after('age');
            $table->string('genre')->nullable()->after('hint_phrase');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['age', 'hint_phrase', 'genre']);
        });
    }
};
