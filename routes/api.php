<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CampaignHistoryController;
use App\Http\Controllers\Api\MessageHistoryController;
use App\Http\Controllers\Api\MessageTemplateController;
use App\Http\Controllers\Api\SubAccountController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\SmsProviderController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/sub-accounts/login', [\App\Http\Controllers\SubAccountController::class, 'login']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Contacts
    Route::post('contacts/import', [ContactController::class, 'import']);
    Route::apiResource('contacts', ContactController::class);

    // Campaign History (doit être AVANT apiResource campaigns)
    Route::get('campaigns/history', [CampaignHistoryController::class, 'index']);
    Route::get('campaigns/stats', [CampaignHistoryController::class, 'stats']);

    // Campaigns
    Route::post('campaigns/{id}/send', [CampaignController::class, 'send']);
    Route::apiResource('campaigns', CampaignController::class);

    // Message Templates
    Route::apiResource('templates', MessageTemplateController::class);

    // Sub Accounts
    Route::post('sub-accounts/{id}/credits', [\App\Http\Controllers\SubAccountController::class, 'addCredits']);
    Route::post('sub-accounts/{id}/permissions', [\App\Http\Controllers\SubAccountController::class, 'updatePermissions']);
    Route::post('sub-accounts/{id}/suspend', [\App\Http\Controllers\SubAccountController::class, 'suspend']);
    Route::post('sub-accounts/{id}/activate', [\App\Http\Controllers\SubAccountController::class, 'activate']);
    Route::apiResource('sub-accounts', \App\Http\Controllers\SubAccountController::class);

    // API Keys
    Route::apiResource('api-keys', ApiKeyController::class);

    // SMS Providers (anciens)
    Route::get('sms-providers', [SmsProviderController::class, 'index']);
    Route::post('sms-providers', [SmsProviderController::class, 'store']);
    Route::get('sms-providers/{code}', [SmsProviderController::class, 'show']);
    Route::post('sms-providers/{code}/test', [SmsProviderController::class, 'test']);

    // SMS Configurations (Airtel & Moov)
    Route::get('sms-configs', [\App\Http\Controllers\Api\SmsConfigController::class, 'index']);
    Route::get('sms-configs/{provider}', [\App\Http\Controllers\Api\SmsConfigController::class, 'show']);
    Route::put('sms-configs/{provider}', [\App\Http\Controllers\Api\SmsConfigController::class, 'update']);
    Route::post('sms-configs/{provider}/test', [\App\Http\Controllers\Api\SmsConfigController::class, 'test']);
    Route::post('sms-configs/{provider}/toggle', [\App\Http\Controllers\Api\SmsConfigController::class, 'toggle']);

    // Message History (doit être AVANT les routes génériques)
    Route::get('messages/history', [MessageHistoryController::class, 'index']);
    Route::get('messages/stats', [MessageHistoryController::class, 'stats']);
    Route::get('messages/export', [MessageHistoryController::class, 'export']);

    // Messages avec rate limiting
    Route::post('messages/send', [MessageController::class, 'send'])
        ->middleware('throttle:sms-send');
    Route::post('messages/analyze', [MessageController::class, 'analyzeNumbers']);
    Route::post('messages/number-info', [MessageController::class, 'getNumberInfo']);

    // User Profile
    Route::get('user/profile', [AuthController::class, 'profile']);
    Route::put('user/profile', [AuthController::class, 'updateProfile']);
});
