<?php

namespace Tests\Feature\Admin;

use App\Models\InnovationModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InnovationStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_override_the_status_of_an_innovation(): void
    {
        $innovation = InnovationModel::factory()->create(['status' => 'published']);
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->put(route('admin.innovations.update-status', $innovation), [
                'status' => 'archived',
                'catatan' => 'Inovasi dibatalkan karena kesalahan input.',
            ])
            ->assertRedirect(route('admin.innovations.show', $innovation))
            ->assertSessionHas('success', 'Status inovasi berhasil diubah.');

        $innovation->refresh();

        $this->assertSame('archived', $innovation->status);
        $this->assertDatabaseHas('reviews', [
            'reviewable_type' => InnovationModel::class,
            'reviewable_id' => $innovation->id,
            'reviewer_id' => $superAdmin->id,
            'status' => 'archived',
            'catatan' => 'Inovasi dibatalkan karena kesalahan input.',
        ]);
    }

    public function test_updating_status_requires_a_reason(): void
    {
        $innovation = InnovationModel::factory()->create(['status' => 'published']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.innovations.update-status', $innovation), ['status' => 'archived'])
            ->assertSessionHasErrors(['catatan' => 'Alasan perubahan status wajib diisi.']);

        $this->assertSame('published', $innovation->fresh()->status);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_updating_status_rejects_an_invalid_status_value(): void
    {
        $innovation = InnovationModel::factory()->create(['status' => 'published']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.innovations.update-status', $innovation), [
                'status' => 'not-a-real-status',
                'catatan' => 'Alasan.',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('published', $innovation->fresh()->status);
    }

    public function test_updating_status_to_the_same_value_is_rejected(): void
    {
        $innovation = InnovationModel::factory()->create(['status' => 'published']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.innovations.update-status', $innovation), [
                'status' => 'published',
                'catatan' => 'Alasan.',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Status baru sama dengan status saat ini.');

        $this->assertDatabaseCount('reviews', 0);
    }

    #[DataProvider('nonSuperAdminRoles')]
    public function test_a_non_super_admin_cannot_override_the_status(string $role): void
    {
        $innovation = InnovationModel::factory()->create(['status' => 'published']);

        $this->actingAs($this->userWithRole($role))
            ->put(route('admin.innovations.update-status', $innovation), [
                'status' => 'archived',
                'catatan' => 'Alasan.',
            ])
            ->assertForbidden();

        $this->assertSame('published', $innovation->fresh()->status);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonSuperAdminRoles(): array
    {
        return [
            'reviewer' => ['reviewer'],
            'admin opd' => ['admin-opd'],
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
