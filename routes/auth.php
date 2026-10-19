<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\OtpLoginController;

Route::middleware('guest')->group(function () {
   //

// Khách: đăng nhập bằng mã OTP
Route::get('login', [OtpLoginController::class, 'create'])->name('login');
Route::post('login', [OtpLoginController::class, 'store'])->middleware('throttle:10,1')->name('otp.send');
Route::get('login/code', [OtpLoginController::class, 'edit'])->name('otp.verify');
Route::post('login/code', [OtpLoginController::class, 'verify'])->middleware('throttle:10,1')->name('otp.check');
Route::post('login/code/resend', [OtpLoginController::class, 'resend'])->middleware('throttle:5,1')->name('otp.resend');
Route::get('login/profile', [OtpLoginController::class, 'profile'])->name('otp.profile');
Route::post('login/profile', [OtpLoginController::class, 'storeProfile'])->name('otp.profile.store');

// Nhân viên, quản trị: đăng nhập bằng mật khẩu
Route::get('admin/login', [AuthenticatedSessionController::class, 'create'])->name('admin.login');
Route::post('admin/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
