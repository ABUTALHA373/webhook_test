<?php

namespace Tests\Unit;

use App\Services\DynamicTemplateEngine;
use PHPUnit\Framework\TestCase;

class DynamicTemplateEngineTest extends TestCase
{
    protected DynamicTemplateEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DynamicTemplateEngine;
    }

    public function test_renders_simple_and_nested_variables(): void
    {
        $template = 'Hello {{body.user.name}}, your order {{body.order_id}} is {{query.status}}';
        $context = [
            'body' => [
                'user' => ['name' => 'Alice'],
                'order_id' => 'ORD-1234',
            ],
            'query' => [
                'status' => 'confirmed',
            ],
        ];

        $result = $this->engine->render($template, $context);
        $this->assertEquals('Hello Alice, your order ORD-1234 is confirmed', $result);
    }

    public function test_renders_system_variables(): void
    {
        $template = 'ID: {{uuid}}, Date: {{date}}';
        $result = $this->engine->render($template, []);

        $this->assertStringContainsString('ID: ', $result);
        $this->assertStringContainsString('Date: ', $result);
        $this->assertMatchesRegularExpression('/[a-f0-9\-]{36}/', $result);
    }

    public function test_evaluates_equals_condition(): void
    {
        $rules = [
            [
                'field' => 'body.action',
                'operator' => 'equals',
                'value' => 'fail',
                'status' => 400,
                'body' => '{"error":"action_failed"}',
            ],
        ];

        $contextMatch = ['body' => ['action' => 'fail']];
        $matched = $this->engine->evaluateRules($rules, $contextMatch);
        $this->assertNotNull($matched);
        $this->assertEquals(400, $matched['status']);
        $this->assertEquals('{"error":"action_failed"}', $matched['body']);

        $contextNoMatch = ['body' => ['action' => 'success']];
        $noMatch = $this->engine->evaluateRules($rules, $contextNoMatch);
        $this->assertNull($noMatch);
    }

    public function test_evaluates_exists_condition(): void
    {
        $rules = [
            [
                'field' => 'headers.x-test-error',
                'operator' => 'exists',
                'status' => 500,
                'body' => '{"error":"server_error"}',
            ],
        ];

        $context = ['headers' => ['x-test-error' => '1']];
        $matched = $this->engine->evaluateRules($rules, $context);
        $this->assertNotNull($matched);
        $this->assertEquals(500, $matched['status']);
    }
}
