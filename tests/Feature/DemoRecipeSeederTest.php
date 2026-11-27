<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\DemoRecipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DemoRecipeSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_demo_seeder_can_be_rerun_and_links_recipes_to_the_demo_accounts(): void
    {
        $storage = Storage::fake('local');
        $existing = Recipe::factory()->create(['title' => 'Original draft']);

        $this->seed(DemoRecipeSeeder::class);
        $this->seed(DemoRecipeSeeder::class);

        $this->assertModelExists($existing);
        $this->assertSame(6, Recipe::query()->published()->count());
        $this->assertSame(2, Recipe::query()->draft()->count());
        $this->assertDatabaseCount('images', 6);
        $this->assertDatabaseCount('comments', 3);
        $this->assertDatabaseCount('recipe_saves', 2);

        $maya = User::query()->where('email', 'maya@example.com')->sole();
        $leo = User::query()->where('email', 'leo@example.com')->sole();
        $this->assertSame(['apples'], $maya->excludedIngredients()->pluck('slug')->all());
        $this->assertFalse($leo->is_profile_public);
        $this->assertSame(['eggs'], $leo->allergens()->pluck('slug')->all());
        $this->assertSame(['vegan'], $leo->preferredDietaryTags()->pluck('slug')->all());

        $pancakes = $leo->savedRecipes()->sole();
        $this->assertSame('Banana Oat Pancakes', $pancakes->title);
        $storage->assertExists($pancakes->thumbnail->path);
        $this->actingAs($leo)->get(route('recipes.show', $pancakes))->assertOk()->assertSee('Allergen match: Eggs');
    }
}
