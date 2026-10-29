<?php

namespace Tests\Feature\Admin;

use App\Models\InnovationModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InnovationReviewTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('reviewerRoles')]
    public function test_reviewer_role_can_approve_a_submitted_innovation(string $role): void
    {
        $innovation = InnovationModel::factory()->create(['status' => 'submitted']);
        $reviewer = $this->userWithRole($role);

        $this->actingAs($reviewer)
            ->post(route('admin.innovations.approve', $innovation), ['catatan' => 'Sudah sesuai kriteria.'])
            ->assertRedirect(route('admin.innovations.show', $innovation))
            ->assertSessionHas('success', 'Inovasi berhasil disetujui dan diterbitkan.');

        $innovation->refresh();

        $this->assertSame('published', $innovation->status);
        $this->assertSame($reviewer->id, $innovation->assigned_reviewer_id);
        $this->assertDatabaseHas('reviews', [
            'reviewable_type' => InnovationModel::class,
            'reviewable_id' => $innovation->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'approved',
            'catatan' => 'Sudah sesuai kriteria.',
        ]);
    }

    #[DataProvider('reviewerRoles')]
    public function test_reviewer_role_can_reject_a_submitted_innovation(string $role): void
    {
        $innovation = InnovationModel::factory()->create(['status' => 'under_review']);
        $reviewer = $this->userWithRole($role);

        $this->actingAs($reviewer)
            ->post(route('admin.innovations.reject', $innovation), ['catatan' => 'Dokumen pendukung tidak lengkap.'])
            ->assertRedirect(route('admin.innovations.show', $innovation))
            ->assertSessionHas('success', 'Inovasi ditolak.');

        $innovation->refresh();

        $this->assertSame('rejected', $innovation->status);
        $this->assertDatabaseHas('reviews', [
            'reviewable_type' => InnovationModel::class,
            'reviewable_id' => $innovation->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'rejected',
            'catatan' => 'Dokumen pendukung tidak lengkap.',
        ]);
    }

    public function test_rejecting_an_innovation_requires_a_reason(): void
    {
        $innovation = InnovationModel::factory()->create(['status' => 'submitted']);

        $this->actingAs($this->userWithRole('reviewer'))
            ->post(route('admin.innovations.reject', $innovation), [])
            ->assertSessionHasErrors(['catatan' => 'Alasan penolakan wajib diisi.']);

        $this->assertSame('submitted', $innovation->fresh()->status);
        $this->assertDatabaseCount('reviews', 0);
    }

    #[DataProvider('finalStatuses')]
    public function test_an_innovation_that_already_has_a_final_status_cannot_be_reviewed_again(string $status): void
    {
        $innovation = InnovationModel::factory()->create(['status' => $status]);

        $this->actingAs($this->userWithRole('reviewer'))
            ->post(route('admin.innovations.approve', $innovation))
            ->assertRedirect()
            ->assertSessionHas('error', 'Inovasi ini tidak dapat ditinjau pada status saat ini.');

        $this->assertSame($status, $innovation->fresh()->status);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_admin_opd_can_view_a_submitted_innovation_but_is_not_offered_review_actions(): void
    {
        $innovation = InnovationModel::factory()->create(['status' => 'submitted']);

        $this->actingAs($this->userWithRole('admin-opd'))
            ->get(route('admin.innovations.show', $innovation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('innovation.status', 'submitted')
                ->where('innovation.reviewable', false));
    }

    #[DataProvider('nonReviewerRoles')]
    public function test_a_non_reviewer_role_cannot_approve_or_reject_an_innovation(string $role): void
    {
        $innovation = InnovationModel::factory()->create(['status' => 'submitted']);

        $this->actingAs($this->userWithRole($role))
            ->post(route('admin.innovations.approve', $innovation))
            ->assertForbidden();

        $this->actingAs($this->userWithRole($role))
            ->post(route('admin.innovations.reject', $innovation), ['catatan' => 'Alasan.'])
            ->assertForbidden();

        $this->assertSame('submitted', $innovation->fresh()->status);
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
     * admin-opd can view innovations but must not approve/reject its own OPD's
     * submissions, so it is excluded from reviewerRoles.
     *
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
