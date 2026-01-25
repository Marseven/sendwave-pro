<?php

namespace App\Services;

use App\Models\Blacklist;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class StopWordService
{
    /**
     * Keywords that trigger automatic unsubscribe
     */
    protected array $stopKeywords = [
        'STOP',
        'ARRET',
        'ARRÊT',
        'UNSUB',
        'UNSUBSCRIBE',
        'DESABONNER',
        'DÉSABONNER',
        'DESINSCRIPTION',
        'DÉSINSCRIPTION',
        'ANNULER',
        'REMOVE',
        'QUIT',
        'END',
        'CANCEL',
        'OPTOUT',
        'OPT-OUT',
    ];

    /**
     * Check if a message content contains a stop keyword
     */
    public function containsStopKeyword(string $content): bool
    {
        $normalizedContent = $this->normalizeText($content);

        foreach ($this->stopKeywords as $keyword) {
            $normalizedKeyword = $this->normalizeText($keyword);

            // Check if the content is exactly the keyword or starts with it
            if ($normalizedContent === $normalizedKeyword ||
                str_starts_with($normalizedContent, $normalizedKeyword . ' ')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the stop keyword found in content
     */
    public function getStopKeyword(string $content): ?string
    {
        $normalizedContent = $this->normalizeText($content);

        foreach ($this->stopKeywords as $keyword) {
            $normalizedKeyword = $this->normalizeText($keyword);

            if ($normalizedContent === $normalizedKeyword ||
                str_starts_with($normalizedContent, $normalizedKeyword . ' ')) {
                return $keyword;
            }
        }

        return null;
    }

    /**
     * Normalize text for comparison
     */
    protected function normalizeText(string $text): string
    {
        // Remove accents
        $text = $this->removeAccents($text);

        // Convert to uppercase
        $text = mb_strtoupper($text);

        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));

        return $text;
    }

    /**
     * Remove accents from string
     */
    protected function removeAccents(string $string): string
    {
        $unwanted = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C', 'Ñ' => 'N',
        ];

        return strtr($string, $unwanted);
    }

    /**
     * Process a STOP request - add phone to blacklist
     */
    public function processStopRequest(int $userId, string $phoneNumber, ?string $content = null): array
    {
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);
        $keyword = $content ? $this->getStopKeyword($content) : 'STOP';

        Log::info('StopWordService - Processing STOP request', [
            'user_id' => $userId,
            'phone' => $normalizedPhone,
            'keyword' => $keyword,
        ]);

        // Check if already blacklisted
        $existing = Blacklist::where('user_id', $userId)
            ->where('phone_number', $normalizedPhone)
            ->first();

        if ($existing) {
            Log::info('StopWordService - Phone already blacklisted', [
                'phone' => $normalizedPhone,
            ]);

            return [
                'success' => true,
                'already_blacklisted' => true,
                'phone' => $normalizedPhone,
                'message' => 'Ce numéro est déjà dans la liste noire',
            ];
        }

        // Add to blacklist
        $blacklist = Blacklist::create([
            'user_id' => $userId,
            'phone_number' => $normalizedPhone,
            'reason' => "Désinscription automatique - Mot-clé: {$keyword}",
            'source' => 'auto_stop',
            'added_at' => now(),
        ]);

        Log::info('StopWordService - Phone added to blacklist', [
            'blacklist_id' => $blacklist->id,
            'phone' => $normalizedPhone,
        ]);

        return [
            'success' => true,
            'already_blacklisted' => false,
            'phone' => $normalizedPhone,
            'blacklist_id' => $blacklist->id,
            'message' => 'Numéro ajouté à la liste noire avec succès',
        ];
    }

    /**
     * Check if a phone number is blacklisted
     */
    public function isBlacklisted(int $userId, string $phoneNumber): bool
    {
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

        return Blacklist::where('user_id', $userId)
            ->where('phone_number', $normalizedPhone)
            ->exists();
    }

    /**
     * Check multiple phone numbers and filter out blacklisted ones
     */
    public function filterBlacklisted(int $userId, array $phoneNumbers): array
    {
        $normalizedNumbers = array_map([$this, 'normalizePhoneNumber'], $phoneNumbers);

        $blacklisted = Blacklist::where('user_id', $userId)
            ->whereIn('phone_number', $normalizedNumbers)
            ->pluck('phone_number')
            ->toArray();

        $allowed = [];
        $filtered = [];

        foreach ($phoneNumbers as $phone) {
            $normalized = $this->normalizePhoneNumber($phone);
            if (in_array($normalized, $blacklisted)) {
                $filtered[] = $phone;
            } else {
                $allowed[] = $phone;
            }
        }

        return [
            'allowed' => $allowed,
            'filtered' => $filtered,
            'allowed_count' => count($allowed),
            'filtered_count' => count($filtered),
        ];
    }

    /**
     * Normalize phone number for storage/comparison
     */
    public function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zeros
        $cleaned = ltrim($cleaned, '0');

        // If starts with country code 241, keep it
        if (str_starts_with($cleaned, '241') && strlen($cleaned) === 11) {
            return '+' . $cleaned;
        }

        // If 8 digits, assume Gabon and add country code
        if (strlen($cleaned) === 8) {
            return '+241' . $cleaned;
        }

        // Return with + if looks like international
        if (strlen($cleaned) >= 10) {
            return '+' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Get all stop keywords
     */
    public function getStopKeywords(): array
    {
        return $this->stopKeywords;
    }

    /**
     * Add custom stop keyword
     */
    public function addStopKeyword(string $keyword): void
    {
        $normalized = $this->normalizeText($keyword);
        if (!in_array($normalized, array_map([$this, 'normalizeText'], $this->stopKeywords))) {
            $this->stopKeywords[] = mb_strtoupper($keyword);
        }
    }

    /**
     * Process incoming SMS for STOP keywords
     * This would be called from an incoming SMS webhook
     */
    public function processIncomingSms(int $userId, string $phoneNumber, string $content): array
    {
        if ($this->containsStopKeyword($content)) {
            return $this->processStopRequest($userId, $phoneNumber, $content);
        }

        return [
            'success' => true,
            'is_stop' => false,
            'phone' => $phoneNumber,
            'message' => 'Message ne contient pas de mot-clé STOP',
        ];
    }

    /**
     * Get blacklist statistics for a user
     */
    public function getBlacklistStats(int $userId): array
    {
        $total = Blacklist::where('user_id', $userId)->count();
        $autoStop = Blacklist::where('user_id', $userId)
            ->where('source', 'auto_stop')
            ->count();
        $manual = Blacklist::where('user_id', $userId)
            ->where('source', '!=', 'auto_stop')
            ->orWhereNull('source')
            ->count();

        return [
            'total' => $total,
            'auto_stop' => $autoStop,
            'manual' => $manual,
        ];
    }
}
