<?php

namespace Tests\Feature\Public;

use App\Models\InnovationModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InnovationShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_a_published_innovation(): void
    {
        $innovation = InnovationModel::factory()->create([
            'title' => 'Aplikasi Posyandu Digital',
            'status' => 'published',
        ]);

        $this->get(route('inovasi.show', $innovation->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Innovations/Show')
                ->where('innovation.title', 'Aplikasi Posyandu Digital'));
    }

    public function test_draft_innovation_is_not_publicly_accessible(): void
    {
        $innovation = InnovationModel::factory()->create(['status' => 'draft']);

        $this->get(route('inovasi.show', $innovation->slug))
            ->assertNotFound();
    }

    public function test_unknown_slug_returns_not_found(): void
    {
        $this->get('/inovasi/slug-yang-tidak-ada')
            ->assertNotFound();
    }

    public function test_viewing_an_innovation_increments_its_views_count(): void
    {
        $innovation = InnovationModel::factory()->create(['status' => 'published', 'views_count' => 0]);

        $this->get(route('inovasi.show', $innovation->slug))->assertOk();

        $this->assertSame(1, $innovation->fresh()->views_count);
    }

    public function test_repeated_views_from_the_same_visitor_are_not_counted_twice(): void
    {
        $innovation = InnovationModel::factory()->create(['status' => 'published', 'views_count' => 0]);

        $this->get(route('inovasi.show', $innovation->slug))->assertOk();
        $this->get(route('inovasi.show', $innovation->slug))->assertOk();

        $this->assertSame(1, $innovation->fresh()->views_count);
    }

    public function test_documents_can_be_downloaded(): void
    {
        Storage::fake('public');

        $innovation = InnovationModel::factory()->create(['status' => 'published']);
        $innovation->addMedia(UploadedFile::fake()->create('panduan.pdf', 100))
            ->toMediaCollection('documents');

        $response = $this->get(route('inovasi.show', $innovation->slug));
        $documents = $response->viewData('page')['props']['innovation']['documents'];

        $this->assertCount(1, $documents);
        $this->assertSame('panduan.pdf', $documents[0]['name']);
        $this->assertStringContainsString('/storage/', $documents[0]['url']);
    }
}
