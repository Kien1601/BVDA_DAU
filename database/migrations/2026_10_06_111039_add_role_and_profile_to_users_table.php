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
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 20)->nullable()->after('email');
                $table->enum('role', ['customer', 'staff', 'admin'])->default('customer')->after('password');
                $table->string('id_card_number', 20)->nullable();
                $table->unsignedInteger('violation_count')->default(0);
            $table->boolean('is_active')->default(true);
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
        {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['phone', 'role', 'id_card_number', 'violation_count', 'is_active']);
            });
        }
};
