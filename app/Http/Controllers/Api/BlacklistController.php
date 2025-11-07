<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blacklist;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class BlacklistController extends Controller
{
    /**
     * List all blacklisted numbers
     */
    public function index(Request $request)
    {
        $blacklist = Blacklist::byUser($request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($blacklist);
    }

    /**
     * Add number to blacklist
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|max:20',
            'reason' => 'nullable|string|max:255',
        ]);

        // Check if already blacklisted
        $exists = Blacklist::isBlacklisted($request->user()->id, $validated['phone_number']);

        if ($exists) {
            return response()->json([
                'message' => 'Ce numéro est déjà dans la liste noire'
            ], 422);
        }

        $blacklist = Blacklist::create([
            'user_id' => $request->user()->id,
            'phone_number' => $validated['phone_number'],
            'reason' => $validated['reason'] ?? null,
            'added_at' => now(),
        ]);

        // Log the action
        AuditLog::logAction(
            'blacklist.add',
            $request->user()->id,
            null,
            Blacklist::class,
            $blacklist->id,
            null,
            ['phone_number' => $validated['phone_number']]
        );

        return response()->json([
            'message' => 'Numéro ajouté à la liste noire',
            'data' => $blacklist
        ], 201);
    }

    /**
     * Remove number from blacklist
     */
    public function destroy(Request $request, int $id)
    {
        $blacklist = Blacklist::byUser($request->user()->id)->findOrFail($id);

        $phoneNumber = $blacklist->phone_number;
        $blacklist->delete();

        // Log the action
        AuditLog::logAction(
            'blacklist.remove',
            $request->user()->id,
            null,
            Blacklist::class,
            $id,
            ['phone_number' => $phoneNumber],
            null
        );

        return response()->json([
            'message' => 'Numéro retiré de la liste noire'
        ]);
    }

    /**
     * Check if number is blacklisted
     */
    public function check(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string',
        ]);

        $isBlacklisted = Blacklist::isBlacklisted(
            $request->user()->id,
            $validated['phone_number']
        );

        return response()->json([
            'phone_number' => $validated['phone_number'],
            'is_blacklisted' => $isBlacklisted
        ]);
    }
}
