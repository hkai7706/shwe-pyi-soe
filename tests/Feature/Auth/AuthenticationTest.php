<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_login_and_logout(): void
    {
        $user = User::factory()->create(['username' => 'principal', 'is_admin' => true]);
        $this->get('/staff/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->post('/staff/login', ['username' => 'principal', 'password' => 'password'])->assertRedirect(route('admin.overview'));
        $this->assertAuthenticatedAs($user);
        $this->post('/staff/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_wrong_password_and_non_admin_are_rejected(): void
    {
        User::factory()->create(['username' => 'principal', 'is_admin' => true]);
        $this->post('/staff/login', ['username' => 'principal', 'password' => 'wrong'])->assertSessionHasErrors('username');
        $this->assertGuest();
        User::factory()->create(['username' => 'visitor', 'is_admin' => false]);
        $this->post('/staff/login', ['username' => 'visitor', 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_configured_admin_password_synchronizes_an_existing_administrator(): void
    {
        config([
            'school.admin_username' => 'principal',
            'school.admin_password' => 'ConfiguredPassword123!',
        ]);

        $user = User::factory()->create([
            'username' => 'principal',
            'is_admin' => true,
            'password' => 'PreviousPassword123!',
        ]);

        $this->post('/staff/login', [
            'username' => 'principal',
            'password' => 'ConfiguredPassword123!',
        ])->assertRedirect(route('admin.overview'));

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('ConfiguredPassword123!', $user->fresh()->password));
    }

    public function test_repeated_failures_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/staff/login', ['username' => 'unknown', 'password' => 'wrong']);
        }
        $this->assertTrue(RateLimiter::tooManyAttempts('unknown|127.0.0.1', 5));
    }

    public function test_public_registration_and_default_login_are_unavailable(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
        $this->get('/login')->assertNotFound();
    }
}
