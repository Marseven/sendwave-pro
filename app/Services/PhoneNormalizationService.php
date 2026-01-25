<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PhoneNormalizationService
{
    /**
     * Supported countries with their phone prefixes
     */
    protected array $countries = [
        'GA' => [
            'name' => 'Gabon',
            'code' => '241',
            'length' => 8, // Local number length without country code
            'operators' => [
                '77' => 'airtel',
                '74' => 'airtel',
                '76' => 'airtel',
                '60' => 'moov',
                '62' => 'moov',
                '65' => 'moov',
                '66' => 'moov',
            ],
        ],
        'CM' => [
            'name' => 'Cameroun',
            'code' => '237',
            'length' => 9,
            'operators' => [
                '65' => 'mtn',
                '67' => 'mtn',
                '68' => 'mtn',
                '69' => 'orange',
                '65' => 'orange',
                '66' => 'nexttel',
            ],
        ],
        'CG' => [
            'name' => 'Congo',
            'code' => '242',
            'length' => 9,
            'operators' => [
                '04' => 'mtn',
                '05' => 'airtel',
                '06' => 'airtel',
            ],
        ],
        'CI' => [
            'name' => 'Côte d\'Ivoire',
            'code' => '225',
            'length' => 10,
            'operators' => [
                '07' => 'orange',
                '05' => 'mtn',
                '01' => 'moov',
            ],
        ],
        'SN' => [
            'name' => 'Sénégal',
            'code' => '221',
            'length' => 9,
            'operators' => [
                '77' => 'orange',
                '78' => 'orange',
                '76' => 'free',
                '70' => 'expresso',
            ],
        ],
    ];

    /**
     * Default country code for normalization
     */
    protected string $defaultCountry = 'GA';

    /**
     * Normalize a phone number to E.164 format
     *
     * @param string $phone Raw phone number
     * @param string|null $countryHint Optional country code hint (GA, CM, etc.)
     * @return array Contains normalized number and metadata
     */
    public function normalize(string $phone, ?string $countryHint = null): array
    {
        // Remove all non-numeric characters except leading +
        $hasPlus = str_starts_with($phone, '+');
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zeros
        $cleaned = ltrim($cleaned, '0');

        // Try to detect country from the number
        $detectedCountry = $this->detectCountry($cleaned);
        $country = $detectedCountry ?? $countryHint ?? $this->defaultCountry;

        $countryInfo = $this->countries[$country] ?? $this->countries[$this->defaultCountry];
        $countryCode = $countryInfo['code'];
        $expectedLength = $countryInfo['length'];

        // Determine the normalized number
        $normalizedNumber = $this->buildE164Number($cleaned, $countryCode, $expectedLength);

        // Detect operator
        $operator = $this->detectOperator($normalizedNumber, $country);

        return [
            'original' => $phone,
            'normalized' => $normalizedNumber,
            'country_code' => $country,
            'country_name' => $countryInfo['name'],
            'dial_code' => $countryCode,
            'operator' => $operator,
            'is_valid' => $this->isValidNumber($normalizedNumber, $countryCode, $expectedLength),
            'format' => [
                'e164' => $normalizedNumber,
                'international' => $this->formatInternational($normalizedNumber, $countryCode),
                'local' => $this->formatLocal($normalizedNumber, $countryCode),
            ],
        ];
    }

    /**
     * Build E.164 formatted number
     */
    protected function buildE164Number(string $cleaned, string $countryCode, int $expectedLength): string
    {
        // If already starts with country code
        if (str_starts_with($cleaned, $countryCode)) {
            $localPart = substr($cleaned, strlen($countryCode));
            if (strlen($localPart) === $expectedLength) {
                return '+' . $cleaned;
            }
        }

        // If number length matches expected local length
        if (strlen($cleaned) === $expectedLength) {
            return '+' . $countryCode . $cleaned;
        }

        // If number is already full international format (with different country)
        if (strlen($cleaned) >= 10) {
            return '+' . $cleaned;
        }

        // Default: prepend country code
        return '+' . $countryCode . $cleaned;
    }

    /**
     * Detect country from phone number
     */
    protected function detectCountry(string $cleaned): ?string
    {
        foreach ($this->countries as $code => $info) {
            if (str_starts_with($cleaned, $info['code'])) {
                $localPart = substr($cleaned, strlen($info['code']));
                if (strlen($localPart) === $info['length']) {
                    return $code;
                }
            }
        }

        return null;
    }

    /**
     * Detect operator from normalized number
     */
    protected function detectOperator(string $normalizedNumber, string $country): ?string
    {
        $countryInfo = $this->countries[$country] ?? null;
        if (!$countryInfo) {
            return null;
        }

        // Get the local part of the number
        $countryCode = $countryInfo['code'];
        if (!str_starts_with($normalizedNumber, '+' . $countryCode)) {
            return null;
        }

        $localPart = substr($normalizedNumber, strlen($countryCode) + 1); // +1 for the +
        $prefix = substr($localPart, 0, 2);

        return $countryInfo['operators'][$prefix] ?? null;
    }

    /**
     * Validate if the normalized number is correct
     */
    protected function isValidNumber(string $normalizedNumber, string $countryCode, int $expectedLength): bool
    {
        if (!str_starts_with($normalizedNumber, '+')) {
            return false;
        }

        $withoutPlus = substr($normalizedNumber, 1);

        // Check if starts with expected country code
        if (str_starts_with($withoutPlus, $countryCode)) {
            $localPart = substr($withoutPlus, strlen($countryCode));
            return strlen($localPart) === $expectedLength;
        }

        // For other countries, just check if it looks like a valid international number
        return strlen($withoutPlus) >= 10 && strlen($withoutPlus) <= 15;
    }

    /**
     * Format number for international display
     */
    protected function formatInternational(string $normalizedNumber, string $countryCode): string
    {
        if (!str_starts_with($normalizedNumber, '+' . $countryCode)) {
            return $normalizedNumber;
        }

        $localPart = substr($normalizedNumber, strlen($countryCode) + 1);

        // Format: +241 77 12 34 56 (for Gabon)
        if (strlen($localPart) === 8) {
            return '+' . $countryCode . ' ' .
                substr($localPart, 0, 2) . ' ' .
                substr($localPart, 2, 2) . ' ' .
                substr($localPart, 4, 2) . ' ' .
                substr($localPart, 6, 2);
        }

        // Format: +237 6 50 12 34 56 (for Cameroon with 9 digits)
        if (strlen($localPart) === 9) {
            return '+' . $countryCode . ' ' .
                substr($localPart, 0, 1) . ' ' .
                substr($localPart, 1, 2) . ' ' .
                substr($localPart, 3, 2) . ' ' .
                substr($localPart, 5, 2) . ' ' .
                substr($localPart, 7, 2);
        }

        return $normalizedNumber;
    }

    /**
     * Format number for local display (without country code)
     */
    protected function formatLocal(string $normalizedNumber, string $countryCode): string
    {
        if (!str_starts_with($normalizedNumber, '+' . $countryCode)) {
            return $normalizedNumber;
        }

        $localPart = substr($normalizedNumber, strlen($countryCode) + 1);

        // Format: 77 12 34 56 (for Gabon)
        if (strlen($localPart) === 8) {
            return substr($localPart, 0, 2) . ' ' .
                substr($localPart, 2, 2) . ' ' .
                substr($localPart, 4, 2) . ' ' .
                substr($localPart, 6, 2);
        }

        return $localPart;
    }

    /**
     * Normalize multiple phone numbers
     *
     * @param array $phones Array of phone numbers
     * @param string|null $countryHint Optional country code hint
     * @return array
     */
    public function normalizeMany(array $phones, ?string $countryHint = null): array
    {
        $results = [
            'valid' => [],
            'invalid' => [],
            'all' => [],
            'by_country' => [],
            'by_operator' => [],
        ];

        foreach ($phones as $phone) {
            $normalized = $this->normalize($phone, $countryHint);
            $results['all'][] = $normalized;

            if ($normalized['is_valid']) {
                $results['valid'][] = $normalized['normalized'];

                // Group by country
                $country = $normalized['country_code'];
                if (!isset($results['by_country'][$country])) {
                    $results['by_country'][$country] = [];
                }
                $results['by_country'][$country][] = $normalized['normalized'];

                // Group by operator
                $operator = $normalized['operator'] ?? 'unknown';
                if (!isset($results['by_operator'][$operator])) {
                    $results['by_operator'][$operator] = [];
                }
                $results['by_operator'][$operator][] = $normalized['normalized'];
            } else {
                $results['invalid'][] = [
                    'original' => $phone,
                    'attempted' => $normalized['normalized'],
                ];
            }
        }

        $results['summary'] = [
            'total' => count($phones),
            'valid_count' => count($results['valid']),
            'invalid_count' => count($results['invalid']),
            'countries' => array_keys($results['by_country']),
            'operators' => array_keys($results['by_operator']),
        ];

        return $results;
    }

    /**
     * Get supported countries list
     */
    public function getSupportedCountries(): array
    {
        return array_map(function ($code) {
            $info = $this->countries[$code];
            return [
                'code' => $code,
                'name' => $info['name'],
                'dial_code' => $info['code'],
                'operators' => array_unique(array_values($info['operators'])),
            ];
        }, array_keys($this->countries));
    }

    /**
     * Set default country
     */
    public function setDefaultCountry(string $countryCode): self
    {
        if (isset($this->countries[$countryCode])) {
            $this->defaultCountry = $countryCode;
        }

        return $this;
    }

    /**
     * Get the dial code for a country
     */
    public function getDialCode(string $countryCode): ?string
    {
        return $this->countries[$countryCode]['code'] ?? null;
    }

    /**
     * Check if a country is supported
     */
    public function isCountrySupported(string $countryCode): bool
    {
        return isset($this->countries[$countryCode]);
    }

    /**
     * Extract just the E.164 number from a phone string
     */
    public function toE164(string $phone, ?string $countryHint = null): string
    {
        $result = $this->normalize($phone, $countryHint);
        return $result['normalized'];
    }

    /**
     * Compare two phone numbers (checks if they're the same after normalization)
     */
    public function isSameNumber(string $phone1, string $phone2, ?string $countryHint = null): bool
    {
        $normalized1 = $this->toE164($phone1, $countryHint);
        $normalized2 = $this->toE164($phone2, $countryHint);

        return $normalized1 === $normalized2;
    }
}
