<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    #[Test]
    public function it_has_user_and_admin_roles(): void
    {
        $this->assertSame('user', UserRole::User->value);
        $this->assertSame('admin', UserRole::Admin->value);
    }
}
