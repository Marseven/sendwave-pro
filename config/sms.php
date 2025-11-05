<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuration SMS - Opérateurs Gabonais
    |--------------------------------------------------------------------------
    |
    | Configuration pour les APIs SMS des opérateurs Airtel et Moov Gabon
    |
    */

    // Configuration Airtel Gabon
    'airtel' => [
        'api_url' => env('AIRTEL_API_URL', 'https://messaging.airtel.ga:9002/smshttp/qs/'),
        'username' => env('AIRTEL_USERNAME', ''),
        'password' => env('AIRTEL_PASSWORD', ''),
        'origin_addr' => env('AIRTEL_ORIGIN_ADDR', ''),
        'enabled' => env('AIRTEL_ENABLED', true),
    ],

    // Configuration Moov Gabon (à configurer ultérieurement)
    'moov' => [
        'enabled' => env('MOOV_ENABLED', false), // Désactivé par défaut (API non disponible)
    ],

    /*
    |--------------------------------------------------------------------------
    | Anciens Providers (Désactivés)
    |--------------------------------------------------------------------------
    |
    | MSG91, SMSALA et WAPI sont désactivés mais conservés pour référence
    |
    */

    'msg91' => [
        'enabled' => false,
        'api_key' => env('MSG91_API_KEY', ''),
        'sender_id' => env('MSG91_SENDER_ID', ''),
    ],

    'smsala' => [
        'enabled' => false,
        'api_key' => env('SMSALA_API_KEY', ''),
        'sender_id' => env('SMSALA_SENDER_ID', ''),
    ],

    'wapi' => [
        'enabled' => false,
        'api_key' => env('WAPI_API_KEY', ''),
        'sender_id' => env('WAPI_SENDER_ID', ''),
    ],

];
