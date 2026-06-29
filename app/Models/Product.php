<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'supplier_id',
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
        'deleted_status',
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
        'deleted_status' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('not_deleted', function (Builder $builder) {
            $builder->where('deleted_status', 0);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplierRecord(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->inventory - $this->reserved_stock - $this->damaged_stock);
    }
}
