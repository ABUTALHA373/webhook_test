<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Endpoint extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'secret_token',
        'response_status',
        'response_delay_ms',
        'response_headers',
        'response_body',
        'conditional_rules',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response_status' => 'integer',
            'response_delay_ms' => 'integer',
            'response_headers' => 'array',
            'conditional_rules' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<WebhookRequest, $this>
     */
    public function webhookRequests(): HasMany
    {
        return $this->hasMany(WebhookRequest::class);
    }

    /**
     * @return HasMany<CallbackRule, $this>
     */
    public function callbackRules(): HasMany
    {
        return $this->hasMany(CallbackRule::class);
    }
}
