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
    public function a_single_configured_administrator_and_demonstration_users_are_seeded_idempotently(): void
    {
        config(['whisklist.admin_password' => 'test-admin-passphrase']);
        $this->seed();
        config(['whisklist.admin_password' => 'unused-after-first-seed']);
        $this->seed();

        $this->assertDatabaseCount('users', 7);
        $administrator = User::query()->where('role', UserRole::Admin)->sole();
        $this->assertSame('admin@example.com', $administrator->email);
        $this->assertTrue(Hash::check('test-admin-passphrase', $administrator->password));

        $this->assertSame(6, User::query()->where('role', UserRole::User)->count());
    }

    #[Test]
    public function no_administrator_is_seeded_with_a_default_password(): void
    {
        config(['whisklist.admin_password' => null]);
        $this->seed();

        $this->assertSame(0, User::query()->where('role', UserRole::Admin)->count());
    }
}
