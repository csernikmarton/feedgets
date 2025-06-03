<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Article;
use App\Models\Feed;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'feed_uuid' => Feed::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'link' => fake()->url(),
            'guid' => fake()->uuid(),
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'is_read' => fake()->boolean(),
        ];
    }

    /**
     * Indicate that the article is read.
     *
     * @return $this
     */
    public function read(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }

    /**
     * Indicate that the article is unread.
     *
     * @return $this
     */
    public function unread(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
        ]);
    }
}
