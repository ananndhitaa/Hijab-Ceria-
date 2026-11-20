<?php

namespace App\Models\Platform;

use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRegistrationHistory extends Model
{
    protected $fillable = [
        'tenant_registration_id',
        'action',
        'from_status',
        'to_status',
        'notes',
        'actor_id',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(TenantRegistration::class, 'tenant_registration_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'SUBMIT'           => 'Pendaftaran Diajukan',
            'REQUEST_REVISION' => 'Permintaan Revisi',
            'RESUBMIT'         => 'Revisi Diajukan Kembali',
            'APPROVE'          => 'Pendaftaran Disetujui',
            'REJECT'           => 'Pendaftaran Ditolak',
            default            => $this->action,
        };
    }
}
