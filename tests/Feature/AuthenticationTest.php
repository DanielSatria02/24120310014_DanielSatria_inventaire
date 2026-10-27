<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_registration_pages_are_available_to_guests(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_registration_creates_a_user_and_authenticates_them(): void
    {
        $response = $this->post('/register', [
            'name' => 'Taylor Example',
            'email' => 'taylor@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Taylor Example',
            'email' => 'taylor@example.com',
        ]);
        $this->assertTrue(Hash::check('secure-password', User::firstOrFail()->password));
    }

    public function test_registration_requires_a_unique_email_and_matching_password_confirmation(): void
    {
        User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => 'secure-password',
        ]);

        $this->from('/register')->post('/register', [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_login_and_logout_manage_the_authenticated_session(): void
    {
        $user = User::create([
            'name' => 'Taylor Example',
            'email' => 'taylor@example.com',
            'password' => 'secure-password',
        ]);

        $this->post('/login', [
            'email' => 'taylor@example.com',
            'password' => 'secure-password',
        ])->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_home_requires_authentication(): void
    {
        $this->get('/home')->assertRedirect('/login');
    }
}
