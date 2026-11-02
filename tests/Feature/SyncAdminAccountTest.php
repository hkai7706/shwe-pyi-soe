<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SyncAdminAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_resets_the_configured_admin_password(): void
    {
        config([
            'school.admin_name' => 'Production Administrator',
            'school.admin_username' => 'production-admin',
            'school.admin_email' => 'admin@example.com',
            'school.admin_password' => 'NewPassword123!',
        ]);

        $this->artisan('app:sync-admin-account --password')
            ->expectsOutput('The staff administrator account has been synchronized.')
            ->assertSuccessful();

        $admin = User::where('username', 'production-admin')->firstOrFail();

        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check('NewPassword123!', $admin->password));
    }

    public function test_it_keeps_the_existing_password_without_the_password_option(): void
    {
        config([
            'school.admin_name' => 'Production Administrator',
            'school.admin_username' => 'production-admin',
            'school.admin_email' => 'admin@example.com',
            'school.admin_password' => 'NewPassword123!',
        ]);

        $admin = User::factory()->create([
            'name' => 'Old Administrator',
            'username' => 'production-admin',
            'email' => 'old@example.com',
            'is_admin' => true,
        ]);

        $this->artisan('app:sync-admin-account')->assertSuccessful();

        $admin->refresh();

        $this->assertSame('Production Administrator', $admin->name);
        $this->assertSame('admin@example.com', $admin->email);
        $this->assertTrue(Hash::check('password', $admin->password));
    }
}
