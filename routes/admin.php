<?php

use App\Http\Controllers\Admin\BomController;
use App\Http\Controllers\Admin\BomTemplateController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BusinessIncomeController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\FinancialRecordController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\KanbanController;
use App\Http\Controllers\Admin\OpeningCashBalanceController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PurchaseApprovalController;
use App\Http\Controllers\Admin\PurchasePaymentController;
use App\Http\Controllers\Admin\ProductionBatchController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\SalesReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes (Full Access for ADMIN role)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:ADMIN'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Customer Management (No physical destroy allowed)
        Route::resource('customers', CustomerController::class)->except(['destroy']);

        // Sales Order Management (No physical destroy allowed)
        Route::post('orders/{order}/confirm-deal', [OrderController::class, 'confirmDeal'])->name('orders.confirm-deal');
        Route::post('orders/{order}/regenerate-tracking-token', [OrderController::class, 'regenerateTrackingToken'])->name('orders.regenerate-tracking-token');
        Route::post('orders/{order}/invoice', [InvoiceController::class, 'store'])->name('orders.invoice.store');
        Route::get('orders/{order}/batches/create', [ProductionBatchController::class, 'create'])->name('orders.batches.create');
        Route::post('orders/{order}/batches', [ProductionBatchController::class, 'store'])->name('orders.batches.store');
        Route::resource('orders', OrderController::class)->except(['destroy']);

        // Production Batch Management (Read & Create Only, PIC Assignment, Partial Handling)
        Route::get('batches', [ProductionBatchController::class, 'index'])->name('batches.index');
        Route::get('batches/{batch}', [ProductionBatchController::class, 'show'])->name('batches.show');
        Route::post('batches/{batch}/reassign-pic', [ProductionBatchController::class, 'reassignPic'])->name('batches.reassign-pic');
        Route::post('batches/{batch}/resume', [ProductionBatchController::class, 'resumeProduction'])->name('batches.resume');
        Route::post('batches/{batch}/partial-close', [ProductionBatchController::class, 'partialClose'])->name('batches.partial-close');

        // Kanban Board (Monitoring & KOMUNIKASI -> DEAL)
        Route::get('kanban', [KanbanController::class, 'index'])->name('kanban.index');

        // Catatan & Ringkasan Keuangan Operasional (Revisi Pembimbing — Tahap 4 & 4B)
        Route::get('financial-records', [FinancialRecordController::class, 'index'])->name('financial-records.index');
        Route::get('opening-cash/edit', [OpeningCashBalanceController::class, 'edit'])
            ->withoutMiddleware('role:ADMIN')
            ->middleware('role:ADMIN,OWNER')
            ->name('opening-cash.edit');
        Route::post('opening-cash', [OpeningCashBalanceController::class, 'store'])
            ->withoutMiddleware('role:ADMIN')
            ->middleware('role:ADMIN,OWNER')
            ->name('opening-cash.store');
        Route::get('purchases/payments', [PurchasePaymentController::class, 'index'])->name('purchases.payments.index');
        Route::get('purchases/{purchase}/payments/create', [PurchasePaymentController::class, 'create'])->name('purchases.payments.create');
        Route::post('purchases/{purchase}/payments', [PurchasePaymentController::class, 'store'])->name('purchases.payments.store');
        Route::get('purchases/payments/{payment}/proof', [PurchasePaymentController::class, 'showProof'])->name('purchases.payments.proof');

        // Persetujuan Purchase Order (Admin Approval Workflow)
        Route::get('purchases/approvals', [PurchaseApprovalController::class, 'index'])->name('purchases.approvals.index');
        Route::get('purchases/approvals/{purchase}', [PurchaseApprovalController::class, 'show'])->name('purchases.approvals.show');
        Route::post('purchases/approvals/{purchase}/approve', [PurchaseApprovalController::class, 'approve'])->name('purchases.approvals.approve');
        Route::post('purchases/approvals/{purchase}/request-revision', [PurchaseApprovalController::class, 'requestRevision'])->name('purchases.approvals.request-revision');
        Route::post('purchases/approvals/{purchase}/reject', [PurchaseApprovalController::class, 'reject'])->name('purchases.approvals.reject');

        // Invoice Management (No physical destroy allowed)
        Route::get('invoices/{invoice}/export-csv', [InvoiceController::class, 'exportCsv'])->name('invoices.export-csv');
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

        // Payment Management (No physical destroy allowed)
        Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');
        Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');

        // Sales Revenue Report (PRD v1.5)
        Route::get('reports/sales/export-csv', [SalesReportController::class, 'exportCsv'])->name('reports.sales.export-csv');
        Route::get('reports/sales', [SalesReportController::class, 'index'])->name('reports.sales');

        // Business Income Management (Non-Invoice Cash Inflow)
        Route::get('incomes/create', [BusinessIncomeController::class, 'create'])->name('incomes.create');
        Route::post('incomes', [BusinessIncomeController::class, 'store'])->name('incomes.store');

        // Business Expense Management (No physical destroy allowed)
        Route::resource('expenses', ExpenseController::class)->except(['destroy']);

        // Product Management (No physical destroy allowed, uses active toggle)
        Route::post('products/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('products.toggle-active');
        Route::resource('products', ProductController::class)->except(['destroy']);

        // Product Variant Management
        Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
        Route::get('variants/{variant}', [ProductVariantController::class, 'show'])->name('variants.show');
        Route::get('variants/{variant}/edit', [ProductVariantController::class, 'edit'])->name('variants.edit');
        Route::put('variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');

        // BOM Management (Strict Scoped Bindings)
        Route::post('variants/{variant}/bom', [BomController::class, 'store'])->name('variants.bom.store');
        Route::put('variants/{variant}/bom/{bomItem}', [BomController::class, 'update'])->name('variants.bom.update')->scopeBindings();
        Route::delete('variants/{variant}/bom/{bomItem}', [BomController::class, 'destroy'])->name('variants.bom.destroy')->scopeBindings();

        // BOM Template Management
        Route::post('bom-templates/{bomTemplate}/toggle-active', [BomTemplateController::class, 'toggleActive'])->name('bom-templates.toggle-active');
        Route::post('bom-templates/{bomTemplate}/items', [BomTemplateController::class, 'storeItem'])->name('bom-templates.items.store');
        Route::put('bom-templates/{bomTemplate}/items/{item}', [BomTemplateController::class, 'updateItem'])->name('bom-templates.items.update');
        Route::delete('bom-templates/{bomTemplate}/items/{item}', [BomTemplateController::class, 'destroyItem'])->name('bom-templates.items.destroy');
        Route::resource('bom-templates', BomTemplateController::class)
            ->parameters(['bom-templates' => 'bomTemplate'])
            ->except(['destroy']);

        // Apply BOM Template to Variant
        Route::post('variants/{variant}/apply-bom-template', [BomController::class, 'applyTemplate'])->name('variants.bom.apply-template');
    });
