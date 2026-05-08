<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
        Vite::prefetch(concurrency: 3);

        // Define a general limit for viewing the contact list
        RateLimiter::for('contacts-view', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Define a strict limit for creating or modifying data
        RateLimiter::for('contacts-modify', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function (Request $request, array $headers) {
                return back()->withErrors([
                    'error' => 'System cooldown: Please wait a moment before sending more data.'
                ]);
            });
        });
    }
}