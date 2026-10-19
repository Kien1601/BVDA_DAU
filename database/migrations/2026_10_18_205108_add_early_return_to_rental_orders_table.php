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
    Schema::table('rental_orders', function (Blueprint $table) {
        $table->dateTime('early_return_at')->nullable()->after('end_time');
        $table->string('early_return_note', 500)->nullable()->after('early_return_at');
        $table->timestamp('early_return_requested_at')->nullable()->after('early_return_note');
    });
}

public function down(): void
{
    Schema::table('rental_orders', function (Blueprint $table) {
        $table->dropColumn(['early_return_at', 'early_return_note', 'early_return_requested_at']);
    });
}
};
