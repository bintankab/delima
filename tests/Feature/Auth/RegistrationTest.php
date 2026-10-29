<?php

namespace Tests\Feature\Auth;

use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_creates_an_active_unverified_publik_account(): void
    {
        Notification::fake();
        Role::findOrCreate('publik', 'web');

        $this->post(route('register'), [
            'name' => 'Warga Bintan',
            'email' => 'warga@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        $user = UserModel::where('email', 'warga@example.test')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('publik'));
        $this->assertTrue($user->is_active);
        $this->assertNull($user->email_verified_at);
    }

    public function test_login_is_blocked_before_email_verification(): void
    {
        Role::findOrCreate('publik', 'web');
        $user = UserModel::factory()->unverified()->create();
        $user->assignRole('publik');

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors([
            'email' => 'Email Anda belum diverifikasi. Silakan cek email Anda untuk tautan verifikasi.',
        ]);

        $this->assertGuest();
    }

    public function test_login_is_blocked_for_a_deactivated_account(): void
    {
        Role::findOrCreate('publik', 'web');
        $user = UserModel::factory()->create(['is_active' => false]);
        $user->assignRole('publik');

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors([
            'email' => 'Akun Anda telah dinonaktifkan. Hubungi admin untuk informasi lebih lanjut.',
        ]);

        $this->assertGuest();
    }

    public function test_verification_link_marks_the_email_verified_without_requiring_login(): void
    {
        Role::findOrCreate('publik', 'web');
        $user = UserModel::factory()->unverified()->create();
        $user->assignRole('publik');

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->get($url)->assertRedirect(route('login'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verified_publik_user_can_log_in_without_waiting_for_admin_approval(): void
    {
        Role::findOrCreate('publik', 'web');
        $user = UserModel::factory()->create();
        $user->assignRole('publik');

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }
}
