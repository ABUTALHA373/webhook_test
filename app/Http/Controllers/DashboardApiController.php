<?php

namespace App\Http\Controllers;

use App\Jobs\ExecuteCallbackJob;
use App\Models\CallbackLog;
use App\Models\CallbackRule;
use App\Models\Endpoint;
use App\Models\OutboundDispatchLog;
use App\Models\WebhookRequest;
use App\Services\WebhookDispatcherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardApiController extends Controller
{
    public function __construct(
        protected WebhookDispatcherService $dispatcherService
    ) {}

    /**
     * List all endpoints.
     */
    public function getEndpoints(): JsonResponse
    {
        $endpoints = Endpoint::withCount('webhookRequests')
            ->with('callbackRules')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($endpoints);
    }

    /**
     * Create an endpoint.
     */
    public function createEndpoint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100|alpha_dash|unique:endpoints,slug',
            'secret_token' => 'nullable|string|max:255',
            'response_status' => 'nullable|integer|between:100,599',
            'response_delay_ms' => 'nullable|integer|min:0|max:30000',
            'response_headers' => 'nullable|array',
            'response_body' => 'nullable|string',
            'conditional_rules' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']).'-'.Str::lower(Str::random(6));
        }

        $endpoint = Endpoint::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'secret_token' => $validated['secret_token'] ?? null,
            'response_status' => $validated['response_status'] ?? 200,
            'response_delay_ms' => $validated['response_delay_ms'] ?? 0,
            'response_headers' => $validated['response_headers'] ?? ['Content-Type' => 'application/json'],
            'response_body' => $validated['response_body'] ?? "{\n  \"status\": \"ok\",\n  \"request_id\": \"{{req.id}}\"\n}",
            'conditional_rules' => $validated['conditional_rules'] ?? [],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json($endpoint, 201);
    }

    /**
     * Update an endpoint.
     */
    public function updateEndpoint(Request $request, int $id): JsonResponse
    {
        $endpoint = Endpoint::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:100|alpha_dash|unique:endpoints,slug,'.$id,
            'secret_token' => 'nullable|string|max:255',
            'response_status' => 'sometimes|integer|between:100,599',
            'response_delay_ms' => 'sometimes|integer|min:0|max:30000',
            'response_headers' => 'nullable|array',
            'response_body' => 'nullable|string',
            'conditional_rules' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $endpoint->update($validated);

        return response()->json($endpoint);
    }

    /**
     * Delete an endpoint.
     */
    public function deleteEndpoint(int $id): JsonResponse
    {
        $endpoint = Endpoint::findOrFail($id);
        $endpoint->delete();

        return response()->json(['message' => 'Endpoint deleted successfully']);
    }

    /**
     * List captured webhook requests with pagination and filters.
     */
    public function getRequests(Request $request): JsonResponse
    {
        $query = WebhookRequest::with('endpoint:id,name,slug')
            ->withCount('callbackLogs')
            ->orderBy('created_at', 'desc');

        if ($request->filled('endpoint_id')) {
            $query->where('endpoint_id', $request->integer('endpoint_id'));
        }

        if ($request->filled('method')) {
            $query->where('method', strtoupper($request->string('method')));
        }

        if ($request->filled('status')) {
            $query->where('response_status', $request->integer('status'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', $search)
                    ->orWhere('path', 'like', $search)
                    ->orWhere('id', 'like', $search)
                    ->orWhere('raw_body', 'like', $search);
            });
        }

        $perPage = min($request->integer('per_page', 50), 100);
        $requests = $query->paginate($perPage);

        return response()->json($requests);
    }

    /**
     * Get single request with its full callback logs.
     */
    public function getRequest(string $id): JsonResponse
    {
        $webhookRequest = WebhookRequest::with(['endpoint', 'callbackLogs.callbackRule'])
            ->findOrFail($id);

        return response()->json($webhookRequest);
    }

    /**
     * Delete single webhook request.
     */
    public function deleteRequest(string $id): JsonResponse
    {
        $webhookRequest = WebhookRequest::findOrFail($id);
        $webhookRequest->delete();

        return response()->json(['message' => 'Request deleted']);
    }

    /**
     * Clear requests.
     */
    public function clearRequests(Request $request): JsonResponse
    {
        $query = WebhookRequest::query();

        if ($request->filled('endpoint_id')) {
            $query->where('endpoint_id', $request->integer('endpoint_id'));
        }

        $count = $query->delete();

        return response()->json(['message' => "Deleted {$count} requests"]);
    }

    /**
     * Replay a captured webhook request.
     */
    public function replayRequest(Request $request, string $id): JsonResponse
    {
        $webhookRequest = WebhookRequest::findOrFail($id);

        $targetUrl = $request->input('target_url') ?: $webhookRequest->url;
        $method = strtoupper($request->input('method') ?: $webhookRequest->method);
        $headers = $request->input('headers') ?: ($webhookRequest->headers ?? []);
        $body = $request->input('body') !== null ? $request->input('body') : ($webhookRequest->raw_body ?? '');

        // Remove host header to avoid connection issues
        unset($headers['host'], $headers['Host'], $headers['content-length'], $headers['Content-Length']);

        $headers['X-Replayed-From'] = $webhookRequest->id;

        $startTime = microtime(true);

        try {
            $client = Http::timeout(15)->withHeaders($headers);
            $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? 'application/json';

            $response = match ($method) {
                'GET' => $client->get($targetUrl),
                'PUT' => $client->withBody($body, $contentType)->put($targetUrl),
                'PATCH' => $client->withBody($body, $contentType)->patch($targetUrl),
                'DELETE' => $client->withBody($body, $contentType)->delete($targetUrl),
                default => $client->withBody($body, $contentType)->post($targetUrl),
            };

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success' => true,
                'target_url' => $targetUrl,
                'method' => $method,
                'status_code' => $response->status(),
                'response_headers' => $response->headers(),
                'response_body' => $response->body(),
                'duration_ms' => $durationMs,
            ]);
        } catch (\Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ], 500);
        }
    }

    /**
     * Callback Rules CRUD.
     */
    public function getCallbackRules(Request $request): JsonResponse
    {
        $query = CallbackRule::with('endpoint:id,name,slug');

        if ($request->filled('endpoint_id')) {
            $query->where('endpoint_id', $request->integer('endpoint_id'));
        }

        return response()->json($query->get());
    }

    public function createCallbackRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint_id' => 'required|exists:endpoints,id',
            'name' => 'required|string|max:255',
            'target_url' => 'required|string',
            'http_method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE',
            'delay_seconds' => 'nullable|integer|min:0|max:300',
            'payload_mode' => 'nullable|string|in:passthrough,template,empty',
            'payload_template' => 'nullable|string',
            'custom_headers' => 'nullable|array',
            'hmac_enabled' => 'nullable|boolean',
            'hmac_secret' => 'nullable|string',
            'hmac_algorithm' => 'nullable|string|in:sha256,sha1,sha512,md5',
            'hmac_header_name' => 'nullable|string',
            'hmac_format' => 'nullable|string|in:hex,base64,prefix_hex,stripe',
            'max_retries' => 'nullable|integer|min:0|max:10',
            'retry_backoff_seconds' => 'nullable|integer|min:1|max:60',
            'is_active' => 'nullable|boolean',
        ]);

        $rule = CallbackRule::create($validated);

        return response()->json($rule, 201);
    }

    public function updateCallbackRule(Request $request, int $id): JsonResponse
    {
        $rule = CallbackRule::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'target_url' => 'sometimes|string',
            'http_method' => 'sometimes|string|in:GET,POST,PUT,PATCH,DELETE',
            'delay_seconds' => 'sometimes|integer|min:0|max:300',
            'payload_mode' => 'sometimes|string|in:passthrough,template,empty',
            'payload_template' => 'nullable|string',
            'custom_headers' => 'nullable|array',
            'hmac_enabled' => 'sometimes|boolean',
            'hmac_secret' => 'nullable|string',
            'hmac_algorithm' => 'sometimes|string|in:sha256,sha1,sha512,md5',
            'hmac_header_name' => 'sometimes|string',
            'hmac_format' => 'sometimes|string|in:hex,base64,prefix_hex,stripe',
            'max_retries' => 'sometimes|integer|min:0|max:10',
            'retry_backoff_seconds' => 'sometimes|integer|min:1|max:60',
            'is_active' => 'sometimes|boolean',
        ]);

        $rule->update($validated);

        return response()->json($rule);
    }

    public function deleteCallbackRule(int $id): JsonResponse
    {
        $rule = CallbackRule::findOrFail($id);
        $rule->delete();

        return response()->json(['message' => 'Callback rule deleted']);
    }

    /**
     * Test trigger a callback rule with dummy or mock data.
     */
    public function testCallbackRule(Request $request, int $id): JsonResponse
    {
        $rule = CallbackRule::findOrFail($id);

        // Create a temporary mock WebhookRequest or use recent one
        $mockRequest = WebhookRequest::where('endpoint_id', $rule->endpoint_id)->latest()->first();

        if (! $mockRequest) {
            $mockRequest = new WebhookRequest([
                'id' => (string) Str::uuid(),
                'endpoint_id' => $rule->endpoint_id,
                'method' => 'POST',
                'url' => url("/hook/{$rule->endpoint->slug}"),
                'path' => "/hook/{$rule->endpoint->slug}",
                'ip_address' => '127.0.0.1',
                'headers' => ['Content-Type' => 'application/json', 'User-Agent' => 'HookForge-Mock'],
                'query_params' => ['test' => 'true'],
                'raw_body' => json_encode(['event' => 'test.simulation', 'id' => 'evt_test_123', 'callback_url' => 'https://httpbin.org/post']),
                'parsed_body' => ['event' => 'test.simulation', 'id' => 'evt_test_123', 'callback_url' => 'https://httpbin.org/post'],
                'response_status' => 200,
            ]);
        }

        $logId = (string) Str::uuid();
        $job = new ExecuteCallbackJob($mockRequest, $rule, 1, $logId);
        app()->call([$job, 'handle']);

        $log = CallbackLog::find($logId);

        return response()->json([
            'message' => 'Callback executed',
            'log' => $log,
        ]);
    }

    /**
     * Dispatch an outbound webhook (manual tester).
     */
    public function dispatchOutbound(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_url' => 'required|url',
            'method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE',
            'payload' => 'nullable|string',
            'headers' => 'nullable|array',
            'hmac_enabled' => 'nullable|boolean',
            'hmac_secret' => 'nullable|string',
            'hmac_header' => 'nullable|string',
            'hmac_algorithm' => 'nullable|string|in:sha256,sha1,sha512,md5',
            'hmac_format' => 'nullable|string|in:hex,base64,prefix_hex,stripe',
        ]);

        $log = $this->dispatcherService->dispatch($validated);

        return response()->json($log);
    }

    /**
     * Get preset sample payloads for dispatcher.
     */
    public function getDispatcherPresets(): JsonResponse
    {
        return response()->json($this->dispatcherService->getPresets());
    }

    /**
     * Get outbound dispatch history.
     */
    public function getDispatchHistory(): JsonResponse
    {
        $history = OutboundDispatchLog::orderBy('created_at', 'desc')->take(50)->get();

        return response()->json($history);
    }

    /**
     * Server-Sent Events (SSE) stream for live updates.
     */
    public function stream(Request $request): StreamedResponse
    {
        return response()->stream(function () {
            // Disable output buffering
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            echo ": connected\n\n";
            flush();

            $lastCheck = now()->subSeconds(2)->timestamp;

            // Stream for 25 seconds before connection re-establishes (standard SSE pattern)
            $startTime = time();
            while (time() - $startTime < 25) {
                if (connection_aborted()) {
                    break;
                }

                $events = Cache::get('hookforge_recent_events', []);
                $newEvents = array_filter($events, fn ($e) => ($e['timestamp'] ?? 0) >= $lastCheck);

                if (! empty($newEvents)) {
                    foreach ($newEvents as $event) {
                        echo "event: {$event['type']}\n";
                        echo 'data: '.json_encode($event)."\n\n";
                    }
                    $lastCheck = now()->timestamp;
                    flush();
                } else {
                    // Send heartbeat ping to keep connection alive
                    echo ": ping\n\n";
                    flush();
                }

                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
