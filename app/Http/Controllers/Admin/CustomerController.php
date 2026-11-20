<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Master\Customer;
use App\Models\System\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $statusFilter = $request->input('status');

        $customers = Customer::with(['user', 'handledBy'])
            ->withDealStatus()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($statusFilter === 'DEAL', function ($query) {
                $query->whereHas('orders.invoice.payments', function ($pq) {
                    $pq->where('status', \App\Enums\PaymentStatus::CONFIRMED)
                       ->where('amount', '>', 0);
                });
            })
            ->when($statusFilter === 'BELUM_DEAL', function ($query) {
                $query->whereDoesntHave('orders.invoice.payments', function ($pq) {
                    $pq->where('status', \App\Enums\PaymentStatus::CONFIRMED)
                       ->where('amount', '>', 0);
                });
            })
            ->latest('first_contact_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', compact('customers', 'search', 'statusFilter'));
    }

    public function create(): View
    {
        $users = User::where('role', 'CUSTOMER')->orderBy('name')->get();
        return view('admin.customers.create', compact('users'));
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['handled_by_user_id'] = $data['handled_by_user_id'] ?? $request->user()->id;
        $data['first_contact_date'] = $data['first_contact_date'] ?? now('Asia/Jakarta')->toDateString();

        $customer = Customer::create($data);

        return redirect()->route('admin.customers.index')
            ->with('success', "Customer {$customer->customer_name} berhasil ditambahkan (Status: Belum Deal).");
    }

    public function show(Customer $customer): View
    {
        $customer->load(['user', 'handledBy', 'orders.invoice.payments']);
        return view('admin.customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        $users = User::where('role', 'CUSTOMER')->orderBy('name')->get();
        return view('admin.customers.edit', compact('customer', 'users'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('admin.customers.index')
            ->with('success', "Data customer {$customer->customer_name} berhasil diperbarui.");
    }
}
