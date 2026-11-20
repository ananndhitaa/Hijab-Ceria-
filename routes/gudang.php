<?php

use App\Http\Controllers\Gudang\DashboardController;
use App\Http\Controllers\Gudang\MaterialController;
use App\Http\Controllers\Gudang\MaterialPlanningController;
use App\Http\Controllers\Gudang\PurchaseController;
use App\Http\Controllers\Gudang\StockMovementController;
use App\Http\Controllers\Gudang\StockOpnameController;
use App\Http\Controllers\Gudang\SupplierController;
use App\Http\Controllers\Gudang\UnitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gudang Routes (GUDANG role)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:GUDANG'])
    ->prefix('gudang')
    ->name('gudang.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Unit Master
        Route::resource('units', UnitController::class)->except(['show']);

        // Material Master (Safe Delete & Active Toggle)
        Route::post('materials/{material}/toggle-active', [MaterialController::class, 'toggleActive'])->name('materials.toggle-active');
        Route::get('materials/{material}/stock-opname', [StockOpnameController::class, 'create'])->name('materials.stock-opname');
        Route::post('materials/{material}/stock-opname', [StockOpnameController::class, 'store'])->name('materials.stock-opname.store');
        Route::resource('materials', MaterialController::class);

        // Material Planning (MRP per Batch - Step G2 & G5)
        Route::get('material-planning', [MaterialPlanningController::class, 'index'])->name('material-planning.index');
        Route::get('material-planning/{batch}', [MaterialPlanningController::class, 'show'])->name('material-planning.show');
        Route::post('material-planning/{batch}/calculate', [MaterialPlanningController::class, 'calculate'])->name('material-planning.calculate');
        Route::post('material-planning/{batch}/allocate', [MaterialPlanningController::class, 'allocate'])->name('material-planning.allocate');

        // Stock Movements History (Step G4 - Read Only)
        Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');

        // Supplier Master (Safe Delete & Active Toggle)
        Route::post('suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])->name('suppliers.toggle-active');
        Route::resource('suppliers', SupplierController::class);

        // Purchase Orders (Step G3 & G4 - No physical destroy allowed)
        Route::get('purchases/history', [PurchaseController::class, 'history'])->name('purchases.history');
        Route::post('purchases/{purchase}/submit-approval', [PurchaseController::class, 'submitApproval'])->name('purchases.submit-approval');
        Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
        Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
        Route::resource('purchases', PurchaseController::class)->except(['destroy']);
    });


