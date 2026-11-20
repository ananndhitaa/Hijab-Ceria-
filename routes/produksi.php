<?php

use App\Http\Controllers\Produksi\BatchController;
use App\Http\Controllers\Produksi\DashboardController;
use App\Http\Controllers\Produksi\ProductionResultController;
use App\Http\Controllers\Produksi\QcResultController;
use App\Http\Controllers\Produksi\ShipmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Produksi Routes (PRODUKSI role)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:PRODUKSI'])
    ->prefix('produksi')
    ->name('produksi.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Batch Work Queue & Operations (Step P1, P2, P3, P4)
        Route::get('batches', [BatchController::class, 'index'])->name('batches.index');
        Route::get('history', [BatchController::class, 'history'])->name('history');
        Route::get('batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
        Route::post('batches/{batch}/start', [BatchController::class, 'start'])->name('batches.start');
        Route::post('batches/{batch}/complete-printing', [BatchController::class, 'completePrinting'])->name('batches.complete-printing');

        // Production Result Recording (Step P3)
        Route::get('batches/{batch}/production-result/create', [ProductionResultController::class, 'create'])->name('batches.production-result.create');
        Route::post('batches/{batch}/production-result', [ProductionResultController::class, 'store'])->name('batches.production-result.store');
        Route::post('batches/{batch}/materials/{batchMaterial}/additional', [ProductionResultController::class, 'requestAdditionalMaterial'])->name('batches.materials.additional');

        // Quality Control Result (Step P4)
        Route::get('batches/{batch}/qc/create', [QcResultController::class, 'create'])->name('batches.qc.create');
        Route::post('batches/{batch}/qc', [QcResultController::class, 'store'])->name('batches.qc.store');

        // Packing Completion (Step P5)
        Route::post('batches/{batch}/complete-packing', [BatchController::class, 'completePacking'])->name('batches.complete-packing');

        // Shipment / Pengiriman Workflow (Step P6C / PRO-08)
        Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');
        Route::get('shipments/create', [ShipmentController::class, 'create'])->name('shipments.create');
        Route::post('shipments', [ShipmentController::class, 'store'])->name('shipments.store');
        Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
        Route::post('shipments/{shipment}/ship', [ShipmentController::class, 'ship'])->name('shipments.ship');
        Route::post('shipments/{shipment}/deliver', [ShipmentController::class, 'deliver'])->name('shipments.deliver');
    });
