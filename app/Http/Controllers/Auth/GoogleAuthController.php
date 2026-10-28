<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();
        $email = Str::lower(trim((string) $googleUser->getEmail()));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! ($googleUser->user['verified_email'] ?? false)) {
            return redirect()->route('login')->with(
                'status',
                'Google did not provide a verified email address. Please try again or use email and password.'
            );
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user === null) {
            $user = DB::transaction(function () use ($googleUser, $email) {
                $name = $googleUser->getName() ?: Str::before($email, '@');
                $company = Company::create(['name' => $name."'s Company"]);

                $user = new User;
                $user->companyID = $company->companyID;
                $user->role = User::ROLE_MANAGER;
                $user->name = $name;
                $user->email = $email;
                $user->password = Str::random(64);
                $user->email_verified_at = now();
                $user->save();

                return $user;
            });
        } elseif ($user->email_verified_at === null) {
            $user->email_verified_at = now();
            $user->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }
}
