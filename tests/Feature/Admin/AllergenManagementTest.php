<?php

namespace Tests\Feature\Admin;

use App\Models\Allergen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AllergenManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function administrators_can_manage_allergens(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.allergens.index'))->assertOk()->assertSee('No allergens have been added yet.');
        $this->actingAs($admin)->post(route('admin.allergens.store'), ['name' => 'Gluten', 'description' => 'Ingredient-linked allergen.'])->assertRedirect(route('admin.allergens.index'));
        $allergen = Allergen::query()->firstOrFail();
        $this->assertSame('gluten', $allergen->slug);
        $this->actingAs($admin)->put(route('admin.allergens.update', $allergen), ['name' => 'Gluten Cereals', 'description' => null])->assertRedirect(route('admin.allergens.index'));
        $allergen->refresh();
        $this->assertSame('gluten-cereals', $allergen->slug);
        $this->actingAs($admin)->delete(route('admin.allergens.destroy', $allergen))->assertRedirect(route('admin.allergens.index'));
        $this->assertModelMissing($allergen);
    }

    #[Test]
    public function regular_users_cannot_manage_allergens(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.allergens.index'))->assertForbidden();
    }
}
