<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $campaigns = Campaign::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:Actif,Terminé,Planifié',
            'messages_sent' => 'nullable|integer',
            'delivery_rate' => 'nullable|numeric|min:0|max:100',
            'ctr' => 'nullable|numeric|min:0|max:100',
            'sms_provider' => 'nullable|in:msg91,smsala,wapi',
            'message_content' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $campaign = Campaign::create([
            'user_id' => $request->user()->id,
            ...$validated
        ]);

        return response()->json($campaign, 201);
    }

    public function show(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($campaign);
    }

    public function update(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:Actif,Terminé,Planifié',
            'messages_sent' => 'sometimes|integer',
            'delivery_rate' => 'sometimes|numeric|min:0|max:100',
            'ctr' => 'sometimes|numeric|min:0|max:100',
            'sms_provider' => 'sometimes|in:msg91,smsala,wapi',
            'message_content' => 'sometimes|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $campaign->update($validated);

        return response()->json($campaign);
    }

    public function destroy(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $campaign->delete();

        return response()->json(['message' => 'Campagne supprimée avec succès']);
    }
}
