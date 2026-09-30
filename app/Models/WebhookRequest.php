<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookRequest extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'endpoint_id',
        'method',
        'url',
        'path',
        'ip_address',
        'user_agent',
        'headers',
        'query_params',
        'raw_body',
        'parsed_body',
        'content_type',
        'content_length',
        'response_status',
        'response_headers',
        'response_body',
        'duration_ms',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'query_params' => 'array',
            'parsed_body' => 'array',
            'response_headers' => 'array',
            'response_status' => 'integer',
            'content_length' => 'integer',
            'duration_ms' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Endpoint, $this>
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    /**
     * @return HasMany<CallbackLog, $this>
     */
    public function callbackLogs(): HasMany
    {
        return $this->hasMany(CallbackLog::class);
    }
}
