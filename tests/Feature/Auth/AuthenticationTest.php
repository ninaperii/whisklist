<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_can_view_the_login_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login');
    }

    #[Test]
    public function users_can_log_in(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => strtoupper($user->email),
            'password' => 'password',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function users_cannot_log_in_with_an_incorrect_password(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function authenticated_users_cannot_view_login(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('login'));

        $response->assertRedirect(route('home'));
    }

    #[Test]
    public function users_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    #[Test]
    public function guests_cannot_access_the_logout_action(): void
    {
        $this->post(route('logout'))
            ->assertRedirect(route('login'));
    }
}
