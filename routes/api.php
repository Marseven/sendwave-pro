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
use App\Http\Controllers\SmsAnalyticsController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\Api\IncomingSmsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CustomRoleController;
use App\Http\Controllers\Api\AccountController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/sub-accounts/login', [SubAccountController::class, 'login']);

// Incoming SMS Webhooks (public - called by SMS providers)
Route::prefix('webhooks/incoming')->group(function () {
    Route::post('/sms', [IncomingSmsController::class, 'handleIncoming']);
    Route::post('/airtel', [IncomingSmsController::class, 'handleAirtelWebhook']);
    Route::post('/moov', [IncomingSmsController::class, 'handleMoovWebhook']);
});

// Phone Normalization (public utility)
Route::get('/phone/countries', [IncomingSmsController::class, 'getSupportedCountries']);

// Routes protégées (Bearer token via Sanctum OU header X-API-Key)
Route::middleware('auth.api')->group(function () {
    // Auth (accessible à tous les utilisateurs authentifiés)
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/permissions', [AuthController::class, 'permissions']);
    Route::get('/auth/available-permissions', [AuthController::class, 'availablePermissions']);
    Route::get('user/profile', [AuthController::class, 'profile']);
    Route::put('user/profile', [AuthController::class, 'updateProfile']);

    // Contacts (permission: manage_contacts)
    Route::middleware('permission:manage_contacts')->group(function () {
        Route::post('contacts/import', [ContactController::class, 'import']);
        Route::post('contacts/preview-import', [ContactController::class, 'previewImport']);
        Route::post('contacts/delete-many', [ContactController::class, 'destroyMany']);
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
        Route::post('campaigns/{id}/clone', [CampaignController::class, 'clone']);
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
        Route::post('templates/{id}/toggle-public', [MessageTemplateController::class, 'togglePublic']);
        Route::apiResource('templates', MessageTemplateController::class);
    });

    // Sub Accounts (réservé au compte parent uniquement - pas de permission)
    Route::post('sub-accounts/transfer-credits', [SubAccountController::class, 'transferCredits']);
    Route::post('sub-accounts/{id}/credits', [SubAccountController::class, 'addCredits']);
    Route::post('sub-accounts/{id}/permissions', [SubAccountController::class, 'updatePermissions']);
    Route::post('sub-accounts/{id}/suspend', [SubAccountController::class, 'suspend']);
    Route::post('sub-accounts/{id}/activate', [SubAccountController::class, 'activate']);
    Route::apiResource('sub-accounts', SubAccountController::class);

    // API Keys (réservé au compte parent uniquement)
    Route::apiResource('api-keys', ApiKeyController::class);
    Route::post('api-keys/{id}/revoke', [ApiKeyController::class, 'revoke']);
    Route::post('api-keys/{id}/regenerate', [ApiKeyController::class, 'regenerate']);

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
    Route::post('sms-configs/{provider}/reset', [\App\Http\Controllers\Api\SmsConfigController::class, 'reset']);

    // Message History (permission: view_history)
    Route::middleware('permission:view_history')->group(function () {
        Route::get('messages/history', [MessageHistoryController::class, 'index']);
        Route::get('messages/stats', [MessageHistoryController::class, 'stats']);
    });

    // Message and Contacts Export (permission: export_data)
    Route::middleware('permission:export_data')->group(function () {
        Route::get('messages/export', [MessageHistoryController::class, 'export']);
        Route::get('contacts/export', [ContactController::class, 'export']);
    });

    // Messages Send (permission: send_sms)
    Route::middleware('permission:send_sms')->group(function () {
        Route::post('messages/send', [MessageController::class, 'send'])
            ->middleware('throttle:sms-send');
        Route::post('messages/send-otp', [MessageController::class, 'sendOtp'])
            ->middleware('throttle:sms-send');
        Route::post('messages/analyze', [MessageController::class, 'analyzeNumbers']);
        Route::post('messages/number-info', [MessageController::class, 'getNumberInfo']);
    });

    // Blacklist (réservé au compte parent uniquement)
    Route::get('blacklist', [\App\Http\Controllers\Api\BlacklistController::class, 'index']);
    Route::post('blacklist', [\App\Http\Controllers\Api\BlacklistController::class, 'store']);
    Route::delete('blacklist/{id}', [\App\Http\Controllers\Api\BlacklistController::class, 'destroy']);
    Route::post('blacklist/check', [\App\Http\Controllers\Api\BlacklistController::class, 'check']);
    Route::get('blacklist/stats', [IncomingSmsController::class, 'blacklistStats']);
    Route::get('blacklist/stop-keywords', [IncomingSmsController::class, 'getStopKeywords']);

    // Phone Normalization
    Route::post('phone/normalize', [IncomingSmsController::class, 'normalizePhone']);
    Route::post('phone/normalize-many', [IncomingSmsController::class, 'normalizePhones']);

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

    // SMS Analytics - Comptabilité analytique (permission: view_analytics)
    Route::middleware('permission:view_analytics')->group(function () {
        Route::get('sms-analytics/overview', [SmsAnalyticsController::class, 'overview']);
        Route::get('sms-analytics/periods', [SmsAnalyticsController::class, 'periods']);
        Route::get('sms-analytics/closures', [SmsAnalyticsController::class, 'closures']);
        Route::get('sms-analytics/closures/{periodKey}', [SmsAnalyticsController::class, 'closureDetail']);
        Route::post('sms-analytics/report', [SmsAnalyticsController::class, 'generateReport']);
        Route::get('sms-analytics/export', [SmsAnalyticsController::class, 'export']);
        Route::get('sms-analytics', [SmsAnalyticsController::class, 'index']);
    });

    // Budget Management (réservé au compte parent uniquement)
    Route::get('budgets/status/{subAccountId?}', [BudgetController::class, 'status']);
    Route::get('budgets/all', [BudgetController::class, 'allStatus']);
    Route::put('budgets/{subAccountId}', [BudgetController::class, 'update']);
    Route::post('budgets/check-send', [BudgetController::class, 'checkSend']);
    Route::get('budgets/history/{subAccountId?}', [BudgetController::class, 'history']);

    // Account Management (SuperAdmin only for most operations)
    Route::prefix('accounts')->group(function () {
        Route::get('/', [AccountController::class, 'index']);
        Route::post('/', [AccountController::class, 'store']);
        Route::get('{id}', [AccountController::class, 'show']);
        Route::put('{id}', [AccountController::class, 'update']);
        Route::delete('{id}', [AccountController::class, 'destroy']);
        Route::post('{id}/credits', [AccountController::class, 'addCredits']);
        Route::post('{id}/suspend', [AccountController::class, 'suspend']);
        Route::post('{id}/activate', [AccountController::class, 'activate']);
        Route::get('{id}/stats', [AccountController::class, 'stats']);
        Route::get('{id}/users', [AccountController::class, 'users']);
    });

    // User Management (Admin can manage agents, SuperAdmin can manage all)
    Route::middleware('permission:manage_sub_accounts')->group(function () {
        Route::get('users/available-roles', [UserController::class, 'availableRoles']);
        Route::get('users/available-permissions', [UserController::class, 'availablePermissions']);
        Route::post('users/{id}/suspend', [UserController::class, 'suspend']);
        Route::post('users/{id}/activate', [UserController::class, 'activate']);
        Route::put('users/{id}/permissions', [UserController::class, 'updatePermissions']);
        Route::apiResource('users', UserController::class);
    });

    // Custom Roles (SuperAdmin only)
    Route::prefix('custom-roles')->group(function () {
        Route::get('permissions', [CustomRoleController::class, 'permissions']);
        Route::post('{id}/duplicate', [CustomRoleController::class, 'duplicate']);
    });
    Route::apiResource('custom-roles', CustomRoleController::class);

    // System Roles and Permissions (read-only)
    Route::get('system-roles', [UserController::class, 'systemRoles']);
    Route::get('system-permissions', [UserController::class, 'systemPermissions']);
});
