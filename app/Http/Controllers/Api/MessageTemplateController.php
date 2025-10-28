<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = MessageTemplate::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($templates);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:100',
        ]);

        $template = MessageTemplate::create([
            'user_id' => $request->user()->id,
            ...$validated
        ]);

        return response()->json($template, 201);
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
        ]);

        $template->update($validated);

        return response()->json($template);
    }

    public function destroy(Request $request, string $id)
    {
        $template = MessageTemplate::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $template->delete();

        return response()->json(['message' => 'Template supprimé avec succès']);
    }
}
