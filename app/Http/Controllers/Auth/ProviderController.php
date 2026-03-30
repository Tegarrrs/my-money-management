<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class ProviderController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::where('google_id', $googleUser->id)->orWhere('email', $googleUser->email)->first();

            if ($user) {
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar ?? $user->avatar
                    ]);
                } else if ($googleUser->avatar && $user->avatar !== $googleUser->avatar) {
                    $user->update(['avatar' => $googleUser->avatar]);
                }
                Auth::login($user);
                return redirect()->intended(route('dashboard', absolute: false));
            }

            $newUser = User::create([
                'name' => $googleUser->name ?? $googleUser->nickname,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'password' => null, // Password nullable since it's OAuth
                'avatar' => $googleUser->avatar
            ]);

            Auth::login($newUser);

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (\Exception $e) {
            return redirect('/login')->withErrors(['email' => 'Gagal masuk dengan Google. Silakan coba lagi.']);
        }
    }
}
