<?php

namespace Tests\Feature\Admin;

use App\Models\CategoryModel;
use App\Models\PostModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.posts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_opd_cannot_open_the_post_list(): void
    {
        $this->actingAs($this->userWithRole('admin-opd'))
            ->get(route('admin.posts.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_the_post_list(): void
    {
        PostModel::factory()->create(['title' => 'Berita A']);
        PostModel::factory()->create(['title' => 'Berita B']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.posts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Posts/A-Posts-Index')
                ->has('posts.data', 2));
    }

    public function test_posts_can_be_filtered_by_title(): void
    {
        PostModel::factory()->create(['title' => 'Panen Raya Bintan']);
        PostModel::factory()->create(['title' => 'Jadwal Vaksin']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.posts.index', ['search' => 'Panen']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Panen Raya Bintan'));
    }

    public function test_posts_can_be_filtered_by_status(): void
    {
        PostModel::factory()->create(['title' => 'Masih Draf', 'status' => 'draft']);
        PostModel::factory()->create(['title' => 'Sudah Terbit', 'status' => 'published']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.posts.index', ['status' => 'published']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Sudah Terbit'));
    }

    public function test_super_admin_can_save_a_draft(): void
    {
        $admin = $this->userWithRole('super-admin');

        $this->actingAs($admin)
            ->post(route('admin.posts.store'), [
                'title' => 'Berita Uji Coba',
                'content' => '<p>Isi berita yang cukup panjang untuk ringkasan otomatis dari paragraf ini.</p>',
                'status' => 'draft',
            ])
            ->assertRedirect(route('admin.posts.index'))
            ->assertSessionHas('success');

        $post = PostModel::query()->where('title', 'Berita Uji Coba')->first();

        $this->assertNotNull($post);
        $this->assertSame('berita-uji-coba', $post->slug);
        $this->assertSame('draft', $post->status);
        $this->assertSame($admin->id, $post->user_id);
        $this->assertNull($post->published_at);
        $this->assertSame('Isi berita yang cukup panjang untuk ringkasan otomatis dari paragraf ini.', $post->excerpt);
    }

    public function test_publishing_without_a_date_sets_published_at_to_today(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.posts.store'), [
                'title' => 'Berita Terbit',
                'content' => '<p>Isi berita.</p>',
                'status' => 'published',
            ])
            ->assertRedirect(route('admin.posts.index'));

        $post = PostModel::query()->where('title', 'Berita Terbit')->first();

        $this->assertNotNull($post);
        $this->assertTrue($post->published_at?->isToday());
    }

    public function test_empty_title_and_content_are_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.posts.create'))
            ->post(route('admin.posts.store'), [])
            ->assertRedirect(route('admin.posts.create'))
            ->assertSessionHasErrors([
                'title' => 'Judul post wajib diisi.',
                'content' => 'Isi post wajib diisi.',
                'status' => 'Status post wajib dipilih.',
            ]);
    }

    public function test_changing_the_title_changes_the_slug(): void
    {
        $post = PostModel::factory()->create(['title' => 'Judul Lama', 'slug' => 'judul-lama']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.posts.update', $post), [
                'title' => 'Judul Baru',
                'content' => '<p>Isi.</p>',
                'status' => 'draft',
            ])
            ->assertRedirect(route('admin.posts.index'));

        $this->assertSame('judul-baru', $post->fresh()->slug);
    }

    public function test_keeping_the_title_keeps_the_slug(): void
    {
        $post = PostModel::factory()->create([
            'title' => 'Judul Tetap',
            'slug' => 'slug-lama',
            'status' => 'draft',
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.posts.update', $post), [
                'title' => 'Judul Tetap',
                'content' => '<p>Isi baru.</p>',
                'status' => 'published',
                'published_at' => '2026-01-15',
            ])
            ->assertRedirect(route('admin.posts.index'));

        $post->refresh();

        $this->assertSame('slug-lama', $post->slug);
        $this->assertSame('2026-01-15', $post->published_at?->toDateString());
    }

    public function test_a_cover_image_is_stored(): void
    {
        Storage::fake('public');

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.posts.store'), [
                'title' => 'Berita Bergambar',
                'content' => '<p>Isi.</p>',
                'status' => 'draft',
                'cover' => UploadedFile::fake()->image('sampul.jpg'),
            ])
            ->assertRedirect(route('admin.posts.index'));

        $post = PostModel::query()->where('title', 'Berita Bergambar')->first();

        $this->assertNotNull($post?->getFirstMedia('cover'));
    }

    public function test_a_post_can_belong_to_several_categories(): void
    {
        $kesehatan = CategoryModel::factory()->create(['name' => 'Kesehatan']);
        $pendidikan = CategoryModel::factory()->create(['name' => 'Pendidikan']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.posts.store'), [
                'title' => 'Berita Berkategori',
                'content' => '<p>Isi.</p>',
                'status' => 'draft',
                'category_ids' => [$pendidikan->id, $kesehatan->id],
            ])
            ->assertRedirect(route('admin.posts.index'));

        $post = PostModel::query()->where('title', 'Berita Berkategori')->first();

        $this->assertNotNull($post);
        $this->assertEqualsCanonicalizing(
            ['Kesehatan', 'Pendidikan'],
            $post->categories()->pluck('name')->all(),
        );
    }

    public function test_updating_a_post_replaces_its_categories(): void
    {
        $kept = CategoryModel::factory()->create(['name' => 'Tetap']);
        $removed = CategoryModel::factory()->create(['name' => 'Lepas']);
        $post = PostModel::factory()->create(['title' => 'Judul Tetap', 'slug' => 'judul-tetap']);
        $post->categories()->attach($removed);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.posts.update', $post), [
                'title' => 'Judul Tetap',
                'content' => '<p>Isi.</p>',
                'status' => 'draft',
                'category_ids' => [$kept->id],
            ])
            ->assertRedirect(route('admin.posts.index'));

        $this->assertEqualsCanonicalizing(
            ['Tetap'],
            $post->categories()->pluck('name')->all(),
        );
    }

    public function test_an_unknown_category_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.posts.create'))
            ->post(route('admin.posts.store'), [
                'title' => 'Berita',
                'content' => '<p>Isi.</p>',
                'status' => 'draft',
                'category_ids' => [99999],
            ])
            ->assertRedirect(route('admin.posts.create'))
            ->assertSessionHasErrors([
                'category_ids.0' => 'Kategori yang dipilih tidak ditemukan.',
            ]);
    }

    public function test_post_can_be_deleted(): void
    {
        $post = PostModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.posts.destroy', $post))
            ->assertRedirect(route('admin.posts.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_the_old_placeholder_address_redirects_to_the_post_list(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->get('/admin/news/index')
            ->assertRedirect('/admin/posts');
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
