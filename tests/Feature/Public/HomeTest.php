<?php

namespace Tests\Feature\Public;

use App\Models\PostModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_published_posts_newest_first(): void
    {
        Storage::fake('public');

        $author = UserModel::factory()->create(['name' => 'Sari Wulandari']);
        $newer = PostModel::factory()->create([
            'user_id' => $author->id,
            'title' => 'Berita Baru',
            'status' => 'published',
            'published_at' => '2026-11-01',
            'excerpt' => 'Ringkasan berita baru.',
        ]);
        $newer->addMedia(UploadedFile::fake()->image('sampul.jpg'))->toMediaCollection('cover');

        PostModel::factory()->create([
            'title' => 'Berita Lama',
            'status' => 'published',
            'published_at' => '2026-01-01',
        ]);
        PostModel::factory()->create([
            'title' => 'Masih Draf',
            'status' => 'draft',
            'published_at' => '2026-12-01',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->has('latestPosts', 2)
                ->where('latestPosts.0.title', 'Berita Baru')
                ->where('latestPosts.0.author', 'Sari Wulandari')
                ->where('latestPosts.0.published_at', '2026-11-01')
                ->where('latestPosts.0.excerpt', 'Ringkasan berita baru.')
                ->where('latestPosts.0.cover', fn (string $cover): bool => $cover !== '')
                ->where('latestPosts.1.title', 'Berita Lama'));
    }
}
