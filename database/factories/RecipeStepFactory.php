<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipeStep>
 */
class RecipeStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'position' => 1,
            'instruction' => fake()->paragraph(),
        ];
    }
}
