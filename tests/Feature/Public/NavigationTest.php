<?php

namespace Tests\Feature\Public;

use App\Models\MenuItemModel;
use App\Models\MenuModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_no_navigation_until_a_header_menu_exists(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('navigation', null));
    }

    public function test_home_shares_header_menu_items_in_order(): void
    {
        $menu = MenuModel::factory()->create();
        $parent = MenuItemModel::factory()->create([
            'menu_id' => $menu->id,
            'label' => 'Layanan',
            'url' => '/layanan',
            'sort_order' => 0,
        ]);
        MenuItemModel::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $parent->id,
            'label' => 'Riset',
            'url' => '/riset',
            'sort_order' => 0,
        ]);
        MenuItemModel::factory()->create([
            'menu_id' => $menu->id,
            'label' => 'Beranda',
            'url' => '/',
            'sort_order' => 1,
            'opens_in_new_tab' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('navigation.0.label', 'Layanan')
                ->where('navigation.0.children.0.label', 'Riset')
                ->where('navigation.1.label', 'Beranda')
                ->where('navigation.1.opens_in_new_tab', true)
                ->has('navigation.1.children', 0));
    }
}
