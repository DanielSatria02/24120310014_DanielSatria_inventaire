<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // OWASP ASVS: long passphrases (12-64 chars) instead of composition rules.
        Password::defaults(fn () => Password::min(12)->max(64));
    }
}
