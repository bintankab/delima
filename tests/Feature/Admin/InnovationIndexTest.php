<?php

namespace Tests\Feature\Admin;

use App\Models\InnovationModel;
use App\Models\OrganisasiModel;
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

    public function test_status_filters_innovations_by_status(): void
    {
        InnovationModel::factory()->create(['title' => 'Draf Lama', 'status' => 'draft']);
        InnovationModel::factory()->create(['title' => 'Sudah Diajukan', 'status' => 'submitted']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.innovations.index', ['status' => 'submitted']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'submitted')
                ->has('innovations.data', 1)
                ->where('innovations.data.0.title', 'Sudah Diajukan'));
    }

    public function test_unknown_status_value_is_ignored(): void
    {
        InnovationModel::factory()->count(2)->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.innovations.index', ['status' => 'not-a-real-status']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', null)
                ->has('innovations.data', 2));
    }

    public function test_editable_flag_is_true_only_for_the_owners_own_unapproved_innovation(): void
    {
        $organisasi = OrganisasiModel::factory()->create();
        $opd = $this->userWithRole('admin-opd');
        $other = $this->userWithRole('admin-opd');
        $opd->organisasis()->sync([$organisasi->id => ['is_primary' => true]]);
        $other->organisasis()->sync([$organisasi->id => ['is_primary' => true]]);

        InnovationModel::factory()->create(['title' => 'Punya Saya', 'user_id' => $opd->id, 'status' => 'submitted']);
        InnovationModel::factory()->create(['title' => 'Punya Saya Tayang', 'user_id' => $opd->id, 'status' => 'published']);
        InnovationModel::factory()->create(['title' => 'Punya Orang Lain', 'user_id' => $other->id, 'status' => 'submitted']);

        $this->actingAs($opd)
            ->get(route('admin.innovations.index', ['sort' => 'title', 'direction' => 'asc']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('innovations.data.0.title', 'Punya Orang Lain')
                ->where('innovations.data.0.editable', false)
                ->where('innovations.data.1.title', 'Punya Saya')
                ->where('innovations.data.1.editable', true)
                ->where('innovations.data.2.title', 'Punya Saya Tayang')
                ->where('innovations.data.2.editable', false));
    }

    public function test_admin_opd_does_not_see_innovations_from_other_organisasis(): void
    {
        $ownOrganisasi = OrganisasiModel::factory()->create();
        $otherOrganisasi = OrganisasiModel::factory()->create();

        $opd = $this->userWithRole('admin-opd');
        $opd->organisasis()->sync([$ownOrganisasi->id => ['is_primary' => true]]);

        $otherOpd = $this->userWithRole('admin-opd');
        $otherOpd->organisasis()->sync([$otherOrganisasi->id => ['is_primary' => true]]);

        InnovationModel::factory()->create(['title' => 'Milik OPD Saya', 'user_id' => $opd->id]);
        InnovationModel::factory()->create(['title' => 'Milik OPD Lain', 'user_id' => $otherOpd->id]);

        $this->actingAs($opd)
            ->get(route('admin.innovations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('innovations.data', 1)
                ->where('innovations.data.0.title', 'Milik OPD Saya'));
    }

    public function test_super_admin_and_reviewer_see_innovations_across_all_organisasis(): void
    {
        $organisasi = OrganisasiModel::factory()->create();
        $opd = $this->userWithRole('admin-opd');
        $opd->organisasis()->sync([$organisasi->id => ['is_primary' => true]]);

        InnovationModel::factory()->create(['title' => 'Dari OPD', 'user_id' => $opd->id]);
        InnovationModel::factory()->create(['title' => 'Tanpa OPD Terkait']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.innovations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('innovations.data', 2));

        $this->actingAs($this->userWithRole('reviewer'))
            ->get(route('admin.innovations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('innovations.data', 2));
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
