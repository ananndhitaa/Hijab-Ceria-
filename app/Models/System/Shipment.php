<?php

namespace App\Models\System;

use App\Enums\ShipmentStatus;
use App\Models\Sales\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'tracking_no',
        'courier',
        'courier_service',
        'shipping_cost',
        'recipient_address',
        'shipped_at',
        'delivered_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'status'        => ShipmentStatus::class,
        'shipped_at'    => 'datetime',
        'delivered_at'  => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
