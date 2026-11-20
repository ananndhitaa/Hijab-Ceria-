<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Platform\PublicStorefrontController;
use App\Services\Tracking\TrackingTokenService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [PublicStorefrontController::class, 'index'])->name('landing');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'submitRequest'])->name('password.request.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Role-Based Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/admin.php';
require __DIR__.'/gudang.php';
require __DIR__.'/produksi.php';

/*
|--------------------------------------------------------------------------
| Owner Routes (Placeholder)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Owner\EmployeeController;
use App\Http\Controllers\Owner\EmployeePerformanceController;
use App\Http\Controllers\Owner\ExpenseAuditController as OwnerExpenseAuditController;
use App\Http\Controllers\Owner\HppController;
use App\Http\Controllers\Owner\OpeningCashBalanceController as OwnerOpeningCashBalanceController;
use App\Http\Controllers\Owner\PerformanceController;
use App\Http\Controllers\Owner\ProfitabilityController;
use App\Http\Controllers\Owner\OwnerPublicationController;
use App\Http\Controllers\Owner\OwnerSubscriptionController;
use App\Http\Controllers\Owner\OwnerAdCampaignController;
use App\Http\Controllers\Owner\OwnerFinancialAnalyticsController;
use App\Http\Controllers\Owner\OwnerServiceRequestController;
use App\Http\Controllers\Owner\OwnerNotificationController;
use App\Http\Controllers\Owner\OwnerComplaintController;
use App\Http\Controllers\Owner\OwnerActivityLogController;

Route::middleware(['auth', 'role:OWNER'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'owner'])->name('dashboard');

        // Saldo Awal Kas Operasional (Owner One-Time Setup)
        Route::get('/opening-cash', [OwnerOpeningCashBalanceController::class, 'create'])->name('opening-cash.create');
        Route::post('/opening-cash', [OwnerOpeningCashBalanceController::class, 'store'])->name('opening-cash.store');

        // Riwayat Audit Pengeluaran Bisnis (Read-Only for Owner)
        Route::get('/expenses/{expense}/histories', [OwnerExpenseAuditController::class, 'show'])->name('expenses.histories');

        // HPP Monitoring (Read-Only)
        Route::get('/hpp', [HppController::class, 'index'])->name('hpp.index');
        Route::get('/hpp/batches/{batch}', [HppController::class, 'showBatch'])->name('hpp.batches.show');
        Route::get('/hpp/orders/{order}', [HppController::class, 'showOrder'])->name('hpp.orders.show');

        // Order Profitability Monitoring (Read-Only)
        Route::get('/profitability', [ProfitabilityController::class, 'index'])->name('profitability.index');
        Route::get('/profitability/orders/{order}', [ProfitabilityController::class, 'show'])->name('profitability.show');

        // Period Financial Performance / Ringkasan Keuangan (Read-Only)
        Route::get('/performance', [PerformanceController::class, 'index'])->name('performance.index');
        Route::get('/financial-summary', [PerformanceController::class, 'index'])->name('financial-summary.index');

        // Manajemen Karyawan (Owner only)
        Route::resource('employees', EmployeeController::class);
        Route::patch('employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])->name('employees.toggle-status');
        Route::post('employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])->name('employees.reset-password');

        // Performa Karyawan (Admin & Produksi) — Read-Only for Owner
        Route::get('/employee-performance', [EmployeePerformanceController::class, 'index'])->name('employee-performance.index');
        Route::get('/employee-performance/admins/{admin}', [EmployeePerformanceController::class, 'adminDetail'])->name('employee-performance.admin-detail');
        Route::get('/employee-performance/production/{user}', [EmployeePerformanceController::class, 'productionDetail'])->name('employee-performance.production-detail');
        Route::get('/employee-performance/unassigned-batches', [EmployeePerformanceController::class, 'unassignedBatches'])->name('employee-performance.unassigned-batches');
        Route::post('/employee-performance/batches/{batch}/assign-pic', [EmployeePerformanceController::class, 'assignBatchPic'])->name('employee-performance.assign-batch-pic');
        Route::post('/employee-performance/batches/bulk-assign-pic', [EmployeePerformanceController::class, 'bulkAssignBatchPic'])->name('employee-performance.bulk-assign-pic');
        Route::post('/employee-performance/unassigned-batches/assign-bulk', [EmployeePerformanceController::class, 'bulkAssignBatchPic'])->name('employee-performance.unassigned-batches.assign-bulk');

        // Log Aktivitas Konveksi (Read-Only for Owner)
        Route::get('/activity-logs', [OwnerActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/invoices/{invoice}/print', [\App\Http\Controllers\Admin\InvoiceController::class, 'print'])->name('invoices.print');

        // SaaS Multi-Tenant: Public Showcase & Listing Slots (Phase 11)
        Route::get('/publications', [OwnerPublicationController::class, 'index'])->name('publications.index');
        Route::post('/publications/{product}/publish', [OwnerPublicationController::class, 'publish'])->name('publications.publish');
        Route::post('/publications/{product}/unpublish', [OwnerPublicationController::class, 'unpublish'])->name('publications.unpublish');
        Route::put('/publications/{product}/showcase', [OwnerPublicationController::class, 'updateShowcase'])->name('publications.update-showcase');
        Route::put('/publications/variants/{variant}/image', [OwnerPublicationController::class, 'updateVariantImage'])->name('publications.update-variant-image');

        // SaaS Multi-Tenant: Monetization & Subscriptions (Phase 11)
        Route::get('/subscriptions', [OwnerSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('/subscriptions/premium', [OwnerSubscriptionController::class, 'storePremium'])->name('subscriptions.premium');
        Route::post('/subscriptions/analytics-addon', [OwnerSubscriptionController::class, 'storeAnalyticsAddon'])->name('subscriptions.analytics-addon');
        Route::post('/subscriptions/slots', [OwnerSubscriptionController::class, 'storeListingSlots'])->name('subscriptions.slots');
        Route::post('/subscriptions/upgrade', [OwnerSubscriptionController::class, 'storeUpgrade'])->name('subscriptions.upgrade');
        Route::get('/subscriptions/invoice/{invoice}', [OwnerSubscriptionController::class, 'showInvoice'])->name('subscriptions.invoice');
        Route::post('/subscriptions/invoice/{invoice}/pay', [OwnerSubscriptionController::class, 'submitPaymentProof'])->name('subscriptions.submit-proof');

        // SaaS Multi-Tenant: Advertising Platform (Phase 11)
        Route::get('/ads', [OwnerAdCampaignController::class, 'index'])->name('ads.index');
        Route::get('/ads/create', [OwnerAdCampaignController::class, 'create'])->name('ads.create');
        Route::post('/ads', [OwnerAdCampaignController::class, 'store'])->name('ads.store');
        Route::get('/ads/{campaign}', [OwnerAdCampaignController::class, 'show'])->name('ads.show');
        Route::post('/ads/{campaign}/book', [OwnerAdCampaignController::class, 'bookSchedule'])->name('ads.book');
        Route::post('/ads/{campaign}/reschedule', [OwnerAdCampaignController::class, 'reschedule'])->name('ads.reschedule');
        Route::post('/ads/{campaign}/revise', [OwnerAdCampaignController::class, 'submitRevision'])->name('ads.revise');
        Route::post('/ads/{campaign}/cancel', [OwnerAdCampaignController::class, 'cancel'])->name('ads.cancel');

        // SaaS Multi-Tenant: Advanced Financial Analytics (Phase 11)
        Route::get('/analytics', [OwnerFinancialAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/export/csv', [OwnerFinancialAnalyticsController::class, 'exportCsv'])->name('analytics.export.csv');
        Route::get('/analytics/export/pdf', [OwnerFinancialAnalyticsController::class, 'exportPdf'])->name('analytics.export.pdf');

        // SaaS Multi-Tenant: Setup & Training Services (Phase 11)
        Route::get('/services', [OwnerServiceRequestController::class, 'index'])->name('services.index');
        Route::get('/services/create', [OwnerServiceRequestController::class, 'create'])->name('services.create');
        Route::post('/services', [OwnerServiceRequestController::class, 'store'])->name('services.store');

        // SaaS Multi-Tenant: In-app Notifications (Phase 11)
        Route::get('/notifications', [OwnerNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/read', [OwnerNotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [OwnerNotificationController::class, 'markAllRead'])->name('notifications.read-all');

        // SaaS Multi-Tenant: Owner Complaints & Help Center (Layanan Tenant)
        Route::get('/complaints', [OwnerComplaintController::class, 'index'])->name('complaints.index');
        Route::get('/complaints/create', [OwnerComplaintController::class, 'create'])->name('complaints.create');
        Route::post('/complaints', [OwnerComplaintController::class, 'store'])->name('complaints.store');
        Route::get('/complaints/{complaint}', [OwnerComplaintController::class, 'show'])->name('complaints.show');
        Route::post('/complaints/{complaint}/reply', [OwnerComplaintController::class, 'reply'])->name('complaints.reply');
    });

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\InvoiceController as CustomerInvoiceController;

Route::middleware(['auth', 'role:CUSTOMER'])
    ->prefix('customer')
    ->name('customer.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'customer'])->name('dashboard');
        Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/review', [CustomerOrderController::class, 'storeReview'])->name('orders.review');
        Route::get('/invoices', [CustomerInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}/print', [CustomerInvoiceController::class, 'print'])->name('invoices.print');
    });

/*
|--------------------------------------------------------------------------
| Public Storefront Showcase (Phase 11)
|--------------------------------------------------------------------------
*/

Route::prefix('storefront')->name('storefront.')->group(function () {
    Route::get('/', [PublicStorefrontController::class, 'index'])->name('index');
    Route::get('/shops', [PublicStorefrontController::class, 'shops'])->name('shops');
    Route::get('/shop/{outlet}', [PublicStorefrontController::class, 'shopProfile'])->name('shop');
    Route::get('/product/{product}', [PublicStorefrontController::class, 'productDetail'])->name('product');

    // Informasi Publik, Bantuan, & Legalitas
    Route::get('/about', [PublicStorefrontController::class, 'about'])->name('about');
    Route::get('/help', [PublicStorefrontController::class, 'help'])->name('help');
    Route::get('/how-to-order', [PublicStorefrontController::class, 'howToOrder'])->name('how-to-order');
    Route::get('/returns', [PublicStorefrontController::class, 'returns'])->name('returns');
    Route::get('/terms', [PublicStorefrontController::class, 'terms'])->name('terms');
    Route::get('/privacy', [PublicStorefrontController::class, 'privacy'])->name('privacy');
    Route::get('/usage-policy', [PublicStorefrontController::class, 'usagePolicy'])->name('usage-policy');
    Route::get('/contact', [PublicStorefrontController::class, 'contact'])->name('contact');
});

/*
|--------------------------------------------------------------------------
| Public Order Tracking (no login required)
|--------------------------------------------------------------------------
*/

Route::prefix('track')->name('track.')->group(function () {
    Route::get('/', function () {
        return view('tracking.index');
    })->name('index');

    Route::get('/{token}', function (string $token, TrackingTokenService $tokenService) {
        $extracted = TrackingTokenService::extractToken($token);

        if (! $extracted) {
            abort(404, 'Token atau tautan pelacakan tidak valid.');
        }

        $trackingToken = $tokenService->findValidToken($extracted);

        if (! $trackingToken) {
            abort(404, 'Link tracking tidak valid atau pesanan tidak ditemukan.');
        }

        $order = $trackingToken->order;

        return view('tracking.show', [
            'token'         => $extracted,
            'trackingToken' => $trackingToken,
            'order'         => $order,
        ]);
    })->name('show')->where('token', '.*');
});

/*
|--------------------------------------------------------------------------
| Public Tenant Free Registration
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Platform\PublicRegistrationController;

Route::prefix('register-tenant')->name('tenant.register')->group(function () {
    Route::get('/', [PublicRegistrationController::class, 'create'])->name('.create');
    Route::post('/', [PublicRegistrationController::class, 'store'])->name('.store');
    Route::get('/success', [PublicRegistrationController::class, 'success'])->name('.success');
    Route::get('/status', [PublicRegistrationController::class, 'checkStatus'])->name('.status');
    Route::post('/{registration}/resubmit', [PublicRegistrationController::class, 'resubmit'])->name('.resubmit');
});

/*
|--------------------------------------------------------------------------
| Super Admin Platform Routes (Platform Authority Only)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Platform\PlatformDashboardController;
use App\Http\Controllers\Platform\TenantRegistrationReviewController;
use App\Http\Controllers\Platform\PlatformTenantController;
use App\Http\Controllers\Platform\PlatformAuditController;
use App\Http\Controllers\Platform\PlatformSystemController;
use App\Http\Controllers\Platform\PlatformBillingReviewController;
use App\Http\Controllers\Platform\PlatformAdReviewController;
use App\Http\Controllers\Platform\PlatformRefundReviewController;
use App\Http\Controllers\Platform\PlatformAnalyticsController;
use App\Http\Controllers\Platform\PlatformServiceManagementController;
use App\Http\Controllers\Platform\PlatformNotificationManagementController;
use App\Http\Controllers\Platform\PlatformRoleController;
use App\Http\Controllers\Platform\PlatformSettingsController;
use App\Http\Controllers\Platform\PlatformComplaintController;

if (app()->environment('local')) {
    Route::get('/platform/dev-auth', function () {
        $user = \App\Models\System\User::where('role', \App\Enums\UserRole::SUPER_ADMIN->value)->first();
        if ($user) {
            \Illuminate\Support\Facades\Auth::login($user);
            return redirect()->route('platform.dashboard');
        }
        return response('No super admin found', 404);
    });
}

Route::middleware(['auth', 'super_admin'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function () {
        Route::get('/dashboard', [PlatformDashboardController::class, 'index'])->name('dashboard');

        // Registration queue & review
        Route::get('/registrations', [TenantRegistrationReviewController::class, 'index'])->name('registrations.index');
        Route::get('/registrations/{registration}', [TenantRegistrationReviewController::class, 'show'])->name('registrations.show');
        Route::post('/registrations/{registration}/approve', [TenantRegistrationReviewController::class, 'approve'])->name('registrations.approve');
        Route::post('/registrations/{registration}/reject', [TenantRegistrationReviewController::class, 'reject'])->name('registrations.reject');
        Route::post('/registrations/{registration}/request-revision', [TenantRegistrationReviewController::class, 'requestRevision'])->name('registrations.request-revision');

        // Tenant directory & suspension/reactivation
        Route::get('/tenants', [PlatformTenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/{outlet}', [PlatformTenantController::class, 'show'])->name('tenants.show');
        Route::patch('/tenants/{outlet}/toggle-status', [PlatformTenantController::class, 'toggleStatus'])->name('tenants.toggle-status');

        // Platform Billing & Invoices
        Route::get('/billing', [PlatformBillingReviewController::class, 'index'])->name('billing.index');
        Route::get('/billing/{invoice}', [PlatformBillingReviewController::class, 'show'])->name('billing.show');
        Route::post('/billing/{invoice}/verify', [PlatformBillingReviewController::class, 'verify'])->name('billing.verify');
        Route::post('/billing/{invoice}/reject', [PlatformBillingReviewController::class, 'reject'])->name('billing.reject');

        // Advertising Review & Moderation
        Route::get('/ads', [PlatformAdReviewController::class, 'index'])->name('ads.index');
        Route::get('/ads/{campaign}', [PlatformAdReviewController::class, 'show'])->name('ads.show');
        Route::post('/ads/{campaign}/review', [PlatformAdReviewController::class, 'review'])->name('ads.review');
        Route::post('/ads/revisions/{revision}/review', [PlatformAdReviewController::class, 'reviewRevision'])->name('ads.review-revision');
        Route::post('/ads/{campaign}/suspend', [PlatformAdReviewController::class, 'suspend'])->name('ads.suspend');

        // Platform Refunds
        Route::get('/refunds', [PlatformRefundReviewController::class, 'index'])->name('refunds.index');
        Route::get('/refunds/{refund}', [PlatformRefundReviewController::class, 'show'])->name('refunds.show');
        Route::post('/refunds/{refund}/approve', [PlatformRefundReviewController::class, 'approve'])->name('refunds.approve');
        Route::post('/refunds/{refund}/complete', [PlatformRefundReviewController::class, 'complete'])->name('refunds.complete');

        // Global Analytics & Platform KPIs
        Route::get('/analytics', [PlatformAnalyticsController::class, 'index'])->name('analytics.index');

        // Implementation & Training Services Management
        Route::get('/services', [PlatformServiceManagementController::class, 'index'])->name('services.index');
        Route::get('/services/{serviceRequest}', [PlatformServiceManagementController::class, 'show'])->name('services.show');
        Route::put('/services/{serviceRequest}', [PlatformServiceManagementController::class, 'update'])->name('services.update');

        // Decision audit trail
        Route::get('/audit', [PlatformAuditController::class, 'index'])->name('audit.index');

        // Safe system monitoring
        Route::get('/system', [PlatformSystemController::class, 'index'])->name('system.index');

        // Owner Complaints Management (Layanan Tenant - Section C)
        Route::get('/complaints', [PlatformComplaintController::class, 'index'])->name('complaints.index');
        Route::get('/complaints/{complaint}', [PlatformComplaintController::class, 'show'])->name('complaints.show');
        Route::post('/complaints/{complaint}/reply', [PlatformComplaintController::class, 'reply'])->name('complaints.reply');
        Route::post('/complaints/{complaint}/status', [PlatformComplaintController::class, 'updateStatus'])->name('complaints.update-status');

        // Broadcast & Notification Management (Task 8)
        Route::get('/notifications', [PlatformNotificationManagementController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/broadcast', [PlatformNotificationManagementController::class, 'broadcast'])->name('notifications.broadcast');

        // Role & Access Governance (Task 8)
        Route::get('/roles', [PlatformRoleController::class, 'index'])->name('roles.index');

        // Platform Configuration & Policies (Task 8)
        Route::get('/settings', [PlatformSettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [PlatformSettingsController::class, 'update'])->name('settings.update');
    });

/*
|--------------------------------------------------------------------------
| 403 / Error Views
|--------------------------------------------------------------------------
*/

Route::get('/403', function () {
    abort(403);
})->name('forbidden');

/*
|--------------------------------------------------------------------------
| Storage Asset Fallback Route (Hardened & Jailed)
|--------------------------------------------------------------------------
| Secure direct file response for local development when symlinks cannot be created.
| Implements strict jail to storage/app/public, blocks traversal, hidden files & executable types.
*/
Route::get('/storage/{path}', function (string $path) {
    // 1. Block null bytes and explicit traversal sequences
    if (str_contains($path, "\0") || str_contains($path, '..')) {
        abort(404);
    }

    $baseDir = realpath(storage_path('app/public'));
    if (! $baseDir) {
        abort(404);
    }

    $targetPath = storage_path('app/public/' . ltrim($path, '/\\'));
    $realTarget = realpath($targetPath);

    // 2. Strict directory jail: target must strictly reside within storage/app/public
    if (! $realTarget || ! str_starts_with($realTarget, $baseDir . DIRECTORY_SEPARATOR)) {
        abort(404);
    }

    // 3. Must be an ordinary readable file
    if (! is_file($realTarget) || ! is_readable($realTarget)) {
        abort(404);
    }

    // 4. Block hidden files (.env, .gitignore, etc.)
    $basename = basename($realTarget);
    if (str_starts_with($basename, '.')) {
        abort(404);
    }

    // 5. Block dangerous/executable file extensions
    $disallowedExtensions = ['php', 'phtml', 'phar', 'sh', 'bat', 'cmd', 'exe', 'env', 'htaccess', 'json', 'lock'];
    $extension = strtolower(pathinfo($realTarget, PATHINFO_EXTENSION));
    if (in_array($extension, $disallowedExtensions, true)) {
        abort(404);
    }

    return response()->file($realTarget);
})->where('path', '.*')->name('storage.fallback');
