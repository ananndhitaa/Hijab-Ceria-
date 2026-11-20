<?php

namespace App\Models\Finance;

use App\Models\Sales\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFinancial extends Model
{
    protected $fillable = [
        'order_id',
        'revenue_amount',
        'hpp_amount',
        'hpp_per_good_pcs',
        'gross_profit',
        'gross_margin',
        'net_profit',
        'net_margin',
        'roi_percent',
        'marketing_spend',
        'mer_value',
        'calculated_at',
    ];

    protected $casts = [
        'revenue_amount'   => 'decimal:2',
        'hpp_amount'       => 'decimal:2',
        'hpp_per_good_pcs' => 'decimal:4',
        'gross_profit'     => 'decimal:2',
        'gross_margin'     => 'decimal:4',
        'net_profit'       => 'decimal:2',
        'net_margin'       => 'decimal:4',
        'roi_percent'      => 'decimal:4',
        'marketing_spend'  => 'decimal:2',
        'mer_value'        => 'decimal:4',
        'calculated_at'    => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
