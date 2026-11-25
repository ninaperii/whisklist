<?php

namespace Tests\Feature;

use App\Enums\RecipeDifficulty;
use App\Enums\RecipeStatus;
use App\Models\Category;
use App\Models\DietaryTag;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecipeDataModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_user_has_authored_recipes_with_enum_casts(): void
    {
        $author = User::factory()->create();
        $recipe = Recipe::factory()->for($author, 'author')->create([
            'difficulty' => RecipeDifficulty::Medium,
        ]);

        $this->assertTrue($author->recipes->contains($recipe));
        $this->assertTrue($recipe->author->is($author));
        $this->assertSame(RecipeDifficulty::Medium, $recipe->difficulty);
        $this->assertSame(RecipeStatus::Draft, $recipe->status);
    }

    #[Test]
    public function recipes_receive_unique_slugs_from_their_titles(): void
    {
        $firstRecipe = Recipe::factory()->create(['title' => 'Perfect Apple Pie']);
        $secondRecipe = Recipe::factory()->create(['title' => 'Perfect Apple Pie']);

        $this->assertSame('perfect-apple-pie', $firstRecipe->slug);
        $this->assertSame('perfect-apple-pie-2', $secondRecipe->slug);
    }

    #[Test]
    public function published_and_draft_scopes_only_return_matching_recipes(): void
    {
        $draft = Recipe::factory()->create();
        $published = Recipe::factory()->published()->create();
        $scheduled = Recipe::factory()->create([
            'status' => RecipeStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $this->assertTrue(Recipe::query()->draft()->get()->contains($draft));
        $this->assertTrue(Recipe::query()->published()->get()->contains($published));
        $this->assertFalse(Recipe::query()->published()->get()->contains($scheduled));
        $this->assertTrue($published->isPublished());
        $this->assertFalse($scheduled->isPublished());
    }

    #[Test]
    public function recipes_can_be_classified_with_categories_and_dietary_tags(): void
    {
        $recipe = Recipe::factory()->create();
        $category = Category::factory()->create();
        $dietaryTag = DietaryTag::factory()->create();

        $recipe->categories()->attach($category);
        $recipe->dietaryTags()->attach($dietaryTag);

        $this->assertTrue($recipe->categories->contains($category));
        $this->assertTrue($category->recipes->contains($recipe));
        $this->assertTrue($recipe->dietaryTags->contains($dietaryTag));
        $this->assertTrue($dietaryTag->recipes->contains($recipe));
    }

    #[Test]
    public function recipe_ingredients_store_amounts_notes_and_display_order(): void
    {
        $recipe = Recipe::factory()->create();
        $flour = Ingredient::factory()->create();
        $salt = Ingredient::factory()->create();

        $recipe->ingredients()->attach($salt, [
            'quantity' => null,
            'unit' => null,
            'notes' => 'to taste',
            'is_main' => false,
            'position' => 2,
        ]);
        $recipe->ingredients()->attach($flour, [
            'quantity' => 250,
            'unit' => 'g',
            'notes' => 'sifted',
            'is_main' => true,
            'position' => 1,
        ]);

        $ingredients = $recipe->ingredients()->get();

        $this->assertTrue($ingredients->first()->is($flour));
        $this->assertEquals(250, $ingredients->first()->pivot->quantity);
        $this->assertSame('g', $ingredients->first()->pivot->unit);
        $this->assertSame('sifted', $ingredients->first()->pivot->notes);
        $this->assertTrue((bool) $ingredients->first()->pivot->is_main);
        $this->assertTrue($flour->recipes->contains($recipe));
    }

    #[Test]
    public function recipe_steps_are_returned_in_instruction_order(): void
    {
        $recipe = Recipe::factory()->create();
        $secondStep = RecipeStep::factory()->for($recipe)->create(['position' => 2]);
        $firstStep = RecipeStep::factory()->for($recipe)->create(['position' => 1]);

        $this->assertTrue($recipe->steps()->first()->is($firstStep));
        $this->assertTrue($recipe->steps()->get()->last()->is($secondStep));
    }

    #[Test]
    public function deleting_a_recipe_removes_its_steps_and_taxonomy_links(): void
    {
        $recipe = Recipe::factory()->create();
        $step = RecipeStep::factory()->for($recipe)->create();
        $category = Category::factory()->create();
        $recipe->categories()->attach($category);

        $recipe->delete();

        $this->assertModelMissing($step);
        $this->assertDatabaseMissing('category_recipe', ['recipe_id' => $recipe->id]);
        $this->assertModelExists($category);
    }
}
