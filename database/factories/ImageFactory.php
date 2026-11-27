<?php

namespace Database\Factories;

use App\Models\Image;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'imageable_type' => (new Recipe)->getMorphClass(),
            'imageable_id' => Recipe::factory(),
            'path' => 'recipe-thumbnails/'.Str::uuid().'.png',
            'alt_text' => fake()->sentence(4),
            'position' => 1,
            'is_thumbnail' => true,
        ];
    }
}
