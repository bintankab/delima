<?php

namespace Tests\Feature\Admin;

use App\Models\RisetModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RisetReviewTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('reviewerRoles')]
    public function test_reviewer_role_can_approve_a_submitted_riset(string $role): void
    {
        $riset = RisetModel::factory()->create(['status' => 'submitted']);
        $reviewer = $this->userWithRole($role);

        $this->actingAs($reviewer)
            ->post(route('admin.risets.approve', $riset), ['catatan' => 'Sudah sesuai kriteria.'])
            ->assertRedirect(route('admin.risets.show', $riset))
            ->assertSessionHas('success', 'Riset berhasil disetujui dan diterbitkan.');

        $riset->refresh();

        $this->assertSame('published', $riset->status);
        $this->assertSame($reviewer->id, $riset->assigned_reviewer_id);
        $this->assertDatabaseHas('reviews', [
            'reviewable_type' => RisetModel::class,
            'reviewable_id' => $riset->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'approved',
            'catatan' => 'Sudah sesuai kriteria.',
        ]);
    }

    #[DataProvider('reviewerRoles')]
    public function test_reviewer_role_can_reject_a_submitted_riset(string $role): void
    {
        $riset = RisetModel::factory()->create(['status' => 'under_review']);
        $reviewer = $this->userWithRole($role);

        $this->actingAs($reviewer)
            ->post(route('admin.risets.reject', $riset), ['catatan' => 'Dokumen laporan tidak lengkap.'])
            ->assertRedirect(route('admin.risets.show', $riset))
            ->assertSessionHas('success', 'Riset ditolak.');

        $riset->refresh();

        $this->assertSame('rejected', $riset->status);
        $this->assertDatabaseHas('reviews', [
            'reviewable_type' => RisetModel::class,
            'reviewable_id' => $riset->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'rejected',
            'catatan' => 'Dokumen laporan tidak lengkap.',
        ]);
    }

    public function test_rejecting_a_riset_requires_a_reason(): void
    {
        $riset = RisetModel::factory()->create(['status' => 'submitted']);

        $this->actingAs($this->userWithRole('reviewer'))
            ->post(route('admin.risets.reject', $riset), [])
            ->assertSessionHasErrors(['catatan' => 'Alasan penolakan wajib diisi.']);

        $this->assertSame('submitted', $riset->fresh()->status);
        $this->assertDatabaseCount('reviews', 0);
    }

    #[DataProvider('finalStatuses')]
    public function test_a_riset_that_already_has_a_final_status_cannot_be_reviewed_again(string $status): void
    {
        $riset = RisetModel::factory()->create(['status' => $status]);

        $this->actingAs($this->userWithRole('reviewer'))
            ->post(route('admin.risets.approve', $riset))
            ->assertRedirect()
            ->assertSessionHas('error', 'Riset ini tidak dapat ditinjau pada status saat ini.');

        $this->assertSame($status, $riset->fresh()->status);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_admin_opd_can_view_a_submitted_riset_but_is_not_offered_review_actions(): void
    {
        $riset = RisetModel::factory()->create(['status' => 'submitted']);

        $this->actingAs($this->userWithRole('admin-opd'))
            ->get(route('admin.risets.show', $riset))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('riset.status', 'submitted')
                ->where('riset.reviewable', false));
    }

    #[DataProvider('nonReviewerRoles')]
    public function test_a_non_reviewer_role_cannot_approve_or_reject_a_riset(string $role): void
    {
        $riset = RisetModel::factory()->create(['status' => 'submitted']);

        $this->actingAs($this->userWithRole($role))
            ->post(route('admin.risets.approve', $riset))
            ->assertForbidden();

        $this->actingAs($this->userWithRole($role))
            ->post(route('admin.risets.reject', $riset), ['catatan' => 'Alasan.'])
            ->assertForbidden();

        $this->assertSame('submitted', $riset->fresh()->status);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function reviewerRoles(): array
    {
        return [
            'super admin' => ['super-admin'],
            'reviewer' => ['reviewer'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonReviewerRoles(): array
    {
        return [
            'admin opd' => ['admin-opd'],
            'bupati' => ['bupati'],
            'dprd' => ['dprd'],
            'asn' => ['asn'],
            'publik' => ['publik'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function finalStatuses(): array
    {
        return [
            'published' => ['published'],
            'rejected' => ['rejected'],
            'draft' => ['draft'],
            'archived' => ['archived'],
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
