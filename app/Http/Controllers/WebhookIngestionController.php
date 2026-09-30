<?php

namespace App\Http\Controllers;

use App\Jobs\ExecuteCallbackJob;
use App\Models\Endpoint;
use App\Models\WebhookRequest;
use App\Services\DynamicTemplateEngine;
use App\Services\HmacSignatureService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WebhookIngestionController extends Controller
{
    public function __construct(
        protected DynamicTemplateEngine $templateEngine,
        protected HmacSignatureService $hmacService
    ) {}

    /**
     * Ingest any incoming webhook request.
     */
    public function ingest(Request $request, string $slug, ?string $any = null): Response
    {
        $startTime = microtime(true);

        $endpoint = Endpoint::where('slug', $slug)->first();

        if (! $endpoint) {
            return response([
                'error' => 'Endpoint not found',
                'slug' => $slug,
                'message' => 'Please create this endpoint in the HookForge dashboard before sending requests.',
            ], 404, ['Content-Type' => 'application/json']);
        }

        if (! $endpoint->is_active) {
            return response([
                'error' => 'Endpoint inactive',
                'slug' => $slug,
                'message' => 'This webhook endpoint is currently disabled.',
            ], 403, ['Content-Type' => 'application/json']);
        }

        // Secret token verification if configured
        if ($endpoint->secret_token) {
            $providedToken = $request->header('X-Webhook-Token')
                ?? $request->header('X-Secret-Token')
                ?? $request->bearerToken()
                ?? $request->query('token');

            if (! $providedToken || ! hash_equals($endpoint->secret_token, $providedToken)) {
                return response([
                    'error' => 'Unauthorized',
                    'message' => 'Invalid or missing endpoint secret token.',
                ], 401, ['Content-Type' => 'application/json']);
            }
        }

        // Extract and sanitize request details
        $rawBody = $request->getContent();
        $parsedBody = null;

        if ($request->isJson()) {
            $parsedBody = $request->json()->all();
        } elseif ($request->is('application/x-www-form-urlencoded') || ! empty($request->post())) {
            $parsedBody = $request->post();
        } else {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $parsedBody = $decoded;
            }
        }

        $allHeaders = [];
        foreach ($request->headers->all() as $name => $values) {
            $allHeaders[$name] = implode(', ', $values);
        }

        $queryParams = $request->query();
        $requestId = (string) Str::uuid();

        // Build context for dynamic rules
        $context = [
            'body' => $parsedBody ?? [],
            'headers' => $allHeaders,
            'query' => $queryParams,
            'req' => [
                'id' => $requestId,
                'ip' => $request->ip(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
            ],
        ];

        // 1. Evaluate Conditional Rules (if any match)
        $matchedOverride = $this->templateEngine->evaluateRules($endpoint->conditional_rules, $context);

        $responseStatus = $matchedOverride['status'] ?? (int) ($endpoint->response_status ?: 200);
        $responseBodyTemplate = $matchedOverride['body'] ?? $endpoint->response_body;
        $responseHeaders = $matchedOverride['headers'] ?? ($endpoint->response_headers ?? []);

        // 2. Render Dynamic Response Body & Headers
        $finalResponseBody = $this->templateEngine->render($responseBodyTemplate, $context);

        $finalHeaders = [];
        if (is_array($responseHeaders)) {
            foreach ($responseHeaders as $k => $v) {
                $finalHeaders[$k] = $this->templateEngine->render((string) $v, $context);
            }
        }

        if (! isset($finalHeaders['Content-Type']) && ! isset($finalHeaders['content-type'])) {
            $finalHeaders['Content-Type'] = 'application/json';
        }

        $finalHeaders['X-HookForge-Request-ID'] = $requestId;

        // 3. Simulate Artificial Latency if configured
        if ($endpoint->response_delay_ms > 0) {
            $delayMicroseconds = min($endpoint->response_delay_ms, 30000) * 1000;
            usleep($delayMicroseconds);
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        // 4. Save WebhookRequest
        $webhookRequest = WebhookRequest::create([
            'id' => $requestId,
            'endpoint_id' => $endpoint->id,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => '/'.ltrim($request->path(), '/'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $allHeaders,
            'query_params' => ! empty($queryParams) ? $queryParams : null,
            'raw_body' => $rawBody ?: null,
            'parsed_body' => $parsedBody,
            'content_type' => $request->header('Content-Type'),
            'content_length' => strlen($rawBody),
            'response_status' => $responseStatus,
            'response_headers' => $finalHeaders,
            'response_body' => $finalResponseBody,
            'duration_ms' => $durationMs,
        ]);

        // Push event into cache for SSE stream subscribers
        $this->broadcastNewRequest($webhookRequest);

        // 5. Trigger Active Callback Rules
        $activeRules = $endpoint->callbackRules()->where('is_active', true)->get();
        foreach ($activeRules as $rule) {
            $delay = (int) ($rule->delay_seconds ?? 0);
            if ($delay > 0) {
                ExecuteCallbackJob::dispatch($webhookRequest, $rule, 1)->delay(now()->addSeconds($delay));
            } else {
                // Execute immediately (handles sync execution or queue)
                $job = new ExecuteCallbackJob($webhookRequest, $rule, 1);
                app()->call([$job, 'handle']);
            }
        }

        return response($finalResponseBody, $responseStatus, $finalHeaders);
    }

    /**
     * Broadcast new request timestamp into cache for SSE subscribers.
     */
    protected function broadcastNewRequest(WebhookRequest $webhookRequest): void
    {
        $queue = Cache::get('hookforge_recent_events', []);
        $queue[] = [
            'type' => 'request.created',
            'id' => $webhookRequest->id,
            'endpoint_id' => $webhookRequest->endpoint_id,
            'method' => $webhookRequest->method,
            'status' => $webhookRequest->response_status,
            'timestamp' => now()->timestamp,
        ];

        // Keep last 50 events
        if (count($queue) > 50) {
            $queue = array_slice($queue, -50);
        }

        Cache::put('hookforge_recent_events', $queue, now()->addMinutes(10));
    }
}
