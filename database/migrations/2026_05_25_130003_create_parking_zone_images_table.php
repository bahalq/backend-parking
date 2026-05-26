<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parking_zone_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_zone_id')->constrained('parking_zones')->onDelete('cascade');
            $table->string('image');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_zone_images');
    }
};
