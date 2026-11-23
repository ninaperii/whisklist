<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_can_view_the_registration_form(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertViewIs('auth.register');
    }

    #[Test]
    public function guests_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Nina Cook',
            'email' => 'NINA@EXAMPLE.COM',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'nina@example.com')->firstOrFail();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    #[Test]
    public function registration_input_is_validated(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => '',
            'email' => 'existing@example.com',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertGuest();
    }

    #[Test]
    public function authenticated_users_cannot_view_registration(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('register'));

        $response->assertRedirect(route('dashboard'));
    }
}
