<?php

namespace App\Services;

class SmsProviderFactory
{
    /**
     * Créer une instance du service provider
     *
     * @param string $code Code du provider (msg91, smsala, whapi)
     * @param array $config Configuration du provider
     * @return SmsProviderInterface
     * @throws \Exception
     */
    public static function make(string $code, array $config): SmsProviderInterface
    {
        return match ($code) {
            'msg91' => new Msg91Service($config),
            'smsala' => new SmsalaService($config),
            'whapi' => new WhapiService($config),
            default => throw new \Exception("Provider non supporté: {$code}"),
        };
    }
}
