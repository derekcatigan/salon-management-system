<?php

namespace App\Http\Controllers\Website;

use App\Enum\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Website\RegisterWebRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RegisterWebController extends Controller
{
    public function index()
    {
        return Inertia::render('Website/RegisterWeb');
    }

    public function store(RegisterWebRequest $request): RedirectResponse
    {
        $config = config('auth_web.register');
        $throttleKey = 'register|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, $config['attempts'])) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many registration attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($throttleKey, $config['decay_minutes'] * 60);

        $user = DB::transaction(function () use ($request) {
            return User::create([
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'role' => RoleEnum::Customer,
                'status' => 'active',
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('web.home')
            ->with('success', 'Account created successfully.');
    }
}
