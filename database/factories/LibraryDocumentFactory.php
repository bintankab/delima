<?php

namespace Database\Factories;

use App\Models\LibraryDocumentModel;
use App\Models\OrganisasiModel;
use App\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LibraryDocumentModel>
 */
#[UseModel(LibraryDocumentModel::class)]
class LibraryDocumentFactory extends Factory
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
            'uploaded_by' => UserModel::factory(),
            'title' => fake()->sentence(5),
            'nomor_dokumen' => fake()->optional()->bothify('SK-###/??/????'),
            'penyusun' => fake()->name(),
            'tahun_terbit' => fake()->numberBetween(2021, now()->year),
            'tanggal_terbit' => fake()->date(),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['regulasi', 'panduan', 'laporan', 'lainnya']),
            'is_public' => true,
        ];
    }
}
