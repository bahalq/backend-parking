<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('traffic_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_zone_id')->constrained('parking_zones')->onDelete('cascade');
            $table->integer('hour_of_day');
            $table->integer('day_of_week'); // 0 (Sunday) to 6 (Saturday)
            $table->integer('vehicle_count')->default(0);
            $table->integer('average_stay_duration_minutes')->default(0);
            $table->decimal('occupancy_rate', 5, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_analytics');
    }
};
