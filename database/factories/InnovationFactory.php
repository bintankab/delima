<?php

namespace Database\Factories;

use App\Models\Innovation;
use App\Models\Organisasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Innovation>
 */
class InnovationFactory extends Factory
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
            'description' => fake()->paragraphs(3, true),
            'jenis_inovasi' => fake()->randomElement(['produk', 'proses', 'pelayanan']),
            'tahun' => fake()->numberBetween(2021, now()->year),
            'status' => 'draft',
            'origin' => 'submission',
        ];
    }
}
