<?php

namespace App\Http\Controllers\Api;

use App\Enums\MessageStatus;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageHistoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/messages/history",
     *     tags={"Messages"},
     *     summary="Get message history",
     *     description="Retrieve paginated history of sent messages with optional filters",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by recipient phone, name, or message content",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by message status",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=false,
     *         description="Filter by message type",
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
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated message history",
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
        $query = Message::where('user_id', Auth::id())
            ->with(['campaign:id,name', 'contact:id,name'])
            ->orderBy('sent_at', 'desc');

        // Filtrer par recherche (destinataire ou contenu)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('recipient_phone', 'LIKE', "%{$search}%")
                  ->orWhere('recipient_name', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        // Filtrer par statut
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filtrer par type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
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
        $perPage = $request->get('perPage', 20);
        $messages = $query->paginate($perPage);

        // Ajouter le nom de la campagne si disponible
        $items = $messages->items();
        foreach ($items as $message) {
            $message->campaign_name = $message->campaign ? $message->campaign->name : null;
            unset($message->campaign);
        }

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/messages/history/{id}",
     *     tags={"Messages"},
     *     summary="Get a specific message",
     *     description="Retrieve a single message by ID with campaign details",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Message ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message details",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Message not found")
     * )
     */
    public function show($id)
    {
        $message = Message::where('user_id', Auth::id())
            ->with(['campaign:id,name'])
            ->findOrFail($id);

        // Ajouter le nom de la campagne
        $message->campaign_name = $message->campaign ? $message->campaign->name : null;
        unset($message->campaign);

        return response()->json([
            'data' => $message
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/messages/stats",
     *     tags={"Messages"},
     *     summary="Get message statistics",
     *     description="Retrieve aggregated statistics for all messages (total, delivered, pending, failed, cost)",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Message statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total", type="integer", example=1000),
     *                 @OA\Property(property="delivered", type="integer", example=950),
     *                 @OA\Property(property="pending", type="integer", example=20),
     *                 @OA\Property(property="failed", type="integer", example=30),
     *                 @OA\Property(property="totalCost", type="number", example=20000)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function stats()
    {
        $userId = Auth::id();

        // Count 'sent' and 'delivered' together as 'delivered' for stats display
        $stats = [
            'total' => Message::where('user_id', $userId)->count(),
            'delivered' => Message::where('user_id', $userId)
                ->whereIn('status', [MessageStatus::SENT->value, MessageStatus::DELIVERED->value])
                ->count(),
            'pending' => Message::where('user_id', $userId)
                ->where('status', MessageStatus::PENDING->value)
                ->count(),
            'failed' => Message::where('user_id', $userId)
                ->where('status', MessageStatus::FAILED->value)
                ->count(),
            'totalCost' => Message::where('user_id', $userId)->sum('cost'),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/messages/export",
     *     tags={"Messages"},
     *     summary="Export message history to CSV",
     *     description="Export filtered message history as a downloadable CSV file",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by recipient phone, name, or message content",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by message status",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=false,
     *         description="Filter by message type",
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
     *     @OA\Response(
     *         response=200,
     *         description="CSV file download",
     *         @OA\MediaType(
     *             mediaType="text/csv"
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function export(Request $request)
    {
        $query = Message::where('user_id', Auth::id())
            ->with(['campaign:id,name'])
            ->orderBy('sent_at', 'desc');

        // Appliquer les mêmes filtres que pour index
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('recipient_phone', 'LIKE', "%{$search}%")
                  ->orWhere('recipient_name', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('dateFrom') && $request->dateFrom) {
            $query->whereDate('sent_at', '>=', $request->dateFrom);
        }

        if ($request->has('dateTo') && $request->dateTo) {
            $query->whereDate('sent_at', '<=', $request->dateTo);
        }

        $messages = $query->get();

        // Créer le contenu CSV
        $csv = "Date,Destinataire,Téléphone,Message,Type,Statut,Coût (XAF),Campagne\n";

        foreach ($messages as $message) {
            $campaignName = $message->campaign ? $message->campaign->name : 'N/A';
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $message->sent_at->format('Y-m-d H:i:s'),
                $message->recipient_name ?? 'Anonyme',
                $message->recipient_phone,
                str_replace('"', '""', $message->content),
                $message->type,
                $message->status,
                $message->cost,
                $campaignName
            );
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="messages_export_' . date('Y-m-d_H-i-s') . '.csv"');
    }
}
