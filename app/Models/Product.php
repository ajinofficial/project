<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
        'translations',
    ];

    protected $casts = [
        'translations' => 'array',
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

    /** Display text is separate from canonical attributes used in forms and business logic. */
    public function localized(string $field, ?string $locale = null): ?string
    {
        if (! in_array($field, ['name', 'category', 'description'], true)) {
            throw new \InvalidArgumentException('Unsupported translatable product field.');
        }
        $locale ??= app()->getLocale();
        if (! \App\Support\LocalePreference::supported($locale)) {
            $locale = config('app.fallback_locale', 'en');
        }
        foreach (array_unique([$locale, config('app.fallback_locale', 'en')]) as $language) {
            $value = $this->translations[$language][$field] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }
        return $this->getAttribute($field);
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

    public function getImageUrlAttribute(?string $value): ?string
    {
        if (! $value || Str::startsWith($value, ['//', 'data:'])) {
            return $value;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            $path = parse_url($value, PHP_URL_PATH);

            // Older uploads were stored with the APP_URL host. Rebuild local
            // upload URLs for the current host so previews survive a move.
            return is_string($path) && Str::startsWith($path, '/uploads/')
                ? asset(ltrim($path, '/'))
                : $value;
        }

        return asset(ltrim($value, '/'));
    }
}
