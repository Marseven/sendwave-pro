<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Services\MessageVariableService;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/templates",
     *     tags={"Templates"},
     *     summary="List all message templates",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="category", in="query", required=false, @OA\Schema(type="string"), description="Filter by category"),
     *     @OA\Parameter(name="public_only", in="query", required=false, @OA\Schema(type="boolean"), description="Filter public templates only"),
     *     @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string", enum={"popular","recent"}), description="Sort order"),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *         @OA\Property(property="categories", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $query = MessageTemplate::query();

        // Include public templates
        $query->where(function ($q) use ($request) {
            $q->where('user_id', $request->user()->id)
              ->orWhere('is_public', true);
        });

        // Filter by category
        if ($request->has('category')) {
            $query->category($request->category);
        }

        // Filter by public only
        if ($request->boolean('public_only')) {
            $query->public();
        }

        // Sort options
        if ($request->input('sort') === 'popular') {
            $query->popular();
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $templates = $query->get();

        return response()->json([
            'data' => $templates,
            'categories' => MessageTemplate::CATEGORIES
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/templates",
     *     tags={"Templates"},
     *     summary="Create a new message template",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name","content","category"},
     *         @OA\Property(property="name", type="string", maxLength=255),
     *         @OA\Property(property="content", type="string", maxLength=320),
     *         @OA\Property(property="category", type="string"),
     *         @OA\Property(property="is_public", type="boolean")
     *     )),
     *     @OA\Response(response=201, description="Template created", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string|max:320',
            'category' => 'required|string|in:' . implode(',', array_keys(MessageTemplate::CATEGORIES)),
            'is_public' => 'nullable|boolean',
        ]);

        $template = MessageTemplate::create([
            'user_id' => $request->user()->id,
            'is_public' => $validated['is_public'] ?? false,
            ...$validated
        ]);

        // Extract variables from content
        $template->extractVariables();

        return response()->json([
            'message' => 'Modèle créé avec succès',
            'data' => $template
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/templates/{id}",
     *     tags={"Templates"},
     *     summary="Get a specific message template",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(type="object")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Template not found")
     * )
     */
    public function show(Request $request, string $id)
    {
        $template = MessageTemplate::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($template);
    }

    /**
     * @OA\Put(
     *     path="/api/templates/{id}",
     *     tags={"Templates"},
     *     summary="Update a message template",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string", maxLength=255),
     *         @OA\Property(property="content", type="string"),
     *         @OA\Property(property="category", type="string", maxLength=100),
     *         @OA\Property(property="is_public", type="boolean")
     *     )),
     *     @OA\Response(response=200, description="Template updated", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Template not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, string $id)
    {
        $template = MessageTemplate::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'category' => 'sometimes|string|max:100',
            'is_public' => 'sometimes|boolean',
        ]);

        $template->update($validated);

        // Re-extract variables if content changed
        if (isset($validated['content'])) {
            $template->extractVariables();
        }

        return response()->json([
            'message' => 'Modèle mis à jour',
            'data' => $template
        ]);
    }

    /**
     * Toggle template public/private status
     *
     * @OA\Post(
     *     path="/api/templates/{id}/toggle-public",
     *     tags={"Templates"},
     *     summary="Toggle template public/private status",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Status toggled", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Template not found")
     * )
     */
    public function togglePublic(Request $request, string $id)
    {
        $template = MessageTemplate::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $template->is_public = !$template->is_public;
        $template->save();

        return response()->json([
            'message' => $template->is_public
                ? 'Modèle partagé avec tous les utilisateurs'
                : 'Modèle rendu privé',
            'data' => $template
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/templates/{id}",
     *     tags={"Templates"},
     *     summary="Delete a message template",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Template deleted", @OA\JsonContent(
     *         @OA\Property(property="message", type="string")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Template not found")
     * )
     */
    public function destroy(Request $request, string $id)
    {
        $template = MessageTemplate::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $template->delete();

        return response()->json(['message' => 'Template supprimé avec succès']);
    }

    /**
     * Use a template (increment usage count)
     *
     * @OA\Post(
     *     path="/api/templates/{id}/use",
     *     tags={"Templates"},
     *     summary="Use a template and increment its usage count",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Template used", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Template not found")
     * )
     */
    public function use(Request $request, string $id)
    {
        $template = MessageTemplate::where(function ($q) use ($request) {
            $q->where('user_id', $request->user()->id)
              ->orWhere('is_public', true);
        })->findOrFail($id);

        $template->incrementUsage();

        return response()->json([
            'message' => 'Modèle utilisé',
            'data' => $template
        ]);
    }

    /**
     * Preview template with sample data
     *
     * @OA\Post(
     *     path="/api/templates/{id}/preview",
     *     tags={"Templates"},
     *     summary="Preview a template with sample data",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=false, @OA\JsonContent(
     *         @OA\Property(property="sample_data", type="object")
     *     )),
     *     @OA\Response(response=200, description="Template preview", @OA\JsonContent(
     *         @OA\Property(property="original", type="string"),
     *         @OA\Property(property="preview", type="string"),
     *         @OA\Property(property="variables", type="array", @OA\Items(type="string"))
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Template not found")
     * )
     */
    public function preview(Request $request, string $id)
    {
        $template = MessageTemplate::where(function ($q) use ($request) {
            $q->where('user_id', $request->user()->id)
              ->orWhere('is_public', true);
        })->findOrFail($id);

        $sampleData = $request->input('sample_data', []);

        $service = new MessageVariableService();
        $preview = $service->previewMessage($template->content, $sampleData);

        return response()->json([
            'original' => $template->content,
            'preview' => $preview,
            'variables' => $template->variables
        ]);
    }

    /**
     * Get template categories
     *
     * @OA\Get(
     *     path="/api/templates/categories",
     *     tags={"Templates"},
     *     summary="Get all available template categories",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function categories()
    {
        return response()->json([
            'data' => MessageTemplate::CATEGORIES
        ]);
    }
}
