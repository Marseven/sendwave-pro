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
    Route::apiResource('campaigns', CampaignController::class);

    // Message Templates
    Route::apiResource('templates', MessageTemplateController::class);

    // Sub Accounts
    Route::apiResource('sub-accounts', SubAccountController::class);

    // API Keys
    Route::apiResource('api-keys', ApiKeyController::class);

    // SMS Providers
    Route::get('sms-providers', [SmsProviderController::class, 'index']);
    Route::post('sms-providers', [SmsProviderController::class, 'store']);
    Route::get('sms-providers/{code}', [SmsProviderController::class, 'show']);
    Route::post('sms-providers/{code}/test', [SmsProviderController::class, 'test']);

    // Message History (doit être AVANT les routes génériques)
    Route::get('messages/history', [MessageHistoryController::class, 'index']);
    Route::get('messages/stats', [MessageHistoryController::class, 'stats']);
    Route::get('messages/export', [MessageHistoryController::class, 'export']);

    // Messages
    Route::post('messages/send', [MessageController::class, 'send']);
    Route::post('messages/analyze', [MessageController::class, 'analyzeNumbers']);
    Route::post('messages/number-info', [MessageController::class, 'getNumberInfo']);

    // User Profile
    Route::get('user/profile', [AuthController::class, 'profile']);
    Route::put('user/profile', [AuthController::class, 'updateProfile']);
});
