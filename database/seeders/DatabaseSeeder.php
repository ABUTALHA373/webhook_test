<?php

namespace Database\Seeders;

use App\Models\CallbackRule;
use App\Models\Endpoint;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultEndpoint = Endpoint::updateOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default Webhook Sink',
                'slug' => 'default',
                'secret_token' => null,
                'response_status' => 200,
                'response_delay_ms' => 0,
                'response_headers' => [
                    'Content-Type' => 'application/json',
                    'X-Powered-By' => 'HookForge-Engine',
                ],
                'response_body' => "{\n  \"status\": \"acknowledged\",\n  \"request_id\": \"{{req.id}}\",\n  \"received_at\": \"{{timestamp_iso}}\",\n  \"event\": \"{{body.event}}\"\n}",
                'conditional_rules' => [
                    [
                        'field' => 'headers.x-test-error',
                        'operator' => 'exists',
                        'value' => 'true',
                        'status' => 500,
                        'body' => "{\n  \"error\": \"Simulated internal server error via header\"\n}",
                    ],
                ],
                'is_active' => true,
            ]
        );

        Endpoint::updateOrCreate(
            ['slug' => 'stripe-mock'],
            [
                'name' => 'Stripe Webhook Receiver',
                'slug' => 'stripe-mock',
                'secret_token' => 'whsec_test_secret_key_8849',
                'response_status' => 200,
                'response_delay_ms' => 150,
                'response_headers' => [
                    'Content-Type' => 'application/json',
                    'X-Stripe-Mock' => 'v1',
                ],
                'response_body' => "{\n  \"received\": true,\n  \"event_id\": \"{{body.id}}\",\n  \"type\": \"{{body.type}}\"\n}",
                'conditional_rules' => [],
                'is_active' => true,
            ]
        );

        CallbackRule::updateOrCreate(
            [
                'endpoint_id' => $defaultEndpoint->id,
                'name' => 'Echo Callback (Dynamic Target)',
            ],
            [
                'endpoint_id' => $defaultEndpoint->id,
                'name' => 'Echo Callback (Dynamic Target)',
                'is_active' => true,
                'target_url' => '{{body.callback_url}}',
                'http_method' => 'POST',
                'delay_seconds' => 1,
                'payload_mode' => 'template',
                'payload_template' => "{\n  \"event\": \"CALLBACK_DISPATCHED\",\n  \"source_request_id\": \"{{req.id}}\",\n  \"processed_at\": \"{{timestamp_iso}}\",\n  \"original_event\": \"{{body.event}}\",\n  \"client_ip\": \"{{req.ip}}\"\n}",
                'custom_headers' => [
                    'Content-Type' => 'application/json',
                    'X-HookForge-Callback' => '1.0',
                ],
                'hmac_enabled' => true,
                'hmac_secret' => 'super_secret_signing_key_42',
                'hmac_algorithm' => 'sha256',
                'hmac_header_name' => 'X-Signature-SHA256',
                'hmac_format' => 'hex',
                'max_retries' => 3,
                'retry_backoff_seconds' => 2,
            ]
        );
    }
}
