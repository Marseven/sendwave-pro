<?php

namespace App\Enums;

enum WebhookEvent: string
{
    case MESSAGE_SENT = 'message.sent';
    case MESSAGE_DELIVERED = 'message.delivered';
    case MESSAGE_FAILED = 'message.failed';
    case MESSAGE_RECEIVED = 'message.received';
    case CAMPAIGN_STARTED = 'campaign.started';
    case CAMPAIGN_COMPLETED = 'campaign.completed';
    case CAMPAIGN_FAILED = 'campaign.failed';
    case CONTACT_CREATED = 'contact.created';
    case CONTACT_UPDATED = 'contact.updated';
    case CONTACT_DELETED = 'contact.deleted';
    case CONTACT_UNSUBSCRIBED = 'contact.unsubscribed';
    case SUB_ACCOUNT_CREATED = 'sub_account.created';
    case SUB_ACCOUNT_SUSPENDED = 'sub_account.suspended';
    case BLACKLIST_ADDED = 'blacklist.added';

    public function label(): string
    {
        return match($this) {
            self::MESSAGE_SENT => 'Message envoyé',
            self::MESSAGE_DELIVERED => 'Message délivré',
            self::MESSAGE_FAILED => 'Message échoué',
            self::MESSAGE_RECEIVED => 'Message reçu (réponse)',
            self::CAMPAIGN_STARTED => 'Campagne démarrée',
            self::CAMPAIGN_COMPLETED => 'Campagne terminée',
            self::CAMPAIGN_FAILED => 'Campagne échouée',
            self::CONTACT_CREATED => 'Contact créé',
            self::CONTACT_UPDATED => 'Contact mis à jour',
            self::CONTACT_DELETED => 'Contact supprimé',
            self::CONTACT_UNSUBSCRIBED => 'Contact désinscrit (STOP)',
            self::SUB_ACCOUNT_CREATED => 'Sous-compte créé',
            self::SUB_ACCOUNT_SUSPENDED => 'Sous-compte suspendu',
            self::BLACKLIST_ADDED => 'Numéro ajouté à la liste noire',
        };
    }

    public static function toArray(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->label();
        }
        return $result;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
