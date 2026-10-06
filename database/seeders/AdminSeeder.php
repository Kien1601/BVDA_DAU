<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['admin@rental.test', 'Quản trị viên', UserRole::Admin],
            ['staff@rental.test', 'Nhân viên', UserRole::Staff],
        ];

        foreach ($accounts as [$email, $name, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->name = $name;
            $user->password = 'password';
            $user->role = $role;
            $user->is_active = true;
            $user->save();
        }
    }
}