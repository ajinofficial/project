<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = ['name', 'monthly_price', 'features', 'store_limit', 'user_limit'];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
