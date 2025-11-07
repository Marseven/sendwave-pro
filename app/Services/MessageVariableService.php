<?php

namespace App\Services;

use App\Models\Contact;

class MessageVariableService
{
    /**
     * Available variables that can be used in messages
     */
    protected array $availableVariables = [
        '{nom}' => 'contact.name',
        '{prenom}' => 'contact.first_name',
        '{email}' => 'contact.email',
        '{telephone}' => 'contact.phone',
    ];

    /**
     * Replace variables in message with contact data
     */
    public function replaceVariables(string $message, Contact $contact): string
    {
        $replacements = [
            '{nom}' => $contact->name ?? '',
            '{prenom}' => $this->extractFirstName($contact->name) ?? '',
            '{email}' => $contact->email ?? '',
            '{telephone}' => $contact->phone ?? '',
        ];

        // Replace custom fields: {custom.field_name}
        if ($contact->custom_fields && is_array($contact->custom_fields)) {
            foreach ($contact->custom_fields as $key => $value) {
                $replacements['{custom.' . $key . '}'] = $value;
            }
        }

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $message
        );
    }

    /**
     * Extract first name from full name
     */
    protected function extractFirstName(?string $fullName): ?string
    {
        if (!$fullName) {
            return null;
        }

        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? null;
    }

    /**
     * Get list of available variables
     */
    public function getAvailableVariables(): array
    {
        return $this->availableVariables;
    }

    /**
     * Find all variables in a message template
     */
    public function findVariables(string $message): array
    {
        preg_match_all('/\{([^}]+)\}/', $message, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Validate that all variables in message are valid
     */
    public function validateVariables(string $message): array
    {
        $variables = $this->findVariables($message);
        $errors = [];

        foreach ($variables as $variable) {
            // Check if it's a standard variable
            if (isset($this->availableVariables[$variable])) {
                continue;
            }

            // Check if it's a custom field variable
            if (preg_match('/^\{custom\.[a-zA-Z0-9_]+\}$/', $variable)) {
                continue;
            }

            $errors[] = $variable;
        }

        return $errors;
    }

    /**
     * Preview message with sample data
     */
    public function previewMessage(string $message, array $sampleData = []): string
    {
        $defaults = [
            '{nom}' => 'Dupont',
            '{prenom}' => 'Jean',
            '{email}' => 'jean.dupont@example.com',
            '{telephone}' => '+24162000000',
        ];

        // Merge with custom sample data
        $replacements = array_merge($defaults, $sampleData);

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $message
        );
    }

    /**
     * Bulk replace variables for multiple contacts
     */
    public function bulkReplace(string $message, array $contacts): array
    {
        $result = [];

        foreach ($contacts as $contact) {
            if ($contact instanceof Contact) {
                $result[$contact->id] = [
                    'contact_id' => $contact->id,
                    'original_message' => $message,
                    'personalized_message' => $this->replaceVariables($message, $contact),
                ];
            }
        }

        return $result;
    }
}
