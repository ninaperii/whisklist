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

class RecipeDraftTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_author_can_save_reopen_and_update_a_draft(): void
    {
        $author = User::factory()->create();
        $category = Category::factory()->create();
        $tag = DietaryTag::factory()->create();
        [$flour, $salt] = Ingredient::factory()->count(2)->create();
        $data = $this->draftData() + [
            'categories' => [$category->id],
            'dietary_tags' => [$tag->id],
            'ingredients' => [
                ['id' => $flour->id, 'quantity' => '1.25', 'unit' => 'cup', 'is_main' => '1'],
                ['id' => $salt->id, 'notes' => 'To taste'],
            ],
            'steps' => ['Mix the dough', 'Bake the crust'],
            'status' => 'published',
            'user_id' => User::factory()->create()->id,
        ];

        $this->actingAs($author)->get(route('recipes.create'))->assertOk();
        $response = $this->post(route('recipes.store'), $data);
        $recipe = Recipe::sole();
        $response->assertRedirect(route('recipes.edit', $recipe));
        $this->assertSame(RecipeStatus::Draft, $recipe->status);
        $this->assertSame($author->id, $recipe->user_id);
        $this->assertTrue($recipe->categories->contains($category));
        $this->assertTrue($recipe->dietaryTags->contains($tag));
        $this->assertEquals(1.25, $recipe->ingredients->first()->pivot->quantity);
        $this->get(route('recipes.mine'))->assertOk()->assertSee($recipe->title);
        $this->get(route('recipes.edit', $recipe))->assertOk()->assertSee('Bake the crust');

        $data['title'] = 'Updated pie';
        $data['ingredients'] = array_reverse($data['ingredients']);
        $data['steps'] = ['Bake the updated crust'];
        unset($data['dietary_tags']);

        $this->put(route('recipes.update', $recipe), $data)->assertRedirect(route('recipes.edit', 'updated-pie'));
        $recipe->refresh();
        $this->assertSame([$salt->id, $flour->id], $recipe->ingredients->modelKeys());
        $this->assertSame(['Bake the updated crust'], $recipe->steps->pluck('instruction')->all());
        $this->assertCount(0, $recipe->dietaryTags);
    }

    #[Test]
    public function a_draft_can_be_saved_before_any_taxonomy_exists(): void
    {
        $this->actingAs(User::factory()->create())->get(route('recipes.create'))->assertOk();
        $this->post(route('recipes.store'), $this->draftData())->assertRedirect(route('recipes.edit', 'apple-pie'));
        $this->assertDatabaseCount('recipes', 1);
    }

    #[Test]
    public function guests_and_other_users_cannot_modify_drafts(): void
    {
        $recipe = Recipe::factory()->create(['title' => 'Someone else’s draft']);

        $this->get(route('recipes.create'))->assertRedirect(route('login'));
        $this->post(route('recipes.store'), $this->draftData())->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('recipes.mine'))->assertOk()->assertDontSee($recipe->title);
        $this->get(route('recipes.edit', $recipe))->assertForbidden();
        $this->put(route('recipes.update', $recipe), $this->draftData())->assertForbidden();
        $this->assertSame('Someone else’s draft', $recipe->fresh()->title);
    }

    #[Test]
    public function validation_rejects_duplicate_ingredients_and_invalid_steps(): void
    {
        $ingredient = Ingredient::factory()->create();
        $this->actingAs(User::factory()->create())
            ->from(route('recipes.create'))
            ->post(route('recipes.store'), $this->draftData() + [
                'ingredients' => [['id' => $ingredient->id], ['id' => $ingredient->id]],
                'steps' => [''],
            ])
            ->assertRedirect(route('recipes.create'))
            ->assertSessionHasErrors(['ingredients.0.id', 'ingredients.1.id', 'steps.0']);

        $this->assertDatabaseCount('recipes', 0);
    }

    #[Test]
    public function authors_can_add_new_ingredients_to_an_empty_catalog_and_reuse_them_by_name(): void
    {
        $this->actingAs(User::factory()->create())->get(route('recipes.create'))
            ->assertOk()->assertSee('Add a new ingredient');
        $this->post(route('recipes.store'), $this->draftData() + [
            'ingredients' => [['new_name' => 'Rice flour', 'quantity' => 200, 'unit' => 'g', 'is_main' => true]],
        ])->assertRedirect(route('recipes.edit', 'apple-pie'));

        $recipe = Recipe::query()->sole();
        $ingredient = Ingredient::query()->sole();
        $this->assertSame('Rice flour', $ingredient->name);
        $this->assertEquals(200, $recipe->ingredients()->sole()->pivot->quantity);
        $this->put(route('recipes.update', $recipe), $this->draftData() + [
            'ingredients' => [['new_name' => 'RICE  FLOUR']],
        ])->assertRedirect(route('recipes.edit', $recipe));
        $this->assertDatabaseCount('ingredients', 1);
    }

    #[Test]
    public function duplicate_new_ingredient_names_roll_back_the_recipe_and_catalog_change(): void
    {
        $this->actingAs(User::factory()->create())->from(route('recipes.create'))
            ->post(route('recipes.store'), $this->draftData() + [
                'ingredients' => [['new_name' => 'Rice flour'], ['new_name' => 'rice FLOUR']],
            ])->assertSessionHasErrors('ingredients.1.id');

        $this->assertDatabaseCount('ingredients', 0);
        $this->assertDatabaseCount('recipes', 0);
    }

    private function draftData(): array
    {
        return [
            'title' => 'Apple pie',
            'preparation_minutes' => 15,
            'cooking_minutes' => 30,
            'servings' => 4,
            'difficulty' => 'easy',
        ];
    }
}
