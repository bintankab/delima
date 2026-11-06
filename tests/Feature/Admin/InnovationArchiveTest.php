<?php

namespace Tests\Feature\Admin;

use App\Enums\JenisInisiator;
use App\Enums\JenisInovasi;
use App\Enums\TahapanInovasi;
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
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Innovations/A-Innovations-Index'));
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
        $category = InnovationCategoryModel::factory()->create();
        $admin = $this->userWithRole('super-admin');
        $otherUser = UserModel::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.innovations.archive.store'), [
                'inisiator' => JenisInisiator::Bupati->value,
                'category_id' => $category->id,
                'title' => 'Aplikasi Layanan Terpadu 2021',
                'jenis_inovasi' => JenisInovasi::Teknologi->value,
                'tahapan' => TahapanInovasi::Implementasi->value,
                'description' => 'Ringkasan arsip.',
                'latar_belakang' => 'Konteks daerah.',
                'tahun' => 2021,
                'implementation_at' => '2021-06-15',
                'user_id' => $otherUser->id,
                'status' => 'draft',
                'origin' => 'submission',
            ])
            ->assertRedirect(route('admin.innovations.index'))
            ->assertSessionHas('success', 'Arsip inovasi berhasil ditambahkan.');

        $this->assertDatabaseHas('innovations', [
            'title' => 'Aplikasi Layanan Terpadu 2021',
            'slug' => 'aplikasi-layanan-terpadu-2021',
            'inisiator' => JenisInisiator::Bupati->value,
            'category_id' => $category->id,
            'user_id' => $admin->id,
            'jenis_inovasi' => JenisInovasi::Teknologi->value,
            'tahun' => 2021,
            'status' => 'published',
            'origin' => 'archive',
        ]);

        $innovation = InnovationModel::query()->where('title', 'Aplikasi Layanan Terpadu 2021')->first();

        $this->assertNotNull($innovation);
        $this->assertSame('2021-06-15', $innovation->implementation_at?->toDateString());
    }

    public function test_archive_form_lists_organisasis_by_name(): void
    {
        $later = OrganisasiModel::factory()->create(['name' => 'Dinas Pendidikan']);
        $first = OrganisasiModel::factory()->create(['name' => 'Badan Kepegawaian']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.innovations.archive.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Innovations/A-Innovations-ArchiveForm')
                ->where('organisasis.0.id', $first->id)
                ->where('organisasis.0.name', 'Badan Kepegawaian')
                ->where('organisasis.1.id', $later->id)
                ->where('organisasis.1.name', 'Dinas Pendidikan'));
    }

    public function test_opd_archive_is_owned_by_the_primary_admin_opd(): void
    {
        $organisasi = OrganisasiModel::factory()->create();
        $category = InnovationCategoryModel::factory()->create();
        $asn = $this->userWithRole('asn');
        $secondaryAdmin = $this->userWithRole('admin-opd');
        $primaryAdmin = $this->userWithRole('admin-opd');
        $organisasi->users()->attach($asn->id, ['is_primary' => true]);
        $organisasi->users()->attach($secondaryAdmin->id, ['is_primary' => false]);
        $organisasi->users()->attach($primaryAdmin->id, ['is_primary' => true]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.innovations.archive.store'), [
                'inisiator' => JenisInisiator::OPD->value,
                'organisasi_id' => $organisasi->id,
                'category_id' => $category->id,
                'title' => 'Layanan OPD 2021',
                'jenis_inovasi' => JenisInovasi::Digital->value,
                'tahapan' => TahapanInovasi::Implementasi->value,
                'description' => 'Ringkasan arsip OPD.',
                'tahun' => 2021,
                'implementation_at' => '2021-08-01',
                'user_id' => $secondaryAdmin->id,
            ])
            ->assertRedirect(route('admin.innovations.index'));

        $this->assertDatabaseHas('innovations', [
            'title' => 'Layanan OPD 2021',
            'inisiator' => JenisInisiator::OPD->value,
            'user_id' => $primaryAdmin->id,
        ]);
    }

    public function test_opd_archive_requires_an_organisasi(): void
    {
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.innovations.archive.create'))
            ->post(route('admin.innovations.archive.store'), [
                'inisiator' => JenisInisiator::OPD->value,
                'category_id' => $category->id,
                'title' => 'Layanan Tanpa OPD',
                'jenis_inovasi' => JenisInovasi::Digital->value,
                'tahapan' => TahapanInovasi::Implementasi->value,
                'description' => 'Ringkasan.',
                'tahun' => 2021,
                'implementation_at' => '2021-08-01',
            ])
            ->assertRedirect(route('admin.innovations.archive.create'))
            ->assertSessionHasErrors([
                'organisasi_id' => 'OPD wajib dipilih.',
            ]);

        $this->assertDatabaseCount('innovations', 0);
    }

    public function test_opd_archive_rejects_an_unknown_organisasi(): void
    {
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.innovations.archive.create'))
            ->post(route('admin.innovations.archive.store'), [
                'inisiator' => JenisInisiator::OPD->value,
                'organisasi_id' => 999999,
                'category_id' => $category->id,
                'title' => 'Layanan OPD Hilang',
                'jenis_inovasi' => JenisInovasi::Digital->value,
                'tahapan' => TahapanInovasi::Implementasi->value,
                'description' => 'Ringkasan.',
                'tahun' => 2021,
                'implementation_at' => '2021-08-01',
            ])
            ->assertRedirect(route('admin.innovations.archive.create'))
            ->assertSessionHasErrors([
                'organisasi_id' => 'OPD yang dipilih tidak ditemukan.',
            ]);

        $this->assertDatabaseCount('innovations', 0);
    }

    public function test_opd_archive_rejects_an_organisasi_without_an_admin_opd(): void
    {
        $organisasi = OrganisasiModel::factory()->create();
        $category = InnovationCategoryModel::factory()->create();
        $asn = $this->userWithRole('asn');
        $organisasi->users()->attach($asn->id, ['is_primary' => true]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.innovations.archive.create'))
            ->post(route('admin.innovations.archive.store'), [
                'inisiator' => JenisInisiator::OPD->value,
                'organisasi_id' => $organisasi->id,
                'category_id' => $category->id,
                'title' => 'Layanan Tanpa Admin',
                'jenis_inovasi' => JenisInovasi::Digital->value,
                'tahapan' => TahapanInovasi::Implementasi->value,
                'description' => 'Ringkasan.',
                'tahun' => 2021,
                'implementation_at' => '2021-08-01',
            ])
            ->assertRedirect(route('admin.innovations.archive.create'))
            ->assertSessionHasErrors([
                'organisasi_id' => 'OPD yang dipilih belum memiliki admin OPD.',
            ]);

        $this->assertDatabaseCount('innovations', 0);
    }

    public function test_super_admin_can_attach_cover_and_documents(): void
    {
        Storage::fake('public');

        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.innovations.archive.store'), [
                'inisiator' => JenisInisiator::Bupati->value,
                'category_id' => $category->id,
                'title' => 'Sistem Antrean Digital',
                'jenis_inovasi' => JenisInovasi::Digital->value,
                'tahapan' => TahapanInovasi::Implementasi->value,
                'description' => 'Ringkasan berkas.',
                'tahun' => 2022,
                'implementation_at' => '2022-03-01',
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
                'inisiator' => 'Jenis inisiator wajib dipilih.',
                'category_id' => 'Kategori wajib dipilih.',
                'title' => 'Judul wajib diisi.',
                'jenis_inovasi' => 'Jenis inovasi wajib dipilih.',
                'tahapan' => 'Tahapan inovasi wajib dipilih.',
                'description' => 'Deskripsi wajib diisi.',
                'tahun' => 'Tahun wajib diisi.',
                'implementation_at' => 'Tanggal implementasi wajib diisi.',
            ]);

        $this->assertDatabaseCount('innovations', 0);
    }

    public function test_year_before_2021_is_rejected(): void
    {
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.innovations.archive.create'))
            ->post(route('admin.innovations.archive.store'), [
                'inisiator' => JenisInisiator::ASN->value,
                'category_id' => $category->id,
                'title' => 'Inovasi Lama',
                'jenis_inovasi' => JenisInovasi::NonDigital->value,
                'tahapan' => TahapanInovasi::Inisiasi->value,
                'description' => 'Ringkasan.',
                'tahun' => 2020,
                'implementation_at' => '2021-06-15',
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
