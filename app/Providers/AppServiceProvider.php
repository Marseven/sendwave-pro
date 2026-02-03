<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Define SMS send rate limiter (per API key or per user)
        RateLimiter::for('sms-send', function (Request $request) {
            $apiKey = $request->attributes->get('api_key');

            if ($apiKey) {
                return Limit::perMinute($apiKey->rate_limit ?? 100)
                    ->by('api_key:' . $apiKey->id);
            }

            return Limit::perMinute(10)
                ->by('user:' . ($request->user()?->id ?: $request->ip()));
        });
    }
}
