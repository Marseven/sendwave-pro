<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * List audit logs
     *
     * @OA\Get(
     *     path="/api/audit-logs",
     *     tags={"Audit Logs"},
     *     summary="List audit logs with optional filters",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="action", in="query", required=false, @OA\Schema(type="string"), description="Filter by action type"),
     *     @OA\Parameter(name="start_date", in="query", required=false, @OA\Schema(type="string", format="date-time"), description="Filter from date"),
     *     @OA\Parameter(name="end_date", in="query", required=false, @OA\Schema(type="string", format="date-time"), description="Filter to date"),
     *     @OA\Parameter(name="model_type", in="query", required=false, @OA\Schema(type="string"), description="Filter by model type"),
     *     @OA\Parameter(name="model_id", in="query", required=false, @OA\Schema(type="integer"), description="Filter by model ID"),
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list of audit logs", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
     *
     * @OA\Get(
     *     path="/api/audit-logs/{id}",
     *     tags={"Audit Logs"},
     *     summary="Get a specific audit log entry",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Audit log details", @OA\JsonContent(
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=404, description="Audit log not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
     *
     * @OA\Get(
     *     path="/api/audit-logs/actions",
     *     tags={"Audit Logs"},
     *     summary="Get list of distinct audit log action types",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Response(response=200, description="List of available action types", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
