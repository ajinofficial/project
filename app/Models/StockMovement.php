<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = ['tenant_id', 'product_id', 'type', 'quantity', 'stock_after', 'reference_type', 'reference_id', 'notes', 'user_id'];

    protected $casts = ['quantity' => 'integer', 'stock_after' => 'integer'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
