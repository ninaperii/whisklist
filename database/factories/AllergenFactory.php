<?php

namespace Database\Factories;

use App\Models\Allergen;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Allergen>
 */
class AllergenFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return ['name' => $name, 'slug' => Str::slug($name), 'description' => fake()->optional()->sentence()];
    }
}
