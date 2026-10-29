<?php

namespace Tests\Feature\Admin;

use App\Enums\JenisInovasi;
use App\Models\InnovationModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_filter_options(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/Analytics')
                ->where('filters.tahun', null)
                ->where('filters.jenis_inovasi', null)
                ->has('tahunOptions')
                ->has('jenisInovasiOptions'));
    }

    public function test_tahun_filter_narrows_the_innovation_stats(): void
    {
        InnovationModel::factory()->create(['tahun' => 2022]);
        InnovationModel::factory()->create(['tahun' => 2023]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.dashboard', ['tahun' => 2023]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.tahun', 2023)
                ->where('stats.total_innovations', 1));
    }

    public function test_jenis_inovasi_filter_narrows_the_innovation_stats(): void
    {
        InnovationModel::factory()->create(['jenis_inovasi' => JenisInovasi::Digital]);
        InnovationModel::factory()->create(['jenis_inovasi' => JenisInovasi::Teknologi]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.dashboard', ['jenis_inovasi' => JenisInovasi::Digital->value]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.jenis_inovasi', JenisInovasi::Digital->value)
                ->where('stats.total_innovations', 1));
    }

    public function test_invalid_tahun_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.dashboard', ['tahun' => 1999]))
            ->assertSessionHasErrors(['tahun']);
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
