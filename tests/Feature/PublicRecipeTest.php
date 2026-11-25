<?php

namespace Tests\Feature;

use App\Enums\RecipeStatus;
use App\Models\Category;
use App\Models\DietaryTag;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicRecipeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_can_browse_published_recipes_across_multiple_pages(): void
    {
        $older = Recipe::factory()->published()->create(['title' => 'Oldest published tart', 'published_at' => now()->subDay()]);
        Recipe::factory()->published()->count(11)->create();
        $newer = Recipe::factory()->published()->create(['title' => 'Newest published pancakes']);
        $newer->thumbnail()->create(['path' => 'recipe-thumbnails/pancakes.png', 'is_thumbnail' => true]);
        $draft = Recipe::factory()->create(['title' => 'Private pie draft']);
        $future = Recipe::factory()->published()->create(['title' => 'Scheduled pie', 'published_at' => now()->addDay()]);

        $this->get(route('recipes.index'))
            ->assertOk()
            ->assertSee(route('recipes.show', $newer), false)
            ->assertSee(route('recipes.thumbnail', $newer), false)
            ->assertDontSee($older->title)
            ->assertDontSee($draft->title)
            ->assertDontSee($future->title);

        $this->get(route('recipes.index', ['page' => 2]))
            ->assertOk()
            ->assertSee($older->title)
            ->assertDontSee($newer->title);
    }

    #[Test]
    public function the_homepage_links_to_recent_published_recipes_only(): void
    {
        $older = Recipe::factory()->published()->create(['title' => 'Lemon tart', 'published_at' => now()->subDay()]);
        $newer = Recipe::factory()->published()->create(['title' => 'Pancakes']);
        $newer->thumbnail()->create(['path' => 'recipe-thumbnails/pancakes.png', 'is_thumbnail' => true]);
        $draft = Recipe::factory()->create(['title' => 'Private pie draft']);
        $future = Recipe::factory()->published()->create(['title' => 'Future pie', 'published_at' => now()->addDay()]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeTextInOrder([$newer->title, $older->title])
            ->assertSee(route('recipes.show', $newer), false)
            ->assertSee(route('recipes.thumbnail', $newer), false)
            ->assertDontSee($draft->title)
            ->assertDontSee($future->title);
    }

    #[Test]
    public function guests_can_read_published_recipes(): void
    {
        $author = User::factory()->create(['is_profile_public' => true]);
        $recipe = Recipe::factory()->published()->for($author, 'author')->create(['title' => 'Lemon tart']);
        $recipe->categories()->attach(Category::factory()->create(['name' => 'Desserts']));
        $recipe->dietaryTags()->attach(DietaryTag::factory()->create(['name' => 'Vegetarian']));
        $recipe->ingredients()->attach(Ingredient::factory()->create(['name' => 'Lemon juice']), [
            'position' => 1, 'quantity' => 1.25, 'unit' => 'cups',
        ]);
        $recipe->steps()->create(['position' => 2, 'instruction' => 'Bake the tart']);
        $recipe->steps()->create(['position' => 1, 'instruction' => 'Mix the filling']);

        $this->get(route('recipes.show', $recipe))
            ->assertOk()
            ->assertSee('Lemon tart')
            ->assertSee(route('users.show', $author), false)
            ->assertSee('Desserts')
            ->assertSee('Vegetarian')
            ->assertSeeTextInOrder(['1.25', 'cups', 'Lemon juice', 'Mix the filling', 'Bake the tart']);
    }

    #[Test]
    public function a_private_profile_does_not_expose_author_details_or_hide_the_recipe(): void
    {
        $author = User::factory()->privateProfile()->create(['name' => 'Private Author']);
        $recipe = Recipe::factory()->published()->for($author, 'author')->create();

        $this->get(route('recipes.show', $recipe))
            ->assertOk()
            ->assertSee($recipe->title)
            ->assertDontSee($author->name)
            ->assertDontSee(route('users.show', $author), false);
    }

    #[Test]
    public function drafts_and_future_publications_are_unavailable_on_the_public_route(): void
    {
        $draft = Recipe::factory()->create();
        $futureRecipe = Recipe::factory()->create([
            'status' => RecipeStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('recipes.show', $draft))->assertNotFound();
        $this->get(route('recipes.show', $futureRecipe))->assertNotFound();
        $this->get(route('recipes.show', 'missing-recipe'))->assertNotFound();
        $this->actingAs($draft->author)->get(route('recipes.show', $draft))->assertNotFound();
        $this->get(route('recipes.edit', $draft))->assertOk();
    }
}
