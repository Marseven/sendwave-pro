<?php

namespace App\Enums;

enum MessageStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';

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
            self::PENDING => 'En attente',
            self::SENT => 'Envoyé',
            self::DELIVERED => 'Livré',
            self::FAILED => 'Échoué',
        };
    }

    /**
     * Check if status is final (no more transitions possible)
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::DELIVERED, self::FAILED]);
    }
}
