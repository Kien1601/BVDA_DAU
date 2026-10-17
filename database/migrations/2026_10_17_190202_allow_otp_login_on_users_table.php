<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chuẩn hóa SĐT cũ về dạng 0xxxxxxxxx (bỏ dấu cách, chấm, +84) trước khi đặt không trùng
        DB::table('users')->whereNotNull('phone')->orderBy('id')->each(function ($user) {
            $digits = preg_replace('/\D/', '', $user->phone);
            if (str_starts_with($digits, '84')) {
                $digits = '0'.substr($digits, 2);
            }
            DB::table('users')->where('id', $user->id)->update(['phone' => $digits ?: null]);
        });

        Schema::table('users', function (Blueprint $table) {
            // Khách đăng nhập bằng SĐT có thể không có email
            $table->string('email')->nullable()->change();
            // Khách đăng nhập bằng mã OTP không có mật khẩu; nhân viên, admin vẫn có
            $table->string('password')->nullable()->change();
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->string('password')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};