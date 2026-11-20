<?php

namespace App\Http\Controllers\Owner;

use App\Enums\BatchStatus;
use App\Enums\PasswordResetStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ResetEmployeePasswordRequest;
use App\Http\Requests\Owner\StoreEmployeeRequest;
use App\Http\Requests\Owner\UpdateEmployeeRequest;
use App\Models\Master\Customer;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\System\PasswordResetRequest;
use App\Models\System\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    /**
     * Allowed internal employee roles manageable by Owner.
     */
    private const ALLOWED_ROLES = [
        UserRole::ADMIN,
        UserRole::GUDANG,
        UserRole::PRODUKSI,
    ];

    /**
     * Display a paginated list of internal employees strictly belonging to the Owner's outlet.
     */
    public function index(Request $request): View
    {
        $outletId = $this->resolveOwnerOutletId();

        $search = $request->input('search');
        $roleFilter = $request->input('role');
        $statusFilter = $request->input('status');

        $employees = User::where('outlet_id', $outletId)
            ->whereIn('role', self::ALLOWED_ROLES)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter, function ($query, $role) {
                $query->where('role', $role);
            })
            ->when($statusFilter, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $roles = self::ALLOWED_ROLES;
        $statuses = [UserStatus::ACTIVE, UserStatus::INACTIVE];

        return view('owner.employees.index', compact('employees', 'roles', 'statuses', 'search', 'roleFilter', 'statusFilter'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(): View
    {
        $this->resolveOwnerOutletId();

        $roles = self::ALLOWED_ROLES;
        $statuses = [UserStatus::ACTIVE, UserStatus::INACTIVE];

        return view('owner.employees.create', compact('roles', 'statuses'));
    }

    /**
     * Store a newly created employee in storage assigned to the Owner's outlet.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $outletId = $this->resolveOwnerOutletId();
        $validated = $request->validated();

        $employee = User::create([
            'outlet_id' => $outletId,
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'password'  => $validated['password'], // hashed by User model casts
            'status'    => $validated['status'],
        ]);

        return redirect()->route('owner.employees.index')
            ->with('success', "Karyawan {$employee->name} ({$employee->role->label()}) berhasil ditambahkan.");
    }

    /**
     * Display the specified employee details.
     */
    public function show(User $employee): View
    {
        $this->ensureEmployeeBelongsToOwner($employee);

        $stats = [];
        if ($employee->isProduksi()) {
            $stats['assigned_batches_count'] = ProductionBatch::where('production_employee_id', $employee->id)->count();
            $stats['completed_batches_count'] = ProductionBatch::where('production_employee_id', $employee->id)
                ->where('status', BatchStatus::SELESAI)
                ->count();
        } elseif ($employee->isAdmin()) {
            $stats['handled_customers_count'] = Customer::where('handled_by_user_id', $employee->id)->count();
            $stats['orders_count'] = Order::where('created_by_user_id', $employee->id)->count();
        }

        return view('owner.employees.show', compact('employee', 'stats'));
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(User $employee): View
    {
        $this->ensureEmployeeBelongsToOwner($employee);

        $roles = self::ALLOWED_ROLES;
        $statuses = [UserStatus::ACTIVE, UserStatus::INACTIVE];

        $hasAssignedBatches = ProductionBatch::where('production_employee_id', $employee->id)->exists();

        return view('owner.employees.edit', compact('employee', 'roles', 'statuses', 'hasAssignedBatches'));
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(UpdateEmployeeRequest $request, User $employee): RedirectResponse
    {
        $this->ensureEmployeeBelongsToOwner($employee);

        $validated = $request->validated();

        $data = [
            'name'   => $validated['name'],
            'email'  => $validated['email'],
            'role'   => $validated['role'],
            'status' => $validated['status'],
        ];

        if ($request->filled('password')) {
            $data['password'] = $validated['password'];
        }

        $employee->update($data);

        return redirect()->route('owner.employees.index')
            ->with('success', "Data karyawan {$employee->name} berhasil diperbarui.");
    }

    /**
     * Toggle the active status of the specified employee.
     */
    public function toggleStatus(User $employee): RedirectResponse
    {
        $this->ensureEmployeeBelongsToOwner($employee);

        $newStatus = $employee->isActive() ? UserStatus::INACTIVE : UserStatus::ACTIVE;
        $employee->update(['status' => $newStatus]);

        $statusLabel = $newStatus->label();
        return redirect()->back()
            ->with('success', "Status karyawan {$employee->name} diubah menjadi {$statusLabel}.");
    }

    /**
     * Reset the password of the specified employee.
     */
    public function resetPassword(ResetEmployeePasswordRequest $request, User $employee): RedirectResponse
    {
        $this->ensureEmployeeBelongsToOwner($employee);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $employee) {
            $employee->forceFill([
                'password'       => Hash::make($validated['password']),
                'remember_token' => null,
            ])->save();

            $this->invalidateUserSessions($employee);

            // Complete any pending password reset requests for this employee
            PasswordResetRequest::where('user_id', $employee->id)
                ->where('status', PasswordResetStatus::PENDING)
                ->update([
                    'status'       => PasswordResetStatus::COMPLETED,
                    'processed_at' => now(),
                    'processed_by' => Auth::id(),
                ]);
        });

        Log::info("Owner #" . Auth::id() . " reset password for employee #{$employee->id} ({$employee->name}).");

        return redirect()->route('owner.employees.show', $employee)
            ->with('success', "Password untuk karyawan {$employee->name} berhasil diperbarui.");
    }

    /**
     * Resolve legitimate outlet ID for authenticated OWNER.
     * Fails closed (aborts 403) if user is not OWNER or outlet_id is empty.
     */
    private function resolveOwnerOutletId(): int
    {
        $owner = Auth::user();
        if (! $owner || $owner->role !== UserRole::OWNER || empty($owner->outlet_id)) {
            abort(403, 'Akses ditolak: Akun Owner tidak terikat ke outlet konveksi yang sah.');
        }

        return (int) $owner->outlet_id;
    }

    /**
     * Ensure the target user is an internal employee belonging to the same outlet as the authenticated Owner.
     */
    private function ensureEmployeeBelongsToOwner(User $employee): void
    {
        $outletId = $this->resolveOwnerOutletId();

        abort_if(
            ! in_array($employee->role, self::ALLOWED_ROLES, true),
            404,
            'Karyawan internal tidak ditemukan.'
        );

        abort_if(
            (int) $employee->outlet_id !== $outletId,
            403,
            'Akses ditolak: Karyawan bukan milik outlet Anda.'
        );
    }

    /**
     * Invalidate all existing sessions for the given user.
     */
    private function invalidateUserSessions(User $user): void
    {
        if (config('session.driver') === 'file') {
            $sessionPath = config('session.files', storage_path('framework/sessions'));

            if (! is_dir($sessionPath)) {
                return;
            }

            foreach (glob($sessionPath . '/*') as $sessionFile) {
                $content = @file_get_contents($sessionFile);
                if ($content === false) {
                    continue;
                }

                if (str_contains($content, '"login_web_') && str_contains($content, (string) $user->id)) {
                    @unlink($sessionFile);
                }
            }
        } elseif (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
    }
}
