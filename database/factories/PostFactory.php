<?php

namespace Database\Factories;

use App\Models\PostModel;
use App\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostModel>
 */
#[UseModel(PostModel::class)]
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => UserModel::factory(),
            'title' => fake()->sentence(6),
            'slug' => fake()->unique()->slug(4),
            'excerpt' => fake()->sentence(12),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'status' => 'draft',
            'published_at' => null,
        ];
    }
}
