<?php

namespace App\Services\SMS;

use Illuminate\Support\Facades\Log;

/**
 * Client SMPP pour l'envoi de SMS
 * Implementation du protocole SMPP v3.4
 */
class SmppClient
{
    // SMPP Command IDs
    const BIND_TRANSCEIVER = 0x00000009;
    const BIND_TRANSCEIVER_RESP = 0x80000009;
    const SUBMIT_SM = 0x00000004;
    const SUBMIT_SM_RESP = 0x80000004;
    const UNBIND = 0x00000006;
    const UNBIND_RESP = 0x80000006;
    const GENERIC_NACK = 0x80000000;
    const ENQUIRE_LINK = 0x00000015;
    const ENQUIRE_LINK_RESP = 0x80000015;

    // TON (Type of Number)
    const TON_UNKNOWN = 0x00;
    const TON_INTERNATIONAL = 0x01;
    const TON_NATIONAL = 0x02;
    const TON_ALPHANUMERIC = 0x05;

    // NPI (Numbering Plan Indicator)
    const NPI_UNKNOWN = 0x00;
    const NPI_ISDN = 0x01;

    // Data Coding
    const ENCODING_DEFAULT = 0x00; // GSM 7-bit
    const ENCODING_UCS2 = 0x08;    // UCS2/UTF-16

    protected $socket;
    protected string $host;
    protected int $port;
    protected string $systemId;
    protected string $password;
    protected int $sequenceNumber = 1;
    protected bool $connected = false;
    protected bool $bound = false;
    protected int $timeout = 30;

    public function __construct(string $host, int $port, string $systemId, string $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->systemId = $systemId;
        $this->password = $password;
    }

    /**
     * Connecter au serveur SMPP
     */
    public function connect(): bool
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        if (!$this->socket) {
            Log::error('SMPP: Connection failed', [
                'host' => $this->host,
                'port' => $this->port,
                'error' => "$errstr ($errno)"
            ]);
            throw new \Exception("Connection failed: $errstr ($errno)");
        }

        stream_set_timeout($this->socket, $this->timeout);
        $this->connected = true;

