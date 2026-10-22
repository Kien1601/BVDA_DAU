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
        // Mỗi khách có đúng 1 hội thoại với cửa hàng
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('customer_lang', 10)->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('auto_replied_at')->nullable();
            $table->timestamp('staff_read_at')->nullable();
            $table->timestamp('customer_read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type', 10); // customer | staff | system
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->string('lang', 10)->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'id']);
        });

        // Bản dịch lưu lại, mỗi tin chỉ dịch 1 lần cho mỗi ngôn ngữ
        Schema::create('message_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('lang', 10);
            $table->text('body');
            $table->timestamps();
            $table->unique(['message_id', 'lang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_translations');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
