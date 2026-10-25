<?php

namespace Tests\Feature\Admin;

use App\Models\InnovationCategoryModel;
use App\Models\InnovationModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InnovationCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.innovation-categories.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_opd_cannot_open_the_category_list(): void
    {
        $this->actingAs($this->userWithRole('admin-opd'))
            ->get(route('admin.innovation-categories.index'))
            ->assertForbidden();
    }

    public function test_super_admin_sees_categories_matching_the_search(): void
    {
        InnovationCategoryModel::factory()->create([
            'name' => 'Pelayanan Publik',
            'slug' => 'pelayanan-publik',
        ]);
        InnovationCategoryModel::factory()->create([
            'name' => 'Kesehatan',
            'slug' => 'kesehatan',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.innovation-categories.index', ['search' => 'Pelayanan']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/InnovationCategories/Index')
                ->has('categories.data', 1)
                ->where('categories.data.0.name', 'Pelayanan Publik'));
    }

    public function test_super_admin_creates_a_child_category_with_a_slug(): void
    {
        $parent = InnovationCategoryModel::factory()->create([
            'name' => 'Pelayanan Publik',
            'slug' => 'pelayanan-publik',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.innovation-categories.store'), [
                'name' => 'Kesehatan',
                'parent_id' => $parent->id,
                'icon' => 'pi pi-heart',
            ])
            ->assertRedirect(route('admin.innovation-categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('innovation_categories', [
            'name' => 'Kesehatan',
            'slug' => 'kesehatan',
            'parent_id' => $parent->id,
            'icon' => 'pi pi-heart',
        ]);
    }

    public function test_duplicate_name_gets_a_suffixed_slug(): void
    {
        InnovationCategoryModel::factory()->create([
            'name' => 'Kesehatan',
            'slug' => 'kesehatan',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.innovation-categories.store'), [
                'name' => 'Kesehatan',
            ])
            ->assertRedirect(route('admin.innovation-categories.index'));

        $this->assertDatabaseHas('innovation_categories', [
            'name' => 'Kesehatan',
            'slug' => 'kesehatan-2',
        ]);
    }

    public function test_empty_name_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.innovation-categories.create'))
            ->post(route('admin.innovation-categories.store'), [])
            ->assertRedirect(route('admin.innovation-categories.create'))
            ->assertSessionHasErrors([
                'name' => 'Nama kategori wajib diisi.',
            ]);
    }

    public function test_unknown_parent_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.innovation-categories.create'))
            ->post(route('admin.innovation-categories.store'), [
                'name' => 'Kesehatan',
                'parent_id' => 99999,
            ])
            ->assertSessionHasErrors([
                'parent_id' => 'Induk yang dipilih tidak ditemukan.',
            ]);
    }

    public function test_category_cannot_become_its_own_parent(): void
    {
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.innovation-categories.edit', $category))
            ->put(route('admin.innovation-categories.update', $category), [
                'name' => $category->name,
                'parent_id' => $category->id,
            ])
            ->assertSessionHasErrors([
                'parent_id' => 'Kategori tidak dapat menjadi induk dari dirinya sendiri.',
            ]);
    }

    public function test_category_cannot_use_a_descendant_as_parent(): void
    {
        $parent = InnovationCategoryModel::factory()->create(['name' => 'Induk']);
        $child = InnovationCategoryModel::factory()->create([
            'name' => 'Anak',
            'parent_id' => $parent->id,
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.innovation-categories.edit', $parent))
            ->put(route('admin.innovation-categories.update', $parent), [
                'name' => 'Induk',
                'parent_id' => $child->id,
            ])
            ->assertSessionHasErrors([
                'parent_id' => 'Induk tidak boleh berupa subkategori dari kategori ini.',
            ]);
    }

    public function test_renaming_a_category_updates_its_slug(): void
    {
        $category = InnovationCategoryModel::factory()->create([
            'name' => 'Lama',
            'slug' => 'lama',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.innovation-categories.update', $category), [
                'name' => 'Baru',
                'icon' => 'pi pi-tag',
            ])
            ->assertRedirect(route('admin.innovation-categories.index'));

        $this->assertDatabaseHas('innovation_categories', [
            'id' => $category->id,
            'name' => 'Baru',
            'slug' => 'baru',
            'icon' => 'pi pi-tag',
        ]);
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.innovation-categories.destroy', $category))
            ->assertRedirect(route('admin.innovation-categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('innovation_categories', ['id' => $category->id]);
    }

    public function test_category_with_a_child_cannot_be_deleted(): void
    {
        $parent = InnovationCategoryModel::factory()->create();
        InnovationCategoryModel::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.innovation-categories.destroy', $parent))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('innovation_categories', ['id' => $parent->id]);
    }

    public function test_category_used_by_an_innovation_cannot_be_deleted(): void
    {
        $category = InnovationCategoryModel::factory()->create();
        InnovationModel::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.innovation-categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('innovation_categories', ['id' => $category->id]);
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
