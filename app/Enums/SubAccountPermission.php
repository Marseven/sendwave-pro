<?php

namespace App\Enums;

enum SubAccountPermission: string
{
    case SEND_SMS = 'send_sms';
    case VIEW_HISTORY = 'view_history';
    case MANAGE_CONTACTS = 'manage_contacts';
    case MANAGE_GROUPS = 'manage_groups';
    case CREATE_CAMPAIGNS = 'create_campaigns';
    case VIEW_ANALYTICS = 'view_analytics';
    case MANAGE_TEMPLATES = 'manage_templates';
    case EXPORT_DATA = 'export_data';

    public function label(): string
    {
        return match($this) {
            self::SEND_SMS => 'Envoyer des SMS',
            self::VIEW_HISTORY => 'Voir l\'historique',
            self::MANAGE_CONTACTS => 'Gérer les contacts',
            self::MANAGE_GROUPS => 'Gérer les groupes',
            self::CREATE_CAMPAIGNS => 'Créer des campagnes',
            self::VIEW_ANALYTICS => 'Voir les analytics',
            self::MANAGE_TEMPLATES => 'Gérer les templates',
            self::EXPORT_DATA => 'Exporter les données',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toArray(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->label();
        }
        return $result;
    }
}
