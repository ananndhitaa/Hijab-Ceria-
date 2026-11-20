<?php

namespace App\Http\Controllers\Platform;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Platform\TenantRegistration;
use App\Models\Platform\TenantRegistrationHistory;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantRegistrationReviewController extends Controller
{
    /**
     * Display registration review queue.
     */
    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'pending');
        $search = $request->input('search');

        $query = TenantRegistration::with(['reviewer', 'outlet'])->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_wa', 'like', "%{$search}%");
            });
        }

        if ($tab === 'pending') {
            $query->where('status', TenantRegistration::STATUS_PENDING);
        } elseif ($tab === 'needs_revision') {
            $query->where('status', TenantRegistration::STATUS_NEEDS_REVISION);
        } elseif ($tab === 'approved') {
            $query->where('status', TenantRegistration::STATUS_APPROVED);
        } elseif ($tab === 'rejected') {
            $query->where('status', TenantRegistration::STATUS_REJECTED);
        }

        $registrations = $query->paginate(15)->withQueryString();

        $counts = [
            'pending'        => TenantRegistration::where('status', TenantRegistration::STATUS_PENDING)->count(),
            'needs_revision' => TenantRegistration::where('status', TenantRegistration::STATUS_NEEDS_REVISION)->count(),
            'approved'       => TenantRegistration::where('status', TenantRegistration::STATUS_APPROVED)->count(),
            'rejected'       => TenantRegistration::where('status', TenantRegistration::STATUS_REJECTED)->count(),
            'all'            => TenantRegistration::count(),
        ];

        $todayCounts = [
            'pending'        => TenantRegistration::where('status', TenantRegistration::STATUS_PENDING)->whereDate('created_at', today())->count(),
            'needs_revision' => TenantRegistration::where('status', TenantRegistration::STATUS_NEEDS_REVISION)->whereDate('updated_at', today())->count(),
            'approved'       => TenantRegistration::where('status', TenantRegistration::STATUS_APPROVED)->whereDate('reviewed_at', today())->count(),
        ];

        return view('platform.registrations.index', compact('registrations', 'tab', 'counts', 'search', 'todayCounts'));
    }

    /**
     * Inspect single tenant registration details.
     */
    public function show(TenantRegistration $registration): View
    {
        $registration->load(['histories.actor', 'reviewer', 'outlet', 'ownerUser']);

        return view('platform.registrations.show', compact('registration'));
    }

    /**
     * Approve registration, provision Outlet, and create OWNER account.
     * Guaranteed idempotent and atomic.
     */
    public function approve(Request $request, TenantRegistration $registration): RedirectResponse
    {
        // 1. Idempotency guard: If already approved, do not re-create outlet or owner
        if ($registration->isApproved()) {
            return redirect()->route('platform.registrations.show', $registration)
                ->with('info', 'Pendaftaran ini sudah disetujui sebelumnya.');
        }

        DB::transaction(function () use ($registration, $request) {
            // Lock registration row
            $lockedReg = TenantRegistration::where('id', $registration->id)->lockForUpdate()->first();

            if ($lockedReg->isApproved()) {
                return;
            }

            // A. Generate unique outlet code and slug
            $baseCode = 'OUT-' . strtoupper(Str::random(4));
            $counter = 1;
            $code = $baseCode;
            while (Outlet::where('outlet_code', $code)->exists()) {
                $code = $baseCode . '-' . $counter++;
            }

            $baseSlug = Str::slug($lockedReg->company_name);
            $slug = $baseSlug;
            $sCounter = 1;
            while (Outlet::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $sCounter++;
            }

            // B. Create or retrieve Outlet
            $outlet = Outlet::create([
                'outlet_name'                => $lockedReg->company_name,
                'outlet_code'                => $code,
                'slug'                       => $slug,
                'phone_wa'                   => $lockedReg->phone_wa,
                'address'                    => $lockedReg->address,
                'description'                => $lockedReg->notes,
                'status'                     => 'ACTIVE',
                'is_public_showcase_enabled' => true,
            ]);

            // C. Create or activate OWNER account
            /** @var User|null $existingUser */
            $existingUser = User::where('email', $lockedReg->email)->first();

            if ($existingUser) {
                $existingUser->update([
                    'name'      => $lockedReg->owner_name,
                    'password'  => $lockedReg->password,
                    'role'      => UserRole::OWNER,
                    'status'    => UserStatus::ACTIVE,
                    'outlet_id' => $outlet->id,
                ]);
                $owner = $existingUser;
            } else {
                $owner = User::create([
                    'name'      => $lockedReg->owner_name,
                    'email'     => $lockedReg->email,
                    'password'  => $lockedReg->password,
                    'role'      => UserRole::OWNER,
                    'status'    => UserStatus::ACTIVE,
                    'outlet_id' => $outlet->id,
                ]);
            }

            // D. Update Registration record
            $fromStatus = $lockedReg->status;
            $lockedReg->update([
                'status'           => TenantRegistration::STATUS_APPROVED,
                'outlet_id'        => $outlet->id,
                'owner_user_id'    => $owner->id,
                'reviewed_by'      => auth()->id(),
                'reviewed_at'      => now(),
                'rejection_reason' => null,
            ]);

            // E. Record Audit History
            TenantRegistrationHistory::create([
                'tenant_registration_id' => $lockedReg->id,
                'action'                 => 'APPROVE',
                'from_status'            => $fromStatus,
                'to_status'              => TenantRegistration::STATUS_APPROVED,
                'notes'                  => 'Pendaftaran disetujui oleh Super Admin. Outlet (' . $outlet->outlet_code . ') dan akun Owner (' . $owner->email . ') berhasil diaktifkan.',
                'actor_id'               => auth()->id(),
            ]);
        });

        return redirect()->route('platform.registrations.show', $registration)
            ->with('success', "Pendaftaran {$registration->company_name} berhasil disetujui. Akun Owner dan outlet ERP aktif.");
    }

    /**
     * Reject tenant registration with required reason.
     */
    public function reject(Request $request, TenantRegistration $registration): RedirectResponse
    {
        if ($registration->isApproved()) {
            return redirect()->back()->with('error', 'Pendaftaran yang sudah disetujui tidak dapat ditolak.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min'      => 'Alasan penolakan minimal 10 karakter.',
        ]);

        DB::transaction(function () use ($registration, $validated) {
            $fromStatus = $registration->status;

            $registration->update([
                'status'           => TenantRegistration::STATUS_REJECTED,
                'rejection_reason' => strip_tags(trim($validated['rejection_reason'])),
                'reviewed_by'      => auth()->id(),
                'reviewed_at'      => now(),
            ]);

            TenantRegistrationHistory::create([
                'tenant_registration_id' => $registration->id,
                'action'                 => 'REJECT',
                'from_status'            => $fromStatus,
                'to_status'              => TenantRegistration::STATUS_REJECTED,
                'notes'                  => 'Pendaftaran ditolak: ' . strip_tags(trim($validated['rejection_reason'])),
                'actor_id'               => auth()->id(),
            ]);
        });

        return redirect()->route('platform.registrations.show', $registration)
            ->with('success', 'Pendaftaran telah ditolak.');
    }

    /**
     * Request revision / correction from applicant.
     */
    public function requestRevision(Request $request, TenantRegistration $registration): RedirectResponse
    {
        if ($registration->isApproved()) {
            return redirect()->back()->with('error', 'Pendaftaran yang sudah disetujui tidak dapat diminta revisi.');
        }

        $validated = $request->validate([
            'revision_notes' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'revision_notes.required' => 'Catatan revisi wajib diisi.',
            'revision_notes.min'      => 'Catatan revisi minimal 10 karakter.',
        ]);

        DB::transaction(function () use ($registration, $validated) {
            $fromStatus = $registration->status;

            $registration->update([
                'status'         => TenantRegistration::STATUS_NEEDS_REVISION,
                'revision_notes' => strip_tags(trim($validated['revision_notes'])),
                'reviewed_by'    => auth()->id(),
                'reviewed_at'    => now(),
            ]);

            TenantRegistrationHistory::create([
                'tenant_registration_id' => $registration->id,
                'action'                 => 'REQUEST_REVISION',
                'from_status'            => $fromStatus,
                'to_status'              => TenantRegistration::STATUS_NEEDS_REVISION,
                'notes'                  => 'Super Admin meminta perbaikan data: ' . strip_tags(trim($validated['revision_notes'])),
                'actor_id'               => auth()->id(),
            ]);
        });

        return redirect()->route('platform.registrations.show', $registration)
            ->with('success', 'Permintaan revisi telah dikirim ke pemohon.');
    }
}
