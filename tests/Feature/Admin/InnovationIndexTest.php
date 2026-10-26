<?php

namespace Tests\Feature\Admin;

use App\Models\InnovationModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InnovationIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_filters_innovations_by_title(): void
    {
        InnovationModel::factory()->create(['title' => 'Aplikasi Layanan Terpadu']);
        InnovationModel::factory()->create(['title' => 'Inovasi Pariwisata Pesisir']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.innovations.index', ['search' => 'Layanan Terpadu']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Innovations/Index')
                ->where('filters.search', 'Layanan Terpadu')
                ->has('innovations.data', 1)
                ->where('innovations.data.0.title', 'Aplikasi Layanan Terpadu'));
    }

    public function test_empty_search_returns_all_innovations(): void
    {
        InnovationModel::factory()->count(3)->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.innovations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', '')
                ->has('innovations.data', 3));
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
