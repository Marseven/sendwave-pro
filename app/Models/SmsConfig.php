<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class SmsConfig extends Model
{
    protected $fillable = [
        'provider',
        'api_url',
        'port',
        'username',
        'password',
        'origin_addr',
        'cost_per_sms',
        'is_active',
        'additional_config',
    ];

    protected $casts = [
        'port' => 'integer',
        'is_active' => 'boolean',
        'cost_per_sms' => 'integer',
        'additional_config' => 'array',
    ];

    protected $hidden = [
        'password', // Masquer le mot de passe dans les réponses JSON
    ];

    /**
     * Encrypt password when setting
     */
    public function setPasswordAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['password'] = $value;
            return;
        }

        // Always encrypt when setting
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt password when getting
     * Handles both encrypted and legacy unencrypted passwords
     */
    public function getPasswordAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            // Try to decrypt (for encrypted passwords)
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // If decryption fails, it's likely a legacy unencrypted password
            // Re-encrypt it for next save
            return $value;
        }
    }

    /**
     * Get the raw (encrypted) password value for checking if migration is needed
     */
    public function getRawPassword(): ?string
    {
        return $this->attributes['password'] ?? null;
    }

    /**
     * Check if password needs migration (is unencrypted)
     */
    public function needsPasswordMigration(): bool
    {
        $raw = $this->getRawPassword();
        if ($raw === null || $raw === '') {
            return false;
        }

        try {
            Crypt::decryptString($raw);
            return false; // Successfully decrypted, no migration needed
        } catch (DecryptException $e) {
            return true; // Decryption failed, needs migration
        }
    }

    /**
     * Migrate unencrypted password to encrypted
     */
    public function migratePassword(): bool
    {
        if (!$this->needsPasswordMigration()) {
            return false;
        }

        $plainPassword = $this->attributes['password'];
        $this->attributes['password'] = Crypt::encryptString($plainPassword);
        return $this->save();
    }
}
