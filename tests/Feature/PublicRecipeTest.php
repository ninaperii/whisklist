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
    public function removing_a_category_chip_clears_its_branch_only(): void
    {
        $desserts = Category::factory()->create(['name' => 'Desserts']);
        $pies = Category::factory()->for($desserts, 'parent')->create(['name' => 'Pies']);
        $fruitPies = Category::factory()->for($pies, 'parent')->create(['name' => 'Fruit pies']);
        $other = Category::factory()->create(['name' => 'Other']);

        foreach (['categories', 'exclude_categories'] as $filter) {
            $this->get(route('recipes.index', ['q' => 'apple', $filter => [$desserts->id, $pies->id, $fruitPies->id, $other->id]]))
                ->assertOk()
                ->assertSee('href="'.e(route('recipes.index', ['q' => 'apple', $filter => [$other->id]])).'"', false);
        }
    }

    #[Test]
    public function quick_filters_use_difficulty_and_total_time(): void
    {
        $details = ['difficulty' => 'easy', 'preparation_minutes' => 8, 'cooking_minutes' => 12];
        Recipe::factory()->published()->create(['title' => 'Quick pancakes'] + $details);
        Recipe::factory()->published()->create(['title' => 'Slow pancakes', 'cooking_minutes' => 13] + $details);
        Recipe::factory()->published()->create(['title' => 'Hard pancakes', 'difficulty' => 'hard'] + $details);
        $this->get(route('recipes.index', [
            'difficulty' => ['easy', 'medium'], 'max_time' => 20,
        ]))->assertOk()->assertSee('Quick pancakes')
            ->assertDontSee('Slow pancakes')->assertDontSee('Hard pancakes');
    }

    #[Test]
    public function visitors_can_combine_search_and_filters_and_exclude_nested_category_branches(): void
    {
        $dessert = Category::factory()->create(['name' => 'Desserts']);
        $pies = Category::factory()->for($dessert, 'parent')->create(['name' => 'Pies']);
        $fruitPies = Category::factory()->for($pies, 'parent')->create(['name' => 'Fruit pies']);
        $cakes = Category::factory()->for($dessert, 'parent')->create(['name' => 'Cakes']);
        $lemon = Ingredient::factory()->create(['name' => 'Lemon']);
        $sugar = Ingredient::factory()->create(['name' => 'Sugar']);
        $glutenFree = DietaryTag::factory()->create(['name' => 'Gluten-free']);
        $vegetarian = DietaryTag::factory()->create(['name' => 'Vegetarian']);

        $cake = Recipe::factory()->published()->create(['title' => 'Lemon layer cake']);
        $pie = Recipe::factory()->published()->create(['title' => 'Lemon meringue pie']);
        $plain = Recipe::factory()->published()->create(['title' => 'Lemon sponge']);
        foreach ([[$cake, $cakes], [$pie, $fruitPies]] as [$recipe, $category]) {
            $recipe->categories()->attach($category);
            $recipe->ingredients()->attach([$lemon->id => ['position' => 1], $sugar->id => ['position' => 2]]);
            $recipe->dietaryTags()->attach([$glutenFree->id, $vegetarian->id]);
        }
        $plain->categories()->attach($cakes);
        $plain->ingredients()->attach($lemon, ['position' => 1]);
        $plain->dietaryTags()->attach($glutenFree);

        $filters = [
            'q' => 'LEMON',
            'categories' => [$dessert->id],
            'ingredients' => [$lemon->id, $sugar->id],
            'require_all_ingredients' => 1,
            'dietary_tags' => [$glutenFree->id, $vegetarian->id],
        ];

        $this->get(route('recipes.index', $filters))->assertOk()
            ->assertSee($cake->title)->assertSee($pie->title)->assertDontSee($plain->title);
        $this->get(route('recipes.index', ['ingredients' => [$lemon->id, $sugar->id]]))->assertOk()
            ->assertSee($cake->title)->assertSee($plain->title);
        $this->get(route('recipes.index', $filters + ['exclude_categories' => [$pies->id]]))->assertOk()
            ->assertSee($cake->title)->assertDontSee($pie->title);
        $this->get(route('recipes.index', $filters + ['exclude_categories' => [$dessert->id]]))
            ->assertRedirect(route('recipes.index'))->assertSessionHasErrors('category_conflict');
        $this->get(route('recipes.index', ['categories' => [$fruitPies->id], 'exclude_categories' => [$dessert->id]]))
            ->assertRedirect(route('recipes.index'))->assertSessionHasErrors('category_conflict');
        $this->get(route('recipes.index', ['q' => 'Lemon', 'ingredients' => [$lemon->id], 'exclude_ingredients' => [$sugar->id]]))
            ->assertOk()->assertSee($plain->title)->assertDontSee($cake->title)->assertDontSee($pie->title);
        $this->get(route('recipes.index', ['ingredients' => [$lemon->id], 'exclude_ingredients' => [$lemon->id]]))
            ->assertRedirect(route('recipes.index'))->assertSessionHasErrors('ingredient_conflict');
        $this->get(route('recipes.index', ['q' => '%']))->assertOk()->assertSee('No recipes match these filters.');
        $this->get(route('recipes.index', ['ingredients' => 'invalid']))->assertRedirect(route('recipes.index'))
            ->assertSessionHasErrors('ingredients');
    }

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
            ->assertSee('href="'.route('recipes.show', $newer, false).'"', false)
            ->assertSee('src="'.route('recipes.thumbnail', $newer, false).'"', false)
            ->assertDontSee($draft->title)
            ->assertDontSee($future->title);
    }

    #[Test]
    public function guests_can_read_published_recipes(): void
    {
        $author = User::factory()->create(['name' => 'Tart Author', 'is_profile_public' => true, 'avatar_path' => 'avatars/tart-author.png']);
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
            ->assertSee('By Tart Author')
            ->assertSee(asset('storage/avatars/tart-author.png'), false)
            ->assertSee(route('users.show', $author), false)
            ->assertSee('Desserts')
            ->assertSee('Vegetarian')
            ->assertSeeTextInOrder(['1.25', 'cups', 'Lemon juice', 'Mix the filling', 'Bake the tart']);
        $this->get(route('recipes.index'))->assertOk()->assertSee('By Tart Author');
    }

    #[Test]
    public function published_recipes_credit_authors_with_private_profiles(): void
    {
        $author = User::factory()->privateProfile()->create(['name' => 'Private Author', 'avatar_path' => 'avatars/private-author.png']);
        $recipe = Recipe::factory()->published()->for($author, 'author')->create();

        $this->get(route('recipes.show', $recipe))
            ->assertOk()
            ->assertSee($recipe->title)
            ->assertSee('By Private Author')
            ->assertSee(asset('storage/avatars/private-author.png'), false)
            ->assertSee(route('users.show', $author), false);
        $this->get(route('recipes.index'))->assertOk()->assertSee('By Private Author');
        $this->get(route('users.show', $author))->assertOk()->assertSee('This profile is private.');
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
