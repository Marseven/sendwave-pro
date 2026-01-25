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
     * Expected payload format (generic):
     * {
     *   "from": "24177123456",        // Sender phone number
     *   "to": "SENDWAVE",             // Recipient (usually sender ID)
     *   "content": "STOP",            // Message content
     *   "user_id": 1,                 // Optional: User ID if known
     *   "api_key": "xxx",             // Optional: API key for auth
     *   "timestamp": "2026-01-25T12:00:00Z"
     * }
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
     */
    public function getSupportedCountries()
    {
        return response()->json([
            'success' => true,
            'data' => $this->phoneNormalizationService->getSupportedCountries(),
        ]);
    }
}
