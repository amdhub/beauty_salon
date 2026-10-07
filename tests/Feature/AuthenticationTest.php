<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_valid_credentials_sign_in_and_logout(): void
    {
        $user = User::factory()->create(['email' => 'salon@example.test', 'password' => 'StrongPassword123']);
        $this->post('/login', ['email' => 'SALON@example.test', 'password' => 'StrongPassword123'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'incorrect'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_malformed_login_fields_are_validation_errors(): void
    {
        $this->post('/login', ['email' => ['unexpected'], 'password' => 'invalid'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_repeated_login_attempts_are_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'limited@example.test', 'password' => 'bad']);
        }
        $this->post('/login', ['email' => 'limited@example.test', 'password' => 'bad'])->assertTooManyRequests();
    }

    public function test_password_change_requires_current_password_and_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword123']);
        $this->actingAs($user)->put('/account/password', ['current_password' => 'wrong', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertSessionHasErrors('current_password');
        $this->put('/account/password', ['current_password' => 'OldPassword123', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
    }
}
