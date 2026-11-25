<?php

namespace App\Models;

use App\Enums\RecipeDifficulty;
use App\Enums\RecipeStatus;
use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'title', 'slug', 'summary', 'preparation_minutes', 'cooking_minutes', 'servings', 'difficulty', 'status', 'published_at'])]
class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Recipe $recipe): void {
            if ($recipe->slug === null || $recipe->isDirty('title')) {
                $recipe->slug = $recipe->uniqueSlug();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'difficulty' => RecipeDifficulty::class,
            'status' => RecipeStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function dietaryTags(): BelongsToMany
    {
        return $this->belongsToMany(DietaryTag::class)->withTimestamps();
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class)
            ->withPivot(['quantity', 'unit', 'notes', 'is_main', 'position'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('position');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', RecipeStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', RecipeStatus::Draft);
    }

    public function isPublished(): bool
    {
        return $this->status === RecipeStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    private function uniqueSlug(): string
    {
        $baseSlug = Str::slug($this->title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'recipe';
        $slug = $baseSlug;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($this->exists, fn (Builder $query): Builder => $query->whereKeyNot($this->getKey()))
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
