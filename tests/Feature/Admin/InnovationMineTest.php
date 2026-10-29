<?php

namespace Tests\Feature\Admin;

use App\Models\InnovationCategoryModel;
use App\Models\InnovationModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InnovationMineTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('submitterRoles')]
    public function test_a_submitter_only_sees_their_own_innovations(string $role): void
    {
        $owner = $this->userWithRole($role);
        $someoneElse = $this->userWithRole($role);

        InnovationModel::factory()->create(['user_id' => $owner->id, 'title' => 'Punya Saya']);
        InnovationModel::factory()->create(['user_id' => $someoneElse->id, 'title' => 'Punya Orang Lain']);

        $this->actingAs($owner)
            ->get(route('admin.innovations.mine'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Innovations/Mine')
                ->has('innovations.data', 1)
                ->where('innovations.data.0.title', 'Punya Saya'));
    }

    #[DataProvider('submitterRoles')]
    public function test_a_submitter_can_open_their_own_innovation_detail(string $role): void
    {
        $owner = $this->userWithRole($role);
        $innovation = InnovationModel::factory()->create(['user_id' => $owner->id, 'status' => 'rejected']);

        $this->actingAs($owner)
            ->get(route('admin.innovations.mine.show', $innovation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Innovations/Show')
                ->where('innovation.id', $innovation->id)
                ->where('innovation.reviewable', false)
                ->where('canManage', true)
                ->where('editRoute', 'admin.innovations.mine.edit')
                ->where('deleteRoute', 'admin.innovations.mine.destroy')
                ->where('backRoute', 'admin.innovations.mine'));
    }

    #[DataProvider('submitterRoles')]
    public function test_a_submitter_cannot_manage_their_already_approved_innovation_from_its_detail_page(string $role): void
    {
        $owner = $this->userWithRole($role);
        $innovation = InnovationModel::factory()->create(['user_id' => $owner->id, 'status' => 'published']);

        $this->actingAs($owner)
            ->get(route('admin.innovations.mine.show', $innovation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canManage', false));
    }

    #[DataProvider('submitterRoles')]
    public function test_a_submitter_cannot_open_someone_elses_innovation_detail(string $role): void
    {
        $owner = $this->userWithRole($role);
        $other = $this->userWithRole($role);
        $innovation = InnovationModel::factory()->create(['user_id' => $other->id]);

        $this->actingAs($owner)
            ->get(route('admin.innovations.mine.show', $innovation))
            ->assertForbidden();
    }

    #[DataProvider('submitterRoles')]
    public function test_a_submitter_can_update_their_own_unapproved_innovation(string $role): void
    {
        $owner = $this->userWithRole($role);
        $innovation = InnovationModel::factory()->create([
            'user_id' => $owner->id,
            'status' => 'submitted',
            'title' => 'Judul Lama',
        ]);
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($owner)
            ->get(route('admin.innovations.mine.edit', $innovation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Innovations/Form')
                ->where('updateRoute', 'admin.innovations.mine.update')
                ->where('showRoute', 'admin.innovations.mine.show')
                ->where('inisiatorLocked', true));

        $this->actingAs($owner)
            ->put(route('admin.innovations.mine.update', $innovation), [
                'category_id' => $category->id,
                'title' => 'Judul Baru',
                'jenis_inovasi' => $innovation->jenis_inovasi->value,
                'tahapan' => $innovation->tahapan->value,
                'description' => 'Deskripsi baru.',
                'tahun' => 2022,
                'implementation_at' => '2022-01-10',
            ])
            ->assertRedirect(route('admin.innovations.mine.show', $innovation));

        $innovation->refresh();
        $this->assertSame('Judul Baru', $innovation->title);
        $this->assertSame('submitted', $innovation->status);
    }

    #[DataProvider('submitterRoles')]
    public function test_a_submitter_cannot_update_an_already_approved_innovation(string $role): void
    {
        $owner = $this->userWithRole($role);
        $innovation = InnovationModel::factory()->create([
            'user_id' => $owner->id,
            'status' => 'published',
            'title' => 'Judul Tetap',
        ]);
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($owner)
            ->get(route('admin.innovations.mine.edit', $innovation))
            ->assertForbidden();

        $this->actingAs($owner)
            ->put(route('admin.innovations.mine.update', $innovation), [
                'category_id' => $category->id,
                'title' => 'Judul Diubah Paksa',
                'jenis_inovasi' => $innovation->jenis_inovasi->value,
                'tahapan' => $innovation->tahapan->value,
                'description' => 'Deskripsi.',
                'tahun' => 2022,
                'implementation_at' => '2022-01-10',
            ])
            ->assertForbidden();

        $this->assertSame('Judul Tetap', $innovation->fresh()->title);
    }

    #[DataProvider('submitterRoles')]
    public function test_a_submitter_cannot_update_someone_elses_innovation(string $role): void
    {
        $owner = $this->userWithRole($role);
        $other = $this->userWithRole($role);
        $innovation = InnovationModel::factory()->create(['user_id' => $other->id, 'status' => 'submitted']);
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($owner)
            ->get(route('admin.innovations.mine.edit', $innovation))
            ->assertForbidden();

        $this->actingAs($owner)
            ->put(route('admin.innovations.mine.update', $innovation), [
                'category_id' => $category->id,
                'title' => 'Dicuri',
                'jenis_inovasi' => $innovation->jenis_inovasi->value,
                'tahapan' => $innovation->tahapan->value,
                'description' => 'Deskripsi.',
                'tahun' => 2022,
                'implementation_at' => '2022-01-10',
            ])
            ->assertForbidden();
    }

    #[DataProvider('submitterRoles')]
    public function test_a_submitter_can_delete_their_own_unapproved_innovation(string $role): void
    {
        $owner = $this->userWithRole($role);
        $innovation = InnovationModel::factory()->create(['user_id' => $owner->id, 'status' => 'rejected']);

        $this->actingAs($owner)
            ->delete(route('admin.innovations.mine.destroy', $innovation), ['redirect_route' => 'admin.innovations.mine'])
            ->assertRedirect(route('admin.innovations.mine'))
            ->assertSessionHas('success', 'Inovasi berhasil dihapus.');

        $this->assertSoftDeleted('innovations', ['id' => $innovation->id]);
    }

    public function test_deleting_without_a_redirect_route_defaults_to_mine(): void
    {
        $owner = $this->userWithRole('publik');
        $innovation = InnovationModel::factory()->create(['user_id' => $owner->id, 'status' => 'submitted']);

        $this->actingAs($owner)
            ->delete(route('admin.innovations.mine.destroy', $innovation))
            ->assertRedirect(route('admin.innovations.mine'));
    }

    public function test_admin_opd_deleting_from_the_index_page_is_redirected_back_there(): void
    {
        $owner = $this->userWithRole('admin-opd');
        $innovation = InnovationModel::factory()->create(['user_id' => $owner->id, 'status' => 'submitted']);

        $this->actingAs($owner)
            ->delete(route('admin.innovations.mine.destroy', $innovation), ['redirect_route' => 'admin.innovations.index'])
            ->assertRedirect(route('admin.innovations.index'));

        $this->assertSoftDeleted('innovations', ['id' => $innovation->id]);
    }

    public function test_an_unknown_redirect_route_falls_back_to_mine(): void
    {
        $owner = $this->userWithRole('publik');
        $innovation = InnovationModel::factory()->create(['user_id' => $owner->id, 'status' => 'submitted']);

        $this->actingAs($owner)
            ->delete(route('admin.innovations.mine.destroy', $innovation), ['redirect_route' => 'admin.users.index'])
            ->assertRedirect(route('admin.innovations.mine'));
    }

    #[DataProvider('submitterRoles')]
    public function test_a_submitter_cannot_delete_an_already_approved_innovation(string $role): void
    {
        $owner = $this->userWithRole($role);
        $innovation = InnovationModel::factory()->create(['user_id' => $owner->id, 'status' => 'published']);

        $this->actingAs($owner)
            ->delete(route('admin.innovations.mine.destroy', $innovation))
            ->assertForbidden();

        $this->assertDatabaseHas('innovations', ['id' => $innovation->id, 'deleted_at' => null]);
    }

    #[DataProvider('submitterRoles')]
    public function test_a_submitter_cannot_delete_someone_elses_innovation(string $role): void
    {
        $owner = $this->userWithRole($role);
        $other = $this->userWithRole($role);
        $innovation = InnovationModel::factory()->create(['user_id' => $other->id, 'status' => 'submitted']);

        $this->actingAs($owner)
            ->delete(route('admin.innovations.mine.destroy', $innovation))
            ->assertForbidden();

        $this->assertDatabaseHas('innovations', ['id' => $innovation->id, 'deleted_at' => null]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function submitterRoles(): array
    {
        return [
            'admin-opd' => ['admin-opd'],
            'bupati' => ['bupati'],
            'dprd' => ['dprd'],
            'asn' => ['asn'],
            'publik' => ['publik'],
        ];
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
