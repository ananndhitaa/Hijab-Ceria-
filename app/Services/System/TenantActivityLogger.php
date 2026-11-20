<?php

namespace App\Services\System;

use App\Models\System\OutletActivityLog;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class TenantActivityLogger
{
    /**
     * Keys to automatically redact from audit payloads.
     */
    protected const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'remember_token',
        'secret',
        'api_key',
        'auth_token',
    ];

    /**
     * Record a tenant-scoped activity log entry.
     *
     * @param int $outletId
     * @param User|int|null $user
     * @param string $module E.g. 'SALES_ORDER', 'BATCH_PRODUKSI', 'PRODUKSI', 'QC', 'TENANT'
     * @param string $actionType E.g. 'CREATE_ORDER', 'CREATE_BATCH', 'ASSIGN_PIC', 'REASSIGN_PIC', 'START_BATCH', 'RECORD_PRODUCTION', 'RECORD_QC', 'COMPLETE_BATCH'
     * @param string $description
     * @param string|null $referenceNo
     * @param Model|null $subject
     * @param array|null $beforeValues
     * @param array|null $afterValues
     * @return OutletActivityLog
     */
    public static function log(
        ?int $outletId,
        User|int|null $user,
        string $module,
        string $actionType,
        string $description,
        ?string $referenceNo = null,
        ?Model $subject = null,
        ?array $beforeValues = null,
        ?array $afterValues = null
    ): OutletActivityLog {
        $actor = $user instanceof User ? $user : ($user ? User::find($user) : auth()->user());

        $userId = $actor?->id;
        $userName = $actor?->name ?? 'Sistem';
        $userRole = $actor?->role instanceof \BackedEnum ? $actor->role->value : ($actor?->role ?? 'SYSTEM');

        // Resolve valid outlet_id
        $finalOutletId = ($outletId && $outletId > 0) ? $outletId : ($actor?->outlet_id ?: null);
        if ($finalOutletId && !\App\Models\System\Outlet::where('id', $finalOutletId)->exists()) {
            $finalOutletId = null;
        }

        $cleanBefore = $beforeValues ? self::sanitizePayload($beforeValues) : null;
        $cleanAfter  = $afterValues ? self::sanitizePayload($afterValues) : null;

        return OutletActivityLog::create([
            'outlet_id'     => $finalOutletId,
            'user_id'       => $userId,
            'user_name'     => $userName,
            'user_role'     => $userRole,
            'module'        => strtoupper($module),
            'action_type'   => strtoupper($actionType),
            'reference_no'  => $referenceNo,
            'subject_type'  => $subject ? get_class($subject) : null,
            'subject_id'    => $subject?->getKey(),
            'description'   => $description,
            'before_values' => $cleanBefore,
            'after_values'  => $cleanAfter,
        ]);
    }

    /**
     * Recursively strip sensitive keys from payload array.
     */
    protected static function sanitizePayload(array $payload): array
    {
        $sanitized = [];
        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizePayload($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
