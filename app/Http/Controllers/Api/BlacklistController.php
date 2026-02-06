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
     *
     * @OA\Get(
     *     path="/api/blacklist",
     *     tags={"Blacklist"},
     *     summary="List all blacklisted numbers",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list of blacklisted numbers", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
     *
     * @OA\Post(
     *     path="/api/blacklist",
     *     tags={"Blacklist"},
     *     summary="Add a phone number to the blacklist",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"phone_number"},
     *         @OA\Property(property="phone_number", type="string", example="24177123456"),
     *         @OA\Property(property="reason", type="string", example="User requested opt-out")
     *     )),
     *     @OA\Response(response=201, description="Number added to blacklist", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=422, description="Number already blacklisted"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
     *
     * @OA\Delete(
     *     path="/api/blacklist/{id}",
     *     tags={"Blacklist"},
     *     summary="Remove a phone number from the blacklist",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Number removed from blacklist", @OA\JsonContent(
     *         @OA\Property(property="message", type="string")
     *     )),
     *     @OA\Response(response=404, description="Blacklist entry not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
     *
     * @OA\Post(
     *     path="/api/blacklist/check",
     *     tags={"Blacklist"},
     *     summary="Check if a phone number is blacklisted",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"phone_number"},
     *         @OA\Property(property="phone_number", type="string", example="24177123456")
     *     )),
     *     @OA\Response(response=200, description="Blacklist check result", @OA\JsonContent(
     *         @OA\Property(property="phone_number", type="string"),
     *         @OA\Property(property="is_blacklisted", type="boolean")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
