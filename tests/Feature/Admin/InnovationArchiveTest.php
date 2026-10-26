<?php

namespace Tests\Feature\Admin;

use App\Models\InnovationCategoryModel;
use App\Models\InnovationModel;
use App\Models\OrganisasiModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InnovationArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_archive_form(): void
    {
        $this->get(route('admin.innovations.archive.create'))
            ->assertRedirect(route('login'));
    }

    #[DataProvider('listingRoles')]
    public function test_archive_roles_can_open_the_innovation_list(string $role): void
    {
        $this->actingAs($this->userWithRole($role))
            ->get(route('admin.innovations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Innovations/Index'));
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_role_cannot_open_the_archive_form(string $role): void
    {
        $this->actingAs($this->userWithRole($role))
            ->get(route('admin.innovations.archive.create'))
            ->assertForbidden();
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_role_cannot_store_an_archive(string $role): void
    {
        $this->actingAs($this->userWithRole($role))
            ->post(route('admin.innovations.archive.store'), [])
            ->assertForbidden();

        $this->assertDatabaseCount('innovations', 0);
    }

    public function test_super_admin_stores_a_published_archive(): void
    {
        $organisasi = OrganisasiModel::factory()->create();
        $category = InnovationCategoryModel::factory()->create();
        $admin = $this->userWithRole('super-admin');
        $otherUser = UserModel::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.innovations.archive.store'), [
                'organisasi_id' => $organisasi->id,
                'category_id' => $category->id,
                'title' => 'Aplikasi Layanan Terpadu 2021',
                'jenis_inovasi' => 'pelayanan',
                'description' => 'Ringkasan arsip.',
                'latar_belakang' => 'Konteks daerah.',
                'tahun' => 2021,
                'published_at' => '2021-06-15',
                'user_id' => $otherUser->id,
                'status' => 'draft',
                'origin' => 'submission',
            ])
            ->assertRedirect(route('admin.innovations.index'))
            ->assertSessionHas('success', 'Arsip inovasi berhasil ditambahkan.');

        $this->assertDatabaseHas('innovations', [
            'title' => 'Aplikasi Layanan Terpadu 2021',
            'slug' => 'aplikasi-layanan-terpadu-2021',
            'organisasi_id' => $organisasi->id,
            'category_id' => $category->id,
            'user_id' => $admin->id,
            'jenis_inovasi' => 'pelayanan',
            'tahun' => 2021,
            'status' => 'published',
            'origin' => 'archive',
        ]);

        $innovation = InnovationModel::query()->where('title', 'Aplikasi Layanan Terpadu 2021')->first();

        $this->assertNotNull($innovation);
        $this->assertSame('2021-06-15', $innovation->published_at?->toDateString());
    }

    public function test_super_admin_can_attach_cover_and_documents(): void
    {
        Storage::fake('public');

        $organisasi = OrganisasiModel::factory()->create();
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.innovations.archive.store'), [
                'organisasi_id' => $organisasi->id,
                'category_id' => $category->id,
                'title' => 'Sistem Antrean Digital',
                'jenis_inovasi' => 'produk',
                'description' => 'Ringkasan berkas.',
                'tahun' => 2022,
                'published_at' => '2022-03-01',
                'cover' => UploadedFile::fake()->image('cover.jpg'),
                'documents' => [
                    UploadedFile::fake()->createWithContent('laporan.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF"),
                ],
            ])
            ->assertRedirect(route('admin.innovations.index'));

        $innovation = InnovationModel::query()->where('title', 'Sistem Antrean Digital')->first();

        $this->assertNotNull($innovation);
        $this->assertCount(1, $innovation->getMedia('cover'));
        $this->assertCount(1, $innovation->getMedia('documents'));
        $this->assertSame('laporan.pdf', $innovation->getFirstMedia('documents')?->file_name);
    }

    public function test_empty_archive_payload_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.innovations.archive.create'))
            ->post(route('admin.innovations.archive.store'), [])
            ->assertRedirect(route('admin.innovations.archive.create'))
            ->assertSessionHasErrors([
                'organisasi_id' => 'OPD asal wajib dipilih.',
                'category_id' => 'Kategori wajib dipilih.',
                'title' => 'Judul wajib diisi.',
                'jenis_inovasi' => 'Jenis inovasi wajib dipilih.',
                'description' => 'Deskripsi wajib diisi.',
                'tahun' => 'Tahun wajib diisi.',
                'published_at' => 'Tanggal terbit wajib diisi.',
            ]);

        $this->assertDatabaseCount('innovations', 0);
    }

    public function test_year_before_2021_is_rejected(): void
    {
        $organisasi = OrganisasiModel::factory()->create();
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.innovations.archive.create'))
            ->post(route('admin.innovations.archive.store'), [
                'organisasi_id' => $organisasi->id,
                'category_id' => $category->id,
                'title' => 'Inovasi Lama',
                'jenis_inovasi' => 'proses',
                'description' => 'Ringkasan.',
                'tahun' => 2020,
                'published_at' => '2021-06-15',
            ])
            ->assertRedirect(route('admin.innovations.archive.create'))
            ->assertSessionHasErrors([
                'tahun' => 'Tahun harus antara 2021 dan '.now()->year.'.',
            ]);

        $this->assertDatabaseCount('innovations', 0);
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
