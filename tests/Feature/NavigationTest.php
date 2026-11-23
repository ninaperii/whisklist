<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_see_public_account_navigation(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('login'), escape: false)
            ->assertSee(route('register'), escape: false)
            ->assertDontSee(route('dashboard'), escape: false);
    }

    #[Test]
    public function authenticated_users_see_dashboard_and_logout_navigation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('dashboard'), escape: false)
            ->assertSee(route('logout'), escape: false)
            ->assertDontSee(route('login'), escape: false)
            ->assertDontSee(route('register'), escape: false);
    }
}
