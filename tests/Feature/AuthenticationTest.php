<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
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
            'company_name' => 'Taylor Traders',
            'email' => 'taylor@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Taylor Example',
            'email' => 'taylor@example.com',
            'role' => 'manager',
        ]);
        $this->assertDatabaseHas('companies', ['name' => 'Taylor Traders']);
        $user = User::firstOrFail();
        $this->assertSame('Taylor Traders', $user->company->name);
        $this->assertTrue(Hash::check('secure-password', $user->password));
    }

    public function test_expired_csrf_token_redirects_to_login_with_a_message(): void
    {
        Route::middleware('web')->post('/_csrf-expired', fn () => throw new TokenMismatchException);

        $this->post('/_csrf-expired')
            ->assertRedirect('/login')
            ->assertSessionHas('status');
    }

    public function test_registration_requires_a_company_name(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Taylor Example',
            'email' => 'taylor@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('company_name');

        $this->assertDatabaseCount('users', 0);
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

    public function test_google_sign_in_creates_an_account_when_the_verified_email_is_new(): void
    {
        $this->mockGoogleUser('new.user@gmail.com', 'New User');

        $this->get('/auth/google/callback')->assertRedirect('/home');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'new.user@gmail.com',
            'name' => 'New User',
        ]);
        $this->assertNotNull(User::query()->where('email', 'new.user@gmail.com')->value('email_verified_at'));
        $user = User::where('email', 'new.user@gmail.com')->firstOrFail();
        $this->assertSame('manager', $user->role);
        $this->assertNotNull($user->company);
    }

    public function test_google_sign_in_uses_an_existing_account_with_the_same_email(): void
    {
        $user = User::create([
            'name' => 'Existing User',
            'email' => 'existing.user@gmail.com',
            'password' => 'existing-password',
        ]);

        $this->mockGoogleUser('existing.user@gmail.com', 'Google Name');

        $this->get('/auth/google/callback')->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('Existing User', $user->fresh()->name);
        $this->assertTrue(Hash::check('existing-password', $user->fresh()->password));
    }

    public function test_google_sign_in_rejects_an_unverified_email(): void
    {
        $this->mockGoogleUser('unverified@gmail.com', 'Unverified User', false);

        $this->get('/auth/google/callback')
            ->assertRedirect('/login')
            ->assertSessionHas('status');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    private function mockGoogleUser(string $email, string $name, bool $verified = true): void
    {
        $googleUser = new class($email, $name, $verified)
        {
            public array $user;

            public function __construct(
                private readonly string $email,
                private readonly string $name,
                bool $verified
            ) {
                $this->user = ['verified_email' => $verified];
            }

            public function getEmail(): string
            {
                return $this->email;
            }

            public function getName(): string
            {
                return $this->name;
            }
        };

        $provider = \Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }
}
