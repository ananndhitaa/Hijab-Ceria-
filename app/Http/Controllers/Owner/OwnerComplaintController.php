<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Platform\OwnerComplaint;
use App\Models\Platform\OwnerComplaintReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerComplaintController extends Controller
{
    /**
     * Display complaints list for the authenticated tenant Owner.
     */
    public function index(Request $request): View
    {
        $outletId = auth()->user()->outlet_id;

        $pageTitle = 'Keluhan & Pusat Bantuan';
        $breadcrumb = 'Executive Monitoring > Keluhan & Pusat Bantuan';

        // Stats calculated strictly from this tenant's actual records
        $stats = [
            'total'         => OwnerComplaint::where('outlet_id', $outletId)->count(),
            'in_progress'   => OwnerComplaint::where('outlet_id', $outletId)->whereIn('status', [OwnerComplaint::STATUS_NEW, OwnerComplaint::STATUS_IN_PROGRESS])->count(),
            'waiting_owner' => OwnerComplaint::where('outlet_id', $outletId)->where('status', OwnerComplaint::STATUS_WAITING_OWNER)->count(),
            'resolved'      => OwnerComplaint::where('outlet_id', $outletId)->whereIn('status', [OwnerComplaint::STATUS_RESOLVED, OwnerComplaint::STATUS_CLOSED])->count(),
        ];

        $query = OwnerComplaint::where('outlet_id', $outletId)->latest('id');

        if ($status = $request->query('status')) {
            if ($status === 'resolved_all') {
                $query->whereIn('status', [OwnerComplaint::STATUS_RESOLVED, OwnerComplaint::STATUS_CLOSED]);
            } elseif ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($category = $request->query('category')) {
            if ($category !== 'all') {
                $query->where('category', $category);
            }
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('complaint_code', 'LIKE', "%{$search}%")
                  ->orWhere('title', 'LIKE', "%{$search}%");
            });
        }

        $complaints = $query->paginate(10)->withQueryString();

        return view('owner.complaints.index', compact('complaints', 'stats', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Show form to create new complaint.
     */
    public function create(): View
    {
        $pageTitle = 'Buat Keluhan Baru';
        $breadcrumb = 'Executive Monitoring > Keluhan & Bantuan > Buat Keluhan';

        $categories = [
            OwnerComplaint::CATEGORY_ERP_SYSTEM      => 'Kendala Sistem ERP',
            OwnerComplaint::CATEGORY_ACCOUNT_ACCESS  => 'Akun & Akses',
            OwnerComplaint::CATEGORY_BILLING_PAYMENT => 'Tagihan & Pembayaran',
            OwnerComplaint::CATEGORY_SUBSCRIPTION    => 'Paket Langganan',
            OwnerComplaint::CATEGORY_ADS_PROMOTION   => 'Iklan & Promosi',
            OwnerComplaint::CATEGORY_SETUP_TRAINING  => 'Setup & Pelatihan',
            OwnerComplaint::CATEGORY_OTHER           => 'Lainnya',
        ];

        return view('owner.complaints.create', compact('pageTitle', 'breadcrumb', 'categories'));
    }

    /**
     * Store newly created complaint.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category'    => ['required', 'string', 'in:ERP_SYSTEM,ACCOUNT_ACCESS,BILLING_PAYMENT,SUBSCRIPTION,ADS_PROMOTION,SETUP_TRAINING,OTHER'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'attachment'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $outletId = auth()->user()->outlet_id;

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('complaints', 'public');
        }

        $complaint = OwnerComplaint::create([
            'complaint_code'  => OwnerComplaint::generateCode(),
            'outlet_id'       => $outletId,
            'user_id'         => auth()->id(),
            'category'        => $validated['category'],
            'title'           => $validated['title'],
            'description'     => $validated['description'],
            'attachment_path' => $attachmentPath,
            'status'          => OwnerComplaint::STATUS_NEW,
            'priority'        => OwnerComplaint::PRIORITY_NORMAL,
        ]);

        return redirect()->route('owner.complaints.show', $complaint)
            ->with('success', "Keluhan #{$complaint->complaint_code} berhasil diajukan dan sedang menunggu tanggapan Super Admin.");
    }

    /**
     * Show complaint detail and conversation.
     */
    public function show(OwnerComplaint $complaint): View
    {
        $outletId = auth()->user()->outlet_id;

        // Strict Tenant Isolation Enforcement
        if ($complaint->outlet_id !== $outletId) {
            abort(403, 'Akses tidak diizinkan. Anda hanya dapat melihat keluhan milik perusahaan konveksi Anda.');
        }

        $complaint->load(['replies.user']);

        $pageTitle = "Keluhan #{$complaint->complaint_code}";
        $breadcrumb = "Executive Monitoring > Keluhan & Bantuan > Detail #{$complaint->complaint_code}";

        return view('owner.complaints.show', compact('complaint', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Owner reply to complaint.
     */
    public function reply(Request $request, OwnerComplaint $complaint): RedirectResponse
    {
        $outletId = auth()->user()->outlet_id;

        // Strict Tenant Isolation Enforcement
        if ($complaint->outlet_id !== $outletId) {
            abort(403, 'Akses tidak diizinkan. Anda hanya dapat membalas keluhan milik perusahaan konveksi Anda.');
        }

        $validated = $request->validate([
            'message'    => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('complaints', 'public');
        }

        OwnerComplaintReply::create([
            'complaint_id'        => $complaint->id,
            'user_id'             => auth()->id(),
            'is_from_super_admin' => false,
            'message'             => $validated['message'],
            'attachment_path'     => $attachmentPath,
        ]);

        // When Owner replies, set status back to IN_PROGRESS so Super Admin knows it needs handling
        $complaint->update([
            'status'        => OwnerComplaint::STATUS_IN_PROGRESS,
            'last_reply_at' => now(),
        ]);

        return redirect()->route('owner.complaints.show', $complaint)
            ->with('success', 'Balasan Anda berhasil dikirim ke tim Super Admin.');
    }
}

