<?php

namespace App\Services\System;

use App\Models\System\DocumentSequence;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OverflowException;

class DocumentNumberGenerator
{
    /**
     * Generate a concurrency-safe document number with format PREFIX-YYYYMM-XXXX.
     * Uses Asia/Jakarta timezone for current period calculation.
     *
     * @param string $prefix
     * @param \DateTimeInterface|null $date
     * @param int|null $explicitOutletId
     * @return string
     * @throws OverflowException
     * @throws AuthorizationException
     */
    public function generate(string $prefix, ?\DateTimeInterface $date = null, ?int $explicitOutletId = null): string
    {
        $now = $date ? Carbon::instance($date)->setTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');
        $period = $now->format('Ym');

        $isTenantIsolationEnabled = config('nayara.tenant_isolation_enabled', false);
        $outletId = null;

        if ($explicitOutletId !== null) {
            $outletId = $explicitOutletId;
        } elseif ($isTenantIsolationEnabled) {
            $outletId = app(TenantContext::class)->currentOutletId();

            if ($outletId === null) {
                throw new AuthorizationException('Gagal menghasilkan nomor dokumen: Tenant tidak terautentikasi atau tidak valid.');
            }
        }

        return DB::transaction(function () use ($prefix, $period, $outletId) {
            $insertData = [
                'prefix'        => $prefix,
                'period'        => $period,
                'last_sequence' => 0,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            if ($outletId !== null || Schema::hasColumn('document_sequences', 'outlet_id')) {
                $insertData['outlet_id'] = $outletId;
            }

            // Ensure the record exists (ignore if another transaction inserted concurrently)
            DB::table('document_sequences')->insertOrIgnore($insertData);

            // Acquire exclusive row-level lock scoped to tenant (or legacy NULL)
            $seqQuery = DocumentSequence::where('prefix', $prefix)
                ->where('period', $period);

            if ($outletId !== null) {
                $seqQuery->where('outlet_id', $outletId);
            } elseif (Schema::hasColumn('document_sequences', 'outlet_id')) {
                $seqQuery->whereNull('outlet_id');
            }

            /** @var DocumentSequence $seqRecord */
            $seqRecord = $seqQuery->lockForUpdate()->firstOrFail();

            if ($seqRecord->last_sequence >= 9999) {
                throw new OverflowException("Nomor urut sequence dokumen untuk prefix {$prefix} periode {$period} telah melebihi batas maksimal (9999).");
            }

            $seqRecord->last_sequence += 1;
            $seqRecord->save();

            return sprintf('%s-%s-%04d', $prefix, $period, $seqRecord->last_sequence);
        });
    }
}
