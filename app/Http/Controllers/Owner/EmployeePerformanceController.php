<?php

namespace App\Http\Controllers\Owner;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Production\ProductionBatch;
use App\Models\System\User;
use App\Services\Owner\AdminPerformanceService;
use App\Services\Owner\ProductionPerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeePerformanceController extends Controller
{
    public function __construct(
        private AdminPerformanceService $performanceService,
        private ProductionPerformanceService $productionPerformanceService
    ) {}

    /**
     * Display Employee Performance dashboard for Owner (Admin & Production tabs).
     */
    public function index(Request $request): View
    {
        $outletId = $this->resolveOwnerOutletId();
        $preset = $request->input('preset', 'this_month');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');

        [$startDate, $endDate, $preset] = $this->performanceService->resolveDateRange($preset, $customStart, $customEnd);

        $performance = $this->performanceService->calculatePerformance($startDate, $endDate, $preset);
        $productionPerformance = $this->productionPerformanceService->calculatePerformance($startDate, $endDate, $preset, $outletId);

        return view('owner.employee-performance.index', compact('performance', 'productionPerformance', 'startDate', 'endDate', 'preset'));
    }

    /**
     * Resolve legitimate outlet ID for authenticated OWNER.
     * Fails closed (aborts 403) if user is not OWNER or outlet_id is empty.
     */
    private function resolveOwnerOutletId(): int
    {
        $owner = auth()->user();
        if (! $owner || $owner->role !== UserRole::OWNER || empty($owner->outlet_id)) {
            abort(403, 'Akses ditolak: Akun Owner tidak terikat ke outlet konveksi yang sah.');
        }

        return (int) $owner->outlet_id;
    }

    /**
     * Display detailed customer and conversion breakdown for a specific Admin.
     */
    public function adminDetail(User $admin, Request $request): View
    {
        abort_if($admin->role !== UserRole::ADMIN, 404, 'Karyawan yang dipilih bukan role Admin.');

        $outletId = $this->resolveOwnerOutletId();
        abort_if((int) $admin->outlet_id !== $outletId, 403, 'Akses ditolak: Karyawan bukan milik outlet Anda.');

        $preset = $request->input('preset', 'this_month');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');

        [$startDate, $endDate, $preset] = $this->performanceService->resolveDateRange($preset, $customStart, $customEnd);

        $detail = $this->performanceService->getAdminDetail($admin, $startDate, $endDate, $preset);

        return view('owner.employee-performance.admin-detail', compact('detail', 'admin', 'startDate', 'endDate', 'preset'));
    }

    /**
     * Display detailed batch and production performance breakdown for a specific Production employee.
     */
    public function productionDetail(User $user, Request $request): View
    {
        abort_if($user->role !== UserRole::PRODUKSI, 404, 'Karyawan yang dipilih bukan role Produksi.');

        $outletId = $this->resolveOwnerOutletId();
        abort_if((int) $user->outlet_id !== $outletId, 403, 'Akses ditolak: Karyawan bukan milik outlet Anda.');

        $preset = $request->input('preset', 'this_month');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');

        [$startDate, $endDate, $preset] = $this->productionPerformanceService->resolveDateRange($preset, $customStart, $customEnd);

        $detail = $this->productionPerformanceService->getProductionDetail($user, $startDate, $endDate, $preset);

        return view('owner.employee-performance.production-detail', compact('detail', 'user', 'startDate', 'endDate', 'preset'));
    }

    /**
     * Display historical batches that currently have no assigned Production PIC.
     */
    public function unassignedBatches(Request $request): View
    {
        $outletId = $this->resolveOwnerOutletId();

        $unassignedBatches = ProductionBatch::forOutlet($outletId)
            ->whereNull('production_employee_id')
            ->whereHas('productionResult')
            ->with([
                'productionResult',
                'orderItem.order.customer',
                'orderItem.variant.product',
            ])
            ->orderBy('id')
            ->get();

        $productionEmployees = User::where('outlet_id', $outletId)
            ->where('role', UserRole::PRODUKSI)
            ->orderByRaw("CASE WHEN status = 'ACTIVE' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        return view('owner.employee-performance.unassigned-batches', compact('unassignedBatches', 'productionEmployees'));
    }

    /**
     * Assign PIC to a specific historical batch.
     */
    public function assignBatchPic(Request $request, ProductionBatch $batch): RedirectResponse
    {
        $outletId = $this->resolveOwnerOutletId();
        abort_if(! $batch->belongsToOutlet($outletId), 403, 'Akses ditolak: Batch produksi bukan milik outlet Anda.');

        $validated = $request->validate([
            'production_employee_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($outletId) {
                    $query->where('role', UserRole::PRODUKSI->value)
                        ->where('outlet_id', $outletId);
                }),
            ],
        ], [
            'production_employee_id.required' => 'Pilih karyawan Produksi sebagai PIC.',
            'production_employee_id.exists'   => 'Karyawan yang dipilih bukan role Produksi yang sah.',
        ]);

        $batchService = app(\App\Services\Production\BatchService::class);
        $batchService->reassignPic($batch, (int) $validated['production_employee_id'], auth()->user(), 'Penugasan PIC oleh Owner');

        $employee = User::find($validated['production_employee_id']);

        return redirect()->back()
            ->with('success', "PIC Batch {$batch->batch_no} berhasil ditetapkan kepada {$employee?->name}.")
            ->with('saved_batch_ids', [$batch->id]);
    }

    /**
     * Bulk assign PIC to multiple historical batches.
     */
    public function bulkAssignBatchPic(Request $request): RedirectResponse
    {
        $outletId = $this->resolveOwnerOutletId();
        $assignments = $request->input('assignments', []);

        if (! is_array($assignments) || empty($assignments)) {
            return redirect()->back()->with('info', 'Tidak ada pilihan penugasan PIC yang dipilih.');
        }

        // Filter out empty selections
        $filtered = [];
        foreach ($assignments as $batchId => $employeeId) {
            if (! empty($employeeId)) {
                $filtered[(int) $batchId] = (int) $employeeId;
            }
        }

        if (empty($filtered)) {
            return redirect()->back()->with('info', 'Tidak ada pilihan penugasan PIC yang dipilih.');
        }

        $batchIds = array_keys($filtered);
        $employeeIds = array_unique(array_values($filtered));

        // Validate employees: must exist, belong to outlet, and have role PRODUKSI
        $validEmployees = User::where('outlet_id', $outletId)
            ->whereIn('id', $employeeIds)
            ->where('role', UserRole::PRODUKSI)
            ->pluck('id')
            ->all();

        if (count($validEmployees) !== count($employeeIds)) {
            return redirect()->back()->withErrors([
                'assignments' => 'Terdapat karyawan yang dipilih bukan merupakan role Produksi yang sah.',
            ]);
        }

        // Validate batches: must exist, belong to outlet, and have NULL production_employee_id
        $validBatches = ProductionBatch::forOutlet($outletId)
            ->whereIn('id', $batchIds)
            ->whereNull('production_employee_id')
            ->get()
            ->keyBy('id');

        if ($validBatches->count() !== count($batchIds)) {
            return redirect()->back()->withErrors([
                'assignments' => 'Terdapat batch yang tidak valid atau sudah memiliki PIC Produksi.',
            ]);
        }

        $savedBatchIds = [];
        $batchService = app(\App\Services\Production\BatchService::class);
        $currentUser = auth()->user();

        \Illuminate\Support\Facades\DB::transaction(function () use ($filtered, $validBatches, $batchService, $currentUser, &$savedBatchIds) {
            foreach ($filtered as $batchId => $employeeId) {
                $batch = $validBatches->get($batchId);
                if ($batch) {
                    $batchService->reassignPic($batch, (int) $employeeId, $currentUser, 'Penugasan massal PIC oleh Owner');
                    $savedBatchIds[] = $batch->id;
                }
            }
        });

        $count = count($savedBatchIds);

        return redirect()->back()
            ->with('success', "{$count} batch historis berhasil dilengkapi PIC Produksi.")
            ->with('saved_batch_ids', $savedBatchIds);
    }
}
