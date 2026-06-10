<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
    }

    public function test_admin_receives_reset_link(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->post('/forgot-password', ['email' => $admin->email]);

        Notification::assertSentTo($admin, ResetPassword::class);
    }

    public function test_non_admin_is_rejected_and_gets_no_link(): void
    {
        Notification::fake();

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->post('/forgot-password', ['email' => $staff->email]);

        $response->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    public function test_inactive_admin_is_rejected_and_gets_no_link(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => false]);

        $response = $this->post('/forgot-password', ['email' => $admin->email]);

        $response->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    public function test_unknown_email_is_rejected_with_error(): void
    {
        Notification::fake();

        $response = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        $response->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    public function test_admin_request_sets_success_status(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->post('/forgot-password', ['email' => $admin->email]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status');
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->post('/forgot-password', ['email' => $admin->email]);

        Notification::assertSentTo($admin, ResetPassword::class, function ($notification) {
            $this->get('/reset-password/'.$notification->token)->assertStatus(200);

            return true;
        });
    }

    public function test_admin_can_reset_password_with_valid_token(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->post('/forgot-password', ['email' => $admin->email]);

        Notification::assertSentTo($admin, ResetPassword::class, function ($notification) use ($admin) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $admin->email,
                'password' => 'new-password-1234',
                'password_confirmation' => 'new-password-1234',
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect(route('login'));

            $this->assertTrue(Hash::check('new-password-1234', $admin->fresh()->password));

            return true;
        });
    }

    public function test_non_admin_cannot_reset_even_with_a_valid_token(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        // Force a token to exist (the request endpoint would never issue one to a
        // non-admin) and confirm the controller-side guard still blocks the reset.
        $token = Password::broker()->createToken($staff);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $staff->email,
            'password' => 'new-password-1234',
            'password_confirmation' => 'new-password-1234',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Hash::check('new-password-1234', $staff->fresh()->password));
    }
}
