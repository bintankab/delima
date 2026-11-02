<?php

namespace Tests\Feature\Admin;

use App\Models\PageModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.pages.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_opd_cannot_open_the_page_list(): void
    {
        $this->actingAs($this->userWithRole('admin-opd'))
            ->get(route('admin.pages.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_save_a_draft(): void
    {
        $admin = $this->userWithRole('super-admin');

        $this->actingAs($admin)
            ->post(route('admin.pages.store'), [
                'title' => 'FrontPage',
                'content' => '<p>Isi halaman depan.</p>',
                'status' => 'draft',
                'layout' => 'layout_1',
            ])
            ->assertRedirect(route('admin.pages.index'))
            ->assertSessionHas('success');

        $page = PageModel::query()->where('title', 'FrontPage')->first();

        $this->assertNotNull($page);
        $this->assertSame($admin->id, $page->user_id);
        $this->assertSame('frontpage', $page->slug);
        $this->assertSame('layout_1', $page->layout);
        $this->assertSame('draft', $page->status);
        $this->assertNull($page->published_at);
    }

    public function test_a_duplicate_title_gets_a_unique_slug(): void
    {
        PageModel::factory()->create(['title' => 'About', 'slug' => 'about']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.pages.store'), [
                'title' => 'About',
                'content' => '<p>Isi.</p>',
                'status' => 'draft',
                'layout' => 'layout_1',
            ])
            ->assertRedirect(route('admin.pages.index'));

        $this->assertNotNull(PageModel::query()->where('slug', 'about-2')->first());
    }

    public function test_an_unknown_layout_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.pages.create'))
            ->post(route('admin.pages.store'), [
                'title' => 'About',
                'content' => '<p>Isi.</p>',
                'status' => 'draft',
                'layout' => 'layout_9',
            ])
            ->assertRedirect(route('admin.pages.create'))
            ->assertSessionHasErrors([
                'layout' => 'Layout tidak valid.',
            ]);
    }

    public function test_empty_title_content_and_layout_are_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.pages.create'))
            ->post(route('admin.pages.store'), [])
            ->assertRedirect(route('admin.pages.create'))
            ->assertSessionHasErrors([
                'title' => 'Judul page wajib diisi.',
                'content' => 'Isi page wajib diisi.',
                'status' => 'Status page wajib dipilih.',
                'layout' => 'Layout wajib dipilih.',
            ]);
    }

    public function test_layout_cards_and_their_image_are_stored_on_the_page(): void
    {
        Storage::fake('public');

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.pages.store'), [
                'title' => 'About',
                'content' => '<p>Isi about.</p>',
                'status' => 'draft',
                'layout' => 'layout_2',
                'cards' => [
                    [
                        'title' => 'Visi',
                        'body' => 'Teks visi',
                        'image' => UploadedFile::fake()->image('visi.jpg'),
                    ],
                ],
            ])
            ->assertRedirect(route('admin.pages.index'));

        $page = PageModel::query()->where('title', 'About')->first();
        $imageId = $page?->fields['cards'][0]['image_id'] ?? null;

        $this->assertSame('Visi', $page?->fields['cards'][0]['title']);
        $this->assertSame('Teks visi', $page?->fields['cards'][0]['body']);
        $this->assertNotNull($page?->getMedia('images')->firstWhere('id', $imageId));
    }

    public function test_two_pages_using_the_same_layout_keep_their_own_cards(): void
    {
        $admin = $this->userWithRole('super-admin');

        $this->actingAs($admin)
            ->post(route('admin.pages.store'), [
                'title' => 'FrontPage',
                'content' => '<p>Depan.</p>',
                'status' => 'draft',
                'layout' => 'layout_2',
                'cards' => [
                    ['title' => 'Sambutan', 'body' => 'Halo'],
                ],
            ])
            ->assertRedirect(route('admin.pages.index'));

        $this->actingAs($admin)
            ->post(route('admin.pages.store'), [
                'title' => 'About',
                'content' => '<p>Tentang.</p>',
                'status' => 'draft',
                'layout' => 'layout_2',
                'cards' => [
                    ['title' => 'Visi', 'body' => 'Teks visi'],
                ],
            ])
            ->assertRedirect(route('admin.pages.index'));

        $front = PageModel::query()->where('title', 'FrontPage')->first();
        $about = PageModel::query()->where('title', 'About')->first();

        $this->assertSame('Sambutan', $front?->fields['cards'][0]['title']);
        $this->assertSame('Visi', $about?->fields['cards'][0]['title']);
        $this->assertSame('layout_2', $front?->layout);
        $this->assertSame('layout_2', $about?->layout);
    }

    public function test_slideshow_images_are_stored_in_the_submitted_order(): void
    {
        Storage::fake('public');
        $admin = $this->userWithRole('super-admin');

        $this->actingAs($admin)
            ->post(route('admin.pages.store'), [
                'title' => 'FrontPage',
                'content' => '<p>Depan.</p>',
                'status' => 'draft',
                'layout' => 'layout_2',
                'new_slides' => [
                    UploadedFile::fake()->image('satu.jpg'),
                    UploadedFile::fake()->image('dua.jpg'),
                ],
                'new_slide_captions' => ['Pertama', 'Kedua'],
            ])
            ->assertRedirect(route('admin.pages.index'));

        $page = PageModel::query()->where('title', 'FrontPage')->first();
        $ids = $page->getMedia('slides')->pluck('id')->all();

        $this->assertCount(2, $ids);
        $this->assertSame('Pertama', $page->getMedia('slides')->first()->getCustomProperty('caption'));

        $this->actingAs($admin)
            ->put(route('admin.pages.update', $page), [
                'title' => 'FrontPage',
                'content' => '<p>Depan.</p>',
                'status' => 'draft',
                'layout' => 'layout_2',
                'slides' => [
                    ['id' => $ids[1], 'caption' => 'Kedua'],
                    ['id' => $ids[0], 'caption' => 'Pertama'],
                ],
            ])
            ->assertRedirect(route('admin.pages.index'));

        $ordered = $page->fresh()->getMedia('slides')->pluck('id')->all();

        $this->assertSame([$ids[1], $ids[0]], $ordered);
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
