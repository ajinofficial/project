<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailIntegration extends Model
{
    protected $fillable = [
        'tenant_id', 'host', 'port', 'encryption', 'username', 'password',
        'from_address', 'from_name', 'is_active', 'last_used_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'encrypted',
        'port' => 'integer',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];
}
