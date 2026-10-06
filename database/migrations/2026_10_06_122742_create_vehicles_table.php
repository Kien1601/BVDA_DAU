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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->string('license_plate', 20)->unique();
            $table->string('name');
            $table->string('brand', 50)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('price_per_hour');
            $table->unsignedInteger('price_per_day');
            $table->unsignedInteger('price_per_week');
            $table->enum('status', ['available', 'rented', 'maintenance'])->default('available');

            // GPS: khách hàng không bao giờ được thấy các cột này
            $table->string('gps_device_id', 32)->nullable()->unique();
            $table->timestamp('last_signal_at')->nullable();   // giờ server nhận, dùng cho C10
            $table->timestamp('last_device_time')->nullable(); // giờ thiết bị của vị trí cuối
            $table->decimal('last_lat', 10, 7)->nullable();
            $table->decimal('last_lng', 10, 7)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
