<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignHistoryController extends Controller
{
    /**
     * Récupérer l'historique des campagnes
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
     * Récupérer une campagne spécifique
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
     * Obtenir les statistiques des campagnes
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
