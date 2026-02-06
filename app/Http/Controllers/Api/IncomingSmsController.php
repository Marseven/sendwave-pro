<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StopWordService;
use App\Services\PhoneNormalizationService;
use App\Services\WebhookService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IncomingSmsController extends Controller
{
    public function __construct(
        protected StopWordService $stopWordService,
        protected PhoneNormalizationService $phoneNormalizationService,
        protected WebhookService $webhookService
    ) {}

    /**
     * Handle incoming SMS from gateway webhook
     * This endpoint is called by SMS providers when they receive a reply SMS
     *
     * @OA\Post(
     *     path="/api/webhooks/incoming/sms",
     *     tags={"Incoming SMS"},
     *     summary="Handle incoming SMS from gateway webhook (generic format)",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"from", "content"},
     *         @OA\Property(property="from", type="string", example="24177123456", description="Sender phone number"),
     *         @OA\Property(property="to", type="string", example="SENDWAVE", description="Recipient (usually sender ID)"),
     *         @OA\Property(property="content", type="string", example="STOP", description="Message content"),
     *         @OA\Property(property="user_id", type="integer", example=1, description="Optional user ID"),
     *         @OA\Property(property="api_key", type="string", description="Optional API key for auth"),
     *         @OA\Property(property="timestamp", type="string", example="2026-01-25T12:00:00Z")
     *     )),
     *     @OA\Response(response=200, description="SMS processed successfully", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="is_stop", type="boolean"),
     *         @OA\Property(property="action", type="string")
     *     )),
     *     @OA\Response(response=400, description="Could not determine user")
     * )
     */
    public function handleIncoming(Request $request)
    {
        $validated = $request->validate([
            'from' => 'required|string',
            'to' => 'nullable|string',
            'content' => 'required|string',
            'user_id' => 'nullable|integer',
            'api_key' => 'nullable|string',
            'timestamp' => 'nullable|string',
        ]);

        Log::info('Incoming SMS received', [
            'from' => $validated['from'],
            'content' => $validated['content'],
        ]);

        // Normalize the phone number
        $normalized = $this->phoneNormalizationService->normalize($validated['from']);
        $phoneNumber = $normalized['normalized'];

        // Determine user_id from API key or request
        $userId = $this->resolveUserId($validated);

        if (!$userId) {
            Log::warning('Incoming SMS - Could not resolve user_id', [
                'from' => $phoneNumber,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not determine user for this incoming message',
            ], 400);
        }

        // Check if this is a STOP message
        if ($this->stopWordService->containsStopKeyword($validated['content'])) {
            $result = $this->stopWordService->processStopRequest(
                $userId,
                $phoneNumber,
                $validated['content']
            );

            // Trigger webhook for contact.unsubscribed event
            if ($result['success'] && !$result['already_blacklisted']) {
                $this->webhookService->trigger('contact.unsubscribed', $userId, [
                    'phone' => $phoneNumber,
                    'keyword' => $this->stopWordService->getStopKeyword($validated['content']),
                    'source' => 'sms_reply',
                    'timestamp' => now()->toISOString(),
                ]);
            }

            return response()->json([
                'success' => true,
                'is_stop' => true,
                'action' => 'blacklisted',
                'data' => $result,
            ]);
        }

        // Not a STOP message - could be processed for other purposes
        // Trigger webhook for message.received event
        $this->webhookService->trigger('message.received', $userId, [
            'from' => $phoneNumber,
            'content' => $validated['content'],
            'is_stop' => false,
            'timestamp' => $validated['timestamp'] ?? now()->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'is_stop' => false,
            'action' => 'none',
            'message' => 'Message received but not a STOP request',
        ]);
    }

    /**
     * Handle incoming SMS webhook from Airtel
     *
     * @OA\Post(
     *     path="/api/webhooks/incoming/airtel",
     *     tags={"Incoming SMS"},
     *     summary="Handle incoming SMS webhook from Airtel (Airtel-specific format)",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="msisdn", type="string", description="Sender phone number (Airtel format)"),
     *         @OA\Property(property="from", type="string", description="Alternative sender field"),
     *         @OA\Property(property="to", type="string"),
     *         @OA\Property(property="short_code", type="string"),
     *         @OA\Property(property="message", type="string", description="Message content"),
     *         @OA\Property(property="text", type="string", description="Alternative message field"),
     *         @OA\Property(property="content", type="string", description="Alternative message field"),
     *         @OA\Property(property="user_id", type="integer"),
     *         @OA\Property(property="timestamp", type="string")
     *     )),
     *     @OA\Response(response=200, description="SMS processed successfully", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="is_stop", type="boolean"),
     *         @OA\Property(property="action", type="string")
     *     )),
     *     @OA\Response(response=400, description="Could not determine user")
     * )
     */
    public function handleAirtelWebhook(Request $request)
    {
        Log::info('Airtel incoming SMS webhook', $request->all());

        // Transform Airtel format to generic format
        $transformed = [
            'from' => $request->input('msisdn') ?? $request->input('from'),
            'to' => $request->input('to') ?? $request->input('short_code'),
            'content' => $request->input('message') ?? $request->input('text') ?? $request->input('content'),
            'user_id' => $request->input('user_id'),
            'timestamp' => $request->input('timestamp') ?? now()->toISOString(),
        ];

        $request->merge($transformed);

        return $this->handleIncoming($request);
    }

    /**
     * Handle incoming SMS webhook from Moov
     *
     * @OA\Post(
     *     path="/api/webhooks/incoming/moov",
     *     tags={"Incoming SMS"},
     *     summary="Handle incoming SMS webhook from Moov (Moov-specific format)",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="sender", type="string", description="Sender phone number (Moov format)"),
     *         @OA\Property(property="from", type="string", description="Alternative sender field"),
     *         @OA\Property(property="receiver", type="string"),
     *         @OA\Property(property="to", type="string"),
     *         @OA\Property(property="message", type="string", description="Message content"),
     *         @OA\Property(property="body", type="string", description="Alternative message field"),
     *         @OA\Property(property="content", type="string", description="Alternative message field"),
     *         @OA\Property(property="user_id", type="integer"),
     *         @OA\Property(property="timestamp", type="string")
     *     )),
     *     @OA\Response(response=200, description="SMS processed successfully", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="is_stop", type="boolean"),
     *         @OA\Property(property="action", type="string")
     *     )),
     *     @OA\Response(response=400, description="Could not determine user")
     * )
     */
    public function handleMoovWebhook(Request $request)
    {
        Log::info('Moov incoming SMS webhook', $request->all());

        // Transform Moov format to generic format
        $transformed = [
            'from' => $request->input('sender') ?? $request->input('from'),
            'to' => $request->input('receiver') ?? $request->input('to'),
            'content' => $request->input('message') ?? $request->input('body') ?? $request->input('content'),
            'user_id' => $request->input('user_id'),
            'timestamp' => $request->input('timestamp') ?? now()->toISOString(),
        ];

        $request->merge($transformed);

        return $this->handleIncoming($request);
    }

    /**
     * Resolve user_id from request
     */
    protected function resolveUserId(array $data): ?int
    {
        // Direct user_id
        if (!empty($data['user_id'])) {
            return (int) $data['user_id'];
        }

        // From API key
        if (!empty($data['api_key'])) {
            $apiKey = \App\Models\ApiKey::where('key', $data['api_key'])
                ->where('is_active', true)
                ->first();

            if ($apiKey) {
                return $apiKey->user_id;
            }
        }

        // Default to first admin user if only one user exists (for simple setups)
        $userCount = User::count();
        if ($userCount === 1) {
            return User::first()->id;
        }

        return null;
    }

    /**
     * Get blacklist stats for current user
     *
     * @OA\Get(
     *     path="/api/blacklist/stats",
     *     tags={"Blacklist"},
     *     summary="Get blacklist statistics for the current user",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Response(response=200, description="Blacklist statistics", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function blacklistStats(Request $request)
    {
        $userId = $request->user()->id;
        $stats = $this->stopWordService->getBlacklistStats($userId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get list of STOP keywords
     *
     * @OA\Get(
     *     path="/api/blacklist/stop-keywords",
     *     tags={"Blacklist"},
     *     summary="Get list of recognized STOP keywords",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Response(response=200, description="List of STOP keywords", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getStopKeywords()
    {
        return response()->json([
            'success' => true,
            'data' => $this->stopWordService->getStopKeywords(),
        ]);
    }

    /**
     * Normalize a phone number
     *
     * @OA\Post(
     *     path="/api/phone/normalize",
     *     tags={"Phone"},
     *     summary="Normalize a single phone number to E.164 format",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"phone"},
     *         @OA\Property(property="phone", type="string", example="77123456"),
     *         @OA\Property(property="country", type="string", example="GA", description="ISO 3166-1 alpha-2 country code")
     *     )),
     *     @OA\Response(response=200, description="Normalized phone number", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function normalizePhone(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'country' => 'nullable|string|size:2',
        ]);

        $result = $this->phoneNormalizationService->normalize(
            $validated['phone'],
            $validated['country'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Normalize multiple phone numbers
     *
     * @OA\Post(
     *     path="/api/phone/normalize-many",
     *     tags={"Phone"},
     *     summary="Normalize multiple phone numbers to E.164 format",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"phones"},
     *         @OA\Property(property="phones", type="array", @OA\Items(type="string"), example={"77123456", "60123456"}),
     *         @OA\Property(property="country", type="string", example="GA", description="ISO 3166-1 alpha-2 country code")
     *     )),
     *     @OA\Response(response=200, description="Normalized phone numbers", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function normalizePhones(Request $request)
    {
        $validated = $request->validate([
            'phones' => 'required|array|min:1',
            'phones.*' => 'required|string',
            'country' => 'nullable|string|size:2',
        ]);

        $result = $this->phoneNormalizationService->normalizeMany(
            $validated['phones'],
            $validated['country'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get supported countries for phone normalization
     *
     * @OA\Get(
     *     path="/api/phone/countries",
     *     tags={"Phone"},
     *     summary="Get list of supported countries for phone normalization",
     *     @OA\Response(response=200, description="List of supported countries", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *     ))
     * )
     */
    public function getSupportedCountries()
    {
        return response()->json([
            'success' => true,
            'data' => $this->phoneNormalizationService->getSupportedCountries(),
        ]);
    }
}
