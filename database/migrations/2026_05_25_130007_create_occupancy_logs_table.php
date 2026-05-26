<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('occupancy_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_spot_id')->constrained('parking_spots')->onDelete('cascade');
            $table->foreignId('parking_zone_id')->constrained('parking_zones')->onDelete('cascade');
            $table->string('vehicle_plate', 20);
            $table->string('action', 20); // Entry, Exit
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupancy_logs');
    }
};
