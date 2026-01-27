<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\WebhookService;
use App\Imports\ContactsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ContactController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $contacts = Contact::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $contacts]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'group' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
            'last_connection' => 'nullable|date',
            'custom_fields' => 'nullable|array',
        ]);

        $contact = Contact::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'group' => $validated['group'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'last_connection' => $validated['last_connection'] ?? now(),
            'custom_fields' => $validated['custom_fields'] ?? [],
        ]);

        // Trigger webhook for contact.created
        $this->webhookService->trigger('contact.created', $request->user()->id, [
            'contact_id' => $contact->id,
            'name' => $contact->name,
            'phone' => $contact->phone,
            'email' => $contact->email,
        ]);

        return response()->json(['data' => $contact], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $contact = Contact::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json(['data' => $contact]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $contact = Contact::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|string|max:20',
            'group' => 'nullable|string|max:100',
            'status' => 'sometimes|in:active,inactive',
            'last_connection' => 'nullable|date',
            'custom_fields' => 'nullable|array',
        ]);

        $contact->update($validated);

        // Trigger webhook for contact.updated
        $this->webhookService->trigger('contact.updated', $request->user()->id, [
            'contact_id' => $contact->id,
            'name' => $contact->name,
            'phone' => $contact->phone,
            'email' => $contact->email,
        ]);

        return response()->json(['data' => $contact]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $contact = Contact::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $contactData = [
            'contact_id' => $contact->id,
            'name' => $contact->name,
            'phone' => $contact->phone,
        ];

        $contact->delete();

        // Trigger webhook for contact.deleted
        $this->webhookService->trigger('contact.deleted', $request->user()->id, $contactData);

        return response()->json(['message' => 'Contact supprimé avec succès']);
    }

    /**
     * Export contacts to CSV
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $group = $request->get('group');
        $status = $request->get('status');

        $query = Contact::where('user_id', $request->user()->id);

        if ($group) {
            $query->where('group', $group);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $contacts = $query->orderBy('name')->get();

        if ($format === 'csv') {
            $headers = ['name', 'email', 'phone', 'group', 'status', 'created_at'];
            $csvContent = implode(',', $headers) . "\n";

            foreach ($contacts as $contact) {
                $row = [
                    $this->escapeCsv($contact->name),
                    $this->escapeCsv($contact->email),
                    $this->escapeCsv($contact->phone),
                    $this->escapeCsv($contact->group ?? ''),
                    $this->escapeCsv($contact->status),
                    $this->escapeCsv($contact->created_at->format('Y-m-d H:i:s')),
                ];
                $csvContent .= implode(',', $row) . "\n";
            }

            $filename = 'contacts_' . now()->format('Y-m-d_His') . '.csv';

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        }

        // JSON format
        return response()->json([
            'data' => $contacts,
            'total' => $contacts->count(),
            'exported_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Escape a value for CSV output
     */
    protected function escapeCsv(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        // If the value contains comma, newline, or quote, wrap in quotes and escape quotes
        if (preg_match('/[,"\n\r]/', $value)) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }

    /**
     * Import contacts from CSV, XLSX, or XLS file
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:20480', // Max 20MB
            'duplicate_action' => 'nullable|in:skip,update,create',
            'column_mapping' => 'nullable|array',
        ]);

        try {
            $file = $request->file('file');
            $duplicateAction = $request->input('duplicate_action', 'skip');
            $columnMapping = $request->input('column_mapping', []);

            // Create the import instance
            $import = new ContactsImport(
                $request->user()->id,
                $duplicateAction,
                $columnMapping
            );

            // Import the file
            Excel::import($import, $file);

            // Get results
            $results = $import->getResults();

            // Build message
            $messages = [];
            if ($results['imported'] > 0) {
                $messages[] = "{$results['imported']} contacts importés";
            }
            if ($results['updated'] > 0) {
                $messages[] = "{$results['updated']} contacts mis à jour";
            }
            if ($results['skipped'] > 0) {
                $messages[] = "{$results['skipped']} doublons ignorés";
            }

            $message = !empty($messages)
                ? implode(', ', $messages)
                : 'Aucun contact importé';

            // Trigger webhook for bulk import
            if ($results['imported'] > 0) {
                $this->webhookService->trigger('contacts.imported', $request->user()->id, [
                    'imported_count' => $results['imported'],
                    'updated_count' => $results['updated'],
                    'skipped_count' => $results['skipped'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'imported' => $results['imported'],
                'updated' => $results['updated'],
                'skipped' => $results['skipped'],
                'errors' => array_slice($results['errors'], 0, 50), // Limit errors to 50
                'total_errors' => count($results['errors']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'importation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview file for import (get headers and sample data)
     */
    public function previewImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:20480',
        ]);

        try {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            // Read file content
            if (in_array($extension, ['xlsx', 'xls'])) {
                $data = Excel::toArray(null, $file);
                $rows = $data[0] ?? [];
            } else {
                // CSV parsing
                $content = file_get_contents($file->getRealPath());
                $lines = array_filter(explode("\n", $content), fn($l) => trim($l) !== '');
                $rows = array_map(fn($line) => str_getcsv($line), $lines);
            }

            if (empty($rows)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le fichier est vide',
                ], 400);
            }

            // Get headers (first row)
            $headers = array_map(fn($h) => trim((string) $h), $rows[0]);

            // Get preview data (up to 5 rows)
            $preview = array_slice($rows, 1, 5);

            // Auto-suggest column mapping
            $suggestedMapping = [];
            foreach ($headers as $header) {
                $headerLower = strtolower($header);
                if (str_contains($headerLower, 'nom') || str_contains($headerLower, 'name')) {
                    $suggestedMapping[$header] = 'name';
                } elseif (str_contains($headerLower, 'email') || str_contains($headerLower, 'mail')) {
                    $suggestedMapping[$header] = 'email';
                } elseif (str_contains($headerLower, 'tel') || str_contains($headerLower, 'phone') || str_contains($headerLower, 'mobile')) {
                    $suggestedMapping[$header] = 'phone';
                } elseif (str_contains($headerLower, 'group') || str_contains($headerLower, 'groupe')) {
                    $suggestedMapping[$header] = 'group';
                } elseif (str_contains($headerLower, 'status') || str_contains($headerLower, 'statut')) {
                    $suggestedMapping[$header] = 'status';
                }
            }

            return response()->json([
                'success' => true,
                'headers' => $headers,
                'preview' => $preview,
                'suggested_mapping' => $suggestedMapping,
                'total_rows' => count($rows) - 1, // Exclude header row
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la lecture du fichier',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
