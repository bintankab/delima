<?php

namespace Tests\Feature\Admin;

use App\Models\MenuItemModel;
use App\Models\MenuModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MenuControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.menus.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_opd_cannot_open_the_menu_list(): void
    {
        $this->actingAs($this->userWithRole('admin-opd'))
            ->get(route('admin.menus.index'))
            ->assertForbidden();
    }

    public function test_super_admin_sees_the_default_header_menu(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Menus/A-Menus-Index')
                ->has('items', 6)
                ->where('items.0.label', 'Beranda')
                ->where('items.0.url', '/')
                ->where('items.2.label', 'Riset'));
    }

    public function test_super_admin_can_add_a_child_item(): void
    {
        $menu = MenuModel::factory()->create();
        $parent = MenuItemModel::factory()->create([
            'menu_id' => $menu->id,
            'label' => 'Riset',
            'url' => '/riset',
            'sort_order' => 0,
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.menus.store'), [
                'label' => 'Dokumen',
                'url' => '/riset/dokumen',
                'parent_id' => $parent->id,
                'opens_in_new_tab' => false,
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success');

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.menus.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('items', 1)
                ->where('items.0.label', 'Riset')
                ->has('items.0.children', 1)
                ->where('items.0.children.0.label', 'Dokumen')
                ->where('items.0.children.0.url', '/riset/dokumen'));
    }

    public function test_an_external_address_can_open_in_a_new_tab(): void
    {
        $menu = MenuModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.menus.store'), [
                'label' => 'Bintan',
                'url' => 'https://bintankab.go.id',
                'opens_in_new_tab' => true,
            ])
            ->assertRedirect(route('admin.menus.index'));

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'label' => 'Bintan',
            'url' => 'https://bintankab.go.id',
            'opens_in_new_tab' => true,
            'sort_order' => 0,
        ]);
    }

    public function test_empty_label_and_address_are_rejected(): void
    {
        MenuModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.menus.create'))
            ->post(route('admin.menus.store'), [])
            ->assertRedirect(route('admin.menus.create'))
            ->assertSessionHasErrors([
                'label' => 'Label menu wajib diisi.',
                'url' => 'Alamat tautan wajib diisi.',
            ]);
    }

    public function test_a_javascript_address_is_rejected(): void
    {
        MenuModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.menus.create'))
            ->post(route('admin.menus.store'), [
                'label' => 'Berbahaya',
                'url' => 'javascript:alert(1)',
            ])
            ->assertRedirect(route('admin.menus.create'))
            ->assertSessionHasErrors([
                'url' => 'Alamat tautan harus diawali garis miring atau memakai http/https.',
            ]);

        $this->assertDatabaseCount('menu_items', 0);
    }

    public function test_a_submenu_cannot_contain_another_submenu(): void
    {
        $menu = MenuModel::factory()->create();
        $parent = MenuItemModel::factory()->create(['menu_id' => $menu->id]);
        $child = MenuItemModel::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $parent->id,
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.menus.create'))
            ->post(route('admin.menus.store'), [
                'label' => 'Terlalu dalam',
                'url' => '/dalam',
                'parent_id' => $child->id,
            ])
            ->assertRedirect(route('admin.menus.create'))
            ->assertSessionHasErrors([
                'parent_id' => 'Induk menu tidak valid.',
            ]);
    }

    public function test_an_item_with_children_cannot_become_a_submenu(): void
    {
        $menu = MenuModel::factory()->create();
        $parent = MenuItemModel::factory()->create([
            'menu_id' => $menu->id,
            'label' => 'Riset',
            'url' => '/riset',
        ]);
        MenuItemModel::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $parent->id,
        ]);
        $other = MenuItemModel::factory()->create([
            'menu_id' => $menu->id,
            'label' => 'Layanan',
            'url' => '/layanan',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.menus.edit', $parent))
            ->put(route('admin.menus.update', $parent), [
                'label' => 'Riset',
                'url' => '/riset',
                'parent_id' => $other->id,
            ])
            ->assertRedirect(route('admin.menus.edit', $parent))
            ->assertSessionHasErrors([
                'parent_id' => 'Item yang punya submenu tidak bisa dijadikan submenu.',
            ]);
    }

    public function test_super_admin_can_rename_an_item(): void
    {
        $menu = MenuModel::factory()->create();
        $item = MenuItemModel::factory()->create([
            'menu_id' => $menu->id,
            'label' => 'Lama',
            'url' => '/lama',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.menus.update', $item), [
                'label' => 'Baru',
                'url' => '/baru',
                'opens_in_new_tab' => false,
            ])
            ->assertRedirect(route('admin.menus.index'));

        $this->assertSame('Baru', $item->fresh()->label);
        $this->assertSame('/baru', $item->fresh()->url);
    }

    public function test_deleting_a_parent_promotes_its_children(): void
    {
        $menu = MenuModel::factory()->create();
        $parent = MenuItemModel::factory()->create(['menu_id' => $menu->id, 'sort_order' => 0]);
        $child = MenuItemModel::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $parent->id,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.menus.destroy', $parent))
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($parent);
        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_an_item_from_another_menu_cannot_be_deleted(): void
    {
        $other = MenuModel::factory()->create([
            'name' => 'Footer',
            'location' => 'footer',
        ]);
        $item = MenuItemModel::factory()->create(['menu_id' => $other->id]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.menus.destroy', $item))
            ->assertNotFound();

        $this->assertModelExists($item);
    }

    public function test_moving_an_item_down_swaps_its_place(): void
    {
        $menu = MenuModel::factory()->create();
        $first = MenuItemModel::factory()->create([
            'menu_id' => $menu->id,
            'label' => 'Pertama',
            'sort_order' => 0,
        ]);
        $second = MenuItemModel::factory()->create([
            'menu_id' => $menu->id,
            'label' => 'Kedua',
            'sort_order' => 1,
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.menus.move-down', $first))
            ->assertRedirect();

        $this->assertSame(1, $first->fresh()->sort_order);
        $this->assertSame(0, $second->fresh()->sort_order);
    }

    public function test_moving_the_first_item_up_leaves_the_order_unchanged(): void
    {
        $menu = MenuModel::factory()->create();
        $first = MenuItemModel::factory()->create([
            'menu_id' => $menu->id,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.menus.move-up', $first))
            ->assertRedirect();

        $this->assertSame(0, $first->fresh()->sort_order);
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
