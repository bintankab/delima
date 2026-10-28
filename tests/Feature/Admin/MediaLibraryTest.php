<?php

namespace Tests\Feature\Admin;

use App\Models\MediaLibraryItemModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_the_media_library(): void
    {
        $this->getJson(route('admin.media-library.index'))->assertUnauthorized();
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_role_cannot_access_the_media_library(string $role): void
    {
        $this->actingAs($this->userWithRole($role))
            ->getJson(route('admin.media-library.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_list_uploaded_images(): void
    {
        Storage::fake('public');

        $item = MediaLibraryItemModel::query()->create();
        $item->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('file');

        $response = $this->actingAs($this->userWithRole('super-admin'))
            ->getJson(route('admin.media-library.index'))
            ->assertOk();

        $response->assertJsonPath('data.0.name', 'old.jpg');
        $response->assertJsonPath('data.0.id', $item->id);
    }

    public function test_super_admin_can_upload_an_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->userWithRole('super-admin'))
            ->postJson(route('admin.media-library.store'), [
                'file' => UploadedFile::fake()->image('banner.jpg', 800, 600),
            ])
            ->assertCreated();

        $response->assertJsonStructure(['id', 'url', 'thumb', 'name', 'size', 'created_at']);
        $response->assertJsonPath('name', 'banner.jpg');

        $data = $response->json();
        $this->assertStringStartsWith('/', $data['url']);
        $this->assertStringStartsWith('/', $data['thumb']);

        $this->assertDatabaseCount('media_library_items', 1);
        $this->assertDatabaseHas('media', [
            'model_type' => MediaLibraryItemModel::class,
            'collection_name' => 'file',
            'file_name' => 'banner.jpg',
        ]);
    }

    public function test_upload_rejects_a_non_image_file(): void
    {
        Storage::fake('public');

        $this->actingAs($this->userWithRole('super-admin'))
            ->postJson(route('admin.media-library.store'), [
                'file' => UploadedFile::fake()->create('document.pdf', 100),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        $this->assertDatabaseCount('media_library_items', 0);
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
