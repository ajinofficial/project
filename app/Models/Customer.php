<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['tenant_id', 'name', 'mobile', 'credit_limit', 'outstanding_balance'];

    protected $casts = ['credit_limit' => 'decimal:2', 'outstanding_balance' => 'decimal:2'];
}
