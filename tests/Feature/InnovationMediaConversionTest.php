<?php

namespace Tests\Feature;

use App\Models\Innovation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;
use Tests\TestCase;

class InnovationMediaConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cover_image_generates_thumb_and_medium_conversions(): void
    {
        $innovation = Innovation::factory()->create();

        $media = $innovation
            ->addMedia(UploadedFile::fake()->image('cover.jpg', 1200, 900))
            ->toMediaCollection('cover');

        $this->assertTrue($media->hasGeneratedConversion('thumb'));
        $this->assertTrue($media->hasGeneratedConversion('medium'));
        $this->assertFileExists($media->getPath('thumb'));
        $this->assertFileExists($media->getPath('medium'));
    }

    public function test_cover_collection_rejects_non_image_files(): void
    {
        $innovation = Innovation::factory()->create();

        $this->expectException(FileUnacceptableForCollection::class);

        $innovation
            ->addMedia(UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'))
            ->toMediaCollection('cover');
    }

    public function test_documents_collection_accepts_non_image_files(): void
    {
        $innovation = Innovation::factory()->create();

        $media = $innovation
            ->addMedia(UploadedFile::fake()->create('proposal.pdf', 10, 'application/pdf'))
            ->toMediaCollection('documents');

        $this->assertSame('proposal.pdf', $media->file_name);
    }
}
