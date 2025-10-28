<?php

namespace App\Services;

interface SmsProviderInterface
{
    /**
     * Envoyer un SMS
     *
     * @param string|array $to Numéro(s) de destination
     * @param string $message Contenu du message
     * @return array Résultat de l'envoi
     */
    public function send($to, string $message): array;

    /**
     * Tester la connexion avec le provider
     *
     * @return array Résultat du test
     */
    public function testConnection(): array;

    /**
     * Obtenir le solde du compte
     *
     * @return array Informations sur le solde
     */
    public function getBalance(): array;
}
