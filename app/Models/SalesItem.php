<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesItem extends Model
{
    protected $fillable = ['sales_order_id', 'product_id', 'quantity', 'selling_price', 'tax_percentage'];

    protected $casts = ['quantity' => 'integer', 'selling_price' => 'decimal:2', 'tax_percentage' => 'decimal:2'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
