<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    public const CATEGORIES = [
        'Rent', 'Utilities', 'Salary', 'Transport', 'Marketing',
        'Maintenance', 'Office supplies', 'Taxes', 'Other',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'upi' => 'UPI',
        'card' => 'Card',
        'bank_transfer' => 'Bank transfer',
        'cheque' => 'Cheque',
    ];

    protected $fillable = [
        'tenant_id', 'created_by', 'title', 'category', 'amount',
        'expense_date', 'payment_method', 'reference_number', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst(str_replace('_', ' ', $this->payment_method));
    }
}
