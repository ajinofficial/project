<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_order_id', 'product_id', 'quantity', 'purchase_price', 'tax_percentage'];

    protected $casts = ['quantity' => 'integer', 'purchase_price' => 'decimal:2', 'tax_percentage' => 'decimal:2'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
