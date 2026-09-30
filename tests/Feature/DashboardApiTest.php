<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\WebhookRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_and_create_endpoints(): void
    {
        $res = $this->getJson('/api/endpoints');
        $res->assertStatus(200);

        $createRes = $this->postJson('/api/endpoints', [
            'name' => 'Stripe Webhooks',
            'slug' => 'stripe-ci',
            'response_status' => 200,
            'response_delay_ms' => 50,
        ]);

        $createRes->assertStatus(201);
        $createRes->assertJsonPath('slug', 'stripe-ci');
        $this->assertDatabaseHas('endpoints', ['slug' => 'stripe-ci']);
    }

    public function test_can_filter_webhook_requests(): void
    {
        $ep = Endpoint::create(['name' => 'Ep', 'slug' => 'ep', 'response_status' => 200]);

        WebhookRequest::create([
            'endpoint_id' => $ep->id,
            'method' => 'POST',
            'url' => 'http://localhost/hook/ep',
            'path' => '/hook/ep',
            'headers' => ['Content-Type' => 'application/json'],
            'raw_body' => '{"search_keyword":"needle"}',
            'response_status' => 200,
        ]);

        WebhookRequest::create([
            'endpoint_id' => $ep->id,
            'method' => 'GET',
            'url' => 'http://localhost/hook/ep',
            'path' => '/hook/ep',
            'headers' => ['Content-Type' => 'application/json'],
            'raw_body' => '{"other":"haystack"}',
            'response_status' => 200,
        ]);

        // Filter by method
        $methodRes = $this->getJson('/api/requests?method=POST');
        $methodRes->assertStatus(200);
        $this->assertCount(1, $methodRes->json('data'));

        // Search by keyword
        $searchRes = $this->getJson('/api/requests?search=needle');
        $searchRes->assertStatus(200);
        $this->assertCount(1, $searchRes->json('data'));
    }

    public function test_outbound_dispatcher_executes_request_and_logs_audit(): void
    {
        Http::fake([
            'https://api.thirdparty.test/incoming' => Http::response(['ack' => true], 200),
        ]);

        $res = $this->postJson('/api/dispatch', [
            'target_url' => 'https://api.thirdparty.test/incoming',
            'method' => 'POST',
            'payload' => '{"event":"ping"}',
            'hmac_enabled' => true,
            'hmac_secret' => 'whsec_test',
            'hmac_header' => 'X-Signature-256',
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('response_status', 200);

        $this->assertDatabaseHas('outbound_dispatch_logs', [
            'target_url' => 'https://api.thirdparty.test/incoming',
            'response_status' => 200,
        ]);
    }
}
