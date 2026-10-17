<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
                // A5: gửi SMS mã OTP. Hiện ghi log, có dịch vụ SMS thật thì thay lớp ở đây
        $this->app->bind(\App\Services\Otp\SmsSender::class, \App\Services\Otp\LogSmsSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
       foreach (config('permissions') as $ability => $roles) {
        Gate::define($ability, fn (User $user) => in_array($user->role->value, $roles, true));
        }
    }
}
