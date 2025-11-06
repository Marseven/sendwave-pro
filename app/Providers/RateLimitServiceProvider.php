<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Rate limit pour l'envoi de SMS
        // Maximum 100 SMS par minute par utilisateur
        RateLimiter::for('sms-send', function (Request $request) {
            return Limit::perMinute(100)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Trop de requêtes. Veuillez patienter avant de réessayer.',
                        'error' => 'rate_limit_exceeded',
                        'limit' => '100 SMS par minute',
                        'retry_after' => $headers['Retry-After'] ?? 60
                    ], 429, $headers);
                });
        });

        // Rate limit global pour l'API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(200)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Rate limit pour les campagnes
        RateLimiter::for('campaign-send', function (Request $request) {
            return [
                // Max 5 campagnes par heure
                Limit::perHour(5)
                    ->by($request->user()?->id)
                    ->response(function () {
                        return response()->json([
                            'message' => 'Limite de campagnes atteinte',
                            'error' => 'campaign_limit_exceeded',
                            'limit' => '5 campagnes par heure'
                        ], 429);
                    }),
                // Max 10000 SMS par heure
                Limit::perHour(10000)
                    ->by('campaign-sms-' . $request->user()?->id)
                    ->response(function () {
                        return response()->json([
                            'message' => 'Limite de SMS en masse atteinte',
                            'error' => 'bulk_sms_limit_exceeded',
                            'limit' => '10000 SMS par heure'
                        ], 429);
                    }),
            ];
        });
    }
}
