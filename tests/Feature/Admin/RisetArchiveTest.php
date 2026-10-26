<?php

namespace Tests\Feature\Admin;

use App\Models\OrganisasiModel;
use App\Models\RisetCategoryModel;
use App\Models\RisetModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RisetArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_archive_form(): void
    {
        $this->get(route('admin.risets.archive.create'))
            ->assertRedirect(route('login'));
    }

    #[DataProvider('listingRoles')]
    public function test_archive_roles_can_open_the_riset_list(string $role): void
    {
        $this->actingAs($this->userWithRole($role))
            ->get(route('admin.risets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Risets/Index'));
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_role_cannot_open_the_archive_form(string $role): void
    {
        $this->actingAs($this->userWithRole($role))
            ->get(route('admin.risets.archive.create'))
            ->assertForbidden();
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_role_cannot_store_an_archive(string $role): void
    {
        $this->actingAs($this->userWithRole($role))
            ->post(route('admin.risets.archive.store'), [])
            ->assertForbidden();

        $this->assertDatabaseCount('risets', 0);
    }

    public function test_super_admin_stores_a_published_archive(): void
    {
        $organisasi = OrganisasiModel::factory()->create();
        $category = RisetCategoryModel::factory()->create();
        $admin = $this->userWithRole('super-admin');
        $otherUser = UserModel::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.risets.archive.store'), [
                'organisasi_id' => $organisasi->id,
                'category_id' => $category->id,
                'title' => 'Studi Gizi 2021',
                'peneliti' => 'Dr. Sari',
                'abstrak' => 'Ringkasan arsip.',
                'latar_belakang' => 'Konteks daerah.',
                'metodologi' => null,
                'tahun' => 2021,
                'published_at' => '2021-06-15',
                'user_id' => $otherUser->id,
                'status' => 'draft',
                'origin' => 'submission',
            ])
            ->assertRedirect(route('admin.risets.index'))
            ->assertSessionHas('success', 'Arsip riset berhasil ditambahkan.');

        $this->assertDatabaseHas('risets', [
            'title' => 'Studi Gizi 2021',
            'slug' => 'studi-gizi-2021',
            'organisasi_id' => $organisasi->id,
            'category_id' => $category->id,
            'user_id' => $admin->id,
            'peneliti' => 'Dr. Sari',
            'tahun' => 2021,
            'status' => 'published',
            'origin' => 'archive',
        ]);

        $riset = RisetModel::query()->where('title', 'Studi Gizi 2021')->first();

        $this->assertNotNull($riset);
        $this->assertSame('2021-06-15', $riset->published_at?->toDateString());
    }

    public function test_super_admin_can_attach_cover_and_documents(): void
    {
        Storage::fake('public');

        $organisasi = OrganisasiModel::factory()->create();
        $category = RisetCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.risets.archive.store'), [
                'organisasi_id' => $organisasi->id,
                'category_id' => $category->id,
                'title' => 'Laporan Kesehatan',
                'peneliti' => 'Tim Riset',
                'abstrak' => 'Ringkasan berkas.',
                'tahun' => 2022,
                'published_at' => '2022-03-01',
                'cover' => UploadedFile::fake()->image('cover.jpg'),
                'documents' => [
                    UploadedFile::fake()->createWithContent('laporan.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF"),
                ],
            ])
            ->assertRedirect(route('admin.risets.index'));

        $riset = RisetModel::query()->where('title', 'Laporan Kesehatan')->first();

        $this->assertNotNull($riset);
        $this->assertCount(1, $riset->getMedia('cover'));
        $this->assertCount(1, $riset->getMedia('documents'));
        $this->assertSame('laporan.pdf', $riset->getFirstMedia('documents')?->file_name);
    }

    public function test_empty_archive_payload_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.risets.archive.create'))
            ->post(route('admin.risets.archive.store'), [])
            ->assertRedirect(route('admin.risets.archive.create'))
            ->assertSessionHasErrors([
                'organisasi_id' => 'OPD asal wajib dipilih.',
                'category_id' => 'Kategori wajib dipilih.',
                'title' => 'Judul wajib diisi.',
                'peneliti' => 'Nama peneliti wajib diisi.',
                'abstrak' => 'Abstrak wajib diisi.',
                'tahun' => 'Tahun wajib diisi.',
                'published_at' => 'Tanggal terbit wajib diisi.',
            ]);

        $this->assertDatabaseCount('risets', 0);
    }

    public function test_year_before_2021_is_rejected(): void
    {
        $organisasi = OrganisasiModel::factory()->create();
        $category = RisetCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.risets.archive.create'))
            ->post(route('admin.risets.archive.store'), [
                'organisasi_id' => $organisasi->id,
                'category_id' => $category->id,
                'title' => 'Studi Lama',
                'peneliti' => 'Dr. Sari',
                'abstrak' => 'Ringkasan.',
                'tahun' => 2020,
                'published_at' => '2021-06-15',
            ])
            ->assertRedirect(route('admin.risets.archive.create'))
            ->assertSessionHasErrors([
                'tahun' => 'Tahun harus antara 2021 dan '.now()->year.'.',
            ]);

        $this->assertDatabaseCount('risets', 0);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function listingRoles(): array
    {
        return [
            'super admin' => ['super-admin'],
            'admin opd' => ['admin-opd'],
            'reviewer' => ['reviewer'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unprivilegedRoles(): array
    {
        return [
            'admin opd' => ['admin-opd'],
            'reviewer' => ['reviewer'],
        ];
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
