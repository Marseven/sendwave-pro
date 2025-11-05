<?php
/**
 * Script temporaire pour exécuter les migrations sur Hostinger
 * À SUPPRIMER après utilisation pour des raisons de sécurité
 *
 * Accès: https://lightgreen-otter-916987.hostingersite.com/run-migration.php
 */

// Sécurité : ajouter un mot de passe simple
$password = 'migrate2024'; // Changez ce mot de passe
if (!isset($_GET['pwd']) || $_GET['pwd'] !== $password) {
    die('Access denied. Usage: run-migration.php?pwd=your_password');
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "<pre>";
echo "=== Running Migrations ===\n\n";

$status = $kernel->call('migrate', ['--force' => true]);

echo "\n=== Migration Status: " . ($status === 0 ? 'SUCCESS' : 'FAILED') . " ===\n";

echo "\n=== Checking sms_configs table ===\n";

try {
    $configs = DB::table('sms_configs')->get();
    echo "Table exists! Current records: " . count($configs) . "\n";

    if (count($configs) === 0) {
        echo "\n=== Creating default records ===\n";

        DB::table('sms_configs')->insert([
            [
                'provider' => 'airtel',
                'api_url' => 'https://messaging.airtel.ga:9002/smshttp/qs/',
                'username' => null,
                'password' => null,
                'origin_addr' => null,
                'cost_per_sms' => 20,
                'is_active' => false,
                'additional_config' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider' => 'moov',
                'api_url' => null,
                'username' => null,
                'password' => null,
                'origin_addr' => null,
                'cost_per_sms' => 20,
                'is_active' => false,
                'additional_config' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        echo "✓ Default Airtel and Moov records created!\n";
    }

    echo "\n=== Current SMS Configs ===\n";
    foreach (DB::table('sms_configs')->get() as $config) {
        echo "Provider: {$config->provider}\n";
        echo "  - Active: " . ($config->is_active ? 'Yes' : 'No') . "\n";
        echo "  - Cost: {$config->cost_per_sms} FCFA\n";
        echo "  - API URL: " . ($config->api_url ?: 'Not set') . "\n";
        echo "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== DONE ===\n";
echo "⚠️  IMPORTANT: DELETE THIS FILE (run-migration.php) FOR SECURITY!\n";
echo "</pre>";
