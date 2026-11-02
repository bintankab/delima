<?php

namespace Tests\Feature\Public;

use App\Models\LibraryDocumentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LibraryDocumentShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_a_public_document(): void
    {
        $document = LibraryDocumentModel::factory()->create([
            'title' => 'Perda Kesehatan',
            'slug' => 'perda-kesehatan',
            'is_public' => true,
        ]);

        $this->get(route('pustaka.show', $document->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/LibraryDocuments/Show')
                ->where('document.title', 'Perda Kesehatan')
                ->where('document.slug', 'perda-kesehatan'));
    }

    public function test_private_document_is_not_publicly_accessible(): void
    {
        $document = LibraryDocumentModel::factory()->create([
            'slug' => 'dokumen-privat',
            'is_public' => false,
        ]);

        $this->get(route('pustaka.show', $document->slug))
            ->assertNotFound();
    }

    public function test_unknown_slug_returns_not_found(): void
    {
        $this->get('/pustaka/slug-yang-tidak-ada')
            ->assertNotFound();
    }

    public function test_viewing_a_document_increments_its_views_count(): void
    {
        $document = LibraryDocumentModel::factory()->create([
            'is_public' => true,
            'views_count' => 0,
        ]);

        $this->get(route('pustaka.show', $document->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('document.views_count', 1));

        $this->assertSame(1, $document->fresh()->views_count);
    }

    public function test_repeated_views_from_the_same_visitor_are_not_counted_twice(): void
    {
        $document = LibraryDocumentModel::factory()->create([
            'is_public' => true,
            'views_count' => 0,
        ]);

        $this->get(route('pustaka.show', $document->slug))->assertOk();
        $this->get(route('pustaka.show', $document->slug))->assertOk();

        $this->assertSame(1, $document->fresh()->views_count);
    }

    public function test_show_page_includes_the_download_link_and_related_public_documents_of_the_same_type(): void
    {
        Storage::fake('public');

        $document = LibraryDocumentModel::factory()->create([
            'title' => 'Regulasi Utama',
            'type' => 'regulasi',
            'is_public' => true,
        ]);
        $document->addMedia(UploadedFile::fake()->create('regulasi.pdf', 100))->toMediaCollection('file');

        LibraryDocumentModel::factory()->create([
            'title' => 'Regulasi Lain',
            'type' => 'regulasi',
            'is_public' => true,
        ]);
        LibraryDocumentModel::factory()->create([
            'title' => 'Laporan Lain',
            'type' => 'laporan',
            'is_public' => true,
        ]);
        LibraryDocumentModel::factory()->create([
            'title' => 'Regulasi Privat',
            'type' => 'regulasi',
            'is_public' => false,
        ]);

        $this->get(route('pustaka.show', $document->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('document.file.name', 'regulasi.pdf')
                ->where('document.file.url', route('pustaka.download', $document))
                ->has('relatedDocuments', 1)
                ->where('relatedDocuments.0.title', 'Regulasi Lain'));
    }
}
