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
            return response(json_encode([
                'error' => 'Endpoint not found',
                'slug' => $slug,
                'message' => 'Please create this endpoint in the HookForge dashboard before sending requests.',
            ]), 404, ['Content-Type' => 'application/json']);
        }

        if (! $endpoint->is_active) {
            return response(json_encode([
                'error' => 'Endpoint inactive',
                'slug' => $slug,
                'message' => 'This webhook endpoint is currently disabled.',
            ]), 403, ['Content-Type' => 'application/json']);
        }

        // Secret token verification if configured
        if ($endpoint->secret_token) {
            $providedToken = $request->header('X-Webhook-Token')
                ?? $request->header('X-Secret-Token')
                ?? $request->bearerToken()
                ?? $request->query('token');

            if (! $providedToken || ! hash_equals($endpoint->secret_token, $providedToken)) {
                return response(json_encode([
                    'error' => 'Unauthorized',
                    'message' => 'Invalid or missing endpoint secret token.',
                ]), 401, ['Content-Type' => 'application/json']);
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

        $paramsMap = array_merge($queryParams, is_array($parsedBody) ? $parsedBody : []);

        // Build context for dynamic rules
        $context = [
            'body' => $parsedBody ?? [],
            'headers' => $allHeaders,
            'query' => $queryParams,
            'param' => $paramsMap,
            'params' => $paramsMap,
            'req' => [
                'id' => $requestId,
                'ip' => $request->ip(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
            ],
        ];

        // ── Required Parameter Validation ─────────────────────────────────────
        $requiredParams = $endpoint->required_params ?? [];
        if (! empty($requiredParams)) {
            $validationErrors = $this->validateRequiredParams($requiredParams, $context);

            if (! empty($validationErrors)) {
                $durationMs = round((microtime(true) - $startTime) * 1000, 2);
                $errBody = json_encode([
                    'error' => 'Validation Failed',
                    'message' => 'One or more required parameters are missing or invalid.',
                    'errors' => $validationErrors,
                ]);

                // Still record the failed request so it appears in the inspector
                WebhookRequest::create([
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
                    'response_status' => 422,
                    'response_headers' => ['Content-Type' => 'application/json'],
                    'response_body' => $errBody,
                    'duration_ms' => $durationMs,
                ]);

                $this->broadcastNewRequest(
                    WebhookRequest::find($requestId) ?? new WebhookRequest([
                        'id' => $requestId,
                        'endpoint_id' => $endpoint->id,
                        'method' => $request->method(),
                        'response_status' => 422,
                    ])
                );

                return response($errBody, 422, [
                    'Content-Type' => 'application/json',
                    'X-HookForge-Request-ID' => $requestId,
                ]);
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        // 1. Evaluate Conditional Rules & Dynamic Status Code
        $matchedOverride = $this->templateEngine->evaluateRules($endpoint->conditional_rules, $context);
        $responseStatus = $this->templateEngine->resolveStatusCode($endpoint->response_status, $context, $endpoint->conditional_rules);
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
                $job = new ExecuteCallbackJob($webhookRequest, $rule, 1);
                app()->call([$job, 'handle']);
            }
        }

        return response($finalResponseBody, $responseStatus, $finalHeaders);
    }

    /**
     * Validate required params against the incoming request context.
     *
     * @param  array<int, array{name: string, source: string, type?: string, error_message?: string}>  $requiredParams
     * @param  array<string, mixed>  $context
     * @return array<string, string> Keyed by param name → error message
     */
    protected function validateRequiredParams(array $requiredParams, array $context): array
    {
        $errors = [];

        foreach ($requiredParams as $param) {
            $name = trim($param['name'] ?? '');
            $source = $param['source'] ?? 'query'; // query | body | header
            $type = $param['type'] ?? 'string';    // string | number | email | url | boolean
            $customMsg = $param['error_message'] ?? null;

            if (empty($name)) {
                continue;
            }

            // Resolve value from the correct source
            $value = match ($source) {
                'query' => data_get($context['query'], $name),
                'body' => data_get($context['body'], $name),
                'header' => $context['headers'][$name] ?? $context['headers'][strtolower($name)] ?? null,
                'any' => data_get($context['query'], $name) ?? data_get($context['body'], $name) ?? ($context['headers'][$name] ?? $context['headers'][strtolower($name)] ?? null),
                default => data_get($context['query'], $name) ?? data_get($context['body'], $name),
            };

            // Check presence
            if ($value === null || $value === '') {
                $errors[$name] = $customMsg ?? "The '{$name}' parameter is required (source: {$source}).";

                continue;
            }

            // Type validation
            $typeError = $this->validateParamType($name, $value, $type);
            if ($typeError !== null) {
                $errors[$name] = $customMsg ?? $typeError;
            }
        }

        return $errors;
    }

    /**
     * Resolve a dot-notation key from a nested array (e.g. "data.user.id").
     */
    protected function resolveNestedKey(mixed $data, string $key): mixed
    {
        if (! is_array($data)) {
            return null;
        }

        // Try direct key first
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        // Dot-notation traversal
        $parts = explode('.', $key);
        $current = $data;
        foreach ($parts as $part) {
            if (! is_array($current) || ! array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }

        return $current;
    }

    /**
     * Validate a param's value against a type constraint.
     *
     * @return string|null Error string or null if valid
     */
    protected function validateParamType(string $name, mixed $value, string $type): ?string
    {
        $strVal = (string) $value;

        return match ($type) {
            'number', 'integer' => is_numeric($value) ? null : "The '{$name}' parameter must be a number.",
            'email' => filter_var($strVal, FILTER_VALIDATE_EMAIL) !== false ? null : "The '{$name}' parameter must be a valid email address.",
            'url' => filter_var($strVal, FILTER_VALIDATE_URL) !== false ? null : "The '{$name}' parameter must be a valid URL.",
            'boolean' => in_array(strtolower($strVal), ['true', 'false', '1', '0', 'yes', 'no']) ? null : "The '{$name}' parameter must be a boolean value.",
            default => null, // 'string' — any non-empty value passes
        };
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

        if (count($queue) > 50) {
            $queue = array_slice($queue, -50);
        }

        Cache::put('hookforge_recent_events', $queue, now()->addMinutes(10));
    }
}
