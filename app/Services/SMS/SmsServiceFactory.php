<?php

namespace App\Services\SMS;

use InvalidArgumentException;

class SmsServiceFactory
{
    /**
     * Créer une instance de service SMS selon le provider
     *
     * @param string $provider Le provider (msg91, smsala, wapi)
     * @return SmsServiceInterface
     * @throws InvalidArgumentException
     */
    public static function make(string $provider): SmsServiceInterface
    {
        return match (strtolower($provider)) {
            'msg91' => new Msg91Service(),
            'smsala' => new SmsAlaService(),
            'wapi' => new WapiService(),
            default => throw new InvalidArgumentException("Provider SMS non supporté: {$provider}"),
        };
    }
}
