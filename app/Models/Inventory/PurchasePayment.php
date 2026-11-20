<?php

namespace App\Models\Inventory;

use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{
    protected $fillable = [
        'purchase_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_no',
        'notes',
        'transfer_proof_path',
        'created_by',
    ];

    public function hasTransferProof(): bool
    {
        return ! empty($this->transfer_proof_path);
    }

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Parent-derived outlet ownership (follows parent Purchase).
     */
    public function getOutletAttribute(): ?\App\Models\System\Outlet
    {
        return $this->purchase?->outlet;
    }

    /**
     * Scope payments to a specific legitimate outlet.
     */
    public function scopeForOutlet(\Illuminate\Database\Eloquent\Builder $query, int $outletId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereHas('purchase', fn ($q) => $q->where('purchases.outlet_id', $outletId));
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
