<?php

namespace Tests\Feature\User;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserAccountFieldsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_account_fields_are_cast_to_their_domain_types(): void
    {
        $user = User::factory()->admin()->privateProfile()->create();

        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertFalse($user->is_profile_public);
        $this->assertTrue($user->isAdmin());
    }

    #[Test]
    public function regular_users_are_not_administrators(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertTrue($user->is_profile_public);
        $this->assertFalse($user->isAdmin());
    }
}
