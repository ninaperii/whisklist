<?php

namespace Tests\Feature;

use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuestHomeCacheTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_warm_command_prepares_the_cache_before_any_guest_visit(): void
    {
        $recipe = Recipe::factory()->published()->create(['title' => 'Warm cache pancakes']);

        $this->artisan('homepage:warm')->assertSuccessful();
        $this->get(route('home'))
            ->assertOk()->assertHeader('X-WhiskList-Home-Cache', 'HIT')->assertSee($recipe->title);
        $this->actingAs($recipe->author)->get(route('home'))
            ->assertOk()->assertHeader('X-WhiskList-Home-Cache', 'BYPASS')->assertSee('Open your dashboard');
        auth()->logout();
        $this->get(route('home'))
            ->assertHeader('X-WhiskList-Home-Cache', 'HIT')->assertDontSee('Open your dashboard');
    }

    #[Test]
    public function cold_guests_fill_the_cache_and_making_a_recipe_private_refreshes_it(): void
    {
        $recipe = Recipe::factory()->published()->create(['title' => 'Soon private pancakes']);

        $this->get(route('home'))
            ->assertOk()->assertHeader('X-WhiskList-Home-Cache', 'MISS')->assertSee($recipe->title);
        $this->get(route('home'))->assertHeader('X-WhiskList-Home-Cache', 'HIT');

        $this->actingAs($recipe->author)->patch(route('recipes.unpublish', $recipe))->assertRedirect();
        auth()->logout();
        $this->get(route('home'))
            ->assertOk()->assertHeader('X-WhiskList-Home-Cache', 'HIT')->assertDontSee($recipe->title);
    }
}
