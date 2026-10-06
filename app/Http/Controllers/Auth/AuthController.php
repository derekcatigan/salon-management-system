<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 3;

    private const DECAY_SECONDS = 30;

    public function index()
    {
        $salonLogo = asset('assets/logos/SalonLogo.jpg');

        return Inertia::render('Auth/Login', [
            'logo' => $salonLogo,
        ]);
    }

    public function throttleStatus(Request $request)
    {
        $email = (string) $request->query('email', '');
        $key = $this->throttleKey($email, $request->ip());

        return response()->json([
            'attempts' => RateLimiter::attempts($key),
            'max_attempts' => self::MAX_ATTEMPTS,
            'available_in' => RateLimiter::availableIn($key),
            'locked' => RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS),
        ]);
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = $this->throttleKey($credentials['email'], $request->ip());

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            $this->flashThrottle($seconds, self::MAX_ATTEMPTS);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            $attempts = RateLimiter::attempts($key);

            if ($attempts >= self::MAX_ATTEMPTS) {
                RateLimiter::clear($key);

                for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
                    RateLimiter::hit($key, self::DECAY_SECONDS);
                }

                $seconds = RateLimiter::availableIn($key);

                $this->flashThrottle($seconds, self::MAX_ATTEMPTS);

                throw ValidationException::withMessages([
                    'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
                ]);
            }

            $this->flashThrottle(0, $attempts);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (\in_array($user->status, ['inactive', 'suspended'], true)) {
            throw ValidationException::withMessages([
                'email' => $user->status === 'suspended'
                    ? 'Your account has been suspended. Please contact an administrator.'
                    : 'Your account is inactive. Please contact an administrator.',
            ]);
        }

        RateLimiter::clear($key);
        session()->forget(['throttle_seconds', 'throttle_attempts', 'throttle_max']);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function throttleKey(string $email, ?string $ip): string
    {
        return Str::transliterate(Str::lower($email).'|'.$ip);
    }

    private function flashThrottle(int $seconds, int $attempts): void
    {
        session()->flash('throttle_seconds', $seconds);
        session()->flash('throttle_attempts', $attempts);
        session()->flash('throttle_max', self::MAX_ATTEMPTS);
    }
}
