<?php

namespace Tests\Feature\Admin;

use App\Models\OrganisasiModel;
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

    public function test_status_filters_risets_by_status(): void
    {
        RisetModel::factory()->create(['title' => 'Draf Lama', 'status' => 'draft']);
        RisetModel::factory()->create(['title' => 'Sudah Diajukan', 'status' => 'submitted']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.risets.index', ['status' => 'submitted']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'submitted')
                ->has('risets.data', 1)
                ->where('risets.data.0.title', 'Sudah Diajukan'));
    }

    public function test_unknown_status_value_is_ignored(): void
    {
        RisetModel::factory()->count(2)->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.risets.index', ['status' => 'not-a-real-status']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', null)
                ->has('risets.data', 2));
    }

    public function test_editable_flag_is_true_only_for_the_owners_own_unapproved_riset(): void
    {
        $organisasi = OrganisasiModel::factory()->create();
        $opd = $this->userWithRole('admin-opd');
        $other = $this->userWithRole('admin-opd');
        $opd->organisasis()->sync([$organisasi->id => ['is_primary' => true]]);
        $other->organisasis()->sync([$organisasi->id => ['is_primary' => true]]);

        RisetModel::factory()->create(['title' => 'Punya Saya', 'organisasi_id' => $organisasi->id, 'user_id' => $opd->id, 'status' => 'submitted']);
        RisetModel::factory()->create(['title' => 'Punya Saya Tayang', 'organisasi_id' => $organisasi->id, 'user_id' => $opd->id, 'status' => 'published']);
        RisetModel::factory()->create(['title' => 'Punya Orang Lain', 'organisasi_id' => $organisasi->id, 'user_id' => $other->id, 'status' => 'submitted']);

        $this->actingAs($opd)
            ->get(route('admin.risets.index', ['sort' => 'title', 'direction' => 'asc']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('risets.data.0.title', 'Punya Orang Lain')
                ->where('risets.data.0.editable', false)
                ->where('risets.data.1.title', 'Punya Saya')
                ->where('risets.data.1.editable', true)
                ->where('risets.data.2.title', 'Punya Saya Tayang')
                ->where('risets.data.2.editable', false));
    }

    public function test_admin_opd_does_not_see_risets_from_other_organisasis(): void
    {
        $ownOrganisasi = OrganisasiModel::factory()->create();
        $otherOrganisasi = OrganisasiModel::factory()->create();

        $opd = $this->userWithRole('admin-opd');
        $opd->organisasis()->sync([$ownOrganisasi->id => ['is_primary' => true]]);

        RisetModel::factory()->create(['title' => 'Milik OPD Saya', 'organisasi_id' => $ownOrganisasi->id]);
        RisetModel::factory()->create(['title' => 'Milik OPD Lain', 'organisasi_id' => $otherOrganisasi->id]);

        $this->actingAs($opd)
            ->get(route('admin.risets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('risets.data', 1)
                ->where('risets.data.0.title', 'Milik OPD Saya'));
    }

    public function test_super_admin_and_reviewer_see_risets_across_all_organisasis(): void
    {
        $organisasiA = OrganisasiModel::factory()->create();
        $organisasiB = OrganisasiModel::factory()->create();

        RisetModel::factory()->create(['organisasi_id' => $organisasiA->id]);
        RisetModel::factory()->create(['organisasi_id' => $organisasiB->id]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.risets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('risets.data', 2));

        $this->actingAs($this->userWithRole('reviewer'))
            ->get(route('admin.risets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('risets.data', 2));
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
