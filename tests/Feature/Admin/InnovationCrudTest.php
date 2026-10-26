<?php

namespace Tests\Feature\Admin;

use App\Models\InnovationCategoryModel;
use App\Models\InnovationModel;
use App\Models\OrganisasiModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InnovationCrudTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('listingRoles')]
    public function test_listing_roles_can_view_an_innovation(string $role): void
    {
        $innovation = InnovationModel::factory()->create();

        $this->actingAs($this->userWithRole($role))
            ->get(route('admin.innovations.show', $innovation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Innovations/Show')
                ->where('innovation.id', $innovation->id));
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_role_cannot_open_the_edit_form(string $role): void
    {
        $innovation = InnovationModel::factory()->create();

        $this->actingAs($this->userWithRole($role))
            ->get(route('admin.innovations.edit', $innovation))
            ->assertForbidden();
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_role_cannot_update_an_innovation(string $role): void
    {
        $innovation = InnovationModel::factory()->create(['title' => 'Judul Asli']);

        $this->actingAs($this->userWithRole($role))
            ->put(route('admin.innovations.update', $innovation), [])
            ->assertForbidden();

        $this->assertSame('Judul Asli', $innovation->fresh()->title);
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_role_cannot_delete_an_innovation(string $role): void
    {
        $innovation = InnovationModel::factory()->create();

        $this->actingAs($this->userWithRole($role))
            ->delete(route('admin.innovations.destroy', $innovation))
            ->assertForbidden();

        $this->assertNotNull(InnovationModel::query()->find($innovation->id));
    }

    public function test_super_admin_updates_innovation_fields(): void
    {
        $innovation = InnovationModel::factory()->create(['title' => 'Judul Lama']);
        $organisasi = OrganisasiModel::factory()->create();
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.innovations.update', $innovation), [
                'organisasi_id' => $organisasi->id,
                'category_id' => $category->id,
                'title' => 'Judul Baru',
                'jenis_inovasi' => 'proses',
                'description' => 'Deskripsi baru.',
                'tahun' => 2022,
                'published_at' => '2022-01-10',
            ])
            ->assertRedirect(route('admin.innovations.show', $innovation))
            ->assertSessionHas('success', 'Inovasi berhasil diperbarui.');

        $innovation->refresh();

        $this->assertSame('Judul Baru', $innovation->title);
        $this->assertSame('judul-baru', $innovation->slug);
        $this->assertSame('proses', $innovation->jenis_inovasi);
        $this->assertSame($organisasi->id, $innovation->organisasi_id);
        $this->assertSame($category->id, $innovation->category_id);
    }

    public function test_super_admin_can_replace_cover_and_manage_documents(): void
    {
        Storage::fake('public');

        $innovation = InnovationModel::factory()->create([
            'category_id' => InnovationCategoryModel::factory()->create()->id,
            'published_at' => '2021-06-01',
        ]);
        $innovation->addMedia(UploadedFile::fake()->image('old-cover.jpg'))->toMediaCollection('cover');
        $innovation->addMedia(UploadedFile::fake()->createWithContent(
            'old-doc.pdf',
            "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF"
        ))->toMediaCollection('documents');

        $oldDocumentId = $innovation->getFirstMedia('documents')->id;

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.innovations.update', $innovation), [
                'organisasi_id' => $innovation->organisasi_id,
                'category_id' => $innovation->category_id,
                'title' => $innovation->title,
                'jenis_inovasi' => $innovation->jenis_inovasi,
                'description' => $innovation->description,
                'tahun' => $innovation->tahun,
                'published_at' => $innovation->published_at->toDateString(),
                'cover' => UploadedFile::fake()->image('new-cover.jpg'),
                'remove_document_ids' => [$oldDocumentId],
                'documents' => [
                    UploadedFile::fake()->createWithContent(
                        'new-doc.pdf',
                        "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF"
                    ),
                ],
            ])
            ->assertRedirect(route('admin.innovations.show', $innovation));

        $innovation->refresh();

        $this->assertCount(1, $innovation->getMedia('cover'));
        $this->assertSame('new-cover.jpg', $innovation->getFirstMedia('cover')->file_name);

        $documents = $innovation->getMedia('documents');
        $this->assertCount(1, $documents);
        $this->assertSame('new-doc.pdf', $documents->first()->file_name);
    }

    public function test_super_admin_deletes_an_innovation(): void
    {
        $innovation = InnovationModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.innovations.destroy', $innovation))
            ->assertRedirect(route('admin.innovations.index'))
            ->assertSessionHas('success', 'Inovasi berhasil dihapus.');

        $this->assertSoftDeleted('innovations', ['id' => $innovation->id]);
    }

    public function test_soft_deleting_an_innovation_keeps_its_media(): void
    {
        Storage::fake('public');

        $innovation = InnovationModel::factory()->create();
        $innovation->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.innovations.destroy', $innovation));

        $this->assertSoftDeleted('innovations', ['id' => $innovation->id]);
        $this->assertDatabaseCount('media', 1);
        Storage::disk('public')->assertExists($innovation->getFirstMedia('cover')->getPathRelativeToRoot());
    }

    public function test_trashed_filter_only_shows_soft_deleted_innovations(): void
    {
        $active = InnovationModel::factory()->create(['title' => 'Inovasi Aktif']);
        $trashed = InnovationModel::factory()->create(['title' => 'Inovasi Terhapus']);
        $trashed->delete();

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.innovations.index', ['trashed' => true]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.trashed', true)
                ->has('innovations.data', 1)
                ->where('innovations.data.0.title', 'Inovasi Terhapus'));

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.innovations.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('innovations.data', 1)
                ->where('innovations.data.0.title', 'Inovasi Aktif'));
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_role_cannot_force_delete_an_innovation(string $role): void
    {
        $innovation = InnovationModel::factory()->create();
        $innovation->delete();

        $this->actingAs($this->userWithRole($role))
            ->delete(route('admin.innovations.force-destroy', $innovation))
            ->assertForbidden();

        $this->assertSoftDeleted('innovations', ['id' => $innovation->id]);
    }

    public function test_force_delete_requires_the_innovation_to_be_trashed_first(): void
    {
        $innovation = InnovationModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.innovations.force-destroy', $innovation))
            ->assertRedirect()
            ->assertSessionHas('error', 'Inovasi harus dihapus (ke sampah) terlebih dahulu sebelum dihapus permanen.');

        $this->assertDatabaseHas('innovations', ['id' => $innovation->id, 'deleted_at' => null]);
    }

    public function test_super_admin_permanently_deletes_a_trashed_innovation_with_its_media(): void
    {
        Storage::fake('public');

        $innovation = InnovationModel::factory()->create();
        $innovation->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');
        $mediaPath = $innovation->getFirstMedia('cover')->getPathRelativeToRoot();
        $innovation->delete();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.innovations.force-destroy', $innovation))
            ->assertRedirect(route('admin.innovations.index', ['trashed' => true]))
            ->assertSessionHas('success', 'Inovasi beserta berkasnya berhasil dihapus permanen.');

        $this->assertDatabaseMissing('innovations', ['id' => $innovation->id]);
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
