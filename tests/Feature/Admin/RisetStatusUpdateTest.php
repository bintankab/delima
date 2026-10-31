<?php

namespace Tests\Feature\Admin;

use App\Models\RisetModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RisetStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_override_the_status_of_a_riset(): void
    {
        $riset = RisetModel::factory()->create(['status' => 'published']);
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->put(route('admin.risets.update-status', $riset), [
                'status' => 'archived',
                'catatan' => 'Riset dibatalkan karena kesalahan input.',
            ])
            ->assertRedirect(route('admin.risets.show', $riset))
            ->assertSessionHas('success', 'Status riset berhasil diubah.');

        $riset->refresh();

        $this->assertSame('archived', $riset->status);
        $this->assertDatabaseHas('reviews', [
            'reviewable_type' => RisetModel::class,
            'reviewable_id' => $riset->id,
            'reviewer_id' => $superAdmin->id,
            'status' => 'archived',
            'catatan' => 'Riset dibatalkan karena kesalahan input.',
        ]);
    }

    public function test_updating_status_requires_a_reason(): void
    {
        $riset = RisetModel::factory()->create(['status' => 'published']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.risets.update-status', $riset), ['status' => 'archived'])
            ->assertSessionHasErrors(['catatan' => 'Alasan perubahan status wajib diisi.']);

        $this->assertSame('published', $riset->fresh()->status);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_updating_status_rejects_an_invalid_status_value(): void
    {
        $riset = RisetModel::factory()->create(['status' => 'published']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.risets.update-status', $riset), [
                'status' => 'not-a-real-status',
                'catatan' => 'Alasan.',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('published', $riset->fresh()->status);
    }

    public function test_updating_status_to_the_same_value_is_rejected(): void
    {
        $riset = RisetModel::factory()->create(['status' => 'published']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.risets.update-status', $riset), [
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
        $riset = RisetModel::factory()->create(['status' => 'published']);

        $this->actingAs($this->userWithRole($role))
            ->put(route('admin.risets.update-status', $riset), [
                'status' => 'archived',
                'catatan' => 'Alasan.',
            ])
            ->assertForbidden();

        $this->assertSame('published', $riset->fresh()->status);
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
