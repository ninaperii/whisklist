<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_can_view_public_profiles(): void
    {
        $user = User::factory()->create([
            'name' => 'Public Cook',
            'bio' => 'I enjoy baking bread.',
            'is_profile_public' => true,
        ]);

        $this->get(route('users.show', $user))
            ->assertOk()
            ->assertSee('Public Cook')
            ->assertSee('I enjoy baking bread.')
            ->assertDontSee($user->email);
    }

    #[Test]
    public function guests_can_see_public_information_on_private_profiles(): void
    {
        $user = User::factory()->privateProfile()->create(['bio' => 'Hidden biography']);

        $this->get(route('users.show', $user))
            ->assertOk()->assertSee($user->name)->assertSee('This profile is private.')->assertDontSee('Hidden biography');
    }

    #[Test]
    public function other_users_can_see_only_public_information_on_private_profiles(): void
    {
        $viewer = User::factory()->create();
        $privateUser = User::factory()->privateProfile()->create(['bio' => 'Hidden biography', 'avatar_path' => 'avatars/cook.png']);
        $published = Recipe::factory()->for($privateUser, 'author')->published()->create();
        $draft = Recipe::factory()->for($privateUser, 'author')->create();

        $this->actingAs($viewer)
            ->get(route('users.show', $privateUser))
            ->assertOk()
            ->assertSee($privateUser->name)
            ->assertSee(route('users.avatar', $privateUser), false)
            ->assertSee('This profile is private.')
            ->assertSee($published->title)
            ->assertDontSee($draft->title)
            ->assertDontSee('Hidden biography');
    }

    #[Test]
    public function users_can_upload_and_replace_a_public_profile_photo(): void
    {
        $storage = Storage::fake('local');
        $user = User::factory()->privateProfile()->create();
        $other = User::factory()->create();
        $data = ['name' => $user->name, 'is_profile_public' => false];

        $this->get(route('users.avatar', $user))->assertNotFound();
        $this->actingAs($user)->put(route('profile.update'), $data + [
            'avatar' => UploadedFile::fake()->image('first.png'), 'user_id' => $other->id,
        ])->assertRedirect(route('profile.edit'));
        $first = $user->fresh()->avatar_path;
        $storage->assertExists($first);
        $this->assertNull($other->fresh()->avatar_path);

        $this->put(route('profile.update'), $data + ['avatar' => UploadedFile::fake()->create('bad.pdf')])
            ->assertSessionHasErrors('avatar');
        $this->assertSame($first, $user->fresh()->avatar_path);
        $this->put(route('profile.update'), $data + ['avatar' => UploadedFile::fake()->image('second.png')])
            ->assertRedirect(route('profile.edit'));
        $storage->assertMissing($first);
        $storage->assertExists($user->fresh()->avatar_path);

        auth()->logout();
        $this->get(route('users.avatar', $user))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get(route('users.show', $user))->assertOk()->assertSee(route('users.avatar', $user), false);
    }

    #[Test]
    public function owners_can_view_their_private_profiles(): void
    {
        $user = User::factory()->privateProfile()->create();

        $this->actingAs($user)
            ->get(route('users.show', $user))
            ->assertOk()
            ->assertSee('This profile is private.');
    }

    #[Test]
    public function administrators_can_view_private_profiles(): void
    {
        $administrator = User::factory()->admin()->create();
        $privateUser = User::factory()->privateProfile()->create();

        $this->actingAs($administrator)
            ->get(route('users.show', $privateUser))
            ->assertOk();
    }

    #[Test]
    public function users_can_edit_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertViewIs('profiles.edit');

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated Cook',
            'bio' => 'My updated biography.',
            'is_profile_public' => false,
            'role' => UserRole::Admin->value,
        ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertSame('Updated Cook', $user->name);
        $this->assertSame('My updated biography.', $user->bio);
        $this->assertFalse($user->is_profile_public);
        $this->assertSame(UserRole::User, $user->role);
    }

    #[Test]
    public function profile_input_is_validated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'name' => '',
                'bio' => str_repeat('a', 1001),
                'is_profile_public' => 'not-a-boolean',
            ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors(['name', 'bio', 'is_profile_public']);
    }

    #[Test]
    public function guests_cannot_edit_profiles(): void
    {
        $this->get(route('profile.edit'))
            ->assertRedirect(route('login'));

        $this->put(route('profile.update'), [])
            ->assertRedirect(route('login'));
    }
}
