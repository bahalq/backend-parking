<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parking_spots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_zone_id')->constrained('parking_zones')->onDelete('cascade');
            $table->foreignId('vehicle_category_id')->constrained('vehicle_categories')->onDelete('cascade');
            $table->string('name', 50);
            $table->string('type', 50)->default('Standard');
            $table->string('status', 50)->default('Available');
            $table->decimal('price_per_hour', 8, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_spots');
    }
};
