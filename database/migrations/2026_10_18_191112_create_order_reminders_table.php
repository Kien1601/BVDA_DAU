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
        Schema::create('order_reminders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('rental_order_id')->constrained()->cascadeOnDelete();
        $table->string('kind', 30);
        $table->dateTime('due_at');
        $table->timestamp('sent_at');
        $table->unique(['rental_order_id', 'kind', 'due_at']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_reminders');
    }
};
