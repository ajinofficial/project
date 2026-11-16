<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    public const STATUSES = ['requested', 'in_progress', 'completed'];

    protected $fillable = ['tenant_id', 'customer_id', 'technician_id', 'service_type', 'description', 'due_date', 'status', 'charge', 'paid_amount', 'completed_at'];
    protected $casts = ['charge' => 'decimal:2', 'paid_amount' => 'decimal:2', 'due_date' => 'date', 'completed_at' => 'datetime'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function costs() { return $this->hasMany(ServiceCost::class); }
    public function getTotalCostAttribute(): float { return round($this->costs->sum(fn ($cost) => $cost->quantity * $cost->unit_cost), 2); }
    public function getProfitAttribute(): float { return round($this->charge - $this->total_cost, 2); }
}
