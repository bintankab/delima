<?php

namespace Tests\Feature\Admin;

use App\Models\RisetCategoryModel;
use App\Models\RisetModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RisetCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.riset-categories.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_opd_cannot_open_the_category_list(): void
    {
        $this->actingAs($this->userWithRole('admin-opd'))
            ->get(route('admin.riset-categories.index'))
            ->assertForbidden();
    }

    public function test_super_admin_sees_categories_matching_the_search(): void
    {
        RisetCategoryModel::factory()->create([
            'name' => 'Kesehatan Masyarakat',
            'slug' => 'kesehatan-masyarakat',
        ]);
        RisetCategoryModel::factory()->create([
            'name' => 'Pendidikan',
            'slug' => 'pendidikan',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.riset-categories.index', ['search' => 'Kesehatan']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/RisetCategories/Index')
                ->has('categories.data', 1)
                ->where('categories.data.0.name', 'Kesehatan Masyarakat'));
    }

    public function test_super_admin_creates_a_child_category_with_a_slug(): void
    {
        $parent = RisetCategoryModel::factory()->create([
            'name' => 'Kesehatan Masyarakat',
            'slug' => 'kesehatan-masyarakat',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.riset-categories.store'), [
                'name' => 'Gizi',
                'parent_id' => $parent->id,
                'icon' => 'pi pi-heart',
            ])
            ->assertRedirect(route('admin.riset-categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('riset_categories', [
            'name' => 'Gizi',
            'slug' => 'gizi',
            'parent_id' => $parent->id,
            'icon' => 'pi pi-heart',
        ]);
    }

    public function test_duplicate_name_gets_a_suffixed_slug(): void
    {
        RisetCategoryModel::factory()->create([
            'name' => 'Gizi',
            'slug' => 'gizi',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.riset-categories.store'), [
                'name' => 'Gizi',
            ])
            ->assertRedirect(route('admin.riset-categories.index'));

        $this->assertDatabaseHas('riset_categories', [
            'name' => 'Gizi',
            'slug' => 'gizi-2',
        ]);
    }

    public function test_empty_name_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.riset-categories.create'))
            ->post(route('admin.riset-categories.store'), [])
            ->assertRedirect(route('admin.riset-categories.create'))
            ->assertSessionHasErrors([
                'name' => 'Nama kategori wajib diisi.',
            ]);
    }

    public function test_unknown_parent_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.riset-categories.create'))
            ->post(route('admin.riset-categories.store'), [
                'name' => 'Gizi',
                'parent_id' => 99999,
            ])
            ->assertSessionHasErrors([
                'parent_id' => 'Induk yang dipilih tidak ditemukan.',
            ]);
    }

    public function test_category_cannot_become_its_own_parent(): void
    {
        $category = RisetCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.riset-categories.edit', $category))
            ->put(route('admin.riset-categories.update', $category), [
                'name' => $category->name,
                'parent_id' => $category->id,
            ])
            ->assertSessionHasErrors([
                'parent_id' => 'Kategori tidak dapat menjadi induk dari dirinya sendiri.',
            ]);
    }

    public function test_category_cannot_use_a_descendant_as_parent(): void
    {
        $parent = RisetCategoryModel::factory()->create(['name' => 'Induk']);
        $child = RisetCategoryModel::factory()->create([
            'name' => 'Anak',
            'parent_id' => $parent->id,
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.riset-categories.edit', $parent))
            ->put(route('admin.riset-categories.update', $parent), [
                'name' => 'Induk',
                'parent_id' => $child->id,
            ])
            ->assertSessionHasErrors([
                'parent_id' => 'Induk tidak boleh berupa subkategori dari kategori ini.',
            ]);
    }

    public function test_renaming_a_category_updates_its_slug(): void
    {
        $category = RisetCategoryModel::factory()->create([
            'name' => 'Lama',
            'slug' => 'lama',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.riset-categories.update', $category), [
                'name' => 'Baru',
                'icon' => 'pi pi-tag',
            ])
            ->assertRedirect(route('admin.riset-categories.index'));

        $this->assertDatabaseHas('riset_categories', [
            'id' => $category->id,
            'name' => 'Baru',
            'slug' => 'baru',
            'icon' => 'pi pi-tag',
        ]);
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $category = RisetCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.riset-categories.destroy', $category))
            ->assertRedirect(route('admin.riset-categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('riset_categories', ['id' => $category->id]);
    }

    public function test_category_with_a_child_cannot_be_deleted(): void
    {
        $parent = RisetCategoryModel::factory()->create();
        RisetCategoryModel::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.riset-categories.destroy', $parent))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('riset_categories', ['id' => $parent->id]);
    }

    public function test_category_used_by_a_riset_cannot_be_deleted(): void
    {
        $category = RisetCategoryModel::factory()->create();
        RisetModel::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.riset-categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('riset_categories', ['id' => $category->id]);
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
