<?php

namespace Tests\Feature;

use App\Models\LibraryDocumentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryDocumentMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_stores_bibliographic_metadata_fields(): void
    {
        $document = LibraryDocumentModel::factory()->create([
            'nomor_dokumen' => 'SK-001/BTN/2023',
            'penyusun' => 'Tim Peneliti Bappeda',
            'tahun_terbit' => 2023,
            'tanggal_terbit' => '2023-05-10',
        ]);

        $this->assertSame('SK-001/BTN/2023', $document->fresh()->nomor_dokumen);
        $this->assertSame('Tim Peneliti Bappeda', $document->fresh()->penyusun);
        $this->assertSame(2023, $document->fresh()->tahun_terbit);
        $this->assertSame('2023-05-10', $document->fresh()->tanggal_terbit->toDateString());
    }

    public function test_document_can_be_tagged_and_filtered_by_tag(): void
    {
        $tagged = LibraryDocumentModel::factory()->create();
        $tagged->attachTag('Stunting');
        $tagged->attachTag('Kesehatan');

        LibraryDocumentModel::factory()->create();

        $results = LibraryDocumentModel::withAnyTags(['Stunting'])->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($tagged));
        $this->assertCount(2, $tagged->fresh()->tags);
    }
}
