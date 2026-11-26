<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Recipe;
use App\View\RecipeNavigation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RecipeCommentController extends Controller
{
    public function mine(Request $request): View
    {
        return view('recipes.comments.mine', [
            'comments' => $request->user()->comments()
                ->with(['recipe' => fn (BelongsTo $recipe) => $recipe->published()])
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    public function show(Request $request, Recipe $recipe, Comment $comment): RedirectResponse
    {
        abort_unless($recipe->isPublished(), 404);

        $newerComments = $recipe->comments()->where('id', '>', $comment->id)->count();
        $page = intdiv($newerComments, Comment::RECIPE_PAGE_SIZE) + 1;
        $parameters = $page > 1 ? ['recipe' => $recipe, 'comments_page' => $page] : ['recipe' => $recipe];

        return redirect(route('recipes.show', $parameters + RecipeNavigation::origin($request, $recipe)['parameters']).'#comment-'.$comment->id);
    }

    public function store(Request $request, Recipe $recipe): RedirectResponse
    {
        abort_unless($recipe->isPublished(), 404);
        $data = $this->validateBody($request);

        DB::transaction(function () use ($recipe, $request, $data): void {
            $recipe = $this->lockPublishedRecipe($recipe);
            $comment = $recipe->comments()->make($data);
            $comment->author()->associate($request->user());
            $comment->save();
        });

        return $this->backToComments($request, $recipe, 'Comment posted.');
    }

    public function edit(Request $request, Recipe $recipe, Comment $comment): View
    {
        abort_unless($recipe->isPublished(), 404);
        Gate::authorize('update', $comment);

        return view('recipes.comments.edit', [
            'recipe' => $recipe,
            'comment' => $comment,
            'recipeOrigin' => RecipeNavigation::origin($request, $recipe),
        ]);
    }

    public function update(Request $request, Recipe $recipe, Comment $comment): RedirectResponse
    {
        abort_unless($recipe->isPublished(), 404);
        Gate::authorize('update', $comment);
        $data = $this->validateBody($request);

        DB::transaction(function () use ($recipe, $comment, $data): void {
            $this->lockPublishedRecipe($recipe)->comments()->findOrFail($comment->id)->update($data);
        });

        return $this->backToComments($request, $recipe, 'Comment updated.');
    }

    public function destroy(Request $request, Recipe $recipe, Comment $comment): RedirectResponse
    {
        abort_unless($recipe->isPublished(), 404);
        Gate::authorize('delete', $comment);

        DB::transaction(function () use ($recipe, $comment): void {
            $this->lockPublishedRecipe($recipe)->comments()->findOrFail($comment->id)->delete();
        });

        return $this->backToComments($request, $recipe, 'Comment deleted.');
    }

    private function validateBody(Request $request): array
    {
        return $request->validate(['body' => ['required', 'string', 'max:2000']], [], ['body' => 'comment']);
    }

    private function lockPublishedRecipe(Recipe $recipe): Recipe
    {
        return Recipe::query()->published()->lockForUpdate()->findOrFail($recipe->id);
    }

    private function backToComments(Request $request, Recipe $recipe, string $message): RedirectResponse
    {
        $parameters = ['recipe' => $recipe] + RecipeNavigation::origin($request, $recipe)['parameters'];

        return redirect(route('recipes.show', $parameters).'#comments')->with('comment_status', $message);
    }
}
