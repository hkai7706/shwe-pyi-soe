<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SyncAdminAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-admin-account
                            {--password : Reset the password from ADMIN_PASSWORD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or synchronize the configured staff administrator account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $username = (string) config('school.admin_username');
        $email = (string) config('school.admin_email');
        $password = config('school.admin_password');

        if (blank($username) || blank($email)) {
            $this->error('ADMIN_USERNAME and ADMIN_EMAIL must be configured.');

            return self::FAILURE;
        }

        $admin = User::where('username', $username)->first();

        if ($admin && ! $admin->is_admin) {
            $this->error('The configured username belongs to a non-admin user.');

            return self::FAILURE;
        }

        $admin ??= User::where('is_admin', true)->first() ?? new User;

        $attributes = [
            'name' => config('school.admin_name'),
            'username' => $username,
            'email' => $email,
            'is_admin' => true,
        ];

        if (! $admin->exists || $this->option('password')) {
            if (blank($password)) {
                $this->error('ADMIN_PASSWORD must be configured to create or reset the administrator password.');

                return self::FAILURE;
            }

            $attributes['password'] = Hash::make($password);
        }

        $admin->fill($attributes)->save();

        $this->info('The staff administrator account has been synchronized.');

        return self::SUCCESS;
    }
}
