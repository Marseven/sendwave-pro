<?php

namespace App\Services\SMS\Operators;

use App\Services\SMS\SmppClient;
use Illuminate\Support\Facades\Log;

class MoovService
{
    protected string $host;
    protected int $port;
    protected string $systemId;
    protected string $password;
    protected string $sourceAddr;
    protected bool $enabled;

    public function __construct()
    {
        // Charger depuis la base de données en priorité, sinon depuis config
        $dbConfig = \App\Models\SmsConfig::where('provider', 'moov')->first();

        if ($dbConfig && $dbConfig->is_active) {
            $this->host = $dbConfig->api_url ?? config('sms.moov.host', '172.16.59.66');
            $this->port = (int) ($dbConfig->port ?? config('sms.moov.port', 12775));
            $this->systemId = $dbConfig->username ?? config('sms.moov.system_id', '');
            $this->password = $dbConfig->password ?? config('sms.moov.password', '');
            $this->sourceAddr = $dbConfig->origin_addr ?? config('sms.moov.source_addr', 'SENDWAVE');
            $this->enabled = true;
        } else {
            // Fallback sur les variables d'environnement
            $this->host = config('sms.moov.host', '172.16.59.66');
            $this->port = (int) config('sms.moov.port', 12775);
            $this->systemId = config('sms.moov.system_id', '');
            $this->password = config('sms.moov.password', '');
            $this->sourceAddr = config('sms.moov.source_addr', 'SENDWAVE');
            $this->enabled = config('sms.moov.enabled', false);
        }
    }

    /**
     * Verifier si le service est configure
     */
    public function isConfigured(): bool
    {
        return $this->enabled &&
               !empty($this->host) &&
               !empty($this->systemId) &&
               !empty($this->password);
    }

