# PRODUCT REQUIREMENTS DOCUMENT (PRD) — v1.4
## Hijab ERP System (Admin & Financial Modules)

**Status**: ACTIVE SOURCE OF TRUTH  
**Version**: 1.4  
**Date**: 2026-08-28  

---

## Revision History

| Version | Date | Description |
|---------|------|-------------|
| v1.0 | 2026-08-20 | Initial Phase 1 & 2 Core Specifications (MRP, Production Results, Sales Order, Invoice). |
| v1.1 | 2026-08-24 | Split Production Batch, Kanban transitions, secure order tracking token. |
| v1.2 | 2026-08-27 | Admin Sales Order Detail UI overview hierarchy, contextual action rules. |
| v1.3 | 2026-08-28 | Admin Sales Order Detail Expense overview simplification, NAYARA Design System alignment. |
| **v1.4** | **2026-08-28** | **Payment Auto-Amount Rule (DP 50%, Pelunasan exact outstanding, Lainnya manual) & Server-Side Enforcement.** |

---

## 1. Payment Auto-Amount Business Rule (v1.4)

### 1.1 Overview
To accelerate payment entry and guarantee financial integrity across invoice transactions, payment amounts are auto-populated on the frontend and strictly validated server-side based on `PaymentType`:

```
PaymentType:
- DP (Uang Muka)
- PELUNASAN (Pelunasan)
- LAINNYA (Lainnya / Nominal Manual)
```

> **Constraint**: Existing database enum values (`DP`, `PELUNASAN`, `LAINNYA`) remain unchanged.

---

### 1.2 Formulas & Enforcement Rules

#### A. DP (Uang Muka)
- **Calculation Formula**:
  $$\text{expected\_dp} = \min\left(\text{bcdiv}(\text{invoice.total\_amount}, 2, 2), \text{outstanding\_balance}\right)$$
- **Frontend Behavior**:
  - Automatically sets `Nominal Pembayaran` to exact server-calculated `dpAmount`.
  - Amount input is `readonly` to prevent accidental manual mismatch.
  - Helper label: *"50% dari total tagihan"* (or capped amount indicator if outstanding < 50%).
- **Backend Enforcement (`PaymentService`)**:
  - Evaluated inside `Invoice::lockForUpdate()` database transaction.
  - Submitted `amount` **MUST EQUAL** `expected_dp` using decimal-safe `bccomp($amount, $expectedDp, 2) === 0`.
  - Rejection message: `"Nominal DP harus sebesar 50% dari total tagihan."`

#### B. PELUNASAN
- **Calculation Formula**:
  $$\text{expected\_pelunasan} = \text{outstanding\_balance}$$
- **Frontend Behavior**:
  - Automatically sets `Nominal Pembayaran` to exact `outstandingBalance`.
  - Amount input is `readonly`.
  - Helper label: *"Sesuai sisa tagihan"*.
- **Backend Enforcement (`PaymentService`)**:
  - Evaluated inside `Invoice::lockForUpdate()`.
  - Submitted `amount` **MUST EQUAL** current `outstanding_balance`.
  - Rejection message: `"Nominal pelunasan harus sesuai dengan sisa tagihan."`
  - Following successful execution, Invoice status transitions to `PAID`.

#### C. LAINNYA (Manual Partial Payment)
- **Frontend Behavior**:
  - Unlocks `Nominal Pembayaran` for manual user input.
  - Helper label: *"Masukkan nominal pembayaran"*.
- **Backend Enforcement (`PaymentService`)**:
  - Validates $0 < \text{amount} \le \text{outstanding\_balance}$.
  - Rejection message for overpayment: `"Jumlah pembayaran melebihi sisa tagihan. Maksimal pembayaran yang dapat dicatat adalah Rp {max}."`

---

### 1.3 Outstanding Balance Integrity
- **Formula**:
  $$\text{outstanding\_balance} = \max\left(0, \text{invoice.total\_amount} - \sum_{\text{status} = \text{CONFIRMED}} \text{payments.amount}\right)$$
- `PENDING` and `CANCELLED` payments **DO NOT** reduce outstanding balance.
- All calculations strictly utilize **BCMath** decimal-safe operations (`bcadd`, `bcsub`, `bcdiv`, `bccomp`).
- Concurrency protection is strictly guaranteed via `Invoice::lockForUpdate()`.
