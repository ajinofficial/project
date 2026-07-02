<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_OWNER = 1;
    public const ROLE_MANAGER = 2;
    public const ROLE_SALES_STAFF = 3;
    public const ROLE_WAREHOUSE_STAFF = 4;
    public const ROLE_ACCOUNTANT = 5;

    public const ROLES = [
        self::ROLE_OWNER => 'Owner',
        self::ROLE_MANAGER => 'Manager',
        self::ROLE_SALES_STAFF => 'Sales staff',
        self::ROLE_WAREHOUSE_STAFF => 'Warehouse staff',
        self::ROLE_ACCOUNTANT => 'Accountant',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'company_name',
        'store_url',
        'phone',
        'country_code',
        'plan',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'plan' => 'integer',
        'role' => 'integer',
        'password' => 'hashed',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getPlanLabelAttribute(): string
    {
        $plan = Plan::find($this->plan);

        return $plan ? ucfirst($plan->name) : 'Starter';
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? 'Owner';
    }
}
