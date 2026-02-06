<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignHistoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/campaigns/history",
     *     tags={"Campaigns"},
     *     summary="Get campaign history",
     *     description="Retrieve paginated history of sent campaigns with optional filters",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by campaign name or message",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by campaign status",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="dateFrom",
     *         in="query",
     *         required=false,
     *         description="Filter by start date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="dateTo",
     *         in="query",
     *         required=false,
     *         description="Filter by end date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated campaign history",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $query = Campaign::where('user_id', Auth::id())
            ->whereNotNull('sent_at')
            ->orderBy('sent_at', 'desc');

        // Filtrer par recherche (nom ou message)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('message', 'LIKE', "%{$search}%");
            });
        }

        // Filtrer par statut
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filtrer par date de début
        if ($request->has('dateFrom') && $request->dateFrom) {
            $query->whereDate('sent_at', '>=', $request->dateFrom);
        }

        // Filtrer par date de fin
        if ($request->has('dateTo') && $request->dateTo) {
            $query->whereDate('sent_at', '<=', $request->dateTo);
        }

        // Pagination
        $perPage = $request->get('perPage', 10);
        $campaigns = $query->paginate($perPage);

        return response()->json([
            'data' => $campaigns->items(),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/campaigns/history/{id}",
     *     tags={"Campaigns"},
     *     summary="Get a specific campaign from history",
     *     description="Retrieve a single campaign by ID from history",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Campaign ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign details",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Campaign not found")
     * )
     */
    public function show($id)
    {
        $campaign = Campaign::where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'data' => $campaign
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/campaigns/stats",
     *     tags={"Campaigns"},
     *     summary="Get campaign statistics",
     *     description="Retrieve aggregated statistics for all campaigns (total, completed, scheduled, failed, cost, recipients)",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Campaign statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="completed", type="integer", example=40),
     *                 @OA\Property(property="scheduled", type="integer", example=5),
     *                 @OA\Property(property="failed", type="integer", example=5),
     *                 @OA\Property(property="totalCost", type="number", example=100000),
     *                 @OA\Property(property="totalRecipients", type="integer", example=5000)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function stats()
    {
        $userId = Auth::id();

        $stats = [
            'total' => Campaign::where('user_id', $userId)->count(),
            'completed' => Campaign::where('user_id', $userId)->where('status', 'completed')->count(),
            'scheduled' => Campaign::where('user_id', $userId)->where('status', 'scheduled')->count(),
            'failed' => Campaign::where('user_id', $userId)->where('status', 'failed')->count(),
            'totalCost' => Campaign::where('user_id', $userId)->sum('cost'),
            'totalRecipients' => Campaign::where('user_id', $userId)->sum('recipients_count'),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }
}
