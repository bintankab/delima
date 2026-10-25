<?php

namespace Database\Factories;

use App\Models\RisetCategoryModel;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RisetCategoryModel>
 */
#[UseModel(RisetCategoryModel::class)]
class RisetCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'icon' => null,
            'parent_id' => null,
        ];
    }
}
