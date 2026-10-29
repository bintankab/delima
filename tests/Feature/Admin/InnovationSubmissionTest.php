<?php

namespace Tests\Feature\Admin;

use App\Enums\JenisInisiator;
use App\Enums\JenisInovasi;
use App\Enums\TahapanInovasi;
use App\Models\InnovationCategoryModel;
use App\Models\InnovationModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InnovationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('inisiatorRoles')]
    public function test_role_submitting_an_innovation_always_gets_its_own_inisiator(string $role, JenisInisiator $expected): void
    {
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole($role))
            ->post(route('admin.innovations.store'), [
                // Spoof attempt: a non-super-admin role must never be able to
                // choose a different inisiator than the one tied to their role.
                'inisiator' => JenisInisiator::Bupati->value,
                'category_id' => $category->id,
                'title' => 'Inovasi dari '.$role,
                'jenis_inovasi' => JenisInovasi::Digital->value,
                'tahapan' => TahapanInovasi::Inisiasi->value,
                'description' => 'Ringkasan inovasi.',
                'tahun' => 2024,
                'implementation_at' => '2024-01-10',
            ]);

        $innovation = InnovationModel::query()->where('title', 'Inovasi dari '.$role)->first();

        $this->assertNotNull($innovation);
        $this->assertSame($expected, $innovation->inisiator);
        $this->assertSame('submitted', $innovation->status);
        $this->assertSame('submission', $innovation->origin);
    }

    /**
     * @return array<string, array{0: string, 1: JenisInisiator}>
     */
    public static function inisiatorRoles(): array
    {
        return [
            'bupati' => ['bupati', JenisInisiator::Bupati],
            'dprd' => ['dprd', JenisInisiator::DPRD],
            'admin-opd' => ['admin-opd', JenisInisiator::OPD],
            'asn' => ['asn', JenisInisiator::ASN],
            'publik' => ['publik', JenisInisiator::Masyarakat],
        ];
    }

    public function test_super_admin_must_choose_the_inisiator_manually(): void
    {
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.innovations.create'))
            ->post(route('admin.innovations.store'), [
                'category_id' => $category->id,
                'title' => 'Inovasi Tanpa Inisiator',
                'jenis_inovasi' => JenisInovasi::Digital->value,
                'tahapan' => TahapanInovasi::Inisiasi->value,
                'description' => 'Ringkasan inovasi.',
                'tahun' => 2024,
                'implementation_at' => '2024-01-10',
            ])
            ->assertSessionHasErrors(['inisiator' => 'Jenis inisiator wajib dipilih.']);

        $this->assertDatabaseCount('innovations', 0);
    }

    public function test_super_admin_can_pick_any_inisiator(): void
    {
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.innovations.store'), [
                'inisiator' => JenisInisiator::DPRD->value,
                'category_id' => $category->id,
                'title' => 'Inovasi Pilihan Super Admin',
                'jenis_inovasi' => JenisInovasi::Digital->value,
                'tahapan' => TahapanInovasi::Inisiasi->value,
                'description' => 'Ringkasan inovasi.',
                'tahun' => 2024,
                'implementation_at' => '2024-01-10',
            ])
            ->assertRedirect(route('admin.innovations.show', InnovationModel::query()->latest()->first()));

        $this->assertDatabaseHas('innovations', [
            'title' => 'Inovasi Pilihan Super Admin',
            'inisiator' => JenisInisiator::DPRD->value,
        ]);
    }

    public function test_reviewer_cannot_submit_an_innovation(): void
    {
        $this->actingAs($this->userWithRole('reviewer'))
            ->get(route('admin.innovations.create'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('reviewer'))
            ->post(route('admin.innovations.store'), [])
            ->assertForbidden();

        $this->assertDatabaseCount('innovations', 0);
    }

    public function test_publik_is_redirected_home_after_submitting(): void
    {
        $category = InnovationCategoryModel::factory()->create();

        $this->actingAs($this->userWithRole('publik'))
            ->post(route('admin.innovations.store'), [
                'category_id' => $category->id,
                'title' => 'Inovasi dari Masyarakat',
                'jenis_inovasi' => JenisInovasi::Digital->value,
                'tahapan' => TahapanInovasi::Inisiasi->value,
                'description' => 'Ringkasan inovasi.',
                'tahun' => 2024,
                'implementation_at' => '2024-01-10',
            ])
            ->assertRedirect(route('home'));
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
