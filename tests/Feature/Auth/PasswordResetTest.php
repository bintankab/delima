<?php

namespace Tests\Feature\Auth;

use App\Models\UserModel;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_renders(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_requesting_a_link_for_a_registered_email_sends_a_notification(): void
    {
        Notification::fake();

        $user = UserModel::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_requesting_a_link_for_an_unregistered_email_shows_the_same_message_without_sending_anything(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'tidak-terdaftar@example.test'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Jika email tersebut terdaftar, tautan untuk mengatur ulang kata sandi telah dikirim.');

        Notification::assertNothingSent();
    }

    public function test_reset_password_page_renders_with_the_token_and_email(): void
    {
        $user = UserModel::factory()->create();
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/ResetPassword')
                ->where('token', $token)
                ->where('email', $user->email));
    }

    public function test_a_valid_token_resets_the_password_and_allows_login_with_it(): void
    {
        $user = UserModel::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'new-password-123',
        ])->assertRedirect(route('home'));
    }

    public function test_an_invalid_token_does_not_reset_the_password(): void
    {
        $user = UserModel::factory()->create();

        $this->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors(['email']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
