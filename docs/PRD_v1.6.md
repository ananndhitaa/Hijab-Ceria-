# PRODUCT REQUIREMENTS DOCUMENT (PRD) — v1.6
## Hijab ERP System (Admin & Financial Modules)

**Status**: ACTIVE SOURCE OF TRUTH  
**Version**: 1.6  
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
| v1.5 | 2026-08-28 | Payment Method Standardization, Invoice CSV Export, Admin Sales Revenue Report, and CSV Formula-Injection Protection. |
| **v1.6** | **2026-08-28** | **Sales Order Pricing Rule: Lock Master Selling Price + Immutable Transaction Snapshot.** |

---

## 1. Sales Order Pricing Architecture (PRD v1.6)

### 1.1 Dual Price Authority Model
To preserve accounting and historical audit integrity across transactions:
1. **MASTER SELLING PRICE (`product_variants.base_selling_price`)**:
   - The authoritative selling price source for **NEW** transaction selection and variant changes.
   - Maintained exclusively in Product Variant Master.
   - Admin price overrides on Sales Order forms are **STRICTLY PROHIBITED**.
2. **TRANSACTION PRICE SNAPSHOT (`order_items.unit_selling_price`)**:
   - The immutable historical price authority for an `OrderItem` once recorded in a Sales Order.
   - Automatically copied from `product_variants.base_selling_price` upon item creation or variant replacement.
   - Subsequent changes to the master price in Product Variant Master **NEVER** rewrite historical `order_items`.

---

### 1.2 Sales Order Creation Rules
- When selecting a Variant, the system automatically displays that Variant's `base_selling_price`.
- `Harga Satuan` on the Sales Order Create form is a **READ-ONLY** surface (no numeric input, no spinner controls).
- **Backend Enforcement**: Server ignores any client-submitted `unit_selling_price`. The backend strictly queries `ProductVariant::findOrFail($variantId)->base_selling_price` to populate `order_items.unit_selling_price`.
- **Unpriced Variant Guard**: If `product_variants.base_selling_price IS NULL` or $\le 0$, the variant cannot be ordered.
  - Rejection message: `"Harga jual varian belum ditetapkan. Atur harga pada Master Produk terlebih dahulu."`

---

### 1.3 Sales Order Edit & Snapshot Preservation Rules
When editing an existing Sales Order (while still editable under existing `OrderStatusService::canEdit()` rules):

| Item Condition | Action / Price Source | Master Price Validation Required? |
|----------------|----------------------|-----------------------------------|
| **Existing item + Same variant** | **Preserve existing `order_items.unit_selling_price`** | **NO** (Historical snapshot is preserved even if current master price became NULL or changed). |
| **Existing item + Changed variant** | Fetch **new** `Variant.base_selling_price` | **YES** (Must have valid master price $> 0$). |
| **New item added on edit** | Fetch selected `Variant.base_selling_price` | **YES** (Must have valid master price $> 0$). |
| **Item removed** | Item deleted from order | N/A |
| **Order locked** (Invoice / Batch exists) | Edit rejected under existing rules | N/A |

- **Item Ownership & Anti-Tampering**: When `items.*.order_item_id` is submitted on edit:
  - Must exist and belong to the specific `Order` being updated (`order_items.order_id === order.id`).
  - Cross-order item ID injection and duplicate item IDs are rejected server-side.

---

### 1.4 Downstream Impact
1. **Invoice Total**: Invoices are snapshots created from $\sum(\text{order\_items.qty\_ordered} \times \text{order\_items.unit\_selling\_price})$ transaction snapshots. Never re-queried from master prices.
2. **Sales Revenue Report**: Realized sales revenue is calculated strictly from $\sum(\text{order\_items.qty\_ordered} \times \text{order\_items.unit\_selling\_price})$ transaction snapshots.
