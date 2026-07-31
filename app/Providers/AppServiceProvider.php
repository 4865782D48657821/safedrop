<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('reports', function (Request $request) {
            $identity = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(20)->by("reports:{$identity}");
        });

        RateLimiter::for('redirect-previews', function (Request $request) {
            $identity = $request->user()?->id ?: $request->ip();

            return Limit::perMinute((int) config('safedrop.redirects.rate_limit_per_minute'))
                ->by("redirect-previews:{$identity}");
        });

        RateLimiter::for('redirect-outbound', function (Request $request) {
            $identity = $request->user()?->id ?: $request->ip();

            return Limit::perMinute((int) config('safedrop.redirects.rate_limit_per_minute'))
                ->by("redirect-outbound:{$identity}");
        });
    }
}
