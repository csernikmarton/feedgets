<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Feed;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Feed>
 */
class FeedFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Feed::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'url' => fake()->url(),
            'description' => fake()->paragraph(),
            'position' => fake()->numberBetween(1, 100),
            'column' => fake()->numberBetween(1, 3),
            'oldest_published_at' => fake()->optional(0.7)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the feed has a specific position.
     *
     * @return $this
     */
    public function position(int $position): self
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }

    /**
     * Indicate that the feed has a specific column.
     *
     * @return $this
     */
    public function column(int $column): self
    {
        return $this->state(fn (array $attributes) => [
            'column' => $column,
        ]);
    }

    /**
     * Indicate that the feed has no oldest_published_at date.
     *
     * @return $this
     */
    public function withoutOldestPublishedAt(): self
    {
        return $this->state(fn (array $attributes) => [
            'oldest_published_at' => null,
        ]);
    }
}
