<?php

namespace Database\Factories;

use App\Enums\JenisInovasi;
use App\Enums\TahapanInovasi;
use App\Models\InnovationModel;
use App\Models\OrganisasiModel;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InnovationModel>
 */
#[UseModel(InnovationModel::class)]
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
            'organisasi_id' => OrganisasiModel::factory(),
            'title' => fake()->sentence(6),
            'slug' => fake()->unique()->slug(4),
            'description' => fake()->paragraphs(3, true),
            'jenis_inovasi' => fake()->randomElement(JenisInovasi::cases()),
            'tahapan' => fake()->randomElement(TahapanInovasi::cases()),
            'tahun' => fake()->numberBetween(2021, now()->year),
            'status' => 'draft',
            'origin' => 'submission',
            'implementation_at' => fake()->dateTimeBetween('2021-01-01', 'now'),
        ];
    }
}
