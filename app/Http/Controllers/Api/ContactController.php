<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\WebhookService;
use Illuminate\Http\Request;

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
     * Import contacts from CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('file');
            $contents = file_get_contents($file->getRealPath());
            $lines = explode("\n", $contents);

            if (count($lines) === 0) {
                return response()->json([
                    'message' => 'Le fichier CSV est vide'
                ], 400);
            }

            // Extract headers
            $headers = str_getcsv($lines[0]);
            $importedCount = 0;
            $errors = [];

            // Process each line
            for ($i = 1; $i < count($lines); $i++) {
                if (empty(trim($lines[$i]))) {
                    continue;
                }

                $row = str_getcsv($lines[$i]);

                // Skip if row doesn't have enough columns
                if (count($row) < count($headers)) {
                    continue;
                }

                // Create associative array
                $data = [];
                foreach ($headers as $index => $header) {
                    $data[$header] = $row[$index] ?? '';
                }

                // Validate required fields
                if (empty($data['name']) || empty($data['email']) || empty($data['phone'])) {
                    $errors[] = "Ligne {$i}: données manquantes";
                    continue;
                }

                try {
                    Contact::create([
                        'user_id' => $request->user()->id,
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                        'group' => $data['group'] ?? null,
                        'status' => $data['status'] ?? 'active',
                        'last_connection' => now(),
                    ]);
                    $importedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Ligne {$i}: " . $e->getMessage();
                }
            }

            return response()->json([
                'message' => "{$importedCount} contacts importés avec succès",
                'imported' => $importedCount,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'importation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
