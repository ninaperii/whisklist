<?php

namespace Tests\Feature\Admin;

use App\Models\DietaryTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DietaryTagManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function administrators_can_manage_dietary_tags(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.dietary-tags.index'))->assertOk()->assertSee('No dietary tags have been added yet.');
        $this->actingAs($admin)->post(route('admin.dietary-tags.store'), ['name' => 'Gluten-free', 'description' => 'Author-selected dietary claim.'])->assertRedirect(route('admin.dietary-tags.index'));
        $tag = DietaryTag::query()->firstOrFail();
        $this->assertSame('gluten-free', $tag->slug);
        $this->actingAs($admin)->put(route('admin.dietary-tags.update', $tag), ['name' => 'No Gluten', 'description' => null])->assertRedirect(route('admin.dietary-tags.index'));
        $tag->refresh();
        $this->assertSame('no-gluten', $tag->slug);
        $this->actingAs($admin)->delete(route('admin.dietary-tags.destroy', $tag))->assertRedirect(route('admin.dietary-tags.index'));
        $this->assertModelMissing($tag);
    }

    #[Test]
    public function regular_users_cannot_manage_dietary_tags(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dietary-tags.index'))->assertForbidden();
    }
}
