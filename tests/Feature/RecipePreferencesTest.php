<?php

namespace Tests\Feature;

use App\Models\Allergen;
use App\Models\DietaryTag;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecipePreferencesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function viewers_see_warnings_only_for_their_own_saved_allergens(): void
    {
        $allergen = Allergen::factory()->create(['name' => 'Wheat']);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.ingredients.store'), [
            'name' => 'Bread flour',
            'allergens' => [$allergen->id],
        ])->assertRedirect(route('admin.ingredients.index'));
        $ingredient = Ingredient::query()->where('slug', 'bread-flour')->sole();
        $recipe = Recipe::factory()->published()->create(['title' => 'Fresh bread']);
        $recipe->ingredients()->attach($ingredient, ['position' => 1]);

        $viewer = User::factory()->create();
        $this->actingAs($viewer)->put(route('profile.recipe-preferences.update'), [
            'allergens' => [$allergen->id],
        ])->assertRedirect();

        foreach ([route('home'), route('recipes.index'), route('recipes.show', $recipe)] as $url) {
            $this->get($url)->assertOk()->assertSee('Allergen match: Wheat');
        }
        $this->actingAs($admin)->get(route('recipes.show', $recipe))->assertOk()->assertDontSee('Allergen match:');
        auth()->logout();
        $this->get(route('recipes.show', $recipe))->assertOk()->assertDontSee('Allergen match:');
    }

    #[Test]
    public function saved_preferences_are_private_and_only_filter_when_requested(): void
    {
        $user = User::factory()->create(['is_profile_public' => true]);
        $tag = DietaryTag::factory()->create(['name' => 'Vegan']);
        $ingredient = Ingredient::factory()->create(['name' => 'Bananas']);
        $matching = Recipe::factory()->published()->create(['title' => 'Oat breakfast']);
        $matching->dietaryTags()->attach($tag);
        $excluded = Recipe::factory()->published()->create(['title' => 'Banana bread']);
        $excluded->dietaryTags()->attach($tag);
        $excluded->ingredients()->attach($ingredient, ['position' => 1]);
        $untagged = Recipe::factory()->published()->create(['title' => 'Plain porridge']);
        $updateUrl = route('profile.recipe-preferences.update');
        $filterUrl = route('recipes.index', ['use_preferences' => 1]);

        $this->put($updateUrl)->assertRedirect(route('login'));
        $this->get($filterUrl)->assertOk()->assertSee($excluded->title);
        $this->actingAs($user)->put($updateUrl, [
            'dietary_tags' => [$tag->id],
            'exclude_ingredients' => [$ingredient->id],
        ])->assertRedirect(route('profile.edit').'#recipe-preferences');

        $this->get(route('recipes.index'))->assertOk()->assertSee($excluded->title);
        $this->get($filterUrl)->assertOk()->assertSee($matching->title)
            ->assertDontSee($excluded->title)->assertDontSee($untagged->title);

        $this->actingAs(User::factory()->create())->get($filterUrl)->assertOk()->assertSee($excluded->title);
        $this->get(route('users.show', $user))->assertOk()->assertDontSee($tag->name)->assertDontSee($ingredient->name);

        $this->actingAs($user)->put($updateUrl, ['exclude_ingredients' => [999999]])
            ->assertSessionHasErrorsIn('recipePreferences', 'exclude_ingredients.0');
        $this->get($filterUrl)->assertOk()->assertDontSee($excluded->title);
        $this->put($updateUrl)->assertRedirect();
        $this->get($filterUrl)->assertOk()->assertSee($excluded->title)->assertSee($untagged->title);
    }
}
