<?php

namespace Tests\Feature\Admin;

use App\Models\OrganisasiModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_bupati_cannot_open_the_user_list(): void
    {
        $this->actingAs($this->userWithRole('bupati'))
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_opd_only_sees_its_own_asn_staff(): void
    {
        $organisasi = OrganisasiModel::factory()->create();
        $otherOrganisasi = OrganisasiModel::factory()->create();

        $adminOpd = $this->userWithRole('admin-opd');
        $adminOpd->organisasis()->sync([$organisasi->id => ['is_primary' => true]]);

        $ownAsn = $this->userWithRole('asn', 'Staf Sendiri');
        $ownAsn->organisasis()->sync([$organisasi->id => ['is_primary' => true]]);

        $otherAsn = $this->userWithRole('asn', 'Staf OPD Lain');
        $otherAsn->organisasis()->sync([$otherOrganisasi->id => ['is_primary' => true]]);

        $this->actingAs($adminOpd)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Staf Sendiri'));
    }

    public function test_admin_opd_creates_an_asn_user_scoped_to_its_own_organisasi(): void
    {
        $organisasi = OrganisasiModel::factory()->create();
        $adminOpd = $this->userWithRole('admin-opd');
        $adminOpd->organisasis()->sync([$organisasi->id => ['is_primary' => true]]);
        Role::findOrCreate('asn', 'web');

        $this->actingAs($adminOpd)
            ->post(route('admin.users.store'), [
                'name' => 'Staf ASN',
                'email' => 'asn@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'asn',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $user = UserModel::where('email', 'asn@example.test')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('asn'));
        $this->assertTrue($user->organisasis->contains($organisasi));
    }

    public function test_admin_opd_cannot_assign_a_role_other_than_asn(): void
    {
        $adminOpd = $this->userWithRole('admin-opd');

        $this->actingAs($adminOpd)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Mencoba Jadi Admin',
                'email' => 'mencoba@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'super-admin',
            ])
            ->assertSessionHasErrors(['role']);
    }

    public function test_super_admin_sees_users_matching_the_search(): void
    {
        UserModel::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.test']);
        UserModel::factory()->create(['name' => 'Siti Aminah', 'email' => 'siti@example.test']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.users.index', ['search' => 'Budi']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Budi Santoso'));
    }

    public function test_super_admin_creates_a_user_with_a_role_and_organisasi(): void
    {
        $admin = $this->userWithRole('super-admin');
        Role::findOrCreate('admin-opd', 'web');
        $organisasi = OrganisasiModel::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.test',
                'phone' => '0812345678',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => true,
                'role' => 'admin-opd',
                'organisasi_id' => $organisasi->id,
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $user = UserModel::where('email', 'budi@example.test')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('admin-opd'));
        $this->assertTrue($user->organisasis->contains($organisasi));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_a_user_created_by_an_admin_can_log_in_immediately_without_verifying_email(): void
    {
        $admin = $this->userWithRole('super-admin');
        Role::findOrCreate('admin-opd', 'web');
        $organisasi = OrganisasiModel::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => true,
                'role' => 'admin-opd',
                'organisasi_id' => $organisasi->id,
            ]);

        $this->post(route('logout'));

        $this->post(route('login'), [
            'email' => 'budi@example.test',
            'password' => 'password123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_admin_opd_role_requires_an_organisasi(): void
    {
        $admin = $this->userWithRole('super-admin');
        Role::findOrCreate('admin-opd', 'web');

        $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin-opd',
            ])
            ->assertSessionHasErrors(['organisasi_id' => 'Organisasi/OPD wajib dipilih untuk role Admin OPD.']);
    }

    public function test_empty_name_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        UserModel::factory()->create(['email' => 'budi@example.test']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Budi Lain',
                'email' => 'budi@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'reviewer',
            ])
            ->assertSessionHasErrors(['email' => 'Email sudah digunakan.']);
    }

    public function test_updating_without_password_keeps_the_old_one(): void
    {
        $user = UserModel::factory()->create(['email' => 'budi@example.test']);
        $user->assignRole(Role::findOrCreate('reviewer', 'web'));
        $originalPassword = $user->password;

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.users.update', $user), [
                'name' => 'Budi Baru',
                'email' => 'budi@example.test',
                'role' => 'reviewer',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();

        $this->assertSame('Budi Baru', $user->name);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_updating_role_replaces_the_previous_role(): void
    {
        $user = UserModel::factory()->create();
        $user->assignRole(Role::findOrCreate('reviewer', 'web'));
        Role::findOrCreate('admin-opd', 'web');
        $organisasi = OrganisasiModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'admin-opd',
                'organisasi_id' => $organisasi->id,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();

        $this->assertTrue($user->hasRole('admin-opd'));
        $this->assertFalse($user->hasRole('reviewer'));
        $this->assertTrue($user->organisasis->contains($organisasi));
    }

    public function test_switching_away_from_admin_opd_clears_its_organisasi(): void
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate('admin-opd', 'web');
        Role::findOrCreate('reviewer', 'web');
        $user->assignRole('admin-opd');
        $organisasi = OrganisasiModel::factory()->create();
        $user->organisasis()->sync([$organisasi->id => ['is_primary' => true]]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'reviewer',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertCount(0, $user->organisasis()->get());
    }

    public function test_user_can_be_deleted(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_cannot_delete_their_own_account(): void
    {
        $admin = $this->userWithRole('super-admin');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    private function userWithRole(string $role, ?string $name = null): UserModel
    {
        $user = UserModel::factory()->create($name === null ? [] : ['name' => $name]);
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
