# OPEN DECISIONS DOCUMENTATION — Hijab ERP System

> **Aturan Penting**: Semua poin di bawah ini adalah keputusan arsitektur/bisnis yang belum dikunci secara final dalam spesifikasi. Sesuai panduan project, developer **TIDAK BOLEH MENEBAK** atau membuat rule sendiri sebelum disetujui pengguna.

---

## LOCKED DECISIONS (Sudah Dikunci 100%)

Seluruh keputusan arsitektur dan bisnis di bawah ini telah disepakati dan dikunci secara final. Dokumentasi detailnya ada di `docs/business-rules.md`, `PROJECT_SPEC.md`, dan `docs/phase-1-final-audit.md`.

| # | Keputusan | Status | Ringkasan Keputusan |
|---|-----------|--------|---------------------|
| 1 | Master Selling Price Storage | ✅ LOCKED | `product_variants.base_selling_price` `DECIMAL(18,2) NULLABLE`. Dicopy ke `order_items.unit_selling_price` sebagai transaction snapshot. Perubahan master price tidak mengubah order lama. |
| 2 | Purchase Receiving | ✅ LOCKED | **Full Receiving Only**. Tidak ada partial receipt. Status: `DRAFT`, `ORDERED`, `RECEIVED`, `CANCELLED`. Tidak ada tabel `purchase_receipts` / `purchase_receipt_items`. |
| 3 | Supporting Status Enum Values | ✅ LOCKED | Semua enum disimpan sebagai `VARCHAR` di MySQL (bukan MySQL ENUM). Nilai dikunci di PHP backed enum. Lihat bagian detail di bawah. |
| 4 | Invoice Cardinality | ✅ LOCKED | Order `hasOne` Invoice. `invoices.order_id` UNIQUE. Invoice `hasMany` Payments. |
| 5 | Shipment Cardinality | ✅ LOCKED | Order `hasMany` Shipments. Satu order dapat dikirim dalam satu atau beberapa shipment. |
| 6 | Auto-Numbering Formats | ✅ LOCKED | Sales Order (`SO-YYYYMM-XXXX`), Production Batch (`BAT-YYYYMM-XXXX`), Purchase Order (`PO-YYYYMM-XXXX`), Invoice (`INV-YYYYMM-XXXX`). Sequence 4 digit reset bulanan per document type. Concurrency-safe & Unique. |
| 7 | Admin Payment Recording Status | ✅ LOCKED | Pencatatan pembayaran manual oleh Admin default ke status `CONFIRMED`. Perhitungan paid amount hanya menghitung status `CONFIRMED`. Overpayment ditolak secara konkurensi aman di bawah row lock Invoice. Tidak ada kolom tambahan tanpa revisi schema. |

### Detail Locked Supporting Status Values (Keputusan #3)

| Enum | Values |
|------|--------|
| `UserStatus` | `ACTIVE`, `INACTIVE` |
| `BatchStatus` | `BAHAN`, `PRINTING`, `PRODUKSI`, `QC`, `PACKING`, `SELESAI`, `CANCELLED` |
| `PurchaseStatus` | `DRAFT`, `ORDERED`, `RECEIVED`, `CANCELLED` |
| `ShipmentStatus` | `PENDING`, `PACKED`, `SHIPPED`, `DELIVERED`, `RETURNED` |
| `PaymentStatus` | `PENDING`, `CONFIRMED`, `CANCELLED` |

> **Catatan BatchStatus**: Production batch dimulai pada tahap `BAHAN`. Tidak menggunakan `PENDING` sebagai status awal.

---

## OPEN DECISIONS (Belum Dikunci)

**NO BLOCKING OPEN DECISIONS**

Semua keputusan arsitektur, data model, enum, kardinalitas relasi, dan format penomoran dokumen telah **100% DIKUNCI (LOCKED)**. Tidak ada requirement menggantung atau open decisions yang memblokir masuk ke **PHASE 2 (Admin Module & Sales Order CRUD)**.