        Log::info('SMPP: Connected', ['host' => $this->host, 'port' => $this->port]);
        return true;
    }

    /**
     * Bind en mode transceiver
     */
    public function bindTransceiver(): array
    {
        if (!$this->connected) {
            throw new \Exception("Not connected to SMPP server");
        }

        $body = $this->systemId . "\x00" .    // system_id
                $this->password . "\x00" .     // password
                "\x00" .                       // system_type (empty)
                pack('C', 0x34) .              // interface_version (SMPP v3.4 = 0x34)
                pack('C', self::TON_UNKNOWN) . // addr_ton
                pack('C', self::NPI_UNKNOWN) . // addr_npi
                "\x00";                        // address_range (empty)

        $response = $this->sendCommand(self::BIND_TRANSCEIVER, $body);

        if ($response['command_id'] === self::BIND_TRANSCEIVER_RESP && $response['status'] === 0) {
            $this->bound = true;
            Log::info('SMPP: Bound as transceiver', ['system_id' => $this->systemId]);
            return ['success' => true, 'status' => $response['status']];
        }

        Log::error('SMPP: Bind failed', ['status' => $response['status']]);
        throw new \Exception("Bind failed with status: " . $response['status']);
    }

    /**
     * Envoyer un SMS
     */
    public function sendSms(
        string $sourceAddr,
        string $destAddr,
        string $message,
        int $sourceTon = self::TON_ALPHANUMERIC,
        int $sourceNpi = self::NPI_UNKNOWN,
        int $destTon = self::TON_INTERNATIONAL,
        int $destNpi = self::NPI_ISDN
    ): array {
        if (!$this->bound) {
            throw new \Exception("Not bound to SMPP server");
        }

        // Encoder le message
        $encodedMessage = $this->encodeMessage($message);
        $dataCoding = $this->detectEncoding($message);

        $body = "\x00" .                              // service_type (empty)
                pack('C', $sourceTon) .               // source_addr_ton
                pack('C', $sourceNpi) .               // source_addr_npi
                $sourceAddr . "\x00" .                // source_addr
                pack('C', $destTon) .                 // dest_addr_ton
                pack('C', $destNpi) .                 // dest_addr_npi
                $destAddr . "\x00" .                  // destination_addr
                pack('C', 0x00) .                     // esm_class (default)
                pack('C', 0x00) .                     // protocol_id
                pack('C', 0x00) .                     // priority_flag
                "\x00" .                              // schedule_delivery_time (empty)
                "\x00" .                              // validity_period (empty)
                pack('C', 0x01) .                     // registered_delivery (request DLR)
                pack('C', 0x00) .                     // replace_if_present_flag
                pack('C', $dataCoding) .              // data_coding
                pack('C', 0x00) .                     // sm_default_msg_id
                pack('C', strlen($encodedMessage)) .  // sm_length
                $encodedMessage;                      // short_message

        $response = $this->sendCommand(self::SUBMIT_SM, $body);

        if ($response['command_id'] === self::SUBMIT_SM_RESP && $response['status'] === 0) {
            $messageId = $this->extractMessageId($response['body']);
            Log::info('SMPP: SMS sent', [
                'source' => $sourceAddr,
                'dest' => $destAddr,
                'message_id' => $messageId
            ]);
            return [
                'success' => true,
                'message_id' => $messageId,
                'status' => $response['status']
            ];
        }

        Log::error('SMPP: SMS send failed', [
            'status' => $response['status'],
            'dest' => $destAddr
        ]);

        return [
            'success' => false,
            'status' => $response['status'],
            'error' => $this->getStatusMessage($response['status'])
        ];
    }

    /**
     * Unbind et deconnecter
     */
    public function disconnect(): void
    {
        if ($this->bound) {
            try {
                $this->sendCommand(self::UNBIND, '');
                $this->bound = false;
                Log::info('SMPP: Unbound');
            } catch (\Exception $e) {
                Log::warning('SMPP: Unbind failed', ['error' => $e->getMessage()]);
            }
        }

        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
            $this->connected = false;
            Log::info('SMPP: Disconnected');
        }
    }

    /**
     * Envoyer une commande SMPP et lire la reponse
     */
    protected function sendCommand(int $commandId, string $body): array
    {
        $sequenceNumber = $this->sequenceNumber++;

        // Header: length (4) + command_id (4) + status (4) + sequence (4) = 16 bytes + body
        $header = pack('N', 16 + strlen($body)) .  // command_length
                  pack('N', $commandId) .           // command_id
                  pack('N', 0x00000000) .           // command_status (0 for requests)
                  pack('N', $sequenceNumber);       // sequence_number

        $pdu = $header . $body;

        // Envoyer
        $written = fwrite($this->socket, $pdu);
        if ($written === false || $written !== strlen($pdu)) {
            throw new \Exception("Failed to write to socket");
        }

        // Lire la reponse
        return $this->readResponse();
    }

    /**
     * Lire une reponse SMPP
     */
    protected function readResponse(): array
    {
        // Lire le header (16 bytes)
        $header = fread($this->socket, 16);
        if (strlen($header) < 16) {
            throw new \Exception("Failed to read response header");
        }

        $headerData = unpack('Nlength/Ncommand_id/Nstatus/Nsequence', $header);

        // Lire le body si present
        $bodyLength = $headerData['length'] - 16;
        $body = '';
        if ($bodyLength > 0) {
            $body = fread($this->socket, $bodyLength);
        }

        return [
            'length' => $headerData['length'],
            'command_id' => $headerData['command_id'],
            'status' => $headerData['status'],
            'sequence' => $headerData['sequence'],
            'body' => $body
        ];
    }

    /**
     * Encoder le message selon le contenu
     */
    protected function encodeMessage(string $message): string
    {
        // Verifier si le message contient des caracteres non-GSM
        if ($this->requiresUCS2($message)) {
            return mb_convert_encoding($message, 'UCS-2BE', 'UTF-8');
        }
        return $message;
    }

    /**
     * Detecter l'encodage necessaire
     */
    protected function detectEncoding(string $message): int
    {
        return $this->requiresUCS2($message) ? self::ENCODING_UCS2 : self::ENCODING_DEFAULT;
    }

    /**
     * Verifier si UCS2 est necessaire
     */
    protected function requiresUCS2(string $message): bool
    {
        // Caracteres GSM 7-bit basiques
        $gsmChars = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

        for ($i = 0; $i < mb_strlen($message, 'UTF-8'); $i++) {
            $char = mb_substr($message, $i, 1, 'UTF-8');
            if (strpos($gsmChars, $char) === false && !ctype_cntrl($char)) {
                // Verifier les caracteres etendus GSM
                $extended = "^{}\\[~]|€";
                if (strpos($extended, $char) === false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Extraire le message_id de la reponse
     */
    protected function extractMessageId(string $body): ?string
    {
        if (empty($body)) {
            return null;
        }
        // Le message_id est une chaine C-string
        $nullPos = strpos($body, "\x00");
        if ($nullPos !== false) {
            return substr($body, 0, $nullPos);
        }
        return $body;
    }

    /**
     * Obtenir le message d'erreur pour un code status
     */
    protected function getStatusMessage(int $status): string
    {
        $messages = [
            0x00000000 => 'OK',
            0x00000001 => 'Invalid message length',
            0x00000002 => 'Invalid command length',
            0x00000003 => 'Invalid command ID',
            0x00000004 => 'Incorrect bind status',
            0x00000005 => 'Already bound',
            0x00000006 => 'Invalid priority flag',
            0x00000007 => 'Invalid registered delivery flag',
            0x00000008 => 'System error',
            0x0000000A => 'Invalid source address',
            0x0000000B => 'Invalid destination address',
            0x0000000C => 'Invalid message ID',
            0x0000000D => 'Bind failed',
            0x0000000E => 'Invalid password',
            0x0000000F => 'Invalid system ID',
            0x00000011 => 'Cancel SM failed',
            0x00000013 => 'Replace SM failed',
            0x00000014 => 'Message queue full',
            0x00000015 => 'Invalid service type',
            0x00000033 => 'Invalid number of destinations',
            0x00000034 => 'Invalid distribution list name',
            0x00000040 => 'Invalid destination flag',
            0x00000042 => 'Invalid submit with replace request',
            0x00000043 => 'Invalid esm_class field',
            0x00000044 => 'Cannot submit to distribution list',
            0x00000045 => 'Submit SM failed',
            0x00000048 => 'Invalid source TON',
            0x00000049 => 'Invalid source NPI',
            0x00000050 => 'Invalid destination TON',
            0x00000051 => 'Invalid destination NPI',
            0x00000053 => 'Invalid system type',
            0x00000054 => 'Invalid replace_if_present flag',
            0x00000055 => 'Invalid number of messages',
            0x00000058 => 'Throttling error',
            0x00000061 => 'Invalid scheduled delivery time',
            0x00000062 => 'Invalid message validity period',
            0x00000063 => 'Predefined message not found',
            0x00000064 => 'ESME receiver temporary error',
            0x00000065 => 'ESME receiver permanent error',
            0x00000066 => 'ESME receiver reject message',
            0x00000067 => 'Query SM request failed',
            0x000000C0 => 'Error in optional part',
            0x000000C1 => 'Optional parameter not allowed',
            0x000000C2 => 'Invalid parameter length',
            0x000000C3 => 'Expected optional parameter missing',
            0x000000C4 => 'Invalid optional parameter value',
            0x000000FE => 'Delivery failure',
            0x000000FF => 'Unknown error',
        ];

        return $messages[$status] ?? "Unknown status code: $status";
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function isBound(): bool
    {
        return $this->bound;
    }
}
