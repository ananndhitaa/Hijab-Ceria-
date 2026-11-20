# PRODUCT REQUIREMENTS DOCUMENT (PRD) — v1.5
## Hijab ERP System (Admin & Financial Modules)

**Status**: ACTIVE SOURCE OF TRUTH  
**Version**: 1.5  
**Date**: 2026-08-28  

---

## Revision History

| Version | Date | Description |
|---------|------|-------------|
| v1.0 | 2026-08-20 | Initial Phase 1 & 2 Core Specifications (MRP, Production Results, Sales Order, Invoice). |
| v1.1 | 2026-08-24 | Split Production Batch, Kanban transitions, secure order tracking token. |
| v1.2 | 2026-08-27 | Admin Sales Order Detail UI overview hierarchy, contextual action rules. |
| v1.3 | 2026-08-28 | Admin Sales Order Detail Expense overview simplification, NAYARA Design System alignment. |
| v1.4 | 2026-08-28 | Payment Auto-Amount Rule (DP 50%, Pelunasan exact outstanding, Lainnya manual) & Server-Side Enforcement. |
| **v1.5** | **2026-08-28** | **Payment Method Standardization, Invoice CSV Export, Admin Sales Revenue Report, and CSV Formula-Injection Protection.** |

---

## 1. Payment Method Standardization

### 1.1 Canonical Methods
The application supports three canonical payment methods:
1. `Transfer Bank`
2. `Tunai`
3. `QRIS`

### 1.2 Bank Transfer Rules
- Specific bank identifiers (`Transfer BCA`, `Transfer Mandiri`, `Transfer BNI`, `Transfer BRI`, etc.) are **NOT** distinct payment methods.
- All bank transfers must be recorded, stored, and displayed as **`Transfer Bank`**.
- Specific bank details or transaction numbers must be placed in `reference_no` or `notes`.
- The Admin payment recording modal uses a controlled `<select>` with the canonical cases (free-text entry is discontinued).

### 1.3 Legacy Normalization
- All existing records with variant bank transfer strings (`Transfer BCA`, `Transfer Mandiri`, `Transfer BNI`, `Transfer BRI`, `Transfer`, `TRANSFER_BCA`, etc.) are normalized to `Transfer Bank`.
- All display views (`Invoice Detail`, `Sales Order Detail`, `Payment History`, reports) display `Transfer Bank` consistently.

---

## 2. Invoice CSV Export

### 2.1 Overview & Security
- Admin users can export flat line-item CSV files directly from the Invoice Detail page (`admin.invoices.export-csv`).
- **Security Boundary**: The export includes only transaction and invoice billing data authorized for the Admin role. It strictly excludes Owner financial metrics (no HPP, material costs, purchase costs, gross/net profit, margin, ROI, MER, or EBITDA).

### 2.2 CSV Structure & Compatibility
- **Encoding**: UTF-8 with BOM (`\xEF\xBB\xBF`) for automatic compatibility with Excel on Windows/macOS.
- **Numbers**: Machine-readable numeric strings (`13500000.00`, `50`), without "Rp" currency symbols or thousands commas.
- **Formula Injection Defense**: All textual fields (Customer, Product, Variant, SKU) are sanitized against CSV formula injection (prefixing dangerous characters `=`, `+`, `-`, `@` with a single quote `'`).
- **Columns**:
  1. `Nomor Invoice`
  2. `Tanggal Invoice`
  3. `Status Invoice`
  4. `Nomor Sales Order`
  5. `Customer`
  6. `Telepon`
  7. `Produk`
  8. `Varian`
  9. `SKU`
  10. `Qty`
  11. `Harga Satuan`
  12. `Subtotal`
  13. `Total Invoice`
  14. `Total Terbayar`
  15. `Sisa Tagihan`

---

## 3. Admin Sales Revenue Report (`/admin/reports/sales`)

### 3.1 Purpose & Role Scope
- Provides Admin with a transaction-level sales and billing summary report.
- This is a **Sales Report** based on order dates, not an Owner profitability or cash-flow report.

### 3.2 Reporting Period Basis
- Filtered by `orders.order_date` in `Asia/Jakarta` timezone (`start_date <= orders.order_date <= end_date`).
- Default period: Current calendar month (`startOfMonth` to `endOfMonth`).

### 3.3 Status Inclusion Rule (Realized Sales)
- Only Sales Orders that have reached confirmed deal status count as realized sales revenue:
  - `DEAL`
  - `BAHAN`
  - `PRINTING`
  - `PRODUKSI`
  - `QC`
  - `PACKING`
  - `DIKIRIM`
  - `SELESAI`
- `KOMUNIKASI` (pre-deal negotiation) is **strictly excluded** from revenue calculations.

### 3.4 Metric Definitions
1. **Total Pendapatan (Revenue)**:
   $$\text{Revenue} = \sum_{\text{Orders in Period}} \left(\sum \text{order\_items.qty\_ordered} \times \text{order\_items.unit\_selling\_price}\right)$$
   - Derived strictly from item snapshot selling prices using BCMath decimal-safe arithmetic.
   - Never derived from invoice or payment amounts.
2. **Pembayaran Diterima (Total Terbayar)**:
   $$\text{Pembayaran Diterima} = \sum_{\text{Invoices of Included Orders}} \left(\sum_{\text{status} = \text{CONFIRMED}} \text{payments.amount}\right)$$
   - Represents total confirmed payments received against invoices for the orders included in the selected order period.
   - `PENDING` and `CANCELLED` payments are strictly excluded.
3. **Sisa Tagihan (Outstanding)**:
   $$\text{Sisa Tagihan} = \sum_{\text{Existing Invoices of Included Orders}} \text{invoice.outstanding\_balance}$$
   - Only calculated for orders that have an existing Invoice.
   - For orders without an invoice (`Invoice = Belum dibuat`), outstanding is displayed as `—` (not counted as invoice debt).

### 3.5 Sales Report CSV Export (`admin.reports.sales.export-csv`)
- Shares identical query and calculation logic with the web report.
- Columns:
  `Tanggal Order`, `No Sales Order`, `Customer`, `Total Qty`, `Total Pendapatan`, `No Invoice`, `Status Invoice`, `Total Terbayar`, `Sisa Tagihan`
- Summary total row at the bottom:
  `TOTAL`, ``, ``, `[Total Qty]`, `[Total Revenue]`, ``, ``, `[Total Paid]`, `[Total Outstanding]`
- Sanitized against spreadsheet formula injection with UTF-8 BOM.
