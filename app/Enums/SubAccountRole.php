<?php

namespace App\Enums;

enum SubAccountRole: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case SENDER = 'sender';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrateur',
            self::MANAGER => 'Gestionnaire',
            self::SENDER => 'Expéditeur',
            self::VIEWER => 'Lecteur',
        };
    }

    public function defaultPermissions(): array
    {
        return match($this) {
            self::ADMIN => SubAccountPermission::values(),
            self::MANAGER => [
                SubAccountPermission::SEND_SMS->value,
                SubAccountPermission::VIEW_HISTORY->value,
                SubAccountPermission::MANAGE_CONTACTS->value,
                SubAccountPermission::MANAGE_GROUPS->value,
                SubAccountPermission::CREATE_CAMPAIGNS->value,
                SubAccountPermission::VIEW_ANALYTICS->value,
            ],
            self::SENDER => [
                SubAccountPermission::SEND_SMS->value,
                SubAccountPermission::VIEW_HISTORY->value,
                SubAccountPermission::MANAGE_CONTACTS->value,
            ],
            self::VIEWER => [
                SubAccountPermission::VIEW_HISTORY->value,
                SubAccountPermission::VIEW_ANALYTICS->value,
            ],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
