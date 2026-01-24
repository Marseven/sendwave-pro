<?php

namespace App\Console\Commands;

use App\Models\SmsConfig;
use Illuminate\Console\Command;

class EncryptSmsPasswords extends Command
{
    protected $signature = 'sms:encrypt-passwords';
    protected $description = 'Encrypt existing unencrypted SMS configuration passwords';

    public function handle(): int
    {
        $this->info('Checking SMS configurations for unencrypted passwords...');

        $configs = SmsConfig::all();
        $migrated = 0;

        foreach ($configs as $config) {
            if ($config->needsPasswordMigration()) {
                $this->line("Migrating password for provider: {$config->provider}");
                $config->migratePassword();
                $migrated++;
            }
        }

        if ($migrated > 0) {
            $this->info("Successfully encrypted {$migrated} password(s).");
        } else {
            $this->info('No passwords needed migration.');
        }

        return Command::SUCCESS;
    }
}
