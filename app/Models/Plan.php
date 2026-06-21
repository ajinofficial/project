<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['name', 'monthly_price', 'features', 'store_limit', 'user_limit'];
}
