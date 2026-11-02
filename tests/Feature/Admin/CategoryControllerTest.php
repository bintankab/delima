<?php

namespace Tests\Feature\Admin;

use App\Models\CategoryModel;
use App\Models\PostModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.post-categories.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_opd_cannot_open_the_category_list(): void
    {
        $this->actingAs($this->userWithRole('admin-opd'))
            ->get(route('admin.post-categories.index'))
            ->assertForbidden();
    }

    public function test_super_admin_sees_categories_matching_the_search(): void
    {
        CategoryModel::factory()->create([
            'name' => 'Pelayanan Publik',
            'slug' => 'pelayanan-publik',
        ]);
        CategoryModel::factory()->create([
            'name' => 'Kesehatan',
            'slug' => 'kesehatan',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.post-categories.index', ['search' => 'Pelayanan']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PostCategories/A-PostCategories-Index')
                ->has('categories.data', 1)
                ->where('categories.data.0.name', 'Pelayanan Publik'));
    }

    public function test_super_admin_creates_a_child_category_with_a_slug(): void
    {
        $parent = CategoryModel::factory()->create([
            'name' => 'Pelayanan Publik',
            'slug' => 'pelayanan-publik',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.post-categories.store'), [
                'name' => 'Kesehatan',
                'parent_id' => $parent->id,
                'description' => 'Layanan kesehatan daerah.',
            ])
            ->assertRedirect(route('admin.post-categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Kesehatan',
            'slug' => 'kesehatan',
            'parent_id' => $parent->id,
            'description' => 'Layanan kesehatan daerah.',
        ]);
    }

    public function test_duplicate_name_gets_a_suffixed_slug(): void
    {
        CategoryModel::factory()->create([
            'name' => 'Kesehatan',
            'slug' => 'kesehatan',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.post-categories.store'), [
                'name' => 'Kesehatan',
            ])
            ->assertRedirect(route('admin.post-categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Kesehatan',
            'slug' => 'kesehatan-2',
        ]);
    }

    public function test_empty_name_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.post-categories.create'))
            ->post(route('admin.post-categories.store'), [])
            ->assertRedirect(route('admin.post-categories.create'))
            ->assertSessionHasErrors([
                'name' => 'Nama kategori wajib diisi.',
            ]);
    }

    public function test_category_cannot_become_its_own_parent(): void
    {
        $category = CategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.post-categories.edit', $category))
            ->put(route('admin.post-categories.update', $category), [
                'name' => $category->name,
                'parent_id' => $category->id,
            ])
            ->assertSessionHasErrors([
                'parent_id' => 'Kategori tidak dapat menjadi induk dari dirinya sendiri.',
            ]);
    }

    public function test_category_cannot_use_a_descendant_as_parent(): void
    {
        $parent = CategoryModel::factory()->create(['name' => 'Induk']);
        $child = CategoryModel::factory()->create([
            'name' => 'Anak',
            'parent_id' => $parent->id,
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.post-categories.edit', $parent))
            ->put(route('admin.post-categories.update', $parent), [
                'name' => 'Induk',
                'parent_id' => $child->id,
            ])
            ->assertSessionHasErrors([
                'parent_id' => 'Induk tidak boleh berupa subkategori dari kategori ini.',
            ]);
    }

    public function test_category_with_a_child_cannot_be_deleted(): void
    {
        $parent = CategoryModel::factory()->create();
        CategoryModel::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.post-categories.destroy', $parent))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertModelExists($parent);
    }

    public function test_deleting_a_category_detaches_it_from_posts(): void
    {
        $category = CategoryModel::factory()->create();
        $post = PostModel::factory()->create();
        $post->categories()->attach($category);

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.post-categories.destroy', $category))
            ->assertRedirect(route('admin.post-categories.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($category);
        $this->assertSame(0, $post->categories()->count());
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
