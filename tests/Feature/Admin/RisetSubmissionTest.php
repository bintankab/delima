<?php

namespace Tests\Feature\Admin;

use App\Models\OrganisasiModel;
use App\Models\RisetCategoryModel;
use App\Models\RisetModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RisetSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_opd_can_open_the_submission_form_with_its_own_organisasi_locked_in(): void
    {
        $organisasi = OrganisasiModel::factory()->create(['name' => 'Dinas Kesehatan']);
        $user = $this->userWithRole('admin-opd');
        $user->organisasis()->sync([$organisasi->id => ['is_primary' => true]]);

        $this->actingAs($user)
            ->get(route('admin.risets.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Risets/ArchiveForm')
                ->where('organisasiLocked', true)
                ->where('organisasiId', $organisasi->id)
                ->where('organisasiName', 'Dinas Kesehatan')
                ->where('submitRoute', 'admin.risets.store'));
    }

    public function test_admin_opd_submitting_a_riset_always_gets_its_own_organisasi_regardless_of_spoofing(): void
    {
        $ownOrganisasi = OrganisasiModel::factory()->create();
        $otherOrganisasi = OrganisasiModel::factory()->create();
        $category = RisetCategoryModel::factory()->create();

        $user = $this->userWithRole('admin-opd');
        $user->organisasis()->sync([$ownOrganisasi->id => ['is_primary' => true]]);

        $this->actingAs($user)
            ->post(route('admin.risets.store'), [
                // Spoof attempt: must never be able to submit under a
                // different OPD than the one the account belongs to.
                'organisasi_id' => $otherOrganisasi->id,
                'category_id' => $category->id,
                'title' => 'Riset Dinas Kesehatan',
                'peneliti' => 'Dr. Ani',
                'abstrak' => 'Ringkasan riset.',
                'tahun' => 2024,
                'published_at' => '2024-01-10',
            ]);

        $riset = RisetModel::query()->where('title', 'Riset Dinas Kesehatan')->first();

        $this->assertNotNull($riset);
        $this->assertSame($ownOrganisasi->id, $riset->organisasi_id);
        $this->assertSame($user->id, $riset->user_id);
        $this->assertSame('submitted', $riset->status);
        $this->assertSame('submission', $riset->origin);
    }

    public function test_admin_opd_without_an_organisasi_cannot_submit_a_riset(): void
    {
        $category = RisetCategoryModel::factory()->create();
        $someOrganisasi = OrganisasiModel::factory()->create();

        $this->actingAs($this->userWithRole('admin-opd'))
            ->post(route('admin.risets.store'), [
                // Spoof a real organisasi_id to get past validation — the
                // account itself has none, so the controller must still
                // refuse regardless of what's submitted.
                'organisasi_id' => $someOrganisasi->id,
                'category_id' => $category->id,
                'title' => 'Riset Tanpa OPD',
                'peneliti' => 'Dr. Ani',
                'abstrak' => 'Ringkasan riset.',
                'tahun' => 2024,
                'published_at' => '2024-01-10',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('risets', 0);
    }

    #[DataProvider('nonSubmitterRoles')]
    public function test_other_roles_cannot_open_or_submit_the_submission_form(string $role): void
    {
        $this->actingAs($this->userWithRole($role))
            ->get(route('admin.risets.create'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole($role))
            ->post(route('admin.risets.store'), [])
            ->assertForbidden();

        $this->assertDatabaseCount('risets', 0);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonSubmitterRoles(): array
    {
        return [
            'super admin' => ['super-admin'],
            'reviewer' => ['reviewer'],
            'bupati' => ['bupati'],
            'dprd' => ['dprd'],
            'asn' => ['asn'],
            'publik' => ['publik'],
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
