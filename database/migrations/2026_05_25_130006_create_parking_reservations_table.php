<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parking_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_spot_id')->constrained('parking_spots')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('total_price', 8, 2);
            $table->string('status', 50)->default('Pending'); // Pending, Confirmed, Cancelled, Completed
            $table->string('reference', 100)->unique();
            $table->string('verification_code', 10)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_reservations');
    }
};
