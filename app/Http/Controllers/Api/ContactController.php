<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
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

        return response()->json(['data' => $contact]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $contact = Contact::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $contact->delete();

        return response()->json(['message' => 'Contact supprimé avec succès']);
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
