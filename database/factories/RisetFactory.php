<?php

namespace Database\Factories;

use App\Models\Organisasi;
use App\Models\Riset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Riset>
 */
class RisetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organisasi_id' => Organisasi::factory(),
            'title' => fake()->sentence(6),
            'slug' => fake()->unique()->slug(4),
            'peneliti' => fake()->name(),
            'abstrak' => fake()->paragraphs(3, true),
            'tahun' => fake()->numberBetween(2021, now()->year),
            'status' => 'draft',
            'origin' => 'submission',
        ];
    }
}
