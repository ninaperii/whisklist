<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_users_can_view_their_dashboard(): void
    {
        $user = User::factory()->create([
            'name' => 'Nina Cook',
            'is_profile_public' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHas('user', $user)
            ->assertViewHas('profileVisibility', 'Public')
            ->assertSee('Welcome, Nina Cook')
            ->assertSee('My WhiskList');
    }

    #[Test]
    public function dashboard_displays_private_profile_visibility(): void
    {
        $user = User::factory()->privateProfile()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Profile visibility:')
            ->assertSee('Private');
    }
}
