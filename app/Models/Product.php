<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'sku',
        'barcode',
        'category',
        'brand',
        'supplier',
        'purchase_price',
        'price',
        'compare_at_price',
        'tax_percentage',
        'inventory',
        'minimum_stock_level',
        'reserved_stock',
        'damaged_stock',
        'returned_stock',
        'status',
        'image_url',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'inventory' => 'integer',
        'minimum_stock_level' => 'integer',
        'reserved_stock' => 'integer',
        'damaged_stock' => 'integer',
        'returned_stock' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->inventory - $this->reserved_stock - $this->damaged_stock);
    }
}
