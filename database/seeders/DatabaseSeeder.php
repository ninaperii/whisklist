<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('whisklist.admin_email');
        $password = config('whisklist.admin_password');
        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            return;
        }

        $administrator = User::query()->firstOrNew(['email' => $email]);
        if ($administrator->exists) {
            return;
        }

        $administrator->forceFill(User::factory()->raw([
            'name' => 'WhiskList Administrator',
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
            'is_profile_public' => false,
        ]))->save();
    }
}
