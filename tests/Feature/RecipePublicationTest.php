<?php

namespace Tests\Feature;

use App\Enums\RecipeStatus;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecipePublicationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owners_can_edit_unpublish_and_delete_recipes(): void
    {
        $storage = Storage::fake('local');
        $author = User::factory()->create();
        $recipe = Recipe::factory()->published()->for($author, 'author')->create(['title' => 'My published pancakes']);
        $draft = Recipe::factory()->for($author, 'author')->create();
        $anotherRecipe = Recipe::factory()->published()->create();
        $thumbnail = $recipe->thumbnail()->create(['path' => 'recipe-thumbnails/pancakes.png', 'is_thumbnail' => true]);
        $storage->put($thumbnail->path, 'thumbnail');
        $publishedAt = $recipe->published_at;
        $publicUrl = route('recipes.show', $recipe);
        $editUrl = route('recipes.edit', $recipe);
        $updateUrl = route('recipes.update', $recipe);
        $unpublishUrl = route('recipes.unpublish', $recipe);
        $deleteUrl = route('recipes.destroy', $recipe);
        $data = $this->completeRecipe() + ['title' => 'Updated pancakes'];

        $this->actingAs($anotherRecipe->author);
        $this->get($editUrl)->assertForbidden();
        $this->put($updateUrl, $data)->assertForbidden();
        $this->patch($unpublishUrl)->assertForbidden();
        $this->delete($deleteUrl)->assertForbidden();

        $this->actingAs($author);
        $this->get(route('recipes.mine'))->assertOk()->assertSee($recipe->title)->assertSee($draft->title)->assertDontSee($anotherRecipe->title);
        $this->get(route('recipes.mine', ['status' => 'draft']))->assertOk()->assertSee($draft->title)->assertDontSee($recipe->title);
        $this->get($editUrl)->assertOk()->assertSee('Save changes');
        $this->put($updateUrl, array_diff_key($data, ['ingredients' => true]))->assertSessionHasErrors('ingredients');
        $this->put($updateUrl, $data)->assertRedirect($publicUrl);
        $this->get($publicUrl)->assertOk()->assertSee('Updated pancakes');
        $this->assertEquals($publishedAt, $recipe->fresh()->published_at);

        $this->patch($unpublishUrl)->assertRedirect(route('recipes.mine'));
        $this->get($publicUrl)->assertNotFound();
        $this->assertSame(RecipeStatus::Draft, $recipe->fresh()->status);
        $storage->assertExists($thumbnail->path);

        $this->delete($deleteUrl)->assertRedirect(route('recipes.mine'));
        $this->assertModelMissing($recipe);
        $this->assertModelMissing($thumbnail);
        $storage->assertMissing($thumbnail->path);
    }

    #[Test]
    public function authors_can_replace_a_private_thumbnail_and_publish_with_the_saved_image(): void
    {
        $storage = Storage::fake('local');
        $author = User::factory()->create();
        $recipe = Recipe::factory()->for($author, 'author')->create(['title' => 'Create', 'slug' => 'create']);
        // Exercise records created before the create route was reserved.
        $recipe->updateQuietly(['slug' => 'create']);
        $data = $this->completeRecipe() + ['title' => $recipe->title];

        $this->actingAs($author)
            ->put(route('recipes.update', $recipe), $data + ['thumbnail' => UploadedFile::fake()->image('first.png')])
            ->assertRedirect(route('recipes.edit', $recipe));
        $oldPath = $recipe->thumbnail()->firstOrFail()->path;
        $storage->assertExists($oldPath);
        $this->get(route('recipes.thumbnail', $recipe))->assertOk();

        $this->actingAs(User::factory()->create());
        $this->get(route('recipes.thumbnail', $recipe))->assertNotFound();
        $this->put(route('recipes.update', $recipe), $data + ['action' => 'publish'])->assertForbidden();

        $this->actingAs($author)
            ->put(route('recipes.update', $recipe), $data + ['thumbnail' => UploadedFile::fake()->image('second.png')])
            ->assertRedirect(route('recipes.edit', $recipe));
        $storage->assertMissing($oldPath);

        $this->put(route('recipes.update', $recipe), $data + ['action' => 'publish'])
            ->assertRedirect(route('recipes.show', 'create-2'));

        $recipe->refresh();
        $this->assertSame(RecipeStatus::Published, $recipe->status);
        $this->assertNotNull($recipe->published_at);
        $storage->assertExists($recipe->thumbnail->path);
        auth()->logout();
        $this->get(route('recipes.show', $recipe))->assertOk()->assertSee(route('recipes.thumbnail', $recipe), false);
        $this->get(route('recipes.thumbnail', $recipe))->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    #[Test]
    public function incomplete_recipes_and_invalid_images_are_rejected_without_publishing(): void
    {
        Storage::fake('local');
        $author = User::factory()->create();
        $recipe = Recipe::factory()->for($author, 'author')->create();
        $data = $this->completeRecipe() + ['title' => $recipe->title];
        $url = route('recipes.update', $recipe);
        $this->actingAs($author);

        $this->put($url, array_diff_key($data, array_flip(['categories', 'ingredients', 'steps'])) + ['action' => 'publish'])
            ->assertSessionHasErrors(['categories', 'ingredients', 'steps']);
        $this->put($url, $data + ['action' => 'publish'])->assertSessionHasErrors('thumbnail');
        $this->put($url, $data + ['thumbnail' => UploadedFile::fake()->create('bad.pdf')])->assertSessionHasErrors('thumbnail');

        $this->assertSame(RecipeStatus::Draft, $recipe->fresh()->status);
        $this->assertDatabaseCount('images', 0);
    }

    #[Test]
    public function authors_can_publish_a_new_recipe_with_an_uploaded_thumbnail_in_one_submit(): void
    {
        Storage::fake('local');
        $author = User::factory()->create();

        $this->actingAs($author)->post(route('recipes.store'), $this->completeRecipe() + [
            'title' => 'Pancakes',
            'action' => 'publish',
            'thumbnail' => UploadedFile::fake()->image('pancakes.png'),
        ])->assertRedirect(route('recipes.show', 'pancakes'));

        $this->assertSame(RecipeStatus::Published, $author->recipes()->sole()->status);
        $this->assertDatabaseCount('images', 1);
    }

    private function completeRecipe(): array
    {
        return [
            'preparation_minutes' => 5,
            'cooking_minutes' => 15,
            'servings' => 4,
            'difficulty' => 'easy',
            'categories' => [Category::factory()->create()->id],
            'ingredients' => [['id' => Ingredient::factory()->create()->id]],
            'steps' => ['Mix and cook.'],
        ];
    }
}
