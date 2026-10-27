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

    public function test_admin_opd_cannot_open_the_user_list(): void
    {
        $this->actingAs($this->userWithRole('admin-opd'))
            ->get(route('admin.users.index'))
            ->assertForbidden();
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

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