    /**
     * Envoyer un SMS via SMPP Moov
     *
     * @param string $phoneNumber Numero de telephone (ex: 24162345678)
     * @param string $message Message a envoyer
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        // Verifier la configuration
        if (!$this->isConfigured()) {
            Log::warning('Moov SMPP: Service non configure', [
                'host' => $this->host,
                'system_id' => $this->systemId,
                'enabled' => $this->enabled
            ]);

            return [
                'success' => false,
                'message' => 'Service Moov SMPP non configure',
                'provider' => 'moov',
                'phone' => $phoneNumber,
                'error' => 'SMPP not configured - missing host, system_id or password',
            ];
        }

        $client = null;

        try {
            // Nettoyer le numero de telephone
            $cleanNumber = $this->cleanPhoneNumber($phoneNumber);

            Log::info('Moov SMPP: Debut envoi', [
                'phone' => $cleanNumber,
                'message_length' => strlen($message),
                'source' => $this->sourceAddr,
                'host' => $this->host
            ]);

            // Creer le client SMPP
            $client = new SmppClient(
                $this->host,
                $this->port,
                $this->systemId,
                $this->password
            );

            // Connexion
            $client->connect();

            // Authentification
            $client->bindTransceiver();

            // Envoi du SMS
            // Essai 1: TON Alphanumeric pour l'expediteur
            try {
                $result = $client->sendSms(
                    $this->sourceAddr,
                    $cleanNumber,
                    $message,
                    SmppClient::TON_ALPHANUMERIC,  // source TON
                    SmppClient::NPI_UNKNOWN,       // source NPI
                    SmppClient::TON_INTERNATIONAL, // dest TON
                    SmppClient::NPI_ISDN           // dest NPI
                );
            } catch (\Exception $e1) {
                Log::warning('Moov SMPP: TON=5 echoue, essai TON=1', ['error' => $e1->getMessage()]);

                // Essai 2: TON International pour l'expediteur
                $result = $client->sendSms(
                    $this->sourceAddr,
                    $cleanNumber,
                    $message,
                    SmppClient::TON_INTERNATIONAL, // source TON
                    SmppClient::NPI_ISDN,          // source NPI
                    SmppClient::TON_INTERNATIONAL, // dest TON
                    SmppClient::NPI_ISDN           // dest NPI
                );
            }

            // Deconnexion
            $client->disconnect();

            if ($result['success']) {
                Log::info('Moov SMPP: SMS envoye avec succes', [
                    'phone' => $cleanNumber,
                    'message_id' => $result['message_id'] ?? null
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS envoye avec succes via Moov SMPP',
                    'provider' => 'moov',
                    'phone' => $cleanNumber,
                    'message_id' => $result['message_id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Echec envoi SMS',
                'provider' => 'moov',
                'phone' => $cleanNumber,
                'error' => $result['error'] ?? 'Unknown error',
                'status_code' => $result['status'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Moov SMPP: Exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Exception lors de l\'envoi du SMS',
                'provider' => 'moov',
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ];

        } finally {
            // S'assurer que la connexion est fermee
            if ($client !== null) {
                try {
                    $client->disconnect();
                } catch (\Exception $e) {
                    // Ignorer les erreurs de deconnexion
                }
            }
        }
    }

    /**
     * Envoyer des SMS en masse
     *
     * @param array $phoneNumbers Tableau de numeros de telephone
     * @param string $message Message a envoyer
     * @return array
     */
    public function sendBulkSms(array $phoneNumbers, string $message): array
    {
        // Verifier la configuration
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'provider' => 'moov',
                'total' => count($phoneNumbers),
                'sent' => 0,
                'failed' => count($phoneNumbers),
                'error' => 'Service Moov SMPP non configure',
                'results' => [],
            ];
        }

        $results = [];
        $success = 0;
        $failed = 0;

        $client = null;

        try {
            // Creer et connecter le client une seule fois pour tous les messages
            $client = new SmppClient(
                $this->host,
                $this->port,
                $this->systemId,
                $this->password
            );

            $client->connect();
            $client->bindTransceiver();

            foreach ($phoneNumbers as $phoneNumber) {
                try {
                    $cleanNumber = $this->cleanPhoneNumber($phoneNumber);

                    $result = $client->sendSms(
                        $this->sourceAddr,
                        $cleanNumber,
                        $message,
                        SmppClient::TON_ALPHANUMERIC,
                        SmppClient::NPI_UNKNOWN,
                        SmppClient::TON_INTERNATIONAL,
                        SmppClient::NPI_ISDN
                    );

                    if ($result['success']) {
                        $success++;
                        $results[] = [
                            'success' => true,
                            'phone' => $cleanNumber,
                            'message_id' => $result['message_id'] ?? null,
                        ];
                    } else {
                        $failed++;
                        $results[] = [
                            'success' => false,
                            'phone' => $cleanNumber,
                            'error' => $result['error'] ?? 'Unknown error',
                        ];
                    }

                } catch (\Exception $e) {
                    $failed++;
                    $results[] = [
                        'success' => false,
                        'phone' => $phoneNumber,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $client->disconnect();

        } catch (\Exception $e) {
            Log::error('Moov SMPP Bulk: Exception connexion', [
                'error' => $e->getMessage()
            ]);

            // Marquer tous les numeros restants comme echecs
            $remaining = count($phoneNumbers) - count($results);
            $failed += $remaining;

            for ($i = 0; $i < $remaining; $i++) {
                $results[] = [
                    'success' => false,
                    'phone' => $phoneNumbers[count($results)] ?? 'unknown',
                    'error' => 'Connection lost: ' . $e->getMessage(),
                ];
            }

        } finally {
            if ($client !== null) {
                try {
                    $client->disconnect();
                } catch (\Exception $e) {
                    // Ignorer
                }
            }
        }

        return [
            'success' => $success > 0,
            'provider' => 'moov',
            'total' => count($phoneNumbers),
            'sent' => $success,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Nettoyer le numero de telephone
     * Format attendu: 24162XXXXXX (avec prefixe pays)
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function cleanPhoneNumber(string $phoneNumber): string
    {
        // Enlever tous les caracteres non numeriques
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Si le numero commence par 0, le remplacer par 241
        if (str_starts_with($cleaned, '0') && strlen($cleaned) === 9) {
            $cleaned = '241' . substr($cleaned, 1);
        }

        // Si le numero n'a pas le prefixe pays, l'ajouter
        if (strlen($cleaned) === 8) {
            $cleaned = '241' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Verifier le solde (non supporte via SMPP basique)
     *
     * @return array|null
     */
    public function getBalance(): ?array
    {
        return null;
    }

    /**
     * Verifier le statut d'un message
     * Note: Necessite implementation query_sm ou DLR
     *
     * @param string $messageId
     * @return array|null
     */
    public function getMessageStatus(string $messageId): ?array
    {
        return null;
    }

    /**
     * Tester la connexion SMPP
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Service non configure',
                'details' => [
                    'host' => $this->host,
                    'port' => $this->port,
                    'system_id' => $this->systemId,
                    'enabled' => $this->enabled,
                ]
            ];
        }

        $client = null;

        try {
            $client = new SmppClient(
                $this->host,
                $this->port,
                $this->systemId,
                $this->password
            );

            $client->connect();
            $client->bindTransceiver();
            $client->disconnect();

            return [
                'success' => true,
                'message' => 'Connexion SMPP reussie',
                'details' => [
                    'host' => $this->host,
                    'port' => $this->port,
                    'system_id' => $this->systemId,
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Echec connexion: ' . $e->getMessage(),
                'details' => [
                    'host' => $this->host,
                    'port' => $this->port,
                    'system_id' => $this->systemId,
                    'error' => $e->getMessage(),
                ]
            ];

        } finally {
            if ($client !== null) {
                try {
                    $client->disconnect();
                } catch (\Exception $e) {
                    // Ignorer
                }
            }
        }
    }
}
