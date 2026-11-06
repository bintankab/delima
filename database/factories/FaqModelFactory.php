<?php

namespace Database\Factories;

use App\Models\FaqModel;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FaqModel>
 */
#[UseModel(FaqModel::class)]
class FaqModelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => fake()->unique()->sentence(),
            'answer' => fake()->paragraph(),
            'category' => fake()->optional()->word(),
            'order' => fake()->numberBetween(0, 20),
        ];
    }
}
