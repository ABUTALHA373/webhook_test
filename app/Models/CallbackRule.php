<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallbackRule extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'endpoint_id',
        'name',
        'is_active',
        'target_url',
        'http_method',
        'delay_seconds',
        'payload_mode',
        'payload_template',
        'custom_headers',
        'hmac_enabled',
        'hmac_secret',
        'hmac_algorithm',
        'hmac_header_name',
        'hmac_format',
        'max_retries',
        'retry_backoff_seconds',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'delay_seconds' => 'integer',
            'custom_headers' => 'array',
            'hmac_enabled' => 'boolean',
            'max_retries' => 'integer',
            'retry_backoff_seconds' => 'integer',
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
    public function logs(): HasMany
    {
        return $this->hasMany(CallbackLog::class);
    }

    /**
     * @return HasMany<CallbackLog, $this>
     */
    public function callbackLogs(): HasMany
    {
        return $this->hasMany(CallbackLog::class);
    }
}
