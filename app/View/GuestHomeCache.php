<?php

namespace App\View;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GuestHomeCache
{
    private const string KEY = 'whisklist:guest-home:latest-recipes:v2';

    public function get(): array
    {
        $html = Cache::get(self::KEY);
        if (is_string($html)) {
            return ['html' => $html, 'status' => 'HIT'];
        }

        return Cache::lock(self::KEY.':lock', 15)->block(5, function (): array {
            $html = Cache::get(self::KEY);
            if (is_string($html)) {
                return ['html' => $html, 'status' => 'HIT'];
            }

            return ['html' => $this->renderAndStore(), 'status' => 'MISS'];
        });
    }

    public function warm(): void
    {
        Cache::lock(self::KEY.':lock', 15)->block(5, function (): void {
            Cache::forget(self::KEY);
            $this->renderAndStore();
        });
    }

    /** @return Collection<int, Recipe> */
    public function latestRecipes(?User $viewer = null): Collection
    {
        return Recipe::query()
            ->published()
            ->with(['thumbnail', 'author'])
            ->withAllergenWarnings($viewer)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();
    }

    private function renderAndStore(): string
    {
        $html = view('home.latest-recipes', [
            'latestRecipes' => $this->latestRecipes(),
            'relativeLinks' => true,
        ])->render();

        Cache::put(self::KEY, $html, now()->addMinutes(10));

        return $html;
    }
}
