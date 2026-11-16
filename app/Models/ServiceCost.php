<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCost extends Model
{
    protected $fillable = ['service_order_id', 'product_id', 'description', 'quantity', 'unit_cost'];
    protected $casts = ['unit_cost' => 'decimal:2', 'quantity' => 'integer'];
}
