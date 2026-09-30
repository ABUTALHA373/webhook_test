<?php

namespace App\Services;

use Illuminate\Support\Str;

class DynamicTemplateEngine
{
    /**
     * Render a dynamic template string using request context.
     *
     * @param  array<string, mixed>  $context
     */
    public function render(?string $template, array $context): string
    {
        if ($template === null || $template === '') {
            return '';
        }

        return preg_replace_callback('/\{\{\s*([\w\.\-]+)\s*\}\}/', function ($matches) use ($context) {
            $key = trim($matches[1]);
            $val = $this->resolveVariable($key, $context);

            if (is_array($val)) {
                return json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            if (is_bool($val)) {
                return $val ? 'true' : 'false';
            }

            if ($val === null) {
                return '';
            }

            return (string) $val;
        }, $template) ?? $template;
    }

    /**
     * Resolve a variable by path from the given context.
     *
     * @param  array<string, mixed>  $context
     */
    public function resolveVariable(string $path, array $context): mixed
    {
        // System variables
        if ($path === 'uuid') {
            return (string) Str::uuid();
        }
        if ($path === 'timestamp') {
            return (string) now()->timestamp;
        }
        if ($path === 'timestamp_ms') {
            return (string) (int) (microtime(true) * 1000);
        }
        if ($path === 'timestamp_iso') {
            return now()->toIso8601String();
        }
        if ($path === 'date') {
            return now()->toDateString();
        }
        if ($path === 'time') {
            return now()->toTimeString();
        }
        if ($path === 'random.int') {
            return (string) random_int(1000, 999999);
        }
        if ($path === 'random.string') {
            return Str::random(12);
        }

        return data_get($context, $path);
    }

    /**
     * Evaluate conditional rules against incoming request context.
     * Returns matching rule with status & body overrides if any rule matches.
     *
     * @param  array<int, array<string, mixed>>|null  $rules
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    public function evaluateRules(?array $rules, array $context): ?array
    {
        if (empty($rules)) {
            return null;
        }

        foreach ($rules as $rule) {
            $field = $rule['field'] ?? null;
            $operator = $rule['operator'] ?? 'equals';
            $expectedValue = $rule['value'] ?? null;

            if (! $field) {
                continue;
            }

            $actualValue = $this->resolveVariable($field, $context);

            if ($this->matchCondition($actualValue, $operator, $expectedValue)) {
                return [
                    'status' => (int) ($rule['status'] ?? 200),
                    'body' => $this->render($rule['body'] ?? null, $context),
                    'headers' => $rule['headers'] ?? null,
                    'matched_rule' => $rule,
                ];
            }
        }

        return null;
    }

    /**
     * Match a single condition.
     */
    protected function matchCondition(mixed $actual, string $operator, mixed $expected): bool
    {
        switch ($operator) {
            case 'equals':
            case '==':
                return (string) $actual === (string) $expected;

            case 'not_equals':
            case '!=':
                return (string) $actual !== (string) $expected;

            case 'contains':
                return is_string($actual) && str_contains(strtolower($actual), strtolower((string) $expected));

            case 'starts_with':
                return is_string($actual) && str_starts_with($actual, (string) $expected);

            case 'ends_with':
                return is_string($actual) && str_ends_with($actual, (string) $expected);

            case 'exists':
                return $actual !== null && $actual !== '';

            case 'does_not_exist':
                return $actual === null || $actual === '';

            case 'gt':
            case '>':
                return is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected;

            case 'gte':
            case '>=':
                return is_numeric($actual) && is_numeric($expected) && (float) $actual >= (float) $expected;

            case 'lt':
            case '<':
                return is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected;

            case 'lte':
            case '<=':
                return is_numeric($actual) && is_numeric($expected) && (float) $actual <= (float) $expected;

            default:
                return false;
        }
    }
}
