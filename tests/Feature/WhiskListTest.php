<?php

namespace Tests\Feature;

use App\Enums\RecipeStatus;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhiskListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function quick_save_and_remove_return_json_for_live_updates(): void
    {
        $recipe = Recipe::factory()->published()->create();
        $this->actingAs(User::factory()->create())
            ->postJson(route('whisklist.store', $recipe))->assertOk()->assertJsonPath('saved', true);
        $this->postJson(route('whisklist.store', $recipe), ['_method' => 'DELETE'])
            ->assertOk()->assertJsonPath('saved', false);
        $this->assertDatabaseCount('recipe_saves', 0);
    }

    #[Test]
    public function a_recipe_can_be_saved_and_removed_from_filtered_results(): void
    {
        $recipe = Recipe::factory()->published()->create(['title' => 'Apple pancakes']);
        $search = route('recipes.index', ['q' => 'apple']);
        $return = ['from' => 'recipes', 'return_search' => 'q=apple'];

        $this->get($search)->assertOk()->assertDontSee('Save Apple pancakes to My WhiskList');
        $this->actingAs(User::factory()->create())->get($search)
            ->assertOk()->assertSee('Save Apple pancakes to My WhiskList');
        $this->post(route('whisklist.store', $recipe), $return)->assertRedirect($search);
        $this->get($search)->assertOk()->assertSee('Remove Apple pancakes from My WhiskList');
        $this->delete(route('whisklist.destroy', $recipe), $return)->assertRedirect($search);
        $this->get($search)->assertOk()->assertSee('Save Apple pancakes to My WhiskList');
    }

    #[Test]
    public function a_user_can_save_a_published_recipe_once_and_remove_it(): void
    {
        $viewer = User::factory()->create();
        $recipe = Recipe::factory()->published()->create(['title' => 'Saved pancakes']);
        $saveUrl = route('whisklist.store', $recipe);
        $publicUrl = route('recipes.show', $recipe);

        $this->get(route('whisklist.index'))->assertRedirect(route('login'));
        $this->post($saveUrl)->assertRedirect(route('login'));

        $this->actingAs($viewer)->get($publicUrl)->assertOk()->assertSee('Save to My WhiskList');
        $this->post($saveUrl)->assertRedirect($publicUrl);
        $this->post($saveUrl)->assertRedirect($publicUrl);
        $this->assertDatabaseCount('recipe_saves', 1);
        $this->get($publicUrl)->assertOk()->assertSee('Remove from My WhiskList');
        $this->get(route('whisklist.index'))->assertOk()->assertSee($recipe->title);

        $this->actingAs(User::factory()->create());
        $this->get(route('whisklist.index'))->assertOk()->assertDontSee($recipe->title);
        $this->delete(route('whisklist.destroy', $recipe))->assertRedirect($publicUrl);
        $this->assertDatabaseCount('recipe_saves', 1);

        $this->actingAs($viewer)->delete(route('whisklist.destroy', $recipe), ['from' => 'whisklist'])
            ->assertRedirect(route('whisklist.index'));
        $this->assertDatabaseCount('recipe_saves', 0);
    }

    #[Test]
    public function unpublished_recipes_are_hidden_and_cannot_be_saved(): void
    {
        $viewer = User::factory()->create();
        $recipe = Recipe::factory()->published()->create(['title' => 'Unpublished pancakes']);
        $saveUrl = route('whisklist.store', $recipe);
        $this->actingAs($viewer)->post($saveUrl)->assertRedirect();

        $recipe->update(['status' => RecipeStatus::Draft, 'published_at' => null]);
        $this->get(route('whisklist.index'))->assertOk()->assertDontSee($recipe->title);
        $this->post($saveUrl)->assertNotFound();
        $this->assertDatabaseCount('recipe_saves', 1);

        $recipe->delete();
        $this->assertDatabaseCount('recipe_saves', 0);
    }
}
