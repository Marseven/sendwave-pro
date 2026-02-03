<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\WebhookService;
use App\Services\PhoneNormalizationService;
use App\Imports\ContactsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ContactController extends Controller
{
    public function __construct(
        protected WebhookService $webhookService,
        protected PhoneNormalizationService $phoneService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/contacts",
     *     tags={"Contacts"},
     *     summary="List all contacts",
     *     description="Retrieve contacts for the authenticated user with search, filters and pagination",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, description="Search by name, email or phone", @OA\Schema(type="string")),
     *     @OA\Parameter(name="group_id", in="query", required=false, description="Filter by contact group ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", required=false, description="Filter by status (active, inactive)", @OA\Schema(type="string", enum={"active", "inactive"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Results per page (default 15, max 100)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="page", in="query", required=false, description="Page number", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of contacts",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+24177123456"),
     *                 @OA\Property(property="group", type="string", example="VIP"),
     *                 @OA\Property(property="status", type="string", example="active")
     *             )),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=42)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $query = Contact::where('user_id', $request->user()->id);

        // Search by name, email or phone
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by contact group
        if ($groupId = $request->input('group_id')) {
            $query->whereHas('groups', function ($q) use ($groupId) {
                $q->where('contact_groups.id', $groupId);
            });
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        $contacts = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $contacts->items(),
            'meta' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/contacts",
     *     tags={"Contacts"},
     *     summary="Create a new contact",
     *     description="Store a newly created contact",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "phone"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="+24177123456"),
     *             @OA\Property(property="group", type="string", example="VIP"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active"),
     *             @OA\Property(property="last_connection", type="string", format="date-time"),
     *             @OA\Property(property="custom_fields", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Contact created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^[+]?[0-9\s\-]{7,20}$/'],
            'group' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
            'last_connection' => 'nullable|date',
            'custom_fields' => 'nullable|array',
        ]);

        $userId = $request->user()->id;

        // Normalize phone number
        $normalization = $this->phoneService->normalize($validated['phone']);
        $normalizedPhone = $normalization['is_valid'] ? $normalization['normalized'] : $validated['phone'];

        // Check for duplicate phone number (on normalized form)
        $existing = Contact::where('user_id', $userId)
            ->where(function ($q) use ($normalizedPhone, $validated) {
                $q->where('phone', $normalizedPhone)
                  ->orWhere('phone', $validated['phone']);
            })
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Un contact avec ce numéro de téléphone existe déjà',
                'existing_contact' => [
                    'id' => $existing->id,
                    'name' => $existing->name,
                    'phone' => $existing->phone,
                ],
            ], 409);
        }

        $contact = Contact::create([
            'user_id' => $userId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $normalizedPhone,
            'group' => $validated['group'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'last_connection' => $validated['last_connection'] ?? now(),
            'custom_fields' => $validated['custom_fields'] ?? [],
        ]);

        // Trigger webhook for contact.created
        $this->webhookService->trigger('contact.created', $userId, [
            'contact_id' => $contact->id,
            'name' => $contact->name,
            'phone' => $contact->phone,
            'email' => $contact->email,
        ]);

        return response()->json(['data' => $contact], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/contacts/{id}",
     *     tags={"Contacts"},
     *     summary="Get a specific contact",
     *     description="Retrieve a single contact by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact details",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Contact not found")
     * )
     */
    public function show(Request $request, string $id)
    {
        $contact = Contact::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json(['data' => $contact]);
    }

    /**
     * @OA\Put(
     *     path="/api/contacts/{id}",
     *     tags={"Contacts"},
     *     summary="Update a contact",
     *     description="Update an existing contact by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="+24177123456"),
     *             @OA\Property(property="group", type="string", example="VIP"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}),
     *             @OA\Property(property="last_connection", type="string", format="date-time"),
     *             @OA\Property(property="custom_fields", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Contact not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, string $id)
    {
        $contact = Contact::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => ['sometimes', 'string', 'max:20', 'regex:/^[+]?[0-9\s\-]{7,20}$/'],
            'group' => 'nullable|string|max:100',
            'status' => 'sometimes|in:active,inactive',
            'last_connection' => 'nullable|date',
            'custom_fields' => 'nullable|array',
        ]);

        // If phone is being updated, normalize and check for duplicates
        if (isset($validated['phone'])) {
            $normalization = $this->phoneService->normalize($validated['phone']);
            $normalizedPhone = $normalization['is_valid'] ? $normalization['normalized'] : $validated['phone'];

            $existing = Contact::where('user_id', $request->user()->id)
                ->where('id', '!=', $contact->id)
                ->where(function ($q) use ($normalizedPhone, $validated) {
                    $q->where('phone', $normalizedPhone)
                      ->orWhere('phone', $validated['phone']);
                })
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'Un autre contact avec ce numéro de téléphone existe déjà',
                    'existing_contact' => [
                        'id' => $existing->id,
                        'name' => $existing->name,
                        'phone' => $existing->phone,
                    ],
                ], 409);
            }

            $validated['phone'] = $normalizedPhone;
        }

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
     * @OA\Delete(
     *     path="/api/contacts/{id}",
     *     tags={"Contacts"},
     *     summary="Delete a contact",
     *     description="Remove a contact by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Contact supprime avec succes")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Contact not found")
     * )
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
     * @OA\Post(
     *     path="/api/contacts/delete-many",
     *     tags={"Contacts"},
     *     summary="Delete multiple contacts",
     *     description="Supprime plusieurs contacts en une seule requête",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacts deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="3 contacts supprimés"),
     *             @OA\Property(property="deleted_count", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function destroyMany(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ]);

        $userId = $request->user()->id;

        $contacts = Contact::where('user_id', $userId)
            ->whereIn('id', $validated['ids'])
            ->get();

        $deletedCount = 0;

        foreach ($contacts as $contact) {
            $contactData = [
                'contact_id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->phone,
            ];

            $contact->delete();
            $deletedCount++;

            $this->webhookService->trigger('contact.deleted', $userId, $contactData);
        }

        return response()->json([
            'message' => "{$deletedCount} contact(s) supprimé(s)",
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/contacts/export",
     *     tags={"Contacts"},
     *     summary="Export contacts",
     *     description="Export contacts to CSV or JSON format",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         required=false,
     *         description="Export format (csv or json)",
     *         @OA\Schema(type="string", enum={"csv", "json"}, default="csv")
     *     ),
     *     @OA\Parameter(
     *         name="group",
     *         in="query",
     *         required=false,
     *         description="Filter by group",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"active", "inactive"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacts exported successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
     * @OA\Post(
     *     path="/api/contacts/import",
     *     tags={"Contacts"},
     *     summary="Import contacts from file",
     *     description="Import contacts from CSV, XLSX, or XLS file (max 20MB)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(property="file", type="string", format="binary", description="CSV, XLSX, or XLS file"),
     *                 @OA\Property(property="duplicate_action", type="string", enum={"skip", "update", "create"}, description="Action for duplicates"),
     *                 @OA\Property(property="column_mapping", type="object", description="Column mapping configuration")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Import completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="10 contacts importes"),
     *             @OA\Property(property="imported", type="integer", example=10),
     *             @OA\Property(property="updated", type="integer", example=2),
     *             @OA\Property(property="skipped", type="integer", example=1),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="total_errors", type="integer", example=0)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Import error")
     * )
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
     * @OA\Post(
     *     path="/api/contacts/preview-import",
     *     tags={"Contacts"},
     *     summary="Preview import file",
     *     description="Preview headers and sample data from an import file before importing",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(property="file", type="string", format="binary", description="CSV, XLSX, or XLS file")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preview data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="headers", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="preview", type="array", @OA\Items(type="array", @OA\Items(type="string"))),
     *             @OA\Property(property="suggested_mapping", type="object"),
     *             @OA\Property(property="total_rows", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Empty file"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="File reading error")
     * )
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
