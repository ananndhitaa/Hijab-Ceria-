<?php

namespace App\Models\Platform;

use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OwnerComplaint extends Model
{
    public const CATEGORY_ERP_SYSTEM      = 'ERP_SYSTEM';
    public const CATEGORY_ACCOUNT_ACCESS  = 'ACCOUNT_ACCESS';
    public const CATEGORY_BILLING_PAYMENT = 'BILLING_PAYMENT';
    public const CATEGORY_SUBSCRIPTION    = 'SUBSCRIPTION';
    public const CATEGORY_ADS_PROMOTION   = 'ADS_PROMOTION';
    public const CATEGORY_SETUP_TRAINING  = 'SETUP_TRAINING';
    public const CATEGORY_OTHER           = 'OTHER';

    public const STATUS_NEW           = 'NEW';
    public const STATUS_IN_PROGRESS   = 'IN_PROGRESS';
    public const STATUS_WAITING_OWNER = 'WAITING_OWNER';
    public const STATUS_RESOLVED      = 'RESOLVED';
    public const STATUS_CLOSED        = 'CLOSED';

    public const PRIORITY_NORMAL = 'NORMAL';
    public const PRIORITY_HIGH   = 'HIGH';
    public const PRIORITY_URGENT = 'URGENT';

    protected $fillable = [
        'complaint_code',
        'outlet_id',
        'user_id',
        'category',
        'title',
        'description',
        'attachment_path',
        'status',
        'priority',
        'resolved_at',
        'closed_at',
        'last_reply_at',
    ];

    protected $casts = [
        'resolved_at'   => 'datetime',
        'closed_at'     => 'datetime',
        'last_reply_at' => 'datetime',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(OwnerComplaintReply::class, 'complaint_id')->orderBy('created_at', 'asc');
    }

    /**
     * Generate unique complaint tracking code: KLH-YYYYMM-XXXX
     */
    public static function generateCode(): string
    {
        $prefix = 'KLH-' . date('Ym') . '-';
        $latest = static::where('complaint_code', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($latest && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $latest->complaint_code, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Human-friendly label for category
     */
    public function categoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_ERP_SYSTEM      => 'Kendala Sistem ERP',
            self::CATEGORY_ACCOUNT_ACCESS  => 'Akun & Akses',
            self::CATEGORY_BILLING_PAYMENT => 'Tagihan & Pembayaran',
            self::CATEGORY_SUBSCRIPTION    => 'Paket Langganan',
            self::CATEGORY_ADS_PROMOTION   => 'Iklan & Promosi',
            self::CATEGORY_SETUP_TRAINING  => 'Setup & Pelatihan',
            default                        => 'Lainnya',
        };
    }

    /**
     * Human-friendly label for status
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_NEW           => 'Baru Masuk',
            self::STATUS_IN_PROGRESS   => 'Sedang Diproses',
            self::STATUS_WAITING_OWNER => 'Menunggu Balasan Owner',
            self::STATUS_RESOLVED      => 'Selesai',
            self::STATUS_CLOSED        => 'Ditutup',
            default                    => $this->status,
        };
    }

    /**
     * Badge CSS classes for status
     */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_NEW           => 'bg-rose-50 text-rose-700 border-rose-200',
            self::STATUS_IN_PROGRESS   => 'bg-amber-50 text-amber-700 border-amber-200',
            self::STATUS_WAITING_OWNER => 'bg-purple-50 text-purple-700 border-purple-200',
            self::STATUS_RESOLVED      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::STATUS_CLOSED        => 'bg-gray-100 text-gray-700 border-gray-200',
            default                    => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }
}

