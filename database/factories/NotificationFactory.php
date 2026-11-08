<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipient_id' => User::factory(),
            'source_audit_id' => null,
            'batch_id' => null,
            'event_category' => 'USER_MANAGEMENT',
            'scope_type' => 'USER',
            'scope_id' => null,
            'sanitized_summary' => fake()->sentence(),
            'visible_item_count' => 1,
            'read_at' => null,
        ];
    }

    /**
     * Mark as read state.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
