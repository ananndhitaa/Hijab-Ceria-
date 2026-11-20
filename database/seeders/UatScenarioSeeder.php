<?php

namespace Database\Seeders;

use App\Enums\BatchStatus;
use App\Enums\ExpenseType;
use App\Enums\InvoiceStatus;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Finance\BusinessExpense;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Customer;
use App\Models\Master\ProductVariant;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\OrderStatusHistory;
use App\Models\System\User;
use App\Services\System\DocumentNumberGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UatScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $numberGenerator = app(DocumentNumberGenerator::class);

        $adminUser    = User::where('email', 'admin@example.com')->first();
        $gudangUser   = User::where('email', 'gudang@example.com')->first();
        $produksiUser = User::where('email', 'produksi@example.com')->first();

        $voalPolosDustyPink = ProductVariant::where('sku', 'HJB-VP-001')->first();
        $voalMotifBunga     = ProductVariant::where('sku', 'HJB-VM-001')->first();

        $cust1 = Customer::first();
        $cust2 = Customer::skip(1)->first();
        $cust3 = Customer::skip(2)->first();

        $baseDate = Carbon::create(2026, 9, 6, 12, 0, 0, 'Asia/Jakarta');

        DB::transaction(function () use (
            $numberGenerator,
            $adminUser,
            $gudangUser,
            $produksiUser,
            $voalPolosDustyPink,
            $voalMotifBunga,
            $cust1,
            $cust2,
            $cust3,
            $baseDate
        ) {
            // ══════════════════════════════════════════════════════════════════
            // SCENARIO 1: Order Menunggu Produksi (Status DEAL)
            // ══════════════════════════════════════════════════════════════════
            $order1Date = $baseDate->copy()->subDays(2);
            $order1 = Order::create([
                'customer_id' => $cust1->id,
                'order_no'    => $numberGenerator->generate('SO', $order1Date),
                'order_date'  => $order1Date,
                'status'      => OrderStatus::DEAL,
                'notes'       => 'Pesanan baru disetujui, menunggu pembuatan batch dan alokasi bahan.',
            ]);

            OrderItem::create([
                'order_id'           => $order1->id,
                'variant_id'         => $voalPolosDustyPink->id,
                'qty_ordered'        => 50,
                'unit_selling_price' => 85000,
            ]);

            OrderStatusHistory::create([
                'order_id'           => $order1->id,
                'status'             => OrderStatus::DEAL,
                'changed_by_user_id' => $adminUser->id,
                'changed_at'         => $order1Date,
                'notes'              => 'Pesanan disetujui DEAL.',
            ]);

            // ══════════════════════════════════════════════════════════════════
            // SCENARIO 2: Completed Batch (Non-Printing) All-Good QC & Ready HPP
            // ══════════════════════════════════════════════════════════════════
            $order2Date = $baseDate->copy()->subDays(5);
            $order2 = Order::create([
                'customer_id' => $cust2->id,
                'order_no'    => $numberGenerator->generate('SO', $order2Date),
                'order_date'  => $order2Date,
                'status'      => OrderStatus::SELESAI,
                'notes'       => 'Pesanan selesai penuh, produksi lolos 100% all-good QC.',
            ]);

            $orderItem2 = OrderItem::create([
                'order_id'           => $order2->id,
                'variant_id'         => $voalPolosDustyPink->id,
                'qty_ordered'        => 100,
                'unit_selling_price' => 85000,
            ]);

            $batch1Date = $baseDate->copy()->subDays(4);
            $batch1 = ProductionBatch::create([
                'order_item_id'                                 => $orderItem2->id,
                'batch_no'                                      => $numberGenerator->generate('BAT', $batch1Date),
                'target_qty'                                    => 100,
                'status'                                        => BatchStatus::SELESAI,
                'requires_printing'                             => false,
                'printing_cost_per_unit_snapshot'               => '0.00',
                'direct_labor_cost_per_unit_snapshot'           => '5000.00',
                'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
                'other_direct_cost_per_unit_snapshot'           => '500.00',
                'material_usage_recorded_at'                    => $baseDate->copy()->subDays(3),
                'material_usage_recorded_by'                    => $gudangUser->id,
                'planned_start_date'                            => $baseDate->copy()->subDays(4),
                'planned_end_date'                              => $baseDate->copy()->subDays(2),
            ]);

            // Materials for Batch 1 from BOM
            foreach ($voalPolosDustyPink->bomItems as $bom) {
                $allocatedQty = bcmul((string) $bom->qty_per_unit, '100', 4);
                $matStock = MaterialStock::where('material_id', $bom->material_id)->first();
                $unitCost = $matStock ? (string) $matStock->avg_unit_cost : '1000.00';

                $bm = BatchMaterial::create([
                    'batch_id'      => $batch1->id,
                    'material_id'   => $bom->material_id,
                    'required_qty'  => $allocatedQty,
                    'allocated_qty' => $allocatedQty,
                    'used_qty'      => $allocatedQty,
                ]);

                StockMovement::create([
                    'material_id'         => $bom->material_id,
                    'movement_type'       => MovementType::OUT,
                    'qty'                 => $allocatedQty,
                    'unit_cost_snapshot'  => $unitCost,
                    'total_cost_snapshot' => bcmul($allocatedQty, $unitCost, 2),
                    'reference_type'      => 'batch_materials',
                    'reference_id'        => $bm->id,
                    'movement_date'       => $baseDate->copy()->subDays(4)->toDateString(),
                    'created_by'          => $gudangUser->id,
                ]);
            }

            $pRes1 = ProductionResult::create([
                'batch_id'    => $batch1->id,
                'actual_qty'  => 100,
                'result_date' => $baseDate->copy()->subDays(3),
                'notes'       => 'Selesai 100 unit tanpa kendala.',
                'recorded_by' => $produksiUser->id,
            ]);

            QcResult::create([
                'production_result_id' => $pRes1->id,
                'good_qty'             => 100,
                'reject_qty'           => 0,
                'qc_date'              => $baseDate->copy()->subDays(2),
                'notes'                => 'Lolos standar kualitas A.',
                'qc_by'                => $produksiUser->id,
            ]);

            // Invoice & Payment for Order 2
            $invoice2Date = $baseDate->copy()->subDays(5);
            $invoice2 = Invoice::create([
                'order_id'     => $order2->id,
                'invoice_no'   => $numberGenerator->generate('INV', $invoice2Date),
                'invoice_date' => $invoice2Date,
                'total_amount' => 8500000,
                'status'       => InvoiceStatus::PAID,
            ]);

            Payment::create([
                'invoice_id'     => $invoice2->id,
                'payment_date'   => $baseDate->copy()->subDays(4),
                'amount'         => 8500000,
                'payment_method' => PaymentMethod::TRANSFER_BANK,
                'payment_type'   => PaymentType::PELUNASAN,
                'status'         => PaymentStatus::CONFIRMED,
                'confirmed_by'   => $adminUser->id,
                'confirmed_at'   => $baseDate->copy()->subDays(4),
            ]);

            // ══════════════════════════════════════════════════════════════════
            // SCENARIO 3: Printing Variant with Partial Reject & Replacement Batch
            // ══════════════════════════════════════════════════════════════════
            $order3Date = $baseDate->copy()->subDays(4);
            $order3 = Order::create([
                'customer_id' => $cust3->id,
                'order_no'    => $numberGenerator->generate('SO', $order3Date),
                'order_date'  => $order3Date,
                'status'      => OrderStatus::SELESAI,
                'notes'       => 'Pesanan Voal Motif Bunga. Terdapat 10 pcs reject pada batch 1 yang diproduksi ulang di batch 2.',
            ]);

            $orderItem3 = OrderItem::create([
                'order_id'           => $order3->id,
                'variant_id'         => $voalMotifBunga->id,
                'qty_ordered'        => 100,
                'unit_selling_price' => 95000,
            ]);

            // Batch 1 (Primary - 100 target, 90 good, 10 reject)
            $batch2Date = $baseDate->copy()->subDays(4);
            $batch2 = ProductionBatch::create([
                'order_item_id'                                 => $orderItem3->id,
                'batch_no'                                      => $numberGenerator->generate('BAT', $batch2Date),
                'target_qty'                                    => 100,
                'status'                                        => BatchStatus::SELESAI,
                'requires_printing'                             => true,
                'printing_cost_per_unit_snapshot'               => '3500.00',
                'direct_labor_cost_per_unit_snapshot'           => '5000.00',
                'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
                'other_direct_cost_per_unit_snapshot'           => '500.00',
                'material_usage_recorded_at'                    => $baseDate->copy()->subDays(3),
                'material_usage_recorded_by'                    => $gudangUser->id,
                'planned_start_date'                            => $baseDate->copy()->subDays(4),
                'planned_end_date'                              => $baseDate->copy()->subDays(2),
            ]);

            foreach ($voalMotifBunga->bomItems as $bom) {
                $allocatedQty = bcmul((string) $bom->qty_per_unit, '100', 4);
                $matStock = MaterialStock::where('material_id', $bom->material_id)->first();
                $unitCost = $matStock ? (string) $matStock->avg_unit_cost : '1000.00';

                $bm = BatchMaterial::create([
                    'batch_id'      => $batch2->id,
                    'material_id'   => $bom->material_id,
                    'required_qty'  => $allocatedQty,
                    'allocated_qty' => $allocatedQty,
                    'used_qty'      => $allocatedQty,
                ]);

                StockMovement::create([
                    'material_id'         => $bom->material_id,
                    'movement_type'       => MovementType::OUT,
                    'qty'                 => $allocatedQty,
                    'unit_cost_snapshot'  => $unitCost,
                    'total_cost_snapshot' => bcmul($allocatedQty, $unitCost, 2),
                    'reference_type'      => 'batch_materials',
                    'reference_id'        => $bm->id,
                    'movement_date'       => $baseDate->copy()->subDays(4)->toDateString(),
                    'created_by'          => $gudangUser->id,
                ]);
            }

            $pRes2 = ProductionResult::create([
                'batch_id'    => $batch2->id,
                'actual_qty'  => 100,
                'result_date' => $baseDate->copy()->subDays(3),
                'notes'       => 'Selesai 100 unit.',
                'recorded_by' => $produksiUser->id,
            ]);

            QcResult::create([
                'production_result_id' => $pRes2->id,
                'good_qty'             => 90,
                'reject_qty'           => 10,
                'qc_date'              => $baseDate->copy()->subDays(2),
                'notes'                => '90 lolos, 10 reject cacat serat printing.',
                'qc_by'                => $produksiUser->id,
            ]);

            // Batch 2 (Replacement - 10 target, 10 good, 0 reject)
            $batch3Date = $baseDate->copy()->subDays(2);
            $batch3 = ProductionBatch::create([
                'order_item_id'                                 => $orderItem3->id,
                'batch_no'                                      => $numberGenerator->generate('BAT', $batch3Date),
                'target_qty'                                    => 10,
                'status'                                        => BatchStatus::SELESAI,
                'requires_printing'                             => true,
                'printing_cost_per_unit_snapshot'               => '3500.00',
                'direct_labor_cost_per_unit_snapshot'           => '5000.00',
                'packaging_process_cost_per_good_unit_snapshot' => '1000.00',
                'other_direct_cost_per_unit_snapshot'           => '500.00',
                'material_usage_recorded_at'                    => $baseDate->copy()->subDays(1),
                'material_usage_recorded_by'                    => $gudangUser->id,
                'planned_start_date'                            => $baseDate->copy()->subDays(2),
                'planned_end_date'                              => $baseDate->copy()->subDays(1),
            ]);

            foreach ($voalMotifBunga->bomItems as $bom) {
                $allocatedQty = bcmul((string) $bom->qty_per_unit, '10', 4);
                $matStock = MaterialStock::where('material_id', $bom->material_id)->first();
                $unitCost = $matStock ? (string) $matStock->avg_unit_cost : '1000.00';

                $bm = BatchMaterial::create([
                    'batch_id'      => $batch3->id,
                    'material_id'   => $bom->material_id,
                    'required_qty'  => $allocatedQty,
                    'allocated_qty' => $allocatedQty,
                    'used_qty'      => $allocatedQty,
                ]);

                StockMovement::create([
                    'material_id'         => $bom->material_id,
                    'movement_type'       => MovementType::OUT,
                    'qty'                 => $allocatedQty,
                    'unit_cost_snapshot'  => $unitCost,
                    'total_cost_snapshot' => bcmul($allocatedQty, $unitCost, 2),
                    'reference_type'      => 'batch_materials',
                    'reference_id'        => $bm->id,
                    'movement_date'       => $baseDate->copy()->subDays(2)->toDateString(),
                    'created_by'          => $gudangUser->id,
                ]);
            }

            $pRes3 = ProductionResult::create([
                'batch_id'    => $batch3->id,
                'actual_qty'  => 10,
                'result_date' => $baseDate->copy()->subDays(1),
                'notes'       => 'Produksi pengganti 10 unit selesai.',
                'recorded_by' => $produksiUser->id,
            ]);

            QcResult::create([
                'production_result_id' => $pRes3->id,
                'good_qty'             => 10,
                'reject_qty'           => 0,
                'qc_date'              => $baseDate->copy()->subDays(1),
                'notes'                => '10 unit pengganti lolos QC sempurna.',
                'qc_by'                => $produksiUser->id,
            ]);

            // Invoice & Payment for Order 3
            $invoice3Date = $baseDate->copy()->subDays(4);
            $invoice3 = Invoice::create([
                'order_id'     => $order3->id,
                'invoice_no'   => $numberGenerator->generate('INV', $invoice3Date),
                'invoice_date' => $invoice3Date,
                'total_amount' => 9500000,
                'status'       => InvoiceStatus::PAID,
            ]);

            Payment::create([
                'invoice_id'     => $invoice3->id,
                'payment_date'   => $baseDate->copy()->subDays(3),
                'amount'         => 9500000,
                'payment_method' => PaymentMethod::TRANSFER_BANK,
                'payment_type'   => PaymentType::PELUNASAN,
                'status'         => PaymentStatus::CONFIRMED,
                'confirmed_by'   => $adminUser->id,
                'confirmed_at'   => $baseDate->copy()->subDays(3),
            ]);

            // ══════════════════════════════════════════════════════════════════
            // SCENARIO 4: Business Expenses (Period Performance Testing)
            // ══════════════════════════════════════════════════════════════════
            BusinessExpense::create([
                'expense_date'  => $baseDate->copy()->subDays(5),
                'expense_type'  => ExpenseType::MARKETING,
                'campaign_name' => 'Meta Ads Ramadhan Launch',
                'description'   => 'Iklan bersponsor Instagram & Facebook',
                'amount'        => 1200000,
                'created_by'    => $adminUser->id,
            ]);

            BusinessExpense::create([
                'expense_date'  => $baseDate->copy()->subDays(3),
                'expense_type'  => ExpenseType::OPERATING,
                'campaign_name' => null,
                'description'   => 'Biaya listrik, air, dan internet workshop',
                'amount'        => 650000,
                'created_by'    => $adminUser->id,
            ]);

            BusinessExpense::create([
                'order_id'      => $order3->id,
                'expense_date'  => $baseDate->copy()->subDays(2),
                'expense_type'  => ExpenseType::OPERATING,
                'campaign_name' => null,
                'description'   => "Packing kayu & lakban fragile khusus ekspedisi Order {$order3->order_no}",
                'amount'        => 150000,
                'created_by'    => $adminUser->id,
            ]);
        });

        $this->command->info('✅ UatScenarioSeeder: Fresh UAT scenarios seeded cleanly.');
    }
}

