<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case AGENT = 'agent';

    /**
     * Get the label for the role
     */
    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Administrateur',
            self::ADMIN => 'Administrateur',
            self::AGENT => 'Agent',
        };
    }

    /**
     * Get the description for the role
     */
    public function description(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Accès complet à toutes les fonctionnalités de la plateforme',
            self::ADMIN => 'Gestion complète du compte et des sous-comptes',
            self::AGENT => 'Opérations courantes (envoi SMS, contacts, campagnes)',
        };
    }

    /**
     * Get default permissions for this role
     */
    public function defaultPermissions(): array
    {
        return match($this) {
            self::SUPER_ADMIN => Permission::values(),
            self::ADMIN => [
                // Admin operations
                Permission::MANAGE_API_KEYS->value,
                Permission::MANAGE_WEBHOOKS->value,
                Permission::MANAGE_SUB_ACCOUNTS->value,
                // Regular operations
                Permission::SEND_SMS->value,
                Permission::VIEW_HISTORY->value,
                Permission::MANAGE_CONTACTS->value,
                Permission::MANAGE_GROUPS->value,
                Permission::CREATE_CAMPAIGNS->value,
                Permission::VIEW_ANALYTICS->value,
                Permission::MANAGE_TEMPLATES->value,
                Permission::EXPORT_DATA->value,
            ],
            self::AGENT => [
                Permission::SEND_SMS->value,
                Permission::VIEW_HISTORY->value,
                Permission::MANAGE_CONTACTS->value,
                Permission::MANAGE_GROUPS->value,
                Permission::CREATE_CAMPAIGNS->value,
                Permission::VIEW_ANALYTICS->value,
                Permission::MANAGE_TEMPLATES->value,
            ],
        };
    }

    /**
     * Get the hierarchy level (higher = more access)
     */
    public function level(): int
    {
        return match($this) {
            self::SUPER_ADMIN => 100,
            self::ADMIN => 50,
            self::AGENT => 10,
        };
    }

    /**
     * Check if this role is at least as privileged as another role
     */
    public function isAtLeast(UserRole $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Get all role values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get roles as array with labels
     */
    public static function toArray(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->label();
        }
        return $result;
    }
}
