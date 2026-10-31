<?php

namespace Tests\Feature\Public;

use App\Models\LibraryDocumentModel;
use App\Models\OrganisasiModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LibraryDocumentIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_public_pustaka_page(): void
    {
        $this->get(route('pustaka'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/LibraryDocuments/Index'));
    }

    public function test_only_public_documents_are_listed(): void
    {
        LibraryDocumentModel::factory()->create(['title' => 'Dokumen Publik', 'is_public' => true]);
        LibraryDocumentModel::factory()->create(['title' => 'Dokumen Privat', 'is_public' => false]);

        $this->get(route('pustaka'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents.data', 1)
                ->where('documents.data.0.title', 'Dokumen Publik'));
    }

    public function test_documents_can_be_filtered_by_title(): void
    {
        LibraryDocumentModel::factory()->create(['title' => 'Perda Kesehatan', 'is_public' => true]);
        LibraryDocumentModel::factory()->create(['title' => 'Perda Pendidikan', 'is_public' => true]);

        $this->get(route('pustaka', ['search' => 'Kesehatan']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents.data', 1)
                ->where('documents.data.0.title', 'Perda Kesehatan'));
    }

    public function test_documents_can_be_filtered_by_type(): void
    {
        LibraryDocumentModel::factory()->create(['title' => 'Regulasi A', 'type' => 'regulasi', 'is_public' => true]);
        LibraryDocumentModel::factory()->create(['title' => 'Laporan B', 'type' => 'laporan', 'is_public' => true]);

        $this->get(route('pustaka', ['type' => 'regulasi']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents.data', 1)
                ->where('documents.data.0.title', 'Regulasi A'));
    }

    public function test_documents_can_be_filtered_by_tahun_terbit(): void
    {
        LibraryDocumentModel::factory()->create(['title' => 'Dokumen 2022', 'tahun_terbit' => 2022, 'is_public' => true]);
        LibraryDocumentModel::factory()->create(['title' => 'Dokumen 2023', 'tahun_terbit' => 2023, 'is_public' => true]);

        $this->get(route('pustaka', ['tahun' => 2023]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents.data', 1)
                ->where('documents.data.0.title', 'Dokumen 2023'));
    }

    public function test_documents_can_be_filtered_by_organisasi(): void
    {
        $organisasiA = OrganisasiModel::factory()->create(['name' => 'Dinas A']);
        $organisasiB = OrganisasiModel::factory()->create(['name' => 'Dinas B']);
        LibraryDocumentModel::factory()->create([
            'title' => 'Dokumen Dinas A',
            'organisasi_id' => $organisasiA->id,
            'is_public' => true,
        ]);
        LibraryDocumentModel::factory()->create([
            'title' => 'Dokumen Dinas B',
            'organisasi_id' => $organisasiB->id,
            'is_public' => true,
        ]);

        $this->get(route('pustaka', ['organisasi_id' => $organisasiA->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents.data', 1)
                ->where('documents.data.0.title', 'Dokumen Dinas A'));
    }

    public function test_filter_options_only_include_public_years_and_organisasis(): void
    {
        $organisasiPublic = OrganisasiModel::factory()->create(['name' => 'Dinas Publik']);
        $organisasiPrivate = OrganisasiModel::factory()->create(['name' => 'Dinas Privat']);

        LibraryDocumentModel::factory()->create([
            'is_public' => true,
            'tahun_terbit' => 2023,
            'organisasi_id' => $organisasiPublic->id,
        ]);
        LibraryDocumentModel::factory()->create([
            'is_public' => false,
            'tahun_terbit' => 2020,
            'organisasi_id' => $organisasiPrivate->id,
        ]);

        $this->get(route('pustaka'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filterOptions.years', [2023])
                ->has('filterOptions.organisasis', 1)
                ->where('filterOptions.organisasis.0.name', 'Dinas Publik'));
    }

    public function test_downloading_a_public_document_redirects_to_the_file_and_counts_the_download(): void
    {
        Storage::fake('public');

        $document = LibraryDocumentModel::factory()->create(['is_public' => true, 'downloads_count' => 0]);
        $document->addMedia(UploadedFile::fake()->create('perda.pdf', 100))->toMediaCollection('file');

        $response = $this->get(route('pustaka.download', $document));

        $response->assertRedirect();
        $this->assertStringContainsString('/storage/', $response->headers->get('Location'));
        $this->assertSame(1, $document->fresh()->downloads_count);
    }

    public function test_a_private_document_cannot_be_downloaded(): void
    {
        Storage::fake('public');

        $document = LibraryDocumentModel::factory()->create(['is_public' => false]);
        $document->addMedia(UploadedFile::fake()->create('internal.pdf', 100))->toMediaCollection('file');

        $this->get(route('pustaka.download', $document))->assertNotFound();
        $this->assertSame(0, $document->fresh()->downloads_count);
    }

    public function test_a_document_without_an_uploaded_file_cannot_be_downloaded(): void
    {
        $document = LibraryDocumentModel::factory()->create(['is_public' => true]);

        $this->get(route('pustaka.download', $document))->assertNotFound();
    }
}
