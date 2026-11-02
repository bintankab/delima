<?php

namespace Tests\Feature\Public;

use App\Models\CategoryModel;
use App\Models\PostModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PostShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_a_published_post(): void
    {
        $author = UserModel::factory()->create(['name' => 'Sari Wulandari']);
        $post = PostModel::factory()->create([
            'user_id' => $author->id,
            'title' => 'Posyandu Digital',
            'slug' => 'posyandu-digital',
            'status' => 'published',
            'published_at' => '2026-10-21',
            'excerpt' => 'Ringkasan posyandu.',
            'views_count' => 0,
        ]);
        PostModel::factory()->create([
            'title' => 'Berita Lain',
            'slug' => 'berita-lain',
            'status' => 'published',
            'published_at' => '2026-10-01',
        ]);
        PostModel::factory()->create([
            'title' => 'Masih Draf',
            'status' => 'draft',
        ]);

        $this->get(route('berita.show', $post->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Posts/Show')
                ->where('post.title', 'Posyandu Digital')
                ->where('post.author', 'Sari Wulandari')
                ->where('post.views_count', 1)
                ->has('relatedPosts', 1)
                ->where('relatedPosts.0.title', 'Berita Lain'));

        $this->assertSame(1, $post->fresh()->views_count);
    }

    public function test_published_post_shows_its_categories_by_name(): void
    {
        $post = PostModel::factory()->create([
            'title' => 'Berita Berkategori',
            'slug' => 'berita-berkategori',
            'status' => 'published',
            'published_at' => '2026-10-21',
        ]);
        $post->categories()->attach([
            CategoryModel::factory()->create(['name' => 'Pendidikan'])->id,
            CategoryModel::factory()->create(['name' => 'Kesehatan'])->id,
        ]);

        $this->get(route('berita.show', $post->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.categories.0.name', 'Kesehatan')
                ->where('post.categories.1.name', 'Pendidikan'));
    }

    public function test_draft_post_is_not_publicly_accessible(): void
    {
        $post = PostModel::factory()->create([
            'slug' => 'masih-draf',
            'status' => 'draft',
        ]);

        $this->get(route('berita.show', $post->slug))
            ->assertNotFound();
    }

    public function test_unknown_slug_returns_not_found(): void
    {
        $this->get('/berita/slug-yang-tidak-ada')
            ->assertNotFound();
    }

    public function test_repeated_views_from_the_same_visitor_are_not_counted_twice(): void
    {
        $post = PostModel::factory()->create([
            'status' => 'published',
            'views_count' => 0,
        ]);

        $this->get(route('berita.show', $post->slug))->assertOk();
        $this->get(route('berita.show', $post->slug))->assertOk();

        $this->assertSame(1, $post->fresh()->views_count);
    }
}
