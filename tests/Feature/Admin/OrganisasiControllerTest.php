<?php

namespace Tests\Feature\Admin;

use App\Models\OrganisasiModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganisasiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.organisasis.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_opd_cannot_open_the_organisasi_list(): void
    {
        $this->actingAs($this->userWithRole('admin-opd'))
            ->get(route('admin.organisasis.index'))
            ->assertForbidden();
    }

    public function test_super_admin_sees_organisasis_matching_the_search(): void
    {
        OrganisasiModel::factory()->create(['name' => 'Dinas Kesehatan']);
        OrganisasiModel::factory()->create(['name' => 'Dinas Pendidikan']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.organisasis.index', ['search' => 'Kesehatan']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Organisasis/Index')
                ->has('organisasis.data', 1)
                ->where('organisasis.data.0.name', 'Dinas Kesehatan'));
    }

    public function test_super_admin_creates_an_organisasi(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.organisasis.store'), [
                'name' => 'Dinas Kesehatan',
                'type' => 'Dinas',
                'code' => 'OPD-001',
                'address' => 'Jl. Merdeka No. 1',
                'contact_person' => 'Budi',
                'phone' => '0812345678',
                'email' => 'dinkes@example.test',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.organisasis.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('organisasis', [
            'name' => 'Dinas Kesehatan',
            'code' => 'OPD-001',
            'is_active' => true,
        ]);
    }

    public function test_empty_name_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.organisasis.create'))
            ->post(route('admin.organisasis.store'), [])
            ->assertRedirect(route('admin.organisasis.create'))
            ->assertSessionHasErrors([
                'name' => 'Nama organisasi wajib diisi.',
            ]);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        OrganisasiModel::factory()->create(['code' => 'OPD-001']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.organisasis.create'))
            ->post(route('admin.organisasis.store'), [
                'name' => 'Dinas Lain',
                'code' => 'OPD-001',
            ])
            ->assertSessionHasErrors([
                'code' => 'Kode organisasi sudah digunakan.',
            ]);
    }

    public function test_updating_can_keep_its_own_code(): void
    {
        $organisasi = OrganisasiModel::factory()->create(['code' => 'OPD-001']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.organisasis.update', $organisasi), [
                'name' => 'Nama Baru',
                'code' => 'OPD-001',
            ])
            ->assertRedirect(route('admin.organisasis.index'));

        $this->assertDatabaseHas('organisasis', [
            'id' => $organisasi->id,
            'name' => 'Nama Baru',
            'code' => 'OPD-001',
        ]);
    }

    public function test_organisasi_without_related_data_can_be_deleted(): void
    {
        $organisasi = OrganisasiModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.organisasis.destroy', $organisasi))
            ->assertRedirect(route('admin.organisasis.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('organisasis', ['id' => $organisasi->id]);
    }

    public function test_organisasi_with_a_user_cannot_be_deleted(): void
    {
        $organisasi = OrganisasiModel::factory()->create();
        $user = UserModel::factory()->create();
        $organisasi->users()->attach($user, ['is_primary' => true]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.organisasis.destroy', $organisasi))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('organisasis', ['id' => $organisasi->id]);
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
