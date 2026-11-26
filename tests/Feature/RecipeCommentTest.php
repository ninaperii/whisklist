<?php

namespace Tests\Feature;

use App\Enums\RecipeStatus;
use App\Models\Comment;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecipeCommentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_comment_history_is_private_and_does_not_link_to_unpublished_recipes(): void
    {
        $user = User::factory()->create();
        $visible = Comment::factory()->for($user, 'author')->create(['body' => 'My public comment']);
        $privateRecipe = Recipe::factory()->create(['title' => 'Hidden recipe title']);
        Comment::factory()->for($user, 'author')->for($privateRecipe)->create(['body' => 'My earlier comment']);
        Comment::factory()->create(['body' => 'Someone else’s comment']);
        $history = route('comments.mine');

        $this->get($history)->assertRedirect(route('login'));
        $this->actingAs($user)->get($history)->assertOk()
            ->assertSee('My public comment')
            ->assertSee(route('recipes.comments.show', [$visible->recipe, $visible]), false)
            ->assertSee('My earlier comment')
            ->assertSee('Recipe unavailable')
            ->assertDontSee($privateRecipe->title)
            ->assertDontSee('Someone else’s comment');
    }

    #[Test]
    public function a_comment_link_opens_the_correct_page_and_comment(): void
    {
        $comment = Comment::factory()->create();
        $recipe = $comment->recipe;
        Comment::factory()->for($recipe)->count(Comment::RECIPE_PAGE_SIZE)->create();

        $url = route('recipes.comments.show', [$recipe, $comment]);
        $this->get($url)->assertRedirect(route('recipes.show', ['recipe' => $recipe, 'comments_page' => 2]).'#comment-'.$comment->id);
        $this->get(route('recipes.show', ['recipe' => $recipe, 'comments_page' => 2]))
            ->assertOk()->assertSee('id="comment-'.$comment->id.'"', false);

        $otherRecipe = Recipe::factory()->published()->create();
        $this->get(route('recipes.comments.show', [$otherRecipe, $comment]))->assertNotFound();
        $recipe->update(['status' => RecipeStatus::Draft, 'published_at' => null]);
        $this->get($url)->assertNotFound();
    }

    #[Test]
    public function signed_in_users_can_post_edit_and_delete_their_comments(): void
    {
        $recipe = Recipe::factory()->published()->create();
        $author = User::factory()->create();
        $publicUrl = route('recipes.show', $recipe);
        $storeUrl = route('recipes.comments.store', $recipe);
        $this->post($storeUrl, ['body' => 'Delicious'])->assertRedirect(route('login'));

        $this->actingAs($author)->from($publicUrl)->post($storeUrl, ['body' => '   '])->assertSessionHasErrors('body');
        $this->from($publicUrl)->post($storeUrl, ['body' => str_repeat('a', 2001)])->assertSessionHasErrors('body');
        $this->post($storeUrl, ['body' => '<script>alert(1)</script>', 'user_id' => $recipe->user_id])
            ->assertRedirect($publicUrl.'#comments');
        $comment = Comment::query()->sole();
        $this->assertTrue($comment->author->is($author));
        $this->assertTrue($comment->recipe->is($recipe));

        $edit = route('recipes.comments.edit', [$recipe, $comment]);
        $update = route('recipes.comments.update', [$recipe, $comment]);
        $delete = route('recipes.comments.destroy', [$recipe, $comment]);
        $this->get($publicUrl)->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
        $this->get($edit)->assertOk();
        $this->patch($update, ['body' => 'Turned out delicious.'])->assertRedirect($publicUrl.'#comments');

        auth()->logout();
        $this->get($publicUrl)->assertOk()->assertSee('Turned out delicious.')->assertDontSee($edit, false);
        $this->actingAs($author)->delete($delete)->assertRedirect($publicUrl.'#comments');
        $this->assertModelMissing($comment);
    }

    #[Test]
    public function comments_are_scoped_to_the_recipe_and_only_their_author_can_modify_them(): void
    {
        $comment = Comment::factory()->create();
        $recipe = $comment->recipe;
        $otherRecipe = Recipe::factory()->published()->create();
        $this->actingAs($recipe->author);
        $this->get(route('recipes.comments.edit', [$recipe, $comment]))->assertForbidden();
        $this->patch(route('recipes.comments.update', [$recipe, $comment]), ['body' => 'Changed'])->assertForbidden();
        $this->delete(route('recipes.comments.destroy', [$recipe, $comment]))->assertForbidden();

        $this->actingAs($comment->author);
        $this->patch(route('recipes.comments.update', [$otherRecipe, $comment]), ['body' => 'Changed'])->assertNotFound();
        $this->delete(route('recipes.comments.destroy', [$otherRecipe, $comment]))->assertNotFound();
        $this->assertSame($comment->body, $comment->fresh()->body);

        $recipe->update(['status' => RecipeStatus::Draft, 'published_at' => null]);
        $this->get(route('recipes.show', $recipe))->assertNotFound();
        $this->post(route('recipes.comments.store', $recipe), ['body' => 'New comment'])->assertNotFound();
        $this->get(route('recipes.comments.edit', [$recipe, $comment]))->assertNotFound();

        $recipe->delete();
        $this->assertModelMissing($comment);
    }
}
