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
    public function only_the_configured_administrator_is_seeded_idempotently(): void
    {
        config([
            'whisklist.admin_email' => 'example-admin@example.test',
            'whisklist.admin_password' => 'test-admin-passphrase',
        ]);
        $this->seed();
        config(['whisklist.admin_password' => 'unused-after-first-seed']);
        $this->seed();

        $this->assertDatabaseCount('users', 1);
        $administrator = User::query()->where('role', UserRole::Admin)->sole();
        $this->assertSame('example-admin@example.test', $administrator->email);
        $this->assertTrue(Hash::check('test-admin-passphrase', $administrator->password));

    }

    #[Test]
    public function no_administrator_is_seeded_with_a_default_password(): void
    {
        config(['whisklist.admin_password' => null]);
        $this->seed();

        $this->assertSame(0, User::query()->where('role', UserRole::Admin)->count());
    }
}
