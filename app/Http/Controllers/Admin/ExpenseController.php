<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExpenseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\BusinessExpenseHistory;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Sales\Order;
use App\Models\System\Outlet;
use App\Services\System\TenantContext;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $search            = $request->input('search');
        $typeFilter        = $request->input('expense_type');
        $attributionFilter = $request->input('attribution');
        $startDate         = $request->input('start_date');
        $endDate           = $request->input('end_date');

        $baseQuery = $outletId !== null
            ? BusinessExpense::forOutlet($outletId)
            : BusinessExpense::query();

        $expenses = $baseQuery->with(['order.customer', 'createdByUser'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('campaign_name', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($oq) use ($search) {
                            $oq->where('order_no', 'like', "%{$search}%")
                                ->orWhereHas('customer', function ($cq) use ($search) {
                                    $cq->where('customer_name', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($typeFilter, function ($query, $type) {
                $query->where('expense_type', $type);
            })
            ->when($attributionFilter, function ($query, $attribution) {
                if ($attribution === 'order') {
                    $query->whereNotNull('order_id');
                } elseif ($attribution === 'general') {
                    $query->whereNull('order_id');
                }
            })
            ->when($startDate, function ($query, $start) {
                $query->whereDate('expense_date', '>=', $start);
            })
            ->when($endDate, function ($query, $end) {
                $query->whereDate('expense_date', '<=', $end);
            })
            ->latest('expense_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $expenseTypes = ExpenseType::cases();

        return view('admin.expenses.index', compact(
            'expenses',
            'search',
            'typeFilter',
            'attributionFilter',
            'startDate',
            'endDate',
            'expenseTypes'
        ));
    }

    public function create(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $ordersQuery = $outletId !== null
            ? Order::forOutlet($outletId)
            : Order::query();

        $orders = $ordersQuery->with('customer')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->get(['id', 'order_no', 'customer_id', 'order_date']);

        $selectedOrderId = null;
        if ($orderIdParam = $request->query('order_id')) {
            if ($orders->contains('id', (int) $orderIdParam)) {
                $selectedOrderId = (int) $orderIdParam;
            }
        }

        $expenseTypes = ExpenseType::cases();
        $redirectTo = $request->query('redirect_to');

        return view('admin.expenses.create', compact('orders', 'expenseTypes', 'selectedOrderId', 'redirectTo'));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();
        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        if ($isIsolationEnabled && $outletId !== null) {
            $validated['outlet_id'] = $outletId;
        } else {
            unset($validated['outlet_id']);
        }

        $expense = DB::transaction(function () use ($validated, $isIsolationEnabled, $outletId) {
            if ($isIsolationEnabled && $outletId !== null) {
                // Level 1: Stable Tenant Cash Mutex - Lock authorized tenant Outlet row
                $lockedOutlet = \App\Models\System\Outlet::whereKey($outletId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedOutlet->isActive()) {
                    abort(403, 'Akses ditolak: Outlet tenant tidak aktif.');
                }
            }

            return BusinessExpense::create($validated);
        });

        if ($request->input('redirect_to') === 'financial-records') {
            return redirect()->route('admin.financial-records.index')
                ->with('success', 'Beban usaha berhasil dicatat dan masuk ke catatan keuangan.');
        }

        return redirect()->route('admin.expenses.show', $expense)
            ->with('success', 'Pengeluaran bisnis berhasil dicatat.');
    }

    public function show(BusinessExpense $expense): View
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($expense->outlet_id !== null && (int) $expense->outlet_id === $outletId, 404);
            if ($expense->order_id !== null && $expense->order) {
                abort_unless((int) $expense->order->outlet_id === $outletId, 404);
            }
        }

        $expense->load([
            'order.customer',
            'createdByUser',
            'histories.actorUser',
            'histories.oldOrder',
            'histories.newOrder',
        ]);

        return view('admin.expenses.show', compact('expense'));
    }

    public function edit(BusinessExpense $expense): View
    {
        $outletId = $this->getActiveTenantOutletId();
        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);

        if ($outletId !== null) {
            abort_unless($expense->outlet_id !== null && (int) $expense->outlet_id === $outletId, 404);
            if ($expense->order_id !== null && $expense->order) {
                abort_unless((int) $expense->order->outlet_id === $outletId, 404);
            }
        }

        $expense->load(['order.customer']);

        $ordersQuery = $outletId !== null
            ? Order::forOutlet($outletId)
            : Order::query();

        $orders = $ordersQuery->with('customer')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->get(['id', 'order_no', 'customer_id', 'order_date']);

        $expenseTypes = ExpenseType::cases();

        return view('admin.expenses.edit', compact('expense', 'orders', 'expenseTypes', 'isIsolationEnabled'));
    }

    public function update(UpdateExpenseRequest $request, BusinessExpense $expense): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();
        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);

        if ($isIsolationEnabled && $outletId !== null) {
            abort_unless($expense->outlet_id !== null && (int) $expense->outlet_id === $outletId, 404);
            if ($expense->order_id !== null && $expense->order) {
                abort_unless((int) $expense->order->outlet_id === $outletId, 404);
            }
        }

        $validated = $request->validated();
        $amendmentReason = $validated['amendment_reason'] ?? null;
        unset($validated['outlet_id'], $validated['created_by'], $validated['amendment_reason']);

        DB::transaction(function () use ($expense, $validated, $amendmentReason, $isIsolationEnabled, $outletId) {
            if ($isIsolationEnabled && $outletId !== null) {
                // Level 1: Stable Tenant Cash Mutex - Lock authorized tenant Outlet row first
                $lockedOutlet = Outlet::whereKey($outletId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedOutlet->isActive()) {
                    abort(403, 'Akses ditolak: Outlet tenant tidak aktif.');
                }
            }

            // Level 2: Lock target Expense row
            /** @var BusinessExpense $lockedExpense */
            $lockedExpense = BusinessExpense::whereKey($expense->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($isIsolationEnabled && $outletId !== null) {
                abort_unless($lockedExpense->outlet_id !== null && (int) $lockedExpense->outlet_id === $outletId, 404);
                if ($lockedExpense->order_id !== null && $lockedExpense->order) {
                    abort_unless((int) $lockedExpense->order->outlet_id === $outletId, 404);
                }

                // Verify replacement order ownership if provided
                if (! empty($validated['order_id'])) {
                    $replacementOrder = Order::find($validated['order_id']);
                    abort_unless($replacementOrder && (int) $replacementOrder->outlet_id === $outletId, 422);
                }

                // Strict Cut-Off Crossing Guard under DB lock
                $opening = OpeningCashBalance::forOutlet($outletId)
                    ->latest('opening_date')
                    ->latest('id')
                    ->first();

                if (! $opening) {
                    throw ValidationException::withMessages([
                        'expense_date' => 'Saldo awal kas tenant belum ditentukan. Koreksi pengeluaran ditolak demi integritas cut-off.',
                    ]);
                }

                $openingDate = $opening->opening_date->format('Y-m-d');
                $oldDate = $lockedExpense->expense_date->format('Y-m-d');
                $newDate = Carbon::parse($validated['expense_date'])->format('Y-m-d');

                $oldIsPostCutoff = strcmp($oldDate, $openingDate) >= 0;
                $newIsPostCutoff = strcmp($newDate, $openingDate) >= 0;

                if ($oldIsPostCutoff && ! $newIsPostCutoff) {
                    throw ValidationException::withMessages([
                        'expense_date' => 'Tanggal pengeluaran tidak boleh dipindahkan dari periode paska saldo awal ke sebelum saldo awal kas (cut-off crossing violation).',
                    ]);
                } elseif (! $oldIsPostCutoff && $newIsPostCutoff) {
                    throw ValidationException::withMessages([
                        'expense_date' => 'Tanggal pengeluaran tidak boleh dipindahkan dari periode sebelum saldo awal ke paska saldo awal kas (cut-off crossing violation).',
                    ]);
                }

                // Capture Old Snapshot from LOCKED record
                $oldAmount = (string) $lockedExpense->amount;
                $oldOrderId = $lockedExpense->order_id;
                $oldType = $lockedExpense->expense_type instanceof ExpenseType
                    ? $lockedExpense->expense_type->value
                    : (string) $lockedExpense->expense_type;
                $oldCampaign = $lockedExpense->campaign_name;
                $oldDescription = (string) $lockedExpense->description;

                // Prepared New Values
                $newAmount = number_format((float) $validated['amount'], 2, '.', '');
                $newOrderId = ! empty($validated['order_id']) ? (int) $validated['order_id'] : null;
                $newType = $validated['expense_type'] instanceof ExpenseType
                    ? $validated['expense_type']->value
                    : (string) $validated['expense_type'];
                $newCampaign = $validated['campaign_name'] ?? null;
                $newDescription = (string) $validated['description'];

                // Check if any mutable field actually changed
                $hasChanged = (bccomp($oldAmount, $newAmount, 2) !== 0)
                    || ($oldDate !== $newDate)
                    || ((int) $oldOrderId !== (int) $newOrderId)
                    || ($oldType !== $newType)
                    || ($oldCampaign !== $newCampaign)
                    || ($oldDescription !== $newDescription);

                // Insert immutable audit history record within same transaction if changed
                if ($hasChanged) {
                    BusinessExpenseHistory::create([
                        'business_expense_id' => $lockedExpense->id,
                        'outlet_id'           => $outletId,
                        'actor_user_id'       => auth()->id(),
                        'reason'              => (string) $amendmentReason,
                        'old_amount'          => $oldAmount,
                        'new_amount'          => $newAmount,
                        'old_expense_date'    => $oldDate,
                        'new_expense_date'    => $newDate,
                        'old_order_id'        => $oldOrderId,
                        'new_order_id'        => $newOrderId,
                        'old_expense_type'    => $oldType,
                        'new_expense_type'    => $newType,
                        'old_campaign_name'   => $oldCampaign,
                        'new_campaign_name'   => $newCampaign,
                        'old_description'     => $oldDescription,
                        'new_description'     => $newDescription,
                    ]);
                }
            }

            $lockedExpense->update($validated);
        });

        return redirect()->route('admin.expenses.show', $expense)
            ->with('success', 'Data pengeluaran berhasil diperbarui.');
    }

    /**
     * Resolve active tenant outlet ID when tenant isolation is enabled.
     * Fails closed (aborts 403) when enabled but user/tenant is invalid.
     */
    protected function getActiveTenantOutletId(): ?int
    {
        if (! config('nayara.tenant_isolation_enabled', false)) {
            return null;
        }

        try {
            return app(TenantContext::class)->currentOutletId();
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage() ?: 'Akses ditolak: Pengguna tidak memiliki akses tenant yang sah.');
        }
    }
}
