<?php

namespace App\Services\SMS;

interface SmsServiceInterface
{
    /**
     * Envoyer un SMS
     *
     * @param string $to Numéro de téléphone du destinataire
     * @param string $message Contenu du message
     * @return array Résultat de l'envoi
     */
    public function sendSms(string $to, string $message): array;

    /**
     * Envoyer des SMS en masse
     *
     * @param array $recipients Liste des destinataires
     * @param string $message Contenu du message
     * @return array Résultat de l'envoi
     */
    public function sendBulkSms(array $recipients, string $message): array;

    /**
     * Obtenir le statut d'un message
     *
     * @param string $messageId ID du message
     * @return array Statut du message
     */
    public function getMessageStatus(string $messageId): array;

    /**
     * Obtenir le solde du compte
     *
     * @return array Informations sur le solde
     */
    public function getBalance(): array;
}
