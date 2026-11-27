<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = config('whisklist.admin_password');
        if (is_string($password) && $password !== '' && ! User::query()->where('email', 'admin@example.com')->exists()) {
            $this->seedUser([
                'name' => 'WhiskList Administrator',
                'email' => 'admin@example.com',
                'role' => UserRole::Admin,
                'bio' => 'WhiskList administrator.',
                'is_profile_public' => false,
                'password' => $password,
            ]);
        }

        foreach ($this->demonstrationUsers() as $user) {
            $this->seedUser($user);
        }
    }

    /**
     * Create or update a deterministic seeded user using the model factory.
     *
     * @param  array{name: string, email: string, role?: UserRole, bio: string, is_profile_public: bool, password?: string}  $overrides
     */
    private function seedUser(array $overrides): void
    {
        $attributes = User::factory()->raw([
            'password' => 'password',
            ...$overrides,
        ]);

        $user = User::query()->firstOrNew([
            'email' => $overrides['email'],
        ]);

        $user->forceFill($attributes)->save();
    }

    /**
     * Get the normal users included in demonstration data.
     *
     * @return array<int, array{name: string, email: string, bio: string, is_profile_public: bool}>
     */
    private function demonstrationUsers(): array
    {
        return [
            [
                'name' => 'Maya Baker',
                'email' => 'maya@example.com',
                'bio' => 'Home baker who enjoys bread and seasonal desserts.',
                'is_profile_public' => true,
            ],
            [
                'name' => 'Leo Martin',
                'email' => 'leo@example.com',
                'bio' => 'Learning to cook simple weeknight meals.',
                'is_profile_public' => true,
            ],
            [
                'name' => 'Sofia Rossi',
                'email' => 'sofia@example.com',
                'bio' => 'Sharing family recipes and fresh pasta ideas.',
                'is_profile_public' => true,
            ],
            [
                'name' => 'Noah Green',
                'email' => 'noah@example.com',
                'bio' => 'Interested in vegetarian cooking and reducing food waste.',
                'is_profile_public' => true,
            ],
            [
                'name' => 'Amira Haddad',
                'email' => 'amira@example.com',
                'bio' => 'Collecting flavorful recipes for shared tables.',
                'is_profile_public' => true,
            ],
            [
                'name' => 'Private Cook',
                'email' => 'private@example.com',
                'bio' => 'A demonstration account with a private profile.',
                'is_profile_public' => false,
            ],
        ];
    }
}
