<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * List audit logs
     */
    public function index(Request $request)
    {
        $query = AuditLog::byUser($request->user()->id);

        // Filter by action
        if ($request->has('action')) {
            $query->action($request->action);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        // Filter by model
        if ($request->has('model_type') && $request->has('model_id')) {
            $query->forModel($request->model_type, $request->model_id);
        }

        $logs = $query->with(['user', 'subAccount'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($logs);
    }

    /**
     * Get specific audit log
     */
    public function show(Request $request, int $id)
    {
        $log = AuditLog::byUser($request->user()->id)
            ->with(['user', 'subAccount'])
            ->findOrFail($id);

        return response()->json(['data' => $log]);
    }

    /**
     * Get available actions
     */
    public function actions(Request $request)
    {
        $actions = AuditLog::byUser($request->user()->id)
            ->select('action')
            ->distinct()
            ->pluck('action');

        return response()->json(['data' => $actions]);
    }
}
