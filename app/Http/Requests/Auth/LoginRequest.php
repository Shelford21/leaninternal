<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        // return [
        //     'email' => ['required', 'string', 'email'],
        //     'password' => ['required', 'string'],
        // ];

        return [
            'username' => ['nullable', 'string', 'required_without:email'],
            'email' => ['nullable', 'string', 'email', 'required_without:username'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */


    // public function authenticate(): void
    // {
    //     $this->ensureIsNotRateLimited();

    //     if (!Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
    //         RateLimiter::hit($this->throttleKey());

    //         throw ValidationException::withMessages([
    //             'email' => trans('auth.failed'),
    //         ]);
    //     }

    //     RateLimiter::clear($this->throttleKey());
    // }
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->filled('email')
            ? $this->only('email', 'password')
            : $this->only('username', 'password');

        // Normalize to lowercase for case-insensitive login
        if (isset($credentials['email'])) {
            $credentials['email'] = strtolower($credentials['email']);
        }
        if (isset($credentials['username'])) {
            $credentials['username'] = strtolower($credentials['username']);
        }

        if (!Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            $field = $this->filled('email') ? 'email' : 'username';

            throw ValidationException::withMessages([
                $field => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    // public function ensureIsNotRateLimited(): void
    // {
    //     if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
    //         return;
    //     }

    //     event(new Lockout($this));

    //     $seconds = RateLimiter::availableIn($this->throttleKey());

    //     throw ValidationException::withMessages([
    //         'email' => trans('auth.throttle', [
    //             'seconds' => $seconds,
    //             'minutes' => ceil($seconds / 60),
    //         ]),
    //     ]);
    // }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
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
    // public function throttleKey(): string
    // {
    //     return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    // }

    public function throttleKey(): string
    {
        $identifier = $this->filled('email') ? $this->string('email') : $this->string('username');

        return Str::transliterate(Str::lower($identifier) . '|' . $this->ip());
    }
}
