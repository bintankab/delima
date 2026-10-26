<?php

namespace Tests\Feature\Public;

use App\Models\InnovationModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InnovationIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_public_innovation_page(): void
    {
        $this->get(route('inovasi'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Innovations/Index'));
    }

    public function test_only_published_innovations_are_listed(): void
    {
        InnovationModel::factory()->create(['title' => 'Inovasi Tayang', 'status' => 'published']);
        InnovationModel::factory()->create(['title' => 'Inovasi Draf', 'status' => 'draft']);

        $this->get(route('inovasi'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('innovations.data', 1)
                ->where('innovations.data.0.title', 'Inovasi Tayang'));
    }

    public function test_description_is_truncated_to_fifty_words(): void
    {
        $longDescription = implode(' ', array_fill(0, 80, 'kata'));

        InnovationModel::factory()->create([
            'status' => 'published',
            'description' => $longDescription,
        ]);

        $response = $this->get(route('inovasi'));
        $shown = $response->viewData('page')['props']['innovations']['data'][0]['description'];

        $this->assertSame(Str::words($longDescription, 50), $shown);
        $this->assertStringEndsWith('...', $shown);
    }

    public function test_cover_url_is_included_when_present(): void
    {
        Storage::fake('public');

        $innovation = InnovationModel::factory()->create(['status' => 'published']);
        $innovation->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');

        $response = $this->get(route('inovasi'));
        $cover = $response->viewData('page')['props']['innovations']['data'][0]['cover'];

        $this->assertNotNull($cover);
        $this->assertStringContainsString('/storage/', $cover);
        $this->assertStringContainsString('thumb', $cover);
    }

    public function test_cover_is_null_when_no_image_was_uploaded(): void
    {
        InnovationModel::factory()->create(['status' => 'published']);

        $response = $this->get(route('inovasi'));
        $cover = $response->viewData('page')['props']['innovations']['data'][0]['cover'];

        $this->assertNull($cover);
    }
}
