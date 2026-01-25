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

    /*
    |--------------------------------------------------------------------------
    | Coût par SMS (en FCFA)
    |--------------------------------------------------------------------------
    */
    'cost_per_sms' => env('SMS_COST_PER_UNIT', 20), // 20 FCFA par défaut

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, if primary operator fails, system will try fallback operator
    |
    */
    'fallback_enabled' => env('SMS_FALLBACK_ENABLED', true),
    'fallback_order' => ['airtel', 'moov'], // Primary -> Fallback

    // Configuration Airtel Gabon
    'airtel' => [
        'api_url' => env('AIRTEL_API_URL', 'https://messaging.airtel.ga:9002/smshttp/qs/'),
        'username' => env('AIRTEL_USERNAME', ''),
        'password' => env('AIRTEL_PASSWORD', ''),
        'origin_addr' => env('AIRTEL_ORIGIN_ADDR', ''),
        'enabled' => env('AIRTEL_ENABLED', true),
        'cost_per_sms' => env('AIRTEL_COST_PER_SMS', 20), // Coût spécifique Airtel
    ],

    // Configuration Moov Gabon (SMPP)
    'moov' => [
        'host' => env('MOOV_SMPP_HOST', '172.16.59.66'),
        'port' => (int) env('MOOV_SMPP_PORT', 12775),
        'system_id' => env('MOOV_SMPP_SYSTEM_ID', ''),
        'password' => env('MOOV_SMPP_PASSWORD', ''),
        'source_addr' => env('MOOV_SMPP_SOURCE_ADDR', 'SENDWAVE'),
        'enabled' => env('MOOV_ENABLED', false),
        'cost_per_sms' => env('MOOV_COST_PER_SMS', 20),
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
