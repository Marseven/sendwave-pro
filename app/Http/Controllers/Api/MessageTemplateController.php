<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Services\MessageVariableService;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
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

    public function show(Request $request, string $id)
    {
        $template = MessageTemplate::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($template);
    }

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

    public function destroy(Request $request, string $id)
    {
        $template = MessageTemplate::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $template->delete();

        return response()->json(['message' => 'Template supprimé avec succès']);
    }

    /**
     * Use a template (increment usage count)
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
     */
    public function categories()
    {
        return response()->json([
            'data' => MessageTemplate::CATEGORIES
        ]);
    }
}
