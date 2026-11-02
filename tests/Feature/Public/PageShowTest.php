<?php

namespace Tests\Feature\Public;

use App\Models\PageModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class PageShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_page_uses_its_layout(): void
    {
        $page = PageModel::factory()->create([
            'title' => 'FrontPage',
            'slug' => 'frontpage',
            'content' => '<p>Isi halaman depan.</p>',
            'status' => 'published',
            'layout' => 'layout_1',
            'published_at' => now(),
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia
                ->component('Public/Pages/Layout1')
                ->where('page.title', 'FrontPage')
                ->where('page.content', '<p>Isi halaman depan.</p>'));
    }

    public function test_a_draft_page_is_hidden(): void
    {
        $page = PageModel::factory()->create([
            'slug' => 'rahasia',
            'status' => 'draft',
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertNotFound();
    }

    public function test_an_unknown_slug_is_not_found(): void
    {
        $this->get('/p/tidak-ada')
            ->assertNotFound();
    }

    public function test_a_layout_with_cards_and_slides_sends_their_image_urls(): void
    {
        Storage::fake('public');

        $page = PageModel::factory()->create([
            'title' => 'About',
            'slug' => 'about',
            'status' => 'published',
            'layout' => 'layout_2',
            'published_at' => now(),
            'content' => '<p>Tentang.</p>',
        ]);

        $image = $page->addMedia(UploadedFile::fake()->image('kartu.jpg'))->toMediaCollection('images');
        $second = $page->addMedia(UploadedFile::fake()->image('dua.jpg'))
            ->withCustomProperties(['caption' => 'Kedua'])
            ->toMediaCollection('slides');
        $first = $page->addMedia(UploadedFile::fake()->image('satu.jpg'))
            ->withCustomProperties(['caption' => 'Pertama'])
            ->toMediaCollection('slides');
        Media::setNewOrder([$first->id, $second->id]);

        $page->update([
            'fields' => [
                'cards' => [
                    ['title' => 'Visi', 'body' => 'Teks visi', 'image_id' => $image->id],
                ],
            ],
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia
                ->component('Public/Pages/Layout2')
                ->where('page.fields.cards.0.title', 'Visi')
                ->where('page.fields.cards.0.url', $image->getUrl())
                ->has('slides', 2)
                ->where('slides.0.caption', 'Pertama')
                ->where('slides.0.url', $first->getUrl())
                ->where('slides.1.caption', 'Kedua')
                ->where('slides.1.url', $second->getUrl()));
    }
}
