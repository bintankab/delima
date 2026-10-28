<?php

namespace Tests\Feature\Public;

use App\Models\RisetModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RisetShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_a_published_riset(): void
    {
        $riset = RisetModel::factory()->create([
            'title' => 'Kajian Pesisir Bintan',
            'status' => 'published',
        ]);

        $this->get(route('riset.show', $riset->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Riset/Show')
                ->where('riset.title', 'Kajian Pesisir Bintan'));
    }

    public function test_draft_riset_is_not_publicly_accessible(): void
    {
        $riset = RisetModel::factory()->create(['status' => 'draft']);

        $this->get(route('riset.show', $riset->slug))
            ->assertNotFound();
    }

    public function test_unknown_slug_returns_not_found(): void
    {
        $this->get('/riset/slug-yang-tidak-ada')
            ->assertNotFound();
    }

    public function test_viewing_a_riset_increments_its_views_count(): void
    {
        $riset = RisetModel::factory()->create(['status' => 'published', 'views_count' => 0]);

        $this->get(route('riset.show', $riset->slug))->assertOk();

        $this->assertSame(1, $riset->fresh()->views_count);
    }

    public function test_repeated_views_from_the_same_visitor_are_not_counted_twice(): void
    {
        $riset = RisetModel::factory()->create(['status' => 'published', 'views_count' => 0]);

        $this->get(route('riset.show', $riset->slug))->assertOk();
        $this->get(route('riset.show', $riset->slug))->assertOk();

        $this->assertSame(1, $riset->fresh()->views_count);
    }

    public function test_documents_can_be_downloaded(): void
    {
        Storage::fake('public');

        $riset = RisetModel::factory()->create(['status' => 'published']);
        $riset->addMedia(UploadedFile::fake()->create('laporan.pdf', 100))
            ->toMediaCollection('documents');

        $response = $this->get(route('riset.show', $riset->slug));
        $documents = $response->viewData('page')['props']['riset']['documents'];

        $this->assertCount(1, $documents);
        $this->assertSame('laporan.pdf', $documents[0]['name']);
        $this->assertStringContainsString('/storage/', $documents[0]['url']);
    }
}
