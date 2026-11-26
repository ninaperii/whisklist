<?php

namespace Tests\Feature;

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
