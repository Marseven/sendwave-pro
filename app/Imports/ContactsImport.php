<?php

namespace App\Imports;

use App\Models\Contact;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;

class ContactsImport implements ToCollection, WithHeadingRow, WithChunkReading, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    protected int $userId;
    protected string $duplicateAction;
    protected array $columnMapping;
    protected int $importedCount = 0;
    protected int $updatedCount = 0;
    protected int $skippedCount = 0;
    protected array $errors = [];

    /**
     * @param int $userId
     * @param string $duplicateAction - 'skip', 'update', 'create'
     * @param array $columnMapping - Maps file columns to contact fields
     */
    public function __construct(int $userId, string $duplicateAction = 'skip', array $columnMapping = [])
    {
        $this->userId = $userId;
        $this->duplicateAction = $duplicateAction;
        $this->columnMapping = $columnMapping;
    }

    /**
     * Process the collection of rows
     */
    public function collection(Collection $rows)
    {
        $contactsToInsert = [];
        $contactsToUpdate = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because index starts at 0 and we skip header

            // Map columns to fields
            $data = $this->mapRowToContact($row);

            if (empty($data['phone'])) {
                $this->errors[] = "Ligne {$rowNumber}: Téléphone requis";
                continue;
            }

            // Check for existing contact by phone or email
            $existingContact = $this->findExistingContact($data);

            if ($existingContact) {
                switch ($this->duplicateAction) {
                    case 'skip':
                        $this->skippedCount++;
                        continue 2;

                    case 'update':
                        $contactsToUpdate[] = [
                            'id' => $existingContact->id,
                            'data' => array_filter($data, fn($v) => $v !== null && $v !== '')
                        ];
                        break;

                    case 'create':
                        // Create anyway (allow duplicates)
                        $contactsToInsert[] = $this->prepareForInsert($data);
                        break;
                }
            } else {
                $contactsToInsert[] = $this->prepareForInsert($data);
            }
        }

        // Bulk insert new contacts
        if (!empty($contactsToInsert)) {
            foreach (array_chunk($contactsToInsert, 500) as $chunk) {
                Contact::insert($chunk);
                $this->importedCount += count($chunk);
            }
        }

        // Update existing contacts
        foreach ($contactsToUpdate as $update) {
            Contact::where('id', $update['id'])->update($update['data']);
            $this->updatedCount++;
        }
    }

    /**
     * Map a row to contact fields using column mapping
     */
    protected function mapRowToContact($row): array
    {
        $data = [
            'name' => null,
            'email' => null,
            'phone' => null,
            'group' => null,
            'status' => 'active',
        ];

        // Convert row to array if it's a collection
        $rowArray = $row instanceof Collection ? $row->toArray() : (array) $row;

        // Apply column mapping
        foreach ($this->columnMapping as $fileColumn => $contactField) {
            if (empty($contactField)) continue;

            $value = $rowArray[$fileColumn] ?? null;
            if ($value !== null && $value !== '') {
                $data[$contactField] = trim((string) $value);
            }
        }

        // Auto-detect columns if no mapping provided
        if (empty($this->columnMapping)) {
            foreach ($rowArray as $key => $value) {
                $keyLower = strtolower((string) $key);
                $value = trim((string) ($value ?? ''));

                if (empty($value)) continue;

                if (str_contains($keyLower, 'nom') || str_contains($keyLower, 'name')) {
                    $data['name'] = $value;
                } elseif (str_contains($keyLower, 'email') || str_contains($keyLower, 'mail')) {
                    $data['email'] = $value;
                } elseif (str_contains($keyLower, 'tel') || str_contains($keyLower, 'phone') || str_contains($keyLower, 'mobile')) {
                    $data['phone'] = $value;
                } elseif (str_contains($keyLower, 'group') || str_contains($keyLower, 'groupe')) {
                    $data['group'] = $value;
                } elseif (str_contains($keyLower, 'status') || str_contains($keyLower, 'statut')) {
                    $data['status'] = in_array(strtolower($value), ['active', 'actif', '1', 'yes', 'oui']) ? 'active' : 'inactive';
                }
            }
        }

        // Clean phone number
        if ($data['phone']) {
            $data['phone'] = preg_replace('/[^\d+]/', '', $data['phone']);
        }

        return $data;
    }

    /**
     * Find existing contact by phone or email
     */
    protected function findExistingContact(array $data): ?Contact
    {
        $query = Contact::where('user_id', $this->userId);

        if (!empty($data['phone'])) {
            $query->where(function ($q) use ($data) {
                $q->where('phone', $data['phone']);
                if (!empty($data['email'])) {
                    $q->orWhere('email', $data['email']);
                }
            });
        } elseif (!empty($data['email'])) {
            $query->where('email', $data['email']);
        } else {
            return null;
        }

        return $query->first();
    }

    /**
     * Prepare data for bulk insert
     */
    protected function prepareForInsert(array $data): array
    {
        return [
            'user_id' => $this->userId,
            'name' => $data['name'] ?? 'Contact',
            'email' => $data['email'],
            'phone' => $data['phone'],
            'group' => $data['group'],
            'status' => $data['status'] ?? 'active',
            'last_connection' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Chunk size for reading large files
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Validation rules for each row
     */
    public function rules(): array
    {
        return [
            '*.phone' => 'nullable',
            '*.email' => 'nullable|email',
        ];
    }

    /**
     * Get import results
     */
    public function getResults(): array
    {
        return [
            'imported' => $this->importedCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'errors' => array_merge($this->errors, $this->getFailureMessages()),
        ];
    }

    /**
     * Get failure messages from validation
     */
    protected function getFailureMessages(): array
    {
        $messages = [];
        foreach ($this->failures() as $failure) {
            $messages[] = "Ligne {$failure->row()}: " . implode(', ', $failure->errors());
        }
        return $messages;
    }
}
