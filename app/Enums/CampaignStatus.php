<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case SENDING = 'sending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Get all status values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get label for status (French)
     */
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Brouillon',
            self::SCHEDULED => 'Planifié',
            self::SENDING => 'En cours',
            self::COMPLETED => 'Terminé',
            self::FAILED => 'Échoué',
            self::CANCELLED => 'Annulé',
        };
    }

    /**
     * Check if campaign can be edited
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::SCHEDULED]);
    }

    /**
     * Check if status is final (no more transitions possible)
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED]);
    }

    /**
     * Map legacy status values to new ones
     */
    public static function fromLegacy(string $legacyStatus): self
    {
        return match(strtolower($legacyStatus)) {
            'actif', 'active', 'sending' => self::SENDING,
            'terminé', 'termine', 'completed', 'done' => self::COMPLETED,
            'planifié', 'planifie', 'scheduled' => self::SCHEDULED,
            'failed', 'error' => self::FAILED,
            'cancelled', 'canceled' => self::CANCELLED,
            default => self::DRAFT,
        };
    }
}
