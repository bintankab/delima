<?php

namespace Tests\Feature\Admin;

use App\Models\RisetModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RisetIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_filters_risets_by_title(): void
    {
        RisetModel::factory()->create(['title' => 'Studi Gizi Balita Bintan']);
        RisetModel::factory()->create(['title' => 'Riset Pariwisata Pesisir']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.risets.index', ['search' => 'Gizi Balita']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Risets/Index')
                ->where('filters.search', 'Gizi Balita')
                ->has('risets.data', 1)
                ->where('risets.data.0.title', 'Studi Gizi Balita Bintan'));
    }

    public function test_search_filters_risets_by_peneliti(): void
    {
        RisetModel::factory()->create(['title' => 'Riset A', 'peneliti' => 'Dr. Sari Wulandari']);
        RisetModel::factory()->create(['title' => 'Riset B', 'peneliti' => 'Budi Santoso']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.risets.index', ['search' => 'Sari']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('risets.data', 1)
                ->where('risets.data.0.peneliti', 'Dr. Sari Wulandari'));
    }

    public function test_empty_search_returns_all_risets(): void
    {
        RisetModel::factory()->count(3)->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.risets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', '')
                ->has('risets.data', 3));
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
