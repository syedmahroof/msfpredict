<?php

namespace App\Http\Controllers;

use App\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(private readonly SocialAuthService $socialAuthService) {}

    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception) {
            return redirect()->route('login')->withErrors(['social' => 'Authentication failed. Please try again.']);
        }

        $user = $this->socialAuthService->findOrCreateUser($socialUser, $provider);

        Auth::login($user, true);

        return redirect()->intended(route('home'));
    }

    private function validateProvider(string $provider): void
    {
        if (! in_array($provider, ['google', 'apple'])) {
            abort(404);
        }
    }
}
