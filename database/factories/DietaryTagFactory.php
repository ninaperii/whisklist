<?php

namespace Database\Factories;

use App\Models\DietaryTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DietaryTag>
 */
class DietaryTagFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return ['name' => $name, 'slug' => Str::slug($name), 'description' => fake()->optional()->sentence()];
    }
}
