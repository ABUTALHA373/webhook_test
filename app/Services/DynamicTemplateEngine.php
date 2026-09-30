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

        // Normalize any literal @{{token}} to {{token}}
        $template = str_replace('@{{', '{{', $template);

        // 1. Process functional helpers: {{default(x, y)}}, {{upper(x)}}, {{lower(x)}}, {{json(x)}}
        $template = preg_replace_callback('/\{\{\s*(default|upper|lower|json|ternary)\s*\(([^)]+)\)\s*\}\}/', function ($matches) use ($context) {
            $func = $matches[1];
            $args = array_map('trim', explode(',', $matches[2]));

            switch ($func) {
                case 'default':
                    $val = $this->resolveVariable($args[0], $context);
                    $fallback = isset($args[1]) ? trim($args[1], '\'"') : '';

                    return ($val !== null && $val !== '') ? (string) $val : $fallback;

                case 'upper':
                    $val = (string) $this->resolveVariable($args[0], $context);

                    return strtoupper($val);

                case 'lower':
                    $val = (string) $this->resolveVariable($args[0], $context);

                    return strtolower($val);

                case 'json':
                    $val = $this->resolveVariable($args[0], $context);

                    return json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                case 'ternary':
                    $conditionVal = $this->resolveVariable($args[0], $context);
                    $truthy = isset($args[1]) ? trim($args[1], '\'"') : 'true';
                    $falsy = isset($args[2]) ? trim($args[2], '\'"') : 'false';

                    return ! empty($conditionVal) ? $truthy : $falsy;

                default:
                    return $matches[0];
            }
        }, $template);

        // 2. Process variables with fallback syntax: {{body.code || 200}}
        $template = preg_replace_callback('/\{\{\s*([\w\.\-]+)\s*\|\|\s*([^}]+)\s*\}\}/', function ($matches) use ($context) {
            $key = trim($matches[1]);
            $fallback = trim($matches[2], '\'" ');
            $val = $this->resolveVariable($key, $context);

            return ($val !== null && $val !== '') ? (string) $val : $fallback;
        }, $template);

        // 3. Process standard variables: {{body.x}}
        return preg_replace_callback('/\{\{\s*([\w\.\-\[\]]+)\s*\}\}/', function ($matches) use ($context) {
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

        // Support array bracket syntax e.g. items[0].id -> items.0.id
        $normalizedPath = str_replace(['[', ']'], ['.', ''], $path);

        $val = data_get($context, $normalizedPath);
        if ($val !== null) {
            return $val;
        }

        // Support param.<name> / params.<name> (checking query first, then body)
        if (str_starts_with($normalizedPath, 'param.')) {
            $subKey = substr($normalizedPath, 6);
            $val = data_get($context, 'query.'.$subKey) ?? data_get($context, 'body.'.$subKey);
            if ($val !== null) {
                return $val;
            }
        }
        if (str_starts_with($normalizedPath, 'params.')) {
            $subKey = substr($normalizedPath, 7);
            $val = data_get($context, 'query.'.$subKey) ?? data_get($context, 'body.'.$subKey);
            if ($val !== null) {
                return $val;
            }
        }

        // Direct fallback: check query, body, and headers directly if path has no prefix (e.g. {{customer_id}})
        return data_get($context, 'query.'.$normalizedPath)
            ?? data_get($context, 'body.'.$normalizedPath)
            ?? data_get($context, 'headers.'.$normalizedPath);
    }

    /**
     * Dynamically resolve the response status code.
     * Checks conditional rules first, then fallback/template interpolation.
     *
     * @param  array<string, mixed>  $context
     * @param  array<int, array<string, mixed>>|null  $rules
     */
    public function resolveStatusCode(int|string|null $configuredStatus, array $context, ?array $rules = null): int
    {
        // Check conditional rules
        $matched = $this->evaluateRules($rules, $context);
        if ($matched && ! empty($matched['status'])) {
            return (int) $matched['status'];
        }

        // Check if configuredStatus is a template e.g. {{body.status_code || 200}}
        if (is_string($configuredStatus) && str_contains($configuredStatus, '{{')) {
            $rendered = $this->render($configuredStatus, $context);
            if (is_numeric($rendered) && (int) $rendered >= 100 && (int) $rendered <= 599) {
                return (int) $rendered;
            }
        }

        if (is_numeric($configuredStatus) && (int) $configuredStatus >= 100 && (int) $configuredStatus <= 599) {
            return (int) $configuredStatus;
        }

        return 200;
    }

    /**
     * Evaluate conditional rules against incoming request context.
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
     * Match a single condition with rich operators.
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

            case 'not_contains':
                return is_string($actual) && ! str_contains(strtolower($actual), strtolower((string) $expected));

            case 'starts_with':
                return is_string($actual) && str_starts_with($actual, (string) $expected);

            case 'ends_with':
                return is_string($actual) && str_ends_with($actual, (string) $expected);

            case 'exists':
                return $actual !== null && $actual !== '';

            case 'does_not_exist':
            case 'empty':
                return $actual === null || $actual === '' || (is_array($actual) && empty($actual));

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

            case 'regex':
                return is_string($actual) && @preg_match('/'.str_replace('/', '\/', (string) $expected).'/', $actual) === 1;

            default:
                return false;
        }
    }
}
