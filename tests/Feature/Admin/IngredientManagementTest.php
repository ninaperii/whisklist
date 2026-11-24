<?php

namespace Tests\Feature\Admin;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IngredientManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function administrators_can_manage_ingredients(): void
    {
        $administrator = User::factory()->admin()->create();

        $this->actingAs($administrator)->get(route('admin.ingredients.index'))->assertOk()->assertSee('No ingredients have been added yet.')->assertSee(route('admin.dashboard'), escape: false);
        $this->actingAs($administrator)->get(route('admin.ingredients.create'))->assertOk();

        $this->actingAs($administrator)->post(route('admin.ingredients.store'), ['name' => 'Olive Oil'])
            ->assertRedirect(route('admin.ingredients.index'));

        $ingredient = Ingredient::query()->firstOrFail();
        $this->assertSame('olive-oil', $ingredient->slug);

        $this->actingAs($administrator)->get(route('admin.ingredients.edit', $ingredient))->assertOk();
        $this->actingAs($administrator)->put(route('admin.ingredients.update', $ingredient), ['name' => 'Extra Virgin Olive Oil'])
            ->assertRedirect(route('admin.ingredients.index'));

        $ingredient->refresh();
        $this->assertSame('extra-virgin-olive-oil', $ingredient->slug);

        $this->actingAs($administrator)->delete(route('admin.ingredients.destroy', $ingredient))
            ->assertRedirect(route('admin.ingredients.index'));
        $this->assertModelMissing($ingredient);
    }

    #[Test]
    public function ingredient_names_must_be_unique(): void
    {
        $administrator = User::factory()->admin()->create();
        Ingredient::factory()->create(['name' => 'Garlic', 'slug' => 'garlic']);

        $this->actingAs($administrator)->post(route('admin.ingredients.store'), ['name' => 'Garlic'])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function regular_users_cannot_manage_ingredients(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.ingredients.index'))
            ->assertForbidden();
    }
}
