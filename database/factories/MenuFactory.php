<?php

namespace Database\Factories;

use App\Models\MenuModel;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuModel>
 */
#[UseModel(MenuModel::class)]
class MenuFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Menu Atas',
            'location' => 'header',
        ];
    }
}
