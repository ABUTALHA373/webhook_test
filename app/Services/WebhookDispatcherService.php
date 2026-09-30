<?php

namespace App\Services;

use App\Models\OutboundDispatchLog;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookDispatcherService
{
    public function __construct(
        protected HmacSignatureService $hmacService
    ) {}

    /**
     * Dispatch an outbound webhook request and record the audit log.
     *
     * @param array{
     *     target_url: string,
     *     method?: string,
     *     payload?: string,
     *     headers?: array<string, string>,
     *     hmac_enabled?: bool,
     *     hmac_secret?: string|null,
     *     hmac_header?: string|null,
     *     hmac_algorithm?: string|null,
     *     hmac_format?: string|null
     * } $data
     */
    public function dispatch(array $data): OutboundDispatchLog
    {
        $targetUrl = trim($data['target_url']);
        $method = strtoupper($data['method'] ?? 'POST');
        $payload = $data['payload'] ?? '{}';
        $headers = $data['headers'] ?? [];

        if (! isset($headers['Content-Type']) && ! isset($headers['content-type'])) {
            $headers['Content-Type'] = 'application/json';
        }

        // HMAC Signature
        if (! empty($data['hmac_enabled']) && ! empty($data['hmac_secret'])) {
            $sigHeaders = $this->hmacService->buildHeader(
                payload: $payload,
                secret: $data['hmac_secret'],
                headerName: ! empty($data['hmac_header']) ? $data['hmac_header'] : 'X-Signature-256',
                algorithm: ! empty($data['hmac_algorithm']) ? $data['hmac_algorithm'] : 'sha256',
                format: ! empty($data['hmac_format']) ? $data['hmac_format'] : 'hex'
            );
            $headers = array_merge($headers, $sigHeaders);
        }

        $headers['User-Agent'] = 'HookForge-Dispatcher/1.0';

        $startTime = microtime(true);
        $logId = (string) Str::uuid();

        try {
            $client = Http::timeout(15)->withHeaders($headers);

            $response = match ($method) {
                'GET' => $client->get($targetUrl),
                'PUT' => $client->withBody($payload, $headers['Content-Type'] ?? 'application/json')->put($targetUrl),
                'PATCH' => $client->withBody($payload, $headers['Content-Type'] ?? 'application/json')->patch($targetUrl),
                'DELETE' => $client->withBody($payload, $headers['Content-Type'] ?? 'application/json')->delete($targetUrl),
                default => $client->withBody($payload, $headers['Content-Type'] ?? 'application/json')->post($targetUrl),
            };

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            return OutboundDispatchLog::create([
                'id' => $logId,
                'target_url' => $targetUrl,
                'method' => $method,
                'request_headers' => $headers,
                'request_body' => $payload,
                'response_status' => $response->status(),
                'response_headers' => $response->headers(),
                'response_body' => Str::limit($response->body(), 65535),
                'duration_ms' => $durationMs,
                'error_message' => $response->successful() ? null : "Target returned HTTP {$response->status()}",
            ]);
        } catch (Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            return OutboundDispatchLog::create([
                'id' => $logId,
                'target_url' => $targetUrl,
                'method' => $method,
                'request_headers' => $headers,
                'request_body' => $payload,
                'response_status' => 0,
                'duration_ms' => $durationMs,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get preset sample payloads.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getPresets(): array
    {
        return [
            'stripe_payment_succeeded' => [
                'name' => 'Stripe: payment_intent.succeeded',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Stripe-Signature' => 't={{timestamp}},v1=simulated_stripe_signature',
                ],
                'payload' => json_encode([
                    'id' => 'evt_'.Str::random(24),
                    'object' => 'event',
                    'api_version' => '2024-06-20',
                    'created' => time(),
                    'data' => [
                        'object' => [
                            'id' => 'pi_'.Str::random(24),
                            'object' => 'payment_intent',
                            'amount' => 4999,
                            'currency' => 'usd',
                            'status' => 'succeeded',
                            'customer' => 'cus_'.Str::random(14),
                            'payment_method' => 'pm_'.Str::random(24),
                        ],
                    ],
                    'type' => 'payment_intent.succeeded',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ],
            'stripe_charge_failed' => [
                'name' => 'Stripe: charge.failed',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'payload' => json_encode([
                    'id' => 'evt_'.Str::random(24),
                    'object' => 'event',
                    'created' => time(),
                    'type' => 'charge.failed',
                    'data' => [
                        'object' => [
                            'id' => 'ch_'.Str::random(24),
                            'amount' => 1999,
                            'failure_code' => 'card_declined',
                            'failure_message' => 'Your card was declined.',
                        ],
                    ],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ],
            'github_push' => [
                'name' => 'GitHub: push',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-GitHub-Event' => 'push',
                    'X-GitHub-Delivery' => (string) Str::uuid(),
                ],
                'payload' => json_encode([
                    'ref' => 'refs/heads/main',
                    'before' => '6113728f27ae82c7b1a12f6593c4e28200e72ae3',
                    'after' => '83266da7066b5d2433dcd52ac140109a47565f69',
                    'repository' => [
                        'name' => 'webhook-core',
                        'full_name' => 'enterprise/webhook-core',
                        'private' => false,
                    ],
                    'pusher' => [
                        'name' => 'senior-dev',
                        'email' => 'dev@company.com',
                    ],
                    'commits' => [
                        [
                            'id' => '83266da7066b5d2433dcd52ac140109a47565f69',
                            'message' => 'feat: dynamic webhook callback system',
                            'timestamp' => now()->toIso8601String(),
                        ],
                    ],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ],
            'shopify_order' => [
                'name' => 'Shopify: orders/create',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Shopify-Topic' => 'orders/create',
                    'X-Shopify-Shop-Domain' => 'mystore.myshopify.com',
                ],
                'payload' => json_encode([
                    'id' => 820982911946154500,
                    'email' => 'customer@example.com',
                    'total_price' => '129.99',
                    'currency' => 'USD',
                    'financial_status' => 'paid',
                    'line_items' => [
                        [
                            'id' => 866550311766439020,
                            'title' => 'Enterprise Webhook License',
                            'price' => '129.99',
                            'quantity' => 1,
                        ],
                    ],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ],
        ];
    }
}
