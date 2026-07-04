<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    public const TYPE_VENDOR = 1;
    public const TYPE_CLIENT = 2;

    public const CATEGORY_RETAIL = 1;
    public const CATEGORY_MOBILE = 2;
    public const CATEGORY_PHARMACY = 3;
    public const CATEGORY_HARDWARE = 4;
    public const CATEGORY_GROCERY = 5;
    public const CATEGORY_APPAREL = 6;
    public const CATEGORY_ELECTRONICS = 7;
    public const CATEGORY_RESTAURANT = 8;
    public const CATEGORY_OTHER = 9;

    public const BUSINESS_CATEGORIES = [
        self::CATEGORY_RETAIL => 'Retail',
        self::CATEGORY_MOBILE => 'Mobile',
        self::CATEGORY_PHARMACY => 'Pharmacy',
        self::CATEGORY_HARDWARE => 'Hardware',
        self::CATEGORY_GROCERY => 'Grocery',
        self::CATEGORY_APPAREL => 'Apparel',
        self::CATEGORY_ELECTRONICS => 'Electronics',
        self::CATEGORY_RESTAURANT => 'Restaurant',
        self::CATEGORY_OTHER => 'Other',
    ];

    protected $fillable = [
        'plan_id',
        'tenant_type',
        'business_name',
        'owner_name',
        'mobile',
        'email',
        'gst_number',
        'business_category',
        'store_address',
        'currency',
        'default_tax_percentage',
        'low_stock_threshold',
        'invoice_prefix',
        'domain_expired_date',
        'role_permissions',
    ];

    protected $casts = [
        'default_tax_percentage' => 'decimal:2',
        'business_category' => 'integer',
        'low_stock_threshold' => 'integer',
        'tenant_type' => 'integer',
        'domain_expired_date' => 'date',
        'role_permissions' => 'array',
    ];

    public function getBusinessCategoryLabelAttribute(): string
    {
        return self::BUSINESS_CATEGORIES[$this->business_category] ?? 'Other';
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
