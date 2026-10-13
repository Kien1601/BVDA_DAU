<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->string('method', 20);
            $table->unsignedBigInteger('amount');
            $table->string('status', 20)->default('pending');
            // Mã mình gửi sang VNPay/MoMo, dùng để tìm lại khi cổng gọi về
            $table->string('txn_ref', 64)->unique();
            // Mã giao dịch phía VNPay/MoMo
            $table->string('provider_txn_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            // Nhân viên ghi nhận (tiền mặt, hoàn tiền); null nếu khách tự trả online
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            // Dữ liệu thô cổng thanh toán gửi về, để tra soát
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['rental_order_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};