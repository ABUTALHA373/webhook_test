<?php

namespace Tests\Feature;

use App\Models\CallbackRule;
use App\Models\Endpoint;
use App\Models\WebhookRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingests_incoming_webhook_and_stores_in_database(): void
    {
        $endpoint = Endpoint::create([
            'name' => 'Test Sink',
            'slug' => 'test-sink',
            'response_status' => 200,
            'response_body' => '{"received": true, "req_id": "{{req.id}}", "event": "{{body.event}}"}',
            'is_active' => true,
        ]);

        $payload = ['event' => 'order.created', 'order_id' => '1001'];

        $response = $this->postJson("/hook/{$endpoint->slug}", $payload, [
            'X-Custom-Client' => 'TestRunner',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('received', true);
        $response->assertJsonPath('event', 'order.created');

        $this->assertDatabaseHas('webhook_requests', [
            'endpoint_id' => $endpoint->id,
            'method' => 'POST',
            'response_status' => 200,
        ]);

        $saved = WebhookRequest::where('endpoint_id', $endpoint->id)->first();
        $this->assertNotNull($saved);
        $this->assertEquals('TestRunner', $saved->headers['x-custom-client'] ?? $saved->headers['X-Custom-Client'] ?? null);
    }

    public function test_returns_404_for_non_existent_endpoint(): void
    {
        $response = $this->postJson('/hook/does-not-exist', ['test' => true]);
        $response->assertStatus(404);
    }

    public function test_evaluates_conditional_override_rules(): void
    {
        $endpoint = Endpoint::create([
            'name' => 'Conditional Endpoint',
            'slug' => 'cond-ep',
            'response_status' => 200,
            'conditional_rules' => [
                [
                    'field' => 'body.fail',
                    'operator' => 'equals',
                    'value' => 'true',
                    'status' => 422,
                    'body' => '{"error": "validation_failed"}',
                ],
            ],
            'is_active' => true,
        ]);

        // Regular request -> 200
        $resNormal = $this->postJson("/hook/{$endpoint->slug}", ['fail' => 'false']);
        $resNormal->assertStatus(200);

        // Matching condition -> 422
        $resFail = $this->postJson("/hook/{$endpoint->slug}", ['fail' => 'true']);
        $resFail->assertStatus(422);
        $resFail->assertJsonPath('error', 'validation_failed');
    }

    public function test_triggers_active_callback_rule_on_ingestion(): void
    {
        Http::fake([
            'https://external-api.test/*' => Http::response(['status' => 'acknowledged'], 200),
        ]);

        $endpoint = Endpoint::create([
            'name' => 'Relay Endpoint',
            'slug' => 'relay-ep',
            'response_status' => 200,
            'is_active' => true,
        ]);

        CallbackRule::create([
            'endpoint_id' => $endpoint->id,
            'name' => 'Forward to External',
            'target_url' => 'https://external-api.test/webhook',
            'http_method' => 'POST',
            'delay_seconds' => 0,
            'payload_mode' => 'passthrough',
            'hmac_enabled' => true,
            'hmac_secret' => 'test_secret',
            'hmac_header_name' => 'X-Signature',
            'is_active' => true,
        ]);

        $this->postJson("/hook/{$endpoint->slug}", [
            'event' => 'user.signup',
            'email' => 'user@test.com',
        ]);

        $this->assertDatabaseHas('callback_logs', [
            'status' => 'success',
            'response_status' => 200,
            'target_url' => 'https://external-api.test/webhook',
        ]);
    }
}
