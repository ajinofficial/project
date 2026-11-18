<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = ['tenant_id', 'product_id', 'type', 'quantity', 'stock_after', 'reference_type', 'reference_id', 'notes', 'user_id', 'created_at', 'updated_at'];

    protected $casts = ['quantity' => 'integer', 'stock_after' => 'integer'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getDisplayNotesAttribute(): ?string
    {
        $notes = $this->notes;
        if (! $notes) {
            return $notes;
        }
        if (preg_match('/^Parts consumed by service #(\d+)$/D', $notes, $match)) {
            return __('common.service_parts_note', ['id' => $match[1]]);
        }
        // Translate only the system-generated suffix; preserve custom notes verbatim.
        if (preg_match('/^(.*) Purchase price: ([\d,.]+)\.(?: Profit: ([\d,.]+)%\. Selling price: ([\d,.]+)\.| Profit amount per unit: ([\d,.]+)\. Selling price: ([\d,.]+)\.)?$/usD', $notes, $match)) {
            $prefix = $match[1] === 'Manual stock adjustment.' ? __($match[1]) : $match[1];
            $text = $prefix.' '.__('common.stock_price_note', ['price' => $match[2]]);
            if (! empty($match[3])) {
                $text .= ' '.__('common.stock_profit_note', ['profit' => $match[3], 'price' => $match[4]]);
            } elseif (! empty($match[5])) {
                $text .= ' '.__('common.stock_profit_amount_note', ['profit' => $match[5], 'price' => $match[6]]);
            }
            return $text;
        }
        return in_array($notes, ['Manual stock adjustment.', 'Invoice generated.'], true) ? __($notes) : $notes;
    }
}
