<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlatformServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerServiceRequestController extends Controller
{
    /**
     * Display service requests history and status.
     */
    public function index(): View
    {
        $outletId = auth()->user()->outlet_id;

        $requests = PlatformServiceRequest::where('outlet_id', $outletId)
            ->latest('id')
            ->paginate(10);

        $pageTitle = 'Layanan & Pelatihan';
        $breadcrumb = 'Executive Monitoring > Layanan & Pelatihan';

        return view('owner.services.index', compact('requests', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Show form to request setup & training.
     */
    public function create(): View
    {
        $pageTitle = 'Ajukan Permintaan Layanan';
        $breadcrumb = 'Executive Monitoring > Layanan & Pelatihan > Ajukan Permintaan';

        return view('owner.services.create', compact('pageTitle', 'breadcrumb'));
    }

    /**
     * Store new setup & training request.
     */
    public function store(Request $request): RedirectResponse
    {
        $outletId = auth()->user()->outlet_id;

        $validated = $request->validate([
            'service_type' => ['required', 'in:SETUP_ONBOARDING,TRAINING_STAFF,CUSTOM_CONSULTATION'],
            'scope_details' => ['required', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date', 'after:today'],
        ]);

        PlatformServiceRequest::create([
            'outlet_id' => $outletId,
            'created_by' => auth()->id(),
            'service_type' => $validated['service_type'],
            'scope_details' => $validated['scope_details'],
            'preferred_date' => $validated['preferred_date'] ?? null,
            'status' => PlatformServiceRequest::STATUS_SUBMITTED,
        ]);

        return redirect()->route('owner.services.index')
            ->with('success', 'Permintaan layanan implementasi & pelatihan berhasil dikirim. Tim NAYARA akan meninjau dan menghubungi Anda untuk estimasi jadwal & ruang lingkup.');
    }
}
