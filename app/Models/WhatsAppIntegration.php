<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppIntegration extends Model
{
    protected $table = 'whatsapp_integrations';

    protected $fillable = [
        'tenant_id', 'business_account_id', 'phone_number_id',
        'access_token', 'is_active', 'last_used_at',
    ];

    protected $hidden = ['access_token'];

    protected $casts = [
        'access_token' => 'encrypted',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];
}
