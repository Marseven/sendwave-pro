<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'msg91' => [
        'api_key' => env('MSG91_API_KEY'),
        'sender_id' => env('MSG91_SENDER_ID', 'JOBSMS'),
    ],

    'smsala' => [
        'api_key' => env('SMSALA_API_KEY'),
        'sender_id' => env('SMSALA_SENDER_ID', 'JOBSMS'),
    ],

    'wapi' => [
        'api_key' => env('WAPI_API_KEY'),
        'sender_id' => env('WAPI_SENDER_ID', 'JOBSMS'),
    ],

];
