<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function regular_users_cannot_access_the_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    #[Test]
    public function administrators_can_access_the_admin_dashboard(): void
    {
        $administrator = User::factory()->admin()->create();
        User::factory()->count(2)->create();

        $this->actingAs($administrator)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertViewHas('totalUsers', 3)
            ->assertViewHas('administratorCount', 1)
            ->assertSee('WhiskList overview');
    }

    #[Test]
    public function only_administrators_see_admin_navigation(): void
    {
        $user = User::factory()->create();
        $administrator = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertDontSee(route('admin.dashboard'), escape: false);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertSee(route('admin.dashboard'), escape: false);
    }
}
