<?php

namespace App\Models;

use Database\Factories\RecipeStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['recipe_id', 'position', 'instruction'])]
class RecipeStep extends Model
{
    /** @use HasFactory<RecipeStepFactory> */
    use HasFactory;

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
