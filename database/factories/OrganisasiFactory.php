<?php

namespace Database\Factories;

use App\Models\Organisasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organisasi>
 */
class OrganisasiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(['Dinas', 'Badan', 'Kecamatan']),
            'code' => fake()->unique()->bothify('OPD-###'),
            'address' => fake()->address(),
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
        ];
    }
}
