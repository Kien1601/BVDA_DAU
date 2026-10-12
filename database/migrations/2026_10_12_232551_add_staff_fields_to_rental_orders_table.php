<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_orders', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('picked_up_at')->nullable();
            $table->foreignId('picked_up_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('original_end_time')->nullable();
            $table->string('surcharge_note', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rental_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('picked_up_by');
            $table->dropConstrainedForeignId('returned_by');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['approved_at', 'picked_up_at', 'original_end_time', 'surcharge_note']);
        });
    }
};