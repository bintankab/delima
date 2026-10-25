<?php

namespace Database\Factories;

use App\Models\OrganisasiModel;
use App\Models\RisetModel;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RisetModel>
 */
#[UseModel(RisetModel::class)]
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
            'organisasi_id' => OrganisasiModel::factory(),
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
