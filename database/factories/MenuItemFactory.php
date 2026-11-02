<?php

namespace Database\Factories;

use App\Models\MenuItemModel;
use App\Models\MenuModel;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItemModel>
 */
#[UseModel(MenuItemModel::class)]
class MenuItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_id' => MenuModel::factory(),
            'parent_id' => null,
            'label' => fake()->words(2, true),
            'url' => '/'.fake()->unique()->slug(2),
            'sort_order' => 0,
            'opens_in_new_tab' => false,
        ];
    }
}
