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
       Schema::create('rental_orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->restrictOnDelete();
    $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
    $table->foreignId('promotion_id')->nullable()->constrained()->restrictOnDelete();

    $table->dateTime('start_time');
    $table->dateTime('end_time');
    $table->dateTime('actual_return_time')->nullable();
    $table->enum('status', ['pending', 'approved', 'active', 'completed', 'cancelled'])->default('pending');

    // Lưu số tiền tại thời điểm đặt: đổi giá sau này không ảnh hưởng đơn cũ
    $table->unsignedInteger('subtotal_amount');            // tiền thuê trước giảm
    $table->unsignedInteger('discount_amount')->default(0);
    $table->unsignedInteger('total_amount');               // sau giảm
    $table->unsignedInteger('deposit_amount');
    $table->unsignedInteger('surcharge_amount')->default(0);

    $table->dateTime('expires_at')->nullable();            // hết hạn giữ chỗ (đơn chờ duyệt)
    $table->dateTime('cancelled_at')->nullable();
    $table->string('cancel_reason')->nullable();
    $table->text('note')->nullable();
    $table->timestamps();

    $table->index(['vehicle_id', 'status', 'start_time', 'end_time']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_orders');
    }
};
