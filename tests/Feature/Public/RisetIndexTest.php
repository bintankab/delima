<?php

namespace Tests\Feature\Public;

use App\Models\RisetModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RisetIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_public_riset_page(): void
    {
        $this->get(route('riset'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Riset/Index'));
    }

    public function test_only_published_risets_are_listed(): void
    {
        RisetModel::factory()->create(['title' => 'Riset Tayang', 'status' => 'published']);
        RisetModel::factory()->create(['title' => 'Riset Draf', 'status' => 'draft']);

        $this->get(route('riset'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('risets.data', 1)
                ->where('risets.data.0.title', 'Riset Tayang'));
    }

    public function test_abstract_is_truncated_to_fifty_words(): void
    {
        $longAbstract = implode(' ', array_fill(0, 80, 'kata'));

        RisetModel::factory()->create([
            'status' => 'published',
            'abstrak' => $longAbstract,
        ]);

        $response = $this->get(route('riset'));
        $shown = $response->viewData('page')['props']['risets']['data'][0]['abstrak'];

        $this->assertSame(Str::words($longAbstract, 50), $shown);
        $this->assertStringEndsWith('...', $shown);
    }

    public function test_cover_url_is_included_when_present(): void
    {
        Storage::fake('public');

        $riset = RisetModel::factory()->create(['status' => 'published']);
        $riset->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');

        $response = $this->get(route('riset'));
        $cover = $response->viewData('page')['props']['risets']['data'][0]['cover'];

        $this->assertNotNull($cover);
        $this->assertStringContainsString('/storage/', $cover);
        $this->assertStringContainsString('thumb', $cover);
    }

    public function test_cover_is_null_when_no_image_was_uploaded(): void
    {
        RisetModel::factory()->create(['status' => 'published']);

        $response = $this->get(route('riset'));
        $cover = $response->viewData('page')['props']['risets']['data'][0]['cover'];

        $this->assertNull($cover);
    }
}
