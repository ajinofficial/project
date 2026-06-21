<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['tenant_id', 'name', 'contact_information', 'gst_number', 'payment_terms', 'outstanding_balance'];

    protected $casts = ['outstanding_balance' => 'decimal:2'];
}
