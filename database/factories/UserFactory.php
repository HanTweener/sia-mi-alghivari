<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'email_verification_source' => 'master_attested',
            'password' => static::$password ??= Hash::make('password123456'),
            'must_change_password' => false,
            'temporary_password_expires_at' => null,
            'password_changed_at' => now(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'is_active' => true,
            'credential_version' => 0,
            'session_generation' => 0,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user has a temporary password and must change it.
     */
    public function temporaryPassword(): static
    {
        return $this->state(fn (array $attributes) => [
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->addDays(7),
            'password_changed_at' => null,
        ]);
    }
}
