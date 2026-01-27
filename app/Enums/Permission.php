<?php

namespace App\Enums;

/**
 * Unified permission enum for Users and SubAccounts
 */
enum Permission: string
{
    // Admin-level permissions (SuperAdmin + Admin only)
    case MANAGE_USERS = 'manage_users';
    case MANAGE_SETTINGS = 'manage_settings';
    case MANAGE_API_KEYS = 'manage_api_keys';
    case MANAGE_WEBHOOKS = 'manage_webhooks';
    case MANAGE_SUB_ACCOUNTS = 'manage_sub_accounts';
    case VIEW_AUDIT_LOGS = 'view_audit_logs';

    // Regular operations (all roles)
    case SEND_SMS = 'send_sms';
    case VIEW_HISTORY = 'view_history';
    case MANAGE_CONTACTS = 'manage_contacts';
    case MANAGE_GROUPS = 'manage_groups';
    case CREATE_CAMPAIGNS = 'create_campaigns';
    case VIEW_ANALYTICS = 'view_analytics';
    case MANAGE_TEMPLATES = 'manage_templates';
    case EXPORT_DATA = 'export_data';

    /**
     * Get the label for the permission
     */
    public function label(): string
    {
        return match($this) {
            // Admin permissions
            self::MANAGE_USERS => 'Gérer les utilisateurs',
            self::MANAGE_SETTINGS => 'Gérer les paramètres',
            self::MANAGE_API_KEYS => 'Gérer les clés API',
            self::MANAGE_WEBHOOKS => 'Gérer les webhooks',
            self::MANAGE_SUB_ACCOUNTS => 'Gérer les sous-comptes',
            self::VIEW_AUDIT_LOGS => 'Voir les journaux d\'audit',
            // Regular permissions
            self::SEND_SMS => 'Envoyer des SMS',
            self::VIEW_HISTORY => 'Voir l\'historique',
            self::MANAGE_CONTACTS => 'Gérer les contacts',
            self::MANAGE_GROUPS => 'Gérer les groupes',
            self::CREATE_CAMPAIGNS => 'Créer des campagnes',
            self::VIEW_ANALYTICS => 'Voir les analytics',
            self::MANAGE_TEMPLATES => 'Gérer les modèles',
            self::EXPORT_DATA => 'Exporter les données',
        };
    }

    /**
     * Get the category for the permission
     */
    public function category(): string
    {
        return match($this) {
            self::MANAGE_USERS,
            self::MANAGE_SETTINGS,
            self::MANAGE_API_KEYS,
            self::MANAGE_WEBHOOKS,
            self::MANAGE_SUB_ACCOUNTS,
            self::VIEW_AUDIT_LOGS => 'Administration',

            self::SEND_SMS,
            self::VIEW_HISTORY,
            self::CREATE_CAMPAIGNS => 'Messagerie',

            self::MANAGE_CONTACTS,
            self::MANAGE_GROUPS => 'Contacts',

            self::VIEW_ANALYTICS,
            self::EXPORT_DATA => 'Rapports',

            self::MANAGE_TEMPLATES => 'Modèles',
        };
    }

    /**
     * Check if this is an admin-only permission
     */
    public function isAdminOnly(): bool
    {
        return in_array($this, [
            self::MANAGE_USERS,
            self::MANAGE_SETTINGS,
            self::MANAGE_API_KEYS,
            self::MANAGE_WEBHOOKS,
            self::MANAGE_SUB_ACCOUNTS,
            self::VIEW_AUDIT_LOGS,
        ]);
    }

    /**
     * Get all permission values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get permissions as array with labels
     */
    public static function toArray(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->label();
        }
        return $result;
    }

    /**
     * Get permissions grouped by category
     */
    public static function groupedByCategory(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $category = $case->category();
            if (!isset($result[$category])) {
                $result[$category] = [];
            }
            $result[$category][] = [
                'value' => $case->value,
                'label' => $case->label(),
                'admin_only' => $case->isAdminOnly(),
            ];
        }
        return $result;
    }

    /**
     * Get only regular (non-admin) permissions
     */
    public static function regularPermissions(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn($p) => !$p->isAdminOnly()
        ));
    }

    /**
     * Get only admin permissions
     */
    public static function adminPermissions(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn($p) => $p->isAdminOnly()
        ));
    }
}
