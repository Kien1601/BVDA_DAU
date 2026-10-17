<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_codes', function (Blueprint $table) {
            $table->id();
            // Email (chữ thường) hoặc SĐT dạng 0xxxxxxxxx
            $table->string('identifier', 191);
            $table->string('channel', 10);          // email | sms
            $table->string('code_hash');            // chỉ lưu bản băm, không lưu mã gốc
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['identifier', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_codes');
    }
};