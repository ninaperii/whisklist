<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function required_administrators_and_demonstration_users_are_seeded_idempotently(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('users', 8);

        foreach (['admin@example.com', 'admin@admin.com'] as $email) {
            $administrator = User::query()->where('email', $email)->firstOrFail();

            $this->assertSame(UserRole::Admin, $administrator->role);
            $this->assertTrue(Hash::check('password', $administrator->password));
        }

        $this->assertSame(6, User::query()->where('role', UserRole::User)->count());
    }
}
