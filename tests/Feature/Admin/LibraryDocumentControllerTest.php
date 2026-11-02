<?php

namespace Tests\Feature\Admin;

use App\Models\LibraryDocumentModel;
use App\Models\OrganisasiModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LibraryDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.library-documents.index'))
            ->assertRedirect(route('login'));
    }

    public function test_reviewer_cannot_open_the_document_list(): void
    {
        $this->actingAs($this->userWithRole('reviewer'))
            ->get(route('admin.library-documents.index'))
            ->assertForbidden();
    }

    public function test_super_admin_sees_documents_from_every_organisasi(): void
    {
        $organisasiA = OrganisasiModel::factory()->create();
        $organisasiB = OrganisasiModel::factory()->create();
        LibraryDocumentModel::factory()->create(['title' => 'Dokumen A', 'organisasi_id' => $organisasiA->id]);
        LibraryDocumentModel::factory()->create(['title' => 'Dokumen B', 'organisasi_id' => $organisasiB->id]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.library-documents.index'))
            ->assertInertia(fn (Assert $page) => $page->has('documents.data', 2));
    }

    public function test_admin_opd_cannot_open_the_document_list(): void
    {
        $this->actingAs($this->userWithRole('admin-opd'))
            ->get(route('admin.library-documents.index'))
            ->assertForbidden();
    }

    public function test_super_admin_creates_a_document_with_a_file(): void
    {
        Storage::fake('public');
        $organisasi = OrganisasiModel::factory()->create();
        $admin = $this->userWithRole('super-admin');

        $this->actingAs($admin)
            ->post(route('admin.library-documents.store'), [
                'title' => 'Peraturan Daerah No. 1',
                'type' => 'regulasi',
                'organisasi_id' => $organisasi->id,
                'is_public' => true,
                'file' => UploadedFile::fake()->create('perda.pdf', 100),
            ])
            ->assertRedirect(route('admin.library-documents.index'))
            ->assertSessionHas('success');

        $document = LibraryDocumentModel::where('title', 'Peraturan Daerah No. 1')->first();

        $this->assertNotNull($document);
        $this->assertSame('peraturan-daerah-no-1', $document->slug);
        $this->assertSame($organisasi->id, $document->organisasi_id);
        $this->assertSame($admin->id, $document->uploaded_by);
        $this->assertNotNull($document->getFirstMedia('file'));
    }

    public function test_admin_opd_cannot_create_a_document(): void
    {
        Storage::fake('public');
        $organisasi = OrganisasiModel::factory()->create();
        $admin = $this->userWithRole('admin-opd');
        $admin->organisasis()->sync([$organisasi->id => ['is_primary' => true]]);

        $this->actingAs($admin)
            ->get(route('admin.library-documents.create'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.library-documents.store'), [
                'title' => 'Laporan BPS 2024',
                'type' => 'laporan',
                'is_public' => true,
                'file' => UploadedFile::fake()->create('laporan.pdf', 100),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('library_documents', ['title' => 'Laporan BPS 2024']);
    }

    public function test_admin_opd_cannot_edit_or_delete_a_document_even_from_their_own_organisasi(): void
    {
        $organisasiMine = OrganisasiModel::factory()->create();
        $document = LibraryDocumentModel::factory()->create(['organisasi_id' => $organisasiMine->id]);

        $admin = $this->userWithRole('admin-opd');
        $admin->organisasis()->sync([$organisasiMine->id => ['is_primary' => true]]);

        $this->actingAs($admin)
            ->get(route('admin.library-documents.edit', $document))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.library-documents.update', $document), ['title' => 'Diubah'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.library-documents.destroy', $document))
            ->assertForbidden();

        $this->assertDatabaseHas('library_documents', ['id' => $document->id, 'deleted_at' => null]);
    }

    public function test_empty_title_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.library-documents.create'))
            ->post(route('admin.library-documents.store'), [])
            ->assertRedirect(route('admin.library-documents.create'))
            ->assertSessionHasErrors(['title', 'type', 'file']);
    }

    public function test_updating_without_a_new_file_keeps_the_existing_one(): void
    {
        Storage::fake('public');
        $document = LibraryDocumentModel::factory()->create(['title' => 'Lama']);
        $document->addMedia(UploadedFile::fake()->create('lama.pdf', 50))->toMediaCollection('file');
        $originalMediaId = $document->getFirstMedia('file')->id;

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.library-documents.update', $document), [
                'title' => 'Baru',
                'type' => 'laporan',
                'is_public' => true,
            ])
            ->assertRedirect(route('admin.library-documents.index'));

        $document->refresh();

        $this->assertSame('Baru', $document->title);
        $this->assertSame('baru', $document->slug);
        $this->assertSame($originalMediaId, $document->getFirstMedia('file')->id);
    }

    public function test_updating_without_changing_the_title_keeps_the_slug(): void
    {
        $document = LibraryDocumentModel::factory()->create([
            'title' => 'Lama',
            'slug' => 'slug-lama',
            'type' => 'laporan',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.library-documents.update', $document), [
                'title' => 'Lama',
                'type' => 'panduan',
                'is_public' => true,
            ])
            ->assertRedirect(route('admin.library-documents.index'));

        $document->refresh();

        $this->assertSame('slug-lama', $document->slug);
        $this->assertSame('panduan', $document->type);
    }

    public function test_a_duplicate_title_gets_a_unique_slug(): void
    {
        Storage::fake('public');
        $admin = $this->userWithRole('super-admin');
        $payload = [
            'title' => 'Panduan yang Sama',
            'type' => 'panduan',
            'is_public' => true,
            'file' => UploadedFile::fake()->create('panduan.pdf', 100),
        ];

        $this->actingAs($admin)
            ->post(route('admin.library-documents.store'), $payload)
            ->assertRedirect(route('admin.library-documents.index'));

        $this->actingAs($admin)
            ->post(route('admin.library-documents.store'), [
                ...$payload,
                'file' => UploadedFile::fake()->create('panduan-2.pdf', 100),
            ])
            ->assertRedirect(route('admin.library-documents.index'));

        $this->assertDatabaseHas('library_documents', ['title' => 'Panduan yang Sama', 'slug' => 'panduan-yang-sama']);
        $this->assertDatabaseHas('library_documents', ['title' => 'Panduan yang Sama', 'slug' => 'panduan-yang-sama-2']);
    }

    public function test_document_can_be_deleted(): void
    {
        $document = LibraryDocumentModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.library-documents.destroy', $document))
            ->assertRedirect(route('admin.library-documents.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('library_documents', ['id' => $document->id]);
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
