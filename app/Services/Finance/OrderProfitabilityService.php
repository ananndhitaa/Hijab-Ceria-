<?php

namespace App\Services\Finance;

use App\Enums\ExpenseType;
use App\Models\Finance\BusinessExpense;
use App\Models\Sales\Order;

class OrderProfitabilityService
{
    public function __construct(
        private OrderHppCalculationService $orderHppService
    ) {}

    /**
     * Calculate authoritative Order Revenue from immutable OrderItem transaction snapshots.
     *
     * Revenue = SUM(order_items.qty_ordered * order_items.unit_selling_price)
     *
     * Invariants:
     * - Uses exact BCMath precision.
     * - Fails safely with DATA_INTEGRITY_ERROR on negative quantity or price.
     * - Never uses product_variants.base_selling_price, invoice total, or payment amounts.
     *
     * @param Order $order
     * @return array{
     *     amount: string|null,
     *     status: string,
     *     error: string|null
     * }
     */
    public function calculateRevenue(Order $order): array
    {
        $order->loadMissing(['items']);
        $items = $order->items;

        if ($items->isEmpty()) {
            return [
                'amount' => '0.00',
                'status' => 'READY',
                'error'  => null,
            ];
        }

        $totalRevenue = '0.0000';

        foreach ($items as $item) {
            $qty = (string) $item->qty_ordered;
            $price = (string) $item->unit_selling_price;

            // Invariant: Negative values are invalid/corrupt
            if (bccomp($qty, '0.0000', 4) < 0 || bccomp($price, '0.00', 2) < 0) {
                return [
                    'amount' => null,
                    'status' => 'DATA_INTEGRITY_ERROR',
                    'error'  => "Integritas data transaksi tidak valid: Kuantitas ({$qty}) atau harga jual per unit ({$price}) bernilai negatif pada OrderItem ID {$item->id}.",
                ];
            }

            $subtotal = bcmul($qty, $price, 4);
            $totalRevenue = bcadd($totalRevenue, $subtotal, 4);
        }

        return [
            'amount' => number_format((float) $totalRevenue, 2, '.', ''),
            'status' => 'READY',
            'error'  => null,
        ];
    }

    /**
     * Get Attributed OPERATING Expenses for this Order.
     *
     * Invariants:
     * - Strictly filters by order_id == Order.id AND expense_type == OPERATING.
     * - Excludes MARKETING, INTEREST, TAX, DEPRECIATION, AMORTIZATION from Order Net Profit.
     * - Excludes global expenses (order_id IS NULL).
     *
     * @param Order $order
     * @return array{
     *     amount: string,
     *     items: array<int, array{
     *         id: int,
     *         expense_date: string|null,
     *         description: string|null,
     *         amount: string,
     *         created_by_name: string
     *     }>,
     *     count: int
     * }
     */
    public function getAttributedOperatingExpenses(Order $order): array
    {
        $expenses = BusinessExpense::with(['createdByUser'])
            ->where('order_id', $order->id)
            ->where('expense_type', ExpenseType::OPERATING)
            ->latest('expense_date')
            ->get();

        $total = '0.00';
        $items = [];

        foreach ($expenses as $exp) {
            $amt = (string) $exp->amount;
            $total = bcadd($total, $amt, 2);

            $items[] = [
                'id'              => $exp->id,
                'expense_date'    => $exp->expense_date ? $exp->expense_date->format('d/m/Y') : null,
                'description'     => $exp->description,
                'amount'          => number_format((float) $amt, 2, '.', ''),
                'created_by_name' => $exp->createdByUser?->name ?? 'System',
            ];
        }

        return [
            'amount' => number_format((float) $total, 2, '.', ''),
            'items'  => $items,
            'count'  => count($items),
        ];
    }

