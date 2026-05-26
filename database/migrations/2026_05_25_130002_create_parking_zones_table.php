<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parking_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_admin')->constrained('users')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('city', 100);
            $table->string('address', 255);
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('total_spots')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_zones');
    }
};
