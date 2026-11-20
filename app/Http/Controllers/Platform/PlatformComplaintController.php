<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\OwnerComplaint;
use App\Models\Platform\OwnerComplaintReply;
use App\Models\Platform\TenantRegistrationHistory;
use App\Services\Platform\PlatformNotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformComplaintController extends Controller
{
    public function __construct(
        protected PlatformNotificationService $notificationService
    ) {}

    /**
     * Display complaints list for Super Admin.
     */
    public function index(Request $request): View
    {
        // 4 KPI Summary Cards based strictly on database records
        $stats = [
            'new'           => OwnerComplaint::where('status', OwnerComplaint::STATUS_NEW)->count(),
            'in_progress'   => OwnerComplaint::where('status', OwnerComplaint::STATUS_IN_PROGRESS)->count(),
            'waiting_owner' => OwnerComplaint::where('status', OwnerComplaint::STATUS_WAITING_OWNER)->count(),
            'resolved'      => OwnerComplaint::whereIn('status', [OwnerComplaint::STATUS_RESOLVED, OwnerComplaint::STATUS_CLOSED])->count(),
        ];

        $query = OwnerComplaint::with(['outlet', 'user'])->latest('id');

        // Search by complaint ID, tenant outlet name, or title
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('complaint_code', 'LIKE', "%{$search}%")
                  ->orWhere('title', 'LIKE', "%{$search}%")
                  ->orWhereHas('outlet', function ($outletQ) use ($search) {
                      $outletQ->where('outlet_name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($userQ) use ($search) {
                      $userQ->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter by category
        if ($category = $request->query('category')) {
            if ($category !== 'all') {
                $query->where('category', $category);
            }
        }

        // Filter by status
        if ($status = $request->query('status')) {
            if ($status === 'resolved_all') {
                $query->whereIn('status', [OwnerComplaint::STATUS_RESOLVED, OwnerComplaint::STATUS_CLOSED]);
            } elseif ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Filter by period
        if ($period = $request->query('period')) {
            if ($period === 'today') {
                $query->whereDate('created_at', Carbon::today());
            } elseif ($period === 'week') {
                $query->where('created_at', '>=', Carbon::now()->subDays(7));
            } elseif ($period === 'month') {
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
            }
        }

        $complaints = $query->paginate(12)->withQueryString();

        return view('platform.complaints.index', compact('complaints', 'stats'));
    }

    /**
     * Show complaint detail and conversation thread.
     */
    public function show(OwnerComplaint $complaint): View
    {
        $complaint->load(['outlet', 'user', 'replies.user']);

        return view('platform.complaints.show', compact('complaint'));
    }

    /**
     * Reply to owner complaint.
     */
    public function reply(Request $request, OwnerComplaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'message'    => ['required', 'string', 'max:5000'],
            'status'     => ['nullable', 'string', 'in:NEW,IN_PROGRESS,WAITING_OWNER,RESOLVED,CLOSED'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('complaints', 'public');
        }

        OwnerComplaintReply::create([
            'complaint_id'        => $complaint->id,
            'user_id'             => auth()->id(),
            'is_from_super_admin' => true,
            'message'             => $validated['message'],
            'attachment_path'     => $attachmentPath,
        ]);

        $newStatus = $validated['status'] ?? OwnerComplaint::STATUS_WAITING_OWNER;
        $updates = [
            'status'        => $newStatus,
            'last_reply_at' => now(),
        ];

        if ($newStatus === OwnerComplaint::STATUS_RESOLVED && ! $complaint->resolved_at) {
            $updates['resolved_at'] = now();
        } elseif ($newStatus === OwnerComplaint::STATUS_CLOSED && ! $complaint->closed_at) {
            $updates['closed_at'] = now();
        }

        $complaint->update($updates);

        // Notify Owner tenant about new reply
        $this->notificationService->notify(
            $complaint->outlet_id,
            $complaint->user_id,
            'COMPLAINT_REPLY',
            "Tanggapan Baru untuk Keluhan #{$complaint->complaint_code}",
            "Super Admin telah membalas pengaduan Anda: \"{$complaint->title}\". Status saat ini: {$complaint->statusLabel()}.",
            route('owner.complaints.show', $complaint)
        );

        return redirect()->route('platform.complaints.show', $complaint)
            ->with('success', 'Balasan berhasil dikirim kepada Owner dan status keluhan telah diperbarui.');
    }

    /**
     * Update complaint status directly.
     */
    public function updateStatus(Request $request, OwnerComplaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:NEW,IN_PROGRESS,WAITING_OWNER,RESOLVED,CLOSED'],
            'notes'  => ['nullable', 'string', 'max:500'],
        ]);

        $oldStatus = $complaint->status;
        $newStatus = $validated['status'];

        $updates = ['status' => $newStatus];
        if ($newStatus === OwnerComplaint::STATUS_RESOLVED && ! $complaint->resolved_at) {
            $updates['resolved_at'] = now();
        } elseif ($newStatus === OwnerComplaint::STATUS_CLOSED && ! $complaint->closed_at) {
            $updates['closed_at'] = now();
        }

        $complaint->update($updates);

        // Notify Owner
        $this->notificationService->notify(
            $complaint->outlet_id,
            $complaint->user_id,
            'COMPLAINT_STATUS',
            "Pembaruan Status Keluhan #{$complaint->complaint_code}",
            "Status pengaduan \"{$complaint->title}\" telah diubah menjadi: {$complaint->statusLabel()}." . (!empty($validated['notes']) ? " Catatan: {$validated['notes']}" : ''),
            route('owner.complaints.show', $complaint)
        );

        return redirect()->route('platform.complaints.show', $complaint)
            ->with('success', "Status keluhan berhasil diubah menjadi: {$complaint->statusLabel()}.");
    }
}

