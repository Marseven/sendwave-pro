<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\WebhookLog;
use App\Models\AuditLog;
use App\Services\WebhookService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * List all webhooks
     *
     * @OA\Get(
     *     path="/api/webhooks",
     *     tags={"Webhooks"},
     *     summary="List all webhooks for the authenticated user",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *         @OA\Property(property="meta", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $webhooks = Webhook::byUser($request->user()->id)
            ->withCount(['logs'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($webhooks);
    }

    /**
     * Get available webhook events
     *
     * @OA\Get(
     *     path="/api/webhooks/events",
     *     tags={"Webhooks"},
     *     summary="Get all available webhook event types",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function events()
    {
        return response()->json([
            'data' => Webhook::EVENTS
        ]);
    }

    /**
     * Create new webhook
     *
     * @OA\Post(
     *     path="/api/webhooks",
     *     tags={"Webhooks"},
     *     summary="Create a new webhook",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name","url","events"},
     *         @OA\Property(property="name", type="string", maxLength=255),
     *         @OA\Property(property="url", type="string", format="url", maxLength=500),
     *         @OA\Property(property="events", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="secret", type="string", minLength=16, maxLength=64),
     *         @OA\Property(property="retry_limit", type="integer", minimum=0, maximum=10),
     *         @OA\Property(property="timeout", type="integer", minimum=5, maximum=120),
     *         @OA\Property(property="is_active", type="boolean")
     *     )),
     *     @OA\Response(response=201, description="Webhook created", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'required|string|in:' . implode(',', array_keys(Webhook::EVENTS)),
            'secret' => 'nullable|string|min:16|max:64',
            'retry_limit' => 'nullable|integer|min:0|max:10',
            'timeout' => 'nullable|integer|min:5|max:120',
            'is_active' => 'nullable|boolean',
        ]);

        $webhook = Webhook::create(array_merge($validated, [
            'user_id' => $request->user()->id,
        ]));

        // Log the action
        AuditLog::logAction(
            'webhook.created',
            $request->user()->id,
            null,
            Webhook::class,
            $webhook->id,
            null,
            ['name' => $webhook->name, 'url' => $webhook->url]
        );

        return response()->json([
            'message' => 'Webhook créé avec succès',
            'data' => $webhook
        ], 201);
    }

    /**
     * Get specific webhook
     *
     * @OA\Get(
     *     path="/api/webhooks/{id}",
     *     tags={"Webhooks"},
     *     summary="Get a specific webhook with recent logs",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Webhook not found")
     * )
     */
    public function show(Request $request, int $id)
    {
        $webhook = Webhook::byUser($request->user()->id)
            ->with(['logs' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }])
            ->findOrFail($id);

        return response()->json(['data' => $webhook]);
    }

    /**
     * Update webhook
     *
     * @OA\Put(
     *     path="/api/webhooks/{id}",
     *     tags={"Webhooks"},
     *     summary="Update an existing webhook",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string", maxLength=255),
     *         @OA\Property(property="url", type="string", format="url", maxLength=500),
     *         @OA\Property(property="events", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="secret", type="string", minLength=16, maxLength=64),
     *         @OA\Property(property="retry_limit", type="integer", minimum=0, maximum=10),
     *         @OA\Property(property="timeout", type="integer", minimum=5, maximum=120),
     *         @OA\Property(property="is_active", type="boolean")
     *     )),
     *     @OA\Response(response=200, description="Webhook updated", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Webhook not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, int $id)
    {
        $webhook = Webhook::byUser($request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url|max:500',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'required|string|in:' . implode(',', array_keys(Webhook::EVENTS)),
            'secret' => 'nullable|string|min:16|max:64',
            'retry_limit' => 'sometimes|integer|min:0|max:10',
            'timeout' => 'sometimes|integer|min:5|max:120',
            'is_active' => 'sometimes|boolean',
        ]);

        $oldValues = $webhook->only(['name', 'url', 'events', 'is_active']);
        $webhook->update($validated);

        // Log the action
        AuditLog::logAction(
            'webhook.updated',
            $request->user()->id,
            null,
            Webhook::class,
            $webhook->id,
            $oldValues,
            $webhook->only(['name', 'url', 'events', 'is_active'])
        );

        return response()->json([
            'message' => 'Webhook mis à jour',
            'data' => $webhook
        ]);
    }

    /**
     * Delete webhook
     *
     * @OA\Delete(
     *     path="/api/webhooks/{id}",
     *     tags={"Webhooks"},
     *     summary="Delete a webhook",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Webhook deleted", @OA\JsonContent(
     *         @OA\Property(property="message", type="string")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Webhook not found")
     * )
     */
    public function destroy(Request $request, int $id)
    {
        $webhook = Webhook::byUser($request->user()->id)->findOrFail($id);

        $webhookData = $webhook->only(['name', 'url']);
        $webhook->delete();

        // Log the action
        AuditLog::logAction(
            'webhook.deleted',
            $request->user()->id,
            null,
            Webhook::class,
            $id,
            $webhookData,
            null
        );

        return response()->json([
            'message' => 'Webhook supprimé'
        ]);
    }

    /**
     * Test webhook delivery
     *
     * @OA\Post(
     *     path="/api/webhooks/{id}/test",
     *     tags={"Webhooks"},
     *     summary="Send a test payload to the webhook URL",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Test successful", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="result", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Webhook not found"),
     *     @OA\Response(response=422, description="Test failed")
     * )
     */
    public function test(Request $request, int $id)
    {
        $webhook = Webhook::byUser($request->user()->id)->findOrFail($id);

        $result = $this->webhookService->test($webhook);

        return response()->json([
            'message' => $result['success'] ? 'Test réussi' : 'Test échoué',
            'result' => $result
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Toggle webhook active status
     *
     * @OA\Post(
     *     path="/api/webhooks/{id}/toggle",
     *     tags={"Webhooks"},
     *     summary="Toggle webhook active/inactive status",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Status toggled", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Webhook not found")
     * )
     */
    public function toggle(Request $request, int $id)
    {
        $webhook = Webhook::byUser($request->user()->id)->findOrFail($id);

        $webhook->is_active = !$webhook->is_active;
        $webhook->save();

        // Log the action
        AuditLog::logAction(
            'webhook.toggled',
            $request->user()->id,
            null,
            Webhook::class,
            $webhook->id,
            ['is_active' => !$webhook->is_active],
            ['is_active' => $webhook->is_active]
        );

        return response()->json([
            'message' => $webhook->is_active ? 'Webhook activé' : 'Webhook désactivé',
            'data' => $webhook
        ]);
    }

    /**
     * Get webhook logs
     *
     * @OA\Get(
     *     path="/api/webhooks/{id}/logs",
     *     tags={"Webhooks"},
     *     summary="Get delivery logs for a specific webhook",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="event", in="query", required=false, @OA\Schema(type="string"), description="Filter by event type"),
     *     @OA\Parameter(name="success", in="query", required=false, @OA\Schema(type="boolean"), description="Filter by success status"),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *         @OA\Property(property="meta", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Webhook not found")
     * )
     */
    public function logs(Request $request, int $id)
    {
        $webhook = Webhook::byUser($request->user()->id)->findOrFail($id);

        $logs = WebhookLog::where('webhook_id', $webhook->id)
            ->when($request->has('event'), function ($query) use ($request) {
                $query->forEvent($request->event);
            })
            ->when($request->has('success'), function ($query) use ($request) {
                $success = filter_var($request->success, FILTER_VALIDATE_BOOLEAN);
                $query->where('success', $success);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($logs);
    }

    /**
     * Get webhook statistics
     *
     * @OA\Get(
     *     path="/api/webhooks/{id}/stats",
     *     tags={"Webhooks"},
     *     summary="Get delivery statistics for a specific webhook",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="total_triggers", type="integer"),
     *             @OA\Property(property="successful", type="integer"),
     *             @OA\Property(property="failed", type="integer"),
     *             @OA\Property(property="success_rate", type="number"),
     *             @OA\Property(property="last_triggered_at", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="events_by_type", type="array", @OA\Items(type="object"))
     *         )
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Webhook not found")
     * )
     */
    public function stats(Request $request, int $id)
    {
        $webhook = Webhook::byUser($request->user()->id)->findOrFail($id);

        $stats = [
            'total_triggers' => $webhook->logs()->count(),
            'successful' => $webhook->logs()->successful()->count(),
            'failed' => $webhook->logs()->failed()->count(),
            'success_rate' => $webhook->success_count > 0
                ? round(($webhook->success_count / ($webhook->success_count + $webhook->failure_count)) * 100, 2)
                : 0,
            'last_triggered_at' => $webhook->last_triggered_at,
            'events_by_type' => $webhook->logs()
                ->selectRaw('event, count(*) as count')
                ->groupBy('event')
                ->get(),
        ];

        return response()->json(['data' => $stats]);
    }
}