    /**
     * Calculate comprehensive Order-level profitability.
     *
     * Profitability Metrics:
     * - Revenue Order = SUM(qty_ordered * unit_selling_price)
     * - HPP Order = OrderHppCalculationService::calculateOrderHpp()
     * - Gross Profit = Revenue - HPP (allows negative)
     * - Gross Margin (%) = (Gross Profit / Revenue) * 100 (NULL if Revenue == 0)
     * - Attributed Operating Expense = SUM(BusinessExpense amount WHERE order_id AND expense_type == OPERATING)
     * - Net Profit = Gross Profit - Attributed Operating Expense (allows negative)
     * - Net Margin (%) = (Net Profit / Revenue) * 100 (NULL if Revenue == 0)
     *
     * @param Order $order
     * @return array{
     *     status: string,
     *     is_ready: bool,
     *     revenue_amount: string|null,
     *     hpp_amount: string|null,
     *     gross_profit: string|null,
     *     gross_margin: string|null,
     *     operating_expense: string,
     *     net_profit: string|null,
     *     net_margin: string|null,
     *     error: string|null,
     *     order_hpp: array<string, mixed>,
     *     operating_expenses: array<string, mixed>
     * }
     */
    public function calculateOrderProfitability(Order $order): array
    {
        // 1. Cancelled Order check (Enum-safe and raw database value safe)
        $rawStatus = $order->getRawOriginal('status') ?? ($order->status instanceof \BackedEnum ? $order->status->value : (string) ($order->status ?? ''));
        if (strtoupper((string) $rawStatus) === 'CANCELLED') {
            return [
                'status'             => 'ORDER_CANCELLED',
                'is_ready'           => false,
                'revenue_amount'     => null,
                'hpp_amount'         => null,
                'gross_profit'       => null,
                'gross_margin'       => null,
                'operating_expense'  => '0.00',
                'net_profit'         => null,
                'net_margin'         => null,
                'error'              => 'Pesanan dibatalkan dan tidak dihitung dalam analisis profitabilitas.',
                'order_hpp'          => [],
                'operating_expenses' => ['amount' => '0.00', 'items' => [], 'count' => 0],
            ];
        }

        // 2. Revenue Calculation & Integrity check
        $revenueRes = $this->calculateRevenue($order);
        if ($revenueRes['status'] === 'DATA_INTEGRITY_ERROR') {
            return [
                'status'             => 'DATA_INTEGRITY_ERROR',
                'is_ready'           => false,
                'revenue_amount'     => null,
                'hpp_amount'         => null,
                'gross_profit'       => null,
                'gross_margin'       => null,
                'operating_expense'  => '0.00',
                'net_profit'         => null,
                'net_margin'         => null,
                'error'              => $revenueRes['error'],
                'order_hpp'          => [],
                'operating_expenses' => ['amount' => '0.00', 'items' => [], 'count' => 0],
            ];
        }

        $revenue = $revenueRes['amount'];

        // 3. Attributed Operating Expenses (Locked: OPERATING only)
        $operatingExpenses = $this->getAttributedOperatingExpenses($order);
        $operatingExpenseAmount = $operatingExpenses['amount'];

        // 4. HPP Calculation & Readiness check
        $orderHpp = $this->orderHppService->calculateOrderHpp($order);

        if (! $orderHpp['is_ready']) {
            return [
                'status'             => 'HPP_NOT_READY',
                'is_ready'           => false,
                'revenue_amount'     => $revenue,
                'hpp_amount'         => null,
                'gross_profit'       => null,
                'gross_margin'       => null,
                'operating_expense'  => $operatingExpenseAmount,
                'net_profit'         => null,
                'net_margin'         => null,
                'error'              => $orderHpp['error'] ?? 'HPP Pesanan belum berstatus Final.',
                'order_hpp'          => $orderHpp,
                'operating_expenses' => $operatingExpenses,
            ];
        }

        // 5. Profitability Calculations (When HPP is READY)
        $hpp = (string) $orderHpp['total_hpp'];

        // Gross Profit = Revenue - HPP (exact BCMath, allow negative)
        $grossProfit = bcsub($revenue, $hpp, 2);

        // Gross Margin = (Gross Profit / Revenue) * 100
        $grossMargin = null;
        if (bccomp($revenue, '0.00', 2) > 0) {
            $gmRatio = bcdiv($grossProfit, $revenue, 6);
            $grossMargin = number_format((float) bcmul($gmRatio, '100', 4), 4, '.', '');
        }

        // Net Profit = Gross Profit - Attributed Operating Expense (exact BCMath, allow negative)
        $netProfit = bcsub($grossProfit, $operatingExpenseAmount, 2);

        // Net Margin = (Net Profit / Revenue) * 100
        $netMargin = null;
        if (bccomp($revenue, '0.00', 2) > 0) {
            $nmRatio = bcdiv($netProfit, $revenue, 6);
            $netMargin = number_format((float) bcmul($nmRatio, '100', 4), 4, '.', '');
        }

        return [
            'status'             => 'READY',
            'is_ready'           => true,
            'revenue_amount'     => $revenue,
            'hpp_amount'         => $hpp,
            'gross_profit'       => number_format((float) $grossProfit, 2, '.', ''),
            'gross_margin'       => $grossMargin,
            'operating_expense'  => $operatingExpenseAmount,
            'net_profit'         => number_format((float) $netProfit, 2, '.', ''),
            'net_margin'         => $netMargin,
            'error'              => null,
            'order_hpp'          => $orderHpp,
            'operating_expenses' => $operatingExpenses,
        ];
    }

    /**
     * Build per-item revenue breakdown from immutable OrderItem transaction snapshots.
     *
     * Returns a simple array of per-item objects that the Owner profitability
     * detail view can display without any new database queries.
     *
     * This does NOT duplicate or replace calculateOrderProfitability();
     * it merely decomposes the revenue line into individual items for
     * easier Owner comprehension.
     *
     * @param Order $order
     * @return array<int, array{
     *     variant_name: string,
     *     product_name: string,
     *     qty: string,
     *     unit_price: string,
     *     subtotal: string
     * }>
     */
    public function calculateOrderItemBreakdown(Order $order): array
    {
        $order->loadMissing(['items.variant.product']);
        $breakdown = [];

        foreach ($order->items as $item) {
            $qty = (string) $item->qty_ordered;
            $price = (string) $item->unit_selling_price;
            $subtotal = bcmul($qty, $price, 2);

            $breakdown[] = [
                'sku'          => $item->variant?->sku ?? '-',
                'variant_name' => $item->variant?->variant_name ?? '-',
                'product_name' => $item->variant?->product?->product_name ?? '-',
                'qty'          => $qty,
                'unit_price'   => number_format((float) $price, 2, '.', ''),
                'subtotal'     => number_format((float) $subtotal, 2, '.', ''),
            ];
        }

        return $breakdown;
    }
}
