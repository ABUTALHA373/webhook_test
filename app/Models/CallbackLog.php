<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallbackLog extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'webhook_request_id',
        'callback_rule_id',
        'status',
        'attempt',
        'target_url',
        'request_method',
        'request_headers',
        'request_body',
        'response_status',
        'response_headers',
        'response_body',
        'duration_ms',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
            'response_headers' => 'array',
            'response_status' => 'integer',
            'attempt' => 'integer',
            'duration_ms' => 'float',
        ];
    }

    /**
     * @return BelongsTo<WebhookRequest, $this>
     */
    public function webhookRequest(): BelongsTo
    {
        return $this->belongsTo(WebhookRequest::class);
    }

    /**
     * @return BelongsTo<CallbackRule, $this>
     */
    public function callbackRule(): BelongsTo
    {
        return $this->belongsTo(CallbackRule::class);
    }
}
