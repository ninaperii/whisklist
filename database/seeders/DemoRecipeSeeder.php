<?php

namespace Database\Seeders;

use App\Enums\RecipeDifficulty;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Comment;
use App\Models\DietaryTag;
use App\Models\Image;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeStep;
use App\Models\User;
use App\View\GuestHomeCache;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DemoRecipeSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RecipeTaxonomySeeder::class);

        $maya = $this->user('Maya Baker', 'maya@example.com', true);
        $leo = $this->user('Leo Martin', 'leo@example.com', false);
        if ($leo->is_profile_public) {
            $leo->update(['is_profile_public' => false]);
        }

        Category::query()->firstOrCreate(['slug' => 'salads'], ['name' => 'Salads']);
        $ingredients = Ingredient::query()->get()->keyBy('slug');
        foreach (['Lemon juice', 'Cinnamon', 'Olive oil', 'Sugar', 'Cucumber'] as $name) {
            $slug = Str::slug($name);
            $ingredients[$slug] = Ingredient::query()->firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        $eggAllergen = Allergen::query()->where('slug', 'eggs')->firstOrFail();
        $milkAllergen = Allergen::query()->where('slug', 'milk')->firstOrFail();
        $wheatAllergen = Allergen::query()->where('slug', 'wheat')->firstOrFail();
        $vegan = DietaryTag::query()->where('slug', 'vegan')->firstOrFail();
        $ingredients['eggs']->allergens()->syncWithoutDetaching([$eggAllergen->id]);
        $ingredients['milk']->allergens()->syncWithoutDetaching([$milkAllergen->id]);
        $ingredients['butter']->allergens()->syncWithoutDetaching([$milkAllergen->id]);
        $ingredients['flour']->allergens()->syncWithoutDetaching([$wheatAllergen->id]);
        $maya->excludedIngredients()->syncWithoutDetaching([$ingredients['apples']->id]);
        $leo->allergens()->syncWithoutDetaching([$eggAllergen->id]);
        $leo->preferredDietaryTags()->syncWithoutDetaching([$vegan->id]);

        $recipes = [];
        $categories = Category::query()->pluck('id', 'slug');
        $tags = DietaryTag::query()->pluck('id', 'slug');

        foreach ($this->recipes() as $key => $data) {
            $author = $data['author'] === 'maya' ? $maya : $leo;
            $recipes[$key] = $this->seedRecipe($key, $data, $author, $ingredients, $categories, $tags);
        }

        $this->comment($recipes['banana-oat-pancakes'], $leo, 'These held together nicely. I used a small ladle for each pancake.');
        $this->comment($recipes['chickpea-tomato-salad'], $maya, 'I added extra lemon juice and served it chilled.');
        $this->comment($recipes['apple-oat-breakfast'], $leo, 'The apple softened in about ten minutes for me.');

        foreach ([[$leo, $recipes['banana-oat-pancakes']], [$maya, $recipes['chickpea-tomato-salad']]] as [$viewer, $recipe]) {
            if (! $viewer->savedRecipes()->wherePivot('recipe_id', $recipe->id)->exists()) {
                $viewer->savedRecipes()->attach($recipe->id, ['created_at' => now()]);
            }
        }

        app(GuestHomeCache::class)->warm();
    }

    private function user(string $name, string $email, bool $isPublic): User
    {
        return User::query()->firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => 'password',
            'is_profile_public' => $isPublic,
        ]);
    }

    private function seedRecipe(string $key, array $data, User $author, EloquentCollection $ingredients, Collection $categories, Collection $tags): Recipe
    {
        $slug = 'demo-'.$key;
        $existing = Recipe::query()->where('slug', $slug)->first();
        if ($existing) {
            if ($existing->user_id !== $author->id) {
                throw new RuntimeException('Demo recipe slug already belongs to another author: '.$slug);
            }

            return $existing;
        }

        $thumbnailPath = null;
        try {
            return DB::transaction(function () use ($slug, $data, $author, $ingredients, $categories, $tags, &$thumbnailPath): Recipe {
                $factory = Recipe::factory()->for($author, 'author');
                if ($data['published'] ?? true) {
                    $factory = $factory->published();
                }

                $recipe = Recipe::withoutEvents(fn (): Recipe => $factory->create([
                    'slug' => $slug,
                    'title' => $data['title'],
                    'summary' => $data['summary'],
                    'preparation_minutes' => $data['prep'],
                    'cooking_minutes' => $data['cook'],
                    'servings' => $data['servings'],
                    'difficulty' => $data['difficulty'] ?? RecipeDifficulty::Easy,
                ]));

                $recipe->categories()->attach([$categories[$data['category']]]);
                $recipe->dietaryTags()->attach(array_map(fn (string $tag): int => $tags[$tag], $data['tags']));
                foreach ($data['ingredients'] as $index => [$name, $quantity, $unit]) {
                    $recipe->ingredients()->attach($ingredients[$name]->id, [
                        'quantity' => $quantity,
                        'unit' => $unit,
                        'position' => $index + 1,
                        'is_main' => $index === 0,
                    ]);
                }

                foreach ($data['steps'] as $index => $instruction) {
                    RecipeStep::factory()->for($recipe)->create(['position' => $index + 1, 'instruction' => $instruction]);
                }

                if ($recipe->isPublished()) {
                    $thumbnailPath = $this->writeThumbnail($recipe, $data['color']);
                    Image::factory()->for($recipe, 'imageable')->create([
                        'path' => $thumbnailPath,
                        'alt_text' => 'Illustrated demo cover for '.$recipe->title,
                    ]);
                }

                return $recipe;
            });
        } catch (Throwable $exception) {
            if ($thumbnailPath !== null) {
                Storage::disk('local')->delete($thumbnailPath);
            }

            throw $exception;
        }
    }

    private function writeThumbnail(Recipe $recipe, string $color): string
    {
        $path = 'recipe-thumbnails/'.Str::uuid().'.svg';
        $title = htmlspecialchars($recipe->title, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $illustration = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360">
                <rect width="640" height="360" fill="$color"/>
                <circle cx="320" cy="138" r="98" fill="#faf6ed"/>
                <circle cx="320" cy="138" r="74" fill="none" stroke="#dad1bf" stroke-width="3"/>
                <path d="M285 158c15-47 69-54 75-30s-28 43-43 36c-13-6-22-6-32-6Z" fill="$color"/>
                <text x="320" y="294" text-anchor="middle" font-size="28" font-family="sans-serif" fill="white">$title</text>
            </svg>
            SVG;

        if (! Storage::disk('local')->put($path, $illustration)) {
            throw new RuntimeException('Could not write the demo recipe cover.');
        }

        return $path;
    }

    private function comment(Recipe $recipe, User $author, string $body): void
    {
        if (! $recipe->comments()->where('user_id', $author->id)->where('body', $body)->exists()) {
            Comment::factory()->for($recipe)->for($author, 'author')->create(['body' => $body]);
        }
    }

    private function recipes(): array
    {
        return [
            'banana-oat-pancakes' => [
                'title' => 'Banana Oat Pancakes', 'author' => 'maya', 'category' => 'breakfast',
                'summary' => 'Small skillet pancakes with ripe banana and rolled oats.',
                'prep' => 10, 'cook' => 12, 'servings' => 2, 'color' => '#8a4d23', 'tags' => ['vegetarian'],
                'ingredients' => [['oats', 100, 'g'], ['bananas', 1, null], ['eggs', 2, null], ['olive-oil', 1, 'tsp']],
                'steps' => ['Mash the banana in a bowl.', 'Beat in the eggs, then stir in the oats and leave the batter for five minutes.', 'Heat the oil in a skillet. Cook small spoonfuls over medium heat until set and golden on both sides.'],
            ],
            'chickpea-tomato-salad' => [
                'title' => 'Chickpea Tomato Salad', 'author' => 'leo', 'category' => 'salads',
                'summary' => 'A quick chickpea salad finished with lemon dressing.',
                'prep' => 12, 'cook' => 0, 'servings' => 3, 'color' => '#326354', 'tags' => ['vegetarian', 'vegan', 'gluten-free'],
                'ingredients' => [['chickpeas', 240, 'g'], ['tomatoes', 150, 'g'], ['lemon-juice', 1, 'tbsp'], ['olive-oil', 1, 'tbsp']],
                'steps' => ['Drain and rinse the chickpeas. Dice the tomatoes.', 'Whisk the lemon juice and olive oil, then toss with the chickpeas and tomatoes.'],
            ],
            'apple-oat-breakfast' => [
                'title' => 'Cinnamon Apple Oats', 'author' => 'leo', 'category' => 'breakfast',
                'summary' => 'Warm stovetop oats with diced apple and cinnamon.',
                'prep' => 5, 'cook' => 12, 'servings' => 2, 'color' => '#924437', 'tags' => ['vegetarian', 'vegan'],
                'ingredients' => [['apples', 1, null], ['oats', 90, 'g'], ['cinnamon', 0.5, 'tsp']],
                'steps' => ['Dice the apple and put it in a saucepan with the oats and 400 mL of water.', 'Simmer for ten to twelve minutes, stirring occasionally. Add cinnamon before serving.'],
            ],
            'apple-hand-pies' => [
                'title' => 'Apple Hand Pies', 'author' => 'maya', 'category' => 'fruit-pies',
                'summary' => 'Individual butter pastry pies filled with cinnamon apples.',
                'prep' => 25, 'cook' => 25, 'servings' => 6, 'color' => '#795047', 'tags' => ['vegetarian'],
                'difficulty' => RecipeDifficulty::Medium,
                'ingredients' => [['apples', 3, null], ['flour', 240, 'g'], ['butter', 120, 'g'], ['sugar', 35, 'g'], ['cinnamon', 1, 'tsp']],
                'steps' => ['Rub cold butter into the flour, add just enough cold water to form a dough, and chill until firm.', 'Dice the apples and toss with sugar and cinnamon. Roll out the pastry and cut six pairs of rounds.', 'Fill and seal the rounds, cut a small vent, and bake at 200°C for about 25 minutes, until golden.'],
            ],
            'lemon-cucumber-salad' => [
                'title' => 'Lemon Cucumber Salad', 'author' => 'leo', 'category' => 'salads',
                'summary' => 'A crisp cucumber and tomato salad with lemon and olive oil.',
                'prep' => 10, 'cook' => 0, 'servings' => 2, 'color' => '#476332', 'tags' => ['vegetarian', 'vegan', 'gluten-free'],
                'ingredients' => [['cucumber', 1, null], ['tomatoes', 150, 'g'], ['lemon-juice', 1, 'tbsp'], ['olive-oil', 2, 'tsp']],
                'steps' => ['Slice the cucumber and tomatoes.', 'Toss with lemon juice and olive oil just before serving.'],
            ],
            'banana-snack-cake' => [
                'title' => 'Banana Snack Cake', 'author' => 'maya', 'category' => 'cakes',
                'summary' => 'A simple banana cake baked in a small square tin.',
                'prep' => 15, 'cook' => 30, 'servings' => 8, 'color' => '#675039', 'tags' => ['vegetarian'],
                'ingredients' => [['bananas', 2, null], ['eggs', 2, null], ['flour', 150, 'g'], ['butter', 75, 'g'], ['sugar', 70, 'g']],
                'steps' => ['Mash the bananas. Whisk the eggs and sugar until fluffy, then fold in the banana, melted butter, and flour.', 'Pour into a lined 18 cm square tin. Bake at 175°C for about 30 minutes, until the center is set.'],
            ],
            'apple-crumble' => [
                'title' => 'Apple Crumble', 'author' => 'maya', 'category' => 'desserts', 'published' => false,
                'summary' => 'Draft for a warm apple crumble with an oat topping.',
                'prep' => 15, 'cook' => 30, 'servings' => 4, 'tags' => ['vegetarian'],
                'ingredients' => [['apples', 3, null], ['oats', 80, 'g'], ['butter', 60, 'g'], ['sugar', 40, 'g']],
                'steps' => ['Slice the apples and spread them in a baking dish.', 'Rub the butter into the oats and sugar. Scatter over the apples and bake at 190°C for 30 minutes.'],
            ],
        ];
    }
}
