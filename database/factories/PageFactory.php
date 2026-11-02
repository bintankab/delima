<?php

namespace Database\Factories;

use App\Enums\PageLayout;
use App\Models\PageModel;
use App\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageModel>
 */
#[UseModel(PageModel::class)]
class PageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => UserModel::factory(),
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(3),
            'excerpt' => fake()->sentence(12),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'fields' => null,
            'layout' => PageLayout::Layout1->value,
            'status' => 'draft',
            'published_at' => null,
        ];
    }
}
