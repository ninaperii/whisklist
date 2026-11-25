<?php

namespace Database\Seeders;

use App\Models\Allergen;
use App\Models\Category;
use App\Models\DietaryTag;
use App\Models\Ingredient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecipeTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->category('Breakfast');
            $desserts = $this->category('Desserts');
            $this->category('Cakes', $desserts);
            $pies = $this->category('Pies', $desserts);
            $this->category('Fruit Pies', $pies);

            foreach (['Flour', 'Oats', 'Eggs', 'Milk', 'Butter', 'Bananas', 'Apples', 'Chickpeas', 'Tomatoes'] as $name) {
                Ingredient::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
            }

            foreach (['Vegetarian', 'Vegan', 'Gluten-free'] as $name) {
                DietaryTag::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
            }

            foreach (['Wheat', 'Milk', 'Eggs', 'Peanuts'] as $name) {
                Allergen::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
            }
        });
    }

    private function category(string $name, ?Category $parent = null): Category
    {
        return Category::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'parent_id' => $parent?->id],
        );
    }
}
