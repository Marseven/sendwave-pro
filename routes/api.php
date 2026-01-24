<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CampaignHistoryController;
use App\Http\Controllers\Api\MessageHistoryController;
use App\Http\Controllers\Api\MessageTemplateController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\SmsProviderController;
use App\Http\Controllers\SubAccountController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/sub-accounts/login', [SubAccountController::class, 'login']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Auth (accessible à tous les utilisateurs authentifiés)
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('user/profile', [AuthController::class, 'profile']);
    Route::put('user/profile', [AuthController::class, 'updateProfile']);

    // Contacts (permission: manage_contacts)
    Route::middleware('permission:manage_contacts')->group(function () {
        Route::post('contacts/import', [ContactController::class, 'import']);
        Route::apiResource('contacts', ContactController::class);
    });

    // Contact Groups (permission: manage_groups)
    Route::middleware('permission:manage_groups')->group(function () {
        Route::post('contact-groups/{id}/contacts/add', [\App\Http\Controllers\ContactGroupController::class, 'addContacts']);
        Route::post('contact-groups/{id}/contacts/remove', [\App\Http\Controllers\ContactGroupController::class, 'removeContacts']);
        Route::get('contact-groups/{id}/contacts', [\App\Http\Controllers\ContactGroupController::class, 'getContacts']);
        Route::apiResource('contact-groups', \App\Http\Controllers\ContactGroupController::class);
    });

    // Campaign History (permission: view_history)
    Route::middleware('permission:view_history')->group(function () {
        Route::get('campaigns/history', [CampaignHistoryController::class, 'index']);
        Route::get('campaigns/stats', [CampaignHistoryController::class, 'stats']);
    });

    // Campaigns (permission: create_campaigns)
    Route::middleware('permission:create_campaigns')->group(function () {
        Route::post('campaigns/{id}/send', [CampaignController::class, 'send']);
        Route::post('campaigns/{id}/schedule', [CampaignController::class, 'storeSchedule']);
        Route::get('campaigns/{id}/schedule', [CampaignController::class, 'getSchedule']);
        Route::delete('campaigns/{id}/schedule', [CampaignController::class, 'deleteSchedule']);
        Route::post('campaigns/{id}/variants', [CampaignController::class, 'storeVariants']);
        Route::get('campaigns/{id}/variants', [CampaignController::class, 'getVariants']);
        Route::delete('campaigns/{id}/variants', [CampaignController::class, 'deleteVariants']);
        Route::apiResource('campaigns', CampaignController::class);
    });

    // Message Templates (permission: manage_templates)
    Route::middleware('permission:manage_templates')->group(function () {
        Route::get('templates/categories', [MessageTemplateController::class, 'categories']);
        Route::post('templates/{id}/use', [MessageTemplateController::class, 'use']);
        Route::post('templates/{id}/preview', [MessageTemplateController::class, 'preview']);
        Route::apiResource('templates', MessageTemplateController::class);
    });

    // Sub Accounts (réservé au compte parent uniquement - pas de permission)
    Route::post('sub-accounts/{id}/credits', [SubAccountController::class, 'addCredits']);
    Route::post('sub-accounts/{id}/permissions', [SubAccountController::class, 'updatePermissions']);
    Route::post('sub-accounts/{id}/suspend', [SubAccountController::class, 'suspend']);
    Route::post('sub-accounts/{id}/activate', [SubAccountController::class, 'activate']);
    Route::apiResource('sub-accounts', SubAccountController::class);

    // API Keys (réservé au compte parent uniquement)
    Route::apiResource('api-keys', ApiKeyController::class);

    // SMS Providers & Configurations (réservé au compte parent uniquement)
    Route::get('sms-providers', [SmsProviderController::class, 'index']);
    Route::post('sms-providers', [SmsProviderController::class, 'store']);
    Route::get('sms-providers/{code}', [SmsProviderController::class, 'show']);
    Route::post('sms-providers/{code}/test', [SmsProviderController::class, 'test']);
    Route::get('sms-configs', [\App\Http\Controllers\Api\SmsConfigController::class, 'index']);
    Route::get('sms-configs/{provider}', [\App\Http\Controllers\Api\SmsConfigController::class, 'show']);
    Route::put('sms-configs/{provider}', [\App\Http\Controllers\Api\SmsConfigController::class, 'update']);
    Route::post('sms-configs/{provider}/test', [\App\Http\Controllers\Api\SmsConfigController::class, 'test']);
    Route::post('sms-configs/{provider}/toggle', [\App\Http\Controllers\Api\SmsConfigController::class, 'toggle']);

    // Message History (permission: view_history)
    Route::middleware('permission:view_history')->group(function () {
        Route::get('messages/history', [MessageHistoryController::class, 'index']);
        Route::get('messages/stats', [MessageHistoryController::class, 'stats']);
    });

    // Message Export (permission: export_data)
    Route::middleware('permission:export_data')->group(function () {
        Route::get('messages/export', [MessageHistoryController::class, 'export']);
    });

    // Messages Send (permission: send_sms)
    Route::middleware('permission:send_sms')->group(function () {
        Route::post('messages/send', [MessageController::class, 'send'])
            ->middleware('throttle:sms-send');
        Route::post('messages/analyze', [MessageController::class, 'analyzeNumbers']);
        Route::post('messages/number-info', [MessageController::class, 'getNumberInfo']);
    });

    // Blacklist (réservé au compte parent uniquement)
    Route::get('blacklist', [\App\Http\Controllers\Api\BlacklistController::class, 'index']);
    Route::post('blacklist', [\App\Http\Controllers\Api\BlacklistController::class, 'store']);
    Route::delete('blacklist/{id}', [\App\Http\Controllers\Api\BlacklistController::class, 'destroy']);
    Route::post('blacklist/check', [\App\Http\Controllers\Api\BlacklistController::class, 'check']);

    // Audit Logs (réservé au compte parent uniquement)
    Route::get('audit-logs', [\App\Http\Controllers\Api\AuditLogController::class, 'index']);
    Route::get('audit-logs/actions', [\App\Http\Controllers\Api\AuditLogController::class, 'actions']);
    Route::get('audit-logs/{id}', [\App\Http\Controllers\Api\AuditLogController::class, 'show']);

    // Webhooks (réservé au compte parent uniquement)
    Route::get('webhooks/events', [\App\Http\Controllers\Api\WebhookController::class, 'events']);
    Route::get('webhooks/{id}/logs', [\App\Http\Controllers\Api\WebhookController::class, 'logs']);
    Route::get('webhooks/{id}/stats', [\App\Http\Controllers\Api\WebhookController::class, 'stats']);
    Route::post('webhooks/{id}/test', [\App\Http\Controllers\Api\WebhookController::class, 'test']);
    Route::post('webhooks/{id}/toggle', [\App\Http\Controllers\Api\WebhookController::class, 'toggle']);
    Route::apiResource('webhooks', \App\Http\Controllers\Api\WebhookController::class);

    // Analytics (permission: view_analytics)
    Route::middleware('permission:view_analytics')->group(function () {
        Route::get('analytics/dashboard', [\App\Http\Controllers\Api\AnalyticsController::class, 'dashboard']);
        Route::get('analytics/chart', [\App\Http\Controllers\Api\AnalyticsController::class, 'chart']);
        Route::get('analytics/report', [\App\Http\Controllers\Api\AnalyticsController::class, 'report']);
        Route::get('analytics/providers', [\App\Http\Controllers\Api\AnalyticsController::class, 'providers']);
        Route::get('analytics/top-campaigns', [\App\Http\Controllers\Api\AnalyticsController::class, 'topCampaigns']);
        Route::post('analytics/update', [\App\Http\Controllers\Api\AnalyticsController::class, 'update']);
    });

    // Analytics Export (permission: export_data)
    Route::middleware('permission:export_data')->group(function () {
        Route::get('analytics/export/pdf', [\App\Http\Controllers\Api\AnalyticsController::class, 'exportPdf']);
        Route::get('analytics/export/excel', [\App\Http\Controllers\Api\AnalyticsController::class, 'exportExcel']);
        Route::get('analytics/export/csv', [\App\Http\Controllers\Api\AnalyticsController::class, 'exportCsv']);
    });
});
