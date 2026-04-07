<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->string('guest_name');
            $table->string('guest_phone');
            $table->string('guest_email')->nullable();
            $table->text('comment')->nullable();
            $table->enum('payment_method', ['cash', 'card', 'paypal'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->unsignedInteger('players_count');
            $table->unsignedInteger('total_price');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'finished'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->index(['room_id', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
