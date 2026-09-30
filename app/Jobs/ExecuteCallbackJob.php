<?php

namespace App\Jobs;

use App\Models\CallbackLog;
use App\Models\CallbackRule;
use App\Models\WebhookRequest;
use App\Services\DynamicTemplateEngine;
use App\Services\HmacSignatureService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ExecuteCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public WebhookRequest $webhookRequest,
        public CallbackRule $callbackRule,
        public int $attempt = 1,
        public ?string $callbackLogId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        DynamicTemplateEngine $templateEngine,
        HmacSignatureService $hmacService
    ): void {
        $paramsMap = array_merge(
            $this->webhookRequest->query_params ?? [],
            is_array($this->webhookRequest->parsed_body) ? $this->webhookRequest->parsed_body : []
        );

        $context = [
            'body' => $this->webhookRequest->parsed_body ?? [],
            'headers' => $this->webhookRequest->headers ?? [],
            'query' => $this->webhookRequest->query_params ?? [],
            'param' => $paramsMap,
            'params' => $paramsMap,
            'req' => [
                'id' => $this->webhookRequest->id,
                'ip' => $this->webhookRequest->ip_address,
                'method' => $this->webhookRequest->method,
                'url' => $this->webhookRequest->url,
                'path' => $this->webhookRequest->path,
            ],
        ];

        // 1. Resolve Target URL
        $targetUrl = trim($templateEngine->render($this->callbackRule->target_url, $context));

        if (! filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            // Target URL is missing or invalid
            CallbackLog::create([
                'id' => $this->callbackLogId ?? (string) Str::uuid(),
                'webhook_request_id' => $this->webhookRequest->id,
                'callback_rule_id' => $this->callbackRule->id,
                'status' => 'failed',
                'attempt' => $this->attempt,
                'target_url' => $targetUrl ?: '(empty URL)',
                'request_method' => $this->callbackRule->http_method ?: 'POST',
                'request_headers' => [],
                'request_body' => '',
                'duration_ms' => 0,
                'error_message' => "Target URL [{$targetUrl}] is invalid or could not be resolved from dynamic tokens.",
            ]);

            return;
        }

        // 2. Prepare Outbound Payload
        $outboundBody = '';
        if ($this->callbackRule->payload_mode === 'passthrough') {
            $outboundBody = $this->webhookRequest->raw_body ?? '';
        } elseif ($this->callbackRule->payload_mode === 'template') {
            $outboundBody = $templateEngine->render($this->callbackRule->payload_template, $context);
        }

        // 3. Prepare Outbound Headers
        $headers = [];
        if (! empty($this->callbackRule->custom_headers) && is_array($this->callbackRule->custom_headers)) {
            foreach ($this->callbackRule->custom_headers as $k => $v) {
                $headers[$k] = $templateEngine->render((string) $v, $context);
            }
        }

        // Default Content-Type if JSON
        if (! isset($headers['Content-Type']) && ! isset($headers['content-type'])) {
            $headers['Content-Type'] = 'application/json';
        }

        // 4. HMAC Signature
        if ($this->callbackRule->hmac_enabled && ! empty($this->callbackRule->hmac_secret)) {
            $sigHeaders = $hmacService->buildHeader(
                payload: $outboundBody,
                secret: $this->callbackRule->hmac_secret,
                headerName: $this->callbackRule->hmac_header_name ?: 'X-Signature-256',
                algorithm: $this->callbackRule->hmac_algorithm ?: 'sha256',
                format: $this->callbackRule->hmac_format ?: 'hex'
            );
            $headers = array_merge($headers, $sigHeaders);
        }

        // Correlation header
        $headers['X-HookForge-Request-ID'] = $this->webhookRequest->id;
        $headers['X-HookForge-Attempt'] = (string) $this->attempt;

        // 5. Create or update CallbackLog in 'in_progress'
        $logId = $this->callbackLogId ?? (string) Str::uuid();
        $log = CallbackLog::updateOrCreate(
            ['id' => $logId],
            [
                'webhook_request_id' => $this->webhookRequest->id,
                'callback_rule_id' => $this->callbackRule->id,
                'status' => 'in_progress',
                'attempt' => $this->attempt,
                'target_url' => $targetUrl,
                'request_method' => strtoupper($this->callbackRule->http_method ?: 'POST'),
                'request_headers' => $headers,
                'request_body' => $outboundBody,
            ]
        );

        $startTime = microtime(true);
        $method = strtoupper($this->callbackRule->http_method ?: 'POST');

        try {
            $client = Http::timeout(15)->withHeaders($headers);

            $response = match ($method) {
                'GET' => $client->get($targetUrl),
                'PUT' => $client->withBody($outboundBody, $headers['Content-Type'] ?? 'application/json')->put($targetUrl),
                'PATCH' => $client->withBody($outboundBody, $headers['Content-Type'] ?? 'application/json')->patch($targetUrl),
                'DELETE' => $client->withBody($outboundBody, $headers['Content-Type'] ?? 'application/json')->delete($targetUrl),
                default => $client->withBody($outboundBody, $headers['Content-Type'] ?? 'application/json')->post($targetUrl),
            };

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);
            $statusCode = $response->status();
            $isSuccess = $response->successful();

            $log->update([
                'status' => $isSuccess ? 'success' : 'failed',
                'response_status' => $statusCode,
                'response_headers' => $response->headers(),
                'response_body' => Str::limit($response->body(), 65535),
                'duration_ms' => $durationMs,
                'error_message' => $isSuccess ? null : "Target responded with HTTP {$statusCode}",
            ]);

            // Retry if failed and attempts left
            if (! $isSuccess && $this->attempt < ($this->callbackRule->max_retries ?? 3)) {
                $this->scheduleRetry();
            }
        } catch (Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            $log->update([
                'status' => 'failed',
                'response_status' => 0,
                'duration_ms' => $durationMs,
                'error_message' => $e->getMessage(),
            ]);

            if ($this->attempt < ($this->callbackRule->max_retries ?? 3)) {
                $this->scheduleRetry();
            }
        }
    }

    /**
     * Schedule next retry with exponential backoff.
     */
    protected function scheduleRetry(): void
    {
        $backoff = (int) ($this->callbackRule->retry_backoff_seconds ?: 2);
        $delaySeconds = $backoff * (2 ** ($this->attempt - 1));

        self::dispatch(
            $this->webhookRequest,
            $this->callbackRule,
            $this->attempt + 1,
            (string) Str::uuid()
        )->delay(now()->addSeconds($delaySeconds));
    }
}
