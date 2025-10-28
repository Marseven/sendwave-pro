<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageHistoryController extends Controller
{
    /**
     * Récupérer l'historique des messages
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
     * Récupérer un message spécifique
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
     * Obtenir les statistiques des messages
     */
    public function stats()
    {
        $userId = Auth::id();

        $stats = [
            'total' => Message::where('user_id', $userId)->count(),
            'delivered' => Message::where('user_id', $userId)->where('status', 'delivered')->count(),
            'pending' => Message::where('user_id', $userId)->where('status', 'pending')->count(),
            'failed' => Message::where('user_id', $userId)->where('status', 'failed')->count(),
            'totalCost' => Message::where('user_id', $userId)->sum('cost'),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Exporter l'historique en CSV
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
