<?php

namespace App\Models\Platform;

use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerComplaintReply extends Model
{
    protected $fillable = [
        'complaint_id',
        'user_id',
        'is_from_super_admin',
        'message',
        'attachment_path',
    ];

    protected $casts = [
        'is_from_super_admin' => 'boolean',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(OwnerComplaint::class, 'complaint_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

