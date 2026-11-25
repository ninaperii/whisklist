<?php

namespace Database\Factories;

use App\Enums\RecipeDifficulty;
use App\Enums\RecipeStatus;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->paragraph(),
            'preparation_minutes' => fake()->numberBetween(5, 90),
            'cooking_minutes' => fake()->numberBetween(0, 180),
            'servings' => fake()->numberBetween(1, 12),
            'difficulty' => fake()->randomElement(RecipeDifficulty::cases()),
            'status' => RecipeStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => RecipeStatus::Published,
            'published_at' => now()->subMinute(),
        ]);
    }
}
