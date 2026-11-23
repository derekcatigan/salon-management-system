<?php

namespace App\Http\Controllers\Website;

use App\Enum\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Website\LoginWebRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthWebController extends Controller
{
    public function index()
    {
        return Inertia::render('Website/LoginWeb');
    }

    public function store(LoginWebRequest $request): RedirectResponse
    {
        $config = config('auth_web.login');
        $maxAttempts = (int) $config['attempts'];
        $decaySeconds = (int) $config['decay_minutes'] * 60;
        $throttleKey = $this->throttleKey($request->input('email'), $request->ip());

        // 1. Throttled?
        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->status(429);
        }

        // 2. Attempt
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, $decaySeconds);

            $attemptsLeft = RateLimiter::remaining($throttleKey, $maxAttempts);
            $retryAfter = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => $attemptsLeft > 0
                    ? "Invalid credentials. You have {$attemptsLeft} attempt(s) left."
                    : "Too many login attempts. Please try again in {$retryAfter} seconds.",
            ]);
        }

        // 3. Customer-only gate
        if (Auth::user()->role !== RoleEnum::Customer) {
            Auth::logout();
            RateLimiter::hit($throttleKey, $decaySeconds);

            throw ValidationException::withMessages([
                'email' => 'This account is not allowed to sign in here.',
            ]);
        }

        // 4. Success — always send the customer to the storefront
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->route('web.home');
    }

    protected function throttleKey(string $email, ?string $ip): string
    {
        return Str::transliterate(Str::lower($email).'|'.$ip);
    }
}
