<?php

namespace App\Services\SMS;

class OperatorDetector
{
    /**
     * Préfixes des opérateurs gabonais
     */
    const AIRTEL_PREFIXES = [
        '77', // Airtel Gabon
        '74', // Airtel Gabon
        '76', // Airtel Gabon
    ];

    const MOOV_PREFIXES = [
        '60', // Moov Gabon
        '62', // Moov Gabon
        '65', // Moov Gabon
        '66', // Moov Gabon
    ];

    /**
     * Détecter l'opérateur d'un numéro de téléphone
     *
     * @param string $phoneNumber Numéro de téléphone (ex: +24177750737, 24177750737, 077750737)
     * @return string 'airtel', 'moov' ou 'unknown'
     */
    public static function detect(string $phoneNumber): string
    {
        // Nettoyer le numéro (enlever espaces, +, tirets, etc.)
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Si le numéro commence par 241 (code pays Gabon), enlever
        if (str_starts_with($cleaned, '241')) {
            $cleaned = substr($cleaned, 3);
        }

        // Enlever le 0 initial (format local gabonais: 074... -> 74...)
        if (str_starts_with($cleaned, '0') && strlen($cleaned) >= 8) {
            $cleaned = substr($cleaned, 1);
        }

        // Récupérer les 2 premiers chiffres (préfixe opérateur au Gabon)
        $prefix = substr($cleaned, 0, 2);

        // Vérifier Airtel
        if (in_array($prefix, self::AIRTEL_PREFIXES)) {
            return 'airtel';
        }

        // Vérifier Moov
        if (in_array($prefix, self::MOOV_PREFIXES)) {
            return 'moov';
        }

        // Opérateur inconnu
        return 'unknown';
    }

    /**
     * Vérifier si un numéro est Airtel
     *
     * @param string $phoneNumber
     * @return bool
     */
    public static function isAirtel(string $phoneNumber): bool
    {
        return self::detect($phoneNumber) === 'airtel';
    }

    /**
     * Vérifier si un numéro est Moov
     *
     * @param string $phoneNumber
     * @return bool
     */
    public static function isMoov(string $phoneNumber): bool
    {
        return self::detect($phoneNumber) === 'moov';
    }

    /**
     * Obtenir des informations détaillées sur le numéro
     *
     * @param string $phoneNumber
     * @return array
     */
    public static function getInfo(string $phoneNumber): array
    {
        $operator = self::detect($phoneNumber);
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (str_starts_with($cleaned, '241')) {
            $cleaned = substr($cleaned, 3);
        }

        // Enlever le 0 initial (format local gabonais)
        if (str_starts_with($cleaned, '0') && strlen($cleaned) >= 8) {
            $cleaned = substr($cleaned, 1);
        }

        $prefix = substr($cleaned, 0, 2);

        return [
            'original' => $phoneNumber,
            'cleaned' => $cleaned,
            'prefix' => $prefix,
            'operator' => $operator,
            'country_code' => '241',
            'full_number' => '241' . $cleaned,
            'formatted' => '+241 ' . $prefix . ' ' . substr($cleaned, 2, 2) . ' ' . substr($cleaned, 4, 2) . ' ' . substr($cleaned, 6, 2),
        ];
    }

    /**
     * Grouper des numéros par opérateur
     *
     * @param array $phoneNumbers
     * @return array ['airtel' => [], 'moov' => [], 'unknown' => []]
     */
    public static function groupByOperator(array $phoneNumbers): array
    {
        $groups = [
            'airtel' => [],
            'moov' => [],
            'unknown' => [],
        ];

        foreach ($phoneNumbers as $phoneNumber) {
            $operator = self::detect($phoneNumber);
            $groups[$operator][] = $phoneNumber;
        }

        return $groups;
    }
}
