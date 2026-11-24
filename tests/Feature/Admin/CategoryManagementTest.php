<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function administrators_can_manage_categories(): void
    {
        $administrator = User::factory()->admin()->create();

        $this->actingAs($administrator)->get(route('admin.categories.index'))->assertOk()->assertSee('No categories have been added yet.')->assertSee(route('admin.dashboard'), escape: false);
        $this->actingAs($administrator)->get(route('admin.categories.create'))->assertOk();

        $this->actingAs($administrator)->post(route('admin.categories.store'), [
            'name' => 'Quick Meals',
            'description' => 'Recipes suitable for busy days.',
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->firstOrFail();
        $this->assertSame('quick-meals', $category->slug);

        $this->actingAs($administrator)->get(route('admin.categories.edit', $category))->assertOk();
        $this->actingAs($administrator)->put(route('admin.categories.update', $category), [
            'name' => 'Weeknight Meals',
            'description' => 'Updated description.',
        ])->assertRedirect(route('admin.categories.index'));

        $category->refresh();
        $this->assertSame('weeknight-meals', $category->slug);

        $this->actingAs($administrator)->delete(route('admin.categories.destroy', $category))->assertRedirect(route('admin.categories.index'));
        $this->assertModelMissing($category);
    }

    #[Test]
    public function category_input_is_validated(): void
    {
        $administrator = User::factory()->admin()->create();
        Category::factory()->create(['name' => 'Dessert', 'slug' => 'dessert']);

        $this->actingAs($administrator)->post(route('admin.categories.store'), [
            'name' => 'Dessert',
            'description' => str_repeat('a', 2001),
        ])->assertSessionHasErrors(['name', 'description']);
    }

    #[Test]
    public function regular_users_cannot_manage_categories(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }

    #[Test]
    public function categories_support_unlimited_nesting_without_cycles(): void
    {
        $administrator = User::factory()->admin()->create();
        $dessert = Category::factory()->create(['name' => 'Dessert', 'slug' => 'dessert']);
        $pies = Category::factory()->create(['parent_id' => $dessert->id, 'name' => 'Pies', 'slug' => 'pies']);
        $fruitPies = Category::factory()->create(['parent_id' => $pies->id, 'name' => 'Fruit Pies', 'slug' => 'fruit-pies']);

        $this->assertTrue($pies->parent->is($dessert));
        $this->assertTrue($dessert->children->contains($pies));
        $this->assertTrue($pies->children->contains($fruitPies));

        $this->actingAs($administrator)
            ->put(route('admin.categories.update', $dessert), [
                'name' => 'Dessert',
                'description' => null,
                'parent_id' => $fruitPies->id,
            ])
            ->assertSessionHasErrors('parent_id');

        $this->actingAs($administrator)
            ->delete(route('admin.categories.destroy', $dessert))
            ->assertSessionHasErrors('category');

        $this->assertModelExists($dessert);
    }
}
