<?php

namespace App\Policies;

use App\Enums\RecipeStatus;
use App\Models\Recipe;
use App\Models\User;

class RecipePolicy
{
    public function update(User $user, Recipe $recipe): bool
    {
        return $recipe->user_id === $user->id && $recipe->status === RecipeStatus::Draft;
    }
}
