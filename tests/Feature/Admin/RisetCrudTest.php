<?php

namespace Tests\Feature\Admin;

use App\Models\OrganisasiModel;
use App\Models\RisetCategoryModel;
use App\Models\RisetModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RisetCrudTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('listingRoles')]
    public function test_listing_roles_can_view_a_riset(string $role): void
    {
        $riset = RisetModel::factory()->create();

        $this->actingAs($this->userWithRole($role))
            ->get(route('admin.risets.show', $riset))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Risets/Show')
                ->where('riset.id', $riset->id));
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_role_cannot_open_the_edit_form(string $role): void
    {
        $riset = RisetModel::factory()->create();

        $this->actingAs($this->userWithRole($role))
            ->get(route('admin.risets.edit', $riset))
            ->assertForbidden();
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_role_cannot_update_a_riset(string $role): void
    {
        $riset = RisetModel::factory()->create(['title' => 'Judul Asli']);

        $this->actingAs($this->userWithRole($role))
            ->put(route('admin.risets.update', $riset), [])
            ->assertForbidden();

        $this->assertSame('Judul Asli', $riset->fresh()->title);
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_role_cannot_delete_a_riset(string $role): void
    {
        $riset = RisetModel::factory()->create();

        $this->actingAs($this->userWithRole($role))
            ->delete(route('admin.risets.destroy', $riset))
            ->assertForbidden();

        $this->assertNotNull(RisetModel::query()->find($riset->id));
    }

    public function test_super_admin_updates_riset_fields(): void
    {
        $riset = RisetModel::factory()->create(['title' => 'Judul Lama']);
        $organisasi = OrganisasiModel::factory()->create();
        $category = RisetCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.risets.update', $riset), [
                'organisasi_id' => $organisasi->id,
                'category_id' => $category->id,
                'title' => 'Judul Baru',
                'peneliti' => 'Dr. Baru',
                'abstrak' => 'Abstrak baru.',
                'tahun' => 2022,
                'published_at' => '2022-01-10',
            ])
            ->assertRedirect(route('admin.risets.show', $riset))
            ->assertSessionHas('success', 'Riset berhasil diperbarui.');

        $riset->refresh();

        $this->assertSame('Judul Baru', $riset->title);
        $this->assertSame('judul-baru', $riset->slug);
        $this->assertSame('Dr. Baru', $riset->peneliti);
        $this->assertSame($organisasi->id, $riset->organisasi_id);
        $this->assertSame($category->id, $riset->category_id);
    }

    public function test_super_admin_can_replace_cover_and_manage_documents(): void
    {
        Storage::fake('public');

        $riset = RisetModel::factory()->create([
            'category_id' => RisetCategoryModel::factory()->create()->id,
            'published_at' => '2021-06-01',
        ]);
        $riset->addMedia(UploadedFile::fake()->image('old-cover.jpg'))->toMediaCollection('cover');
        $riset->addMedia(UploadedFile::fake()->createWithContent(
            'old-doc.pdf',
            "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF"
        ))->toMediaCollection('documents');

        $oldDocumentId = $riset->getFirstMedia('documents')->id;

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.risets.update', $riset), [
                'organisasi_id' => $riset->organisasi_id,
                'category_id' => $riset->category_id,
                'title' => $riset->title,
                'peneliti' => $riset->peneliti,
                'abstrak' => $riset->abstrak,
                'tahun' => $riset->tahun,
                'published_at' => $riset->published_at->toDateString(),
                'cover' => UploadedFile::fake()->image('new-cover.jpg'),
                'remove_document_ids' => [$oldDocumentId],
                'documents' => [
                    UploadedFile::fake()->createWithContent(
                        'new-doc.pdf',
                        "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF"
                    ),
                ],
            ])
            ->assertRedirect(route('admin.risets.show', $riset));

        $riset->refresh();

        $this->assertCount(1, $riset->getMedia('cover'));
        $this->assertSame('new-cover.jpg', $riset->getFirstMedia('cover')->file_name);

        $documents = $riset->getMedia('documents');
        $this->assertCount(1, $documents);
        $this->assertSame('new-doc.pdf', $documents->first()->file_name);
    }

    public function test_super_admin_deletes_a_riset(): void
    {
        $riset = RisetModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.risets.destroy', $riset))
            ->assertRedirect(route('admin.risets.index'))
            ->assertSessionHas('success', 'Riset berhasil dihapus.');

        $this->assertSoftDeleted('risets', ['id' => $riset->id]);
    }

    public function test_soft_deleting_a_riset_keeps_its_media(): void
    {
        Storage::fake('public');

        $riset = RisetModel::factory()->create();
        $riset->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.risets.destroy', $riset));

        $this->assertSoftDeleted('risets', ['id' => $riset->id]);
        $this->assertDatabaseCount('media', 1);
        Storage::disk('public')->assertExists($riset->getFirstMedia('cover')->getPathRelativeToRoot());
    }

    public function test_trashed_filter_only_shows_soft_deleted_risets(): void
    {
        $active = RisetModel::factory()->create(['title' => 'Riset Aktif']);
        $trashed = RisetModel::factory()->create(['title' => 'Riset Terhapus']);
        $trashed->delete();

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.risets.index', ['trashed' => true]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.trashed', true)
                ->has('risets.data', 1)
                ->where('risets.data.0.title', 'Riset Terhapus'));

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.risets.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('risets.data', 1)
                ->where('risets.data.0.title', 'Riset Aktif'));
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_role_cannot_force_delete_a_riset(string $role): void
    {
        $riset = RisetModel::factory()->create();
        $riset->delete();

        $this->actingAs($this->userWithRole($role))
            ->delete(route('admin.risets.force-destroy', $riset))
            ->assertForbidden();

        $this->assertSoftDeleted('risets', ['id' => $riset->id]);
    }

    public function test_force_delete_requires_the_riset_to_be_trashed_first(): void
    {
        $riset = RisetModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.risets.force-destroy', $riset))
            ->assertRedirect()
            ->assertSessionHas('error', 'Riset harus dihapus (ke sampah) terlebih dahulu sebelum dihapus permanen.');

        $this->assertDatabaseHas('risets', ['id' => $riset->id, 'deleted_at' => null]);
    }

    public function test_super_admin_permanently_deletes_a_trashed_riset_with_its_media(): void
    {
        Storage::fake('public');

        $riset = RisetModel::factory()->create();
        $riset->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');
        $mediaPath = $riset->getFirstMedia('cover')->getPathRelativeToRoot();
        $riset->delete();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.risets.force-destroy', $riset))
            ->assertRedirect(route('admin.risets.index', ['trashed' => true]))
            ->assertSessionHas('success', 'Riset beserta berkasnya berhasil dihapus permanen.');

        $this->assertDatabaseMissing('risets', ['id' => $riset->id]);
        $this->assertDatabaseCount('media', 0);
        Storage::disk('public')->assertMissing($mediaPath);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function listingRoles(): array
    {
        return [
            'super admin' => ['super-admin'],
            'admin opd' => ['admin-opd'],
            'reviewer' => ['reviewer'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unprivilegedRoles(): array
    {
        return [
            'admin opd' => ['admin-opd'],
            'reviewer' => ['reviewer'],
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
