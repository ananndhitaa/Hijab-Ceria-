<?php

namespace App\Models\Platform;

use App\Models\Master\Customer;
use App\Models\Sales\Order;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReview extends Model
{
    protected $fillable = [
        'order_id',
        'customer_id',
        'outlet_id',
        'user_id',
        'customer_name',
        'customer_role',
        'customer_city',
        'rating',
        'review_text',
        'avatar_path',
        'is_featured',
        'is_approved',
    ];

    protected $casts = [
        'rating'      => 'integer',
        'is_featured' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

