<?php

namespace App\Models;

use Database\Factories\DietaryTagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description'])]
class DietaryTag extends Model
{
    /** @use HasFactory<DietaryTagFactory> */
    use HasFactory;

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class)->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
