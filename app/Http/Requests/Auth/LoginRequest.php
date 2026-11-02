<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [...$this->only('username', 'password'), 'is_admin' => true];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            $this->synchronizeConfiguredAdminPassword();

            if (Auth::attempt($credentials, $this->boolean('remember'))) {
                RateLimiter::clear($this->throttleKey());

                return;
            }

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Synchronize an existing administrator's password with the configured recovery password.
     */
    private function synchronizeConfiguredAdminPassword(): void
    {
        $configuredPassword = config('school.admin_password');
        $configuredUsername = config('school.admin_username');
        $submittedUsername = (string) $this->input('username');
        $submittedPassword = (string) $this->input('password');

        if (blank($configuredPassword)
            || ! hash_equals((string) $configuredUsername, $submittedUsername)
            || ! hash_equals((string) $configuredPassword, $submittedPassword)) {
            return;
        }

        $administrator = User::query()
            ->where('username', $configuredUsername)
            ->where('is_admin', true)
            ->first();

        if (! $administrator instanceof User) {
            return;
        }

        $administrator->forceFill([
            'password' => Hash::make($configuredPassword),
        ])->save();
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
