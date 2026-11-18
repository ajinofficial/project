<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'type',
        'channel',
        'title',
        'message',
        'read_at',
        'translation_data',
    ];

    protected $casts = [
        'translation_data' => 'array',
        'read_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getTitleAttribute(string $value): string
    {
        return __($value);
    }

    public function getMessageAttribute(string $value): string
    {
        $data = $this->translation_data ?? \App\Support\NotificationText::historicalData($this->type, $value);
        if (! is_array($data) || ! isset($data['key']) || ! str_starts_with($data['key'], 'notifications.events.')) {
            return __($value);
        }
        $parameters = $data['parameters'] ?? [];
        foreach (['role', 'kind'] as $field) {
            if (isset($parameters[$field])) {
                $parameters[$field] = __(ucfirst($parameters[$field]));
            }
        }
        return app('translator')->has($data['key']) ? __($data['key'], $parameters) : $value;
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
