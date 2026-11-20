# DATA MODEL DOCUMENTATION — Hijab ERP System

## 1. Klasifikasi Data

Tabel-tabel dalam sistem diklasifikasikan ke dalam 3 kelompok utama:

### A. MASTER DATA
Tabel yang mendefinisikan master referensi dasar sistem:
- `users`: Pengguna sistem internal & akun customer langganan.
- `customers`: Data pelanggan (bisa berdiri sendiri tanpa login `user_id = NULL` atau terikat `user_id`).
- `suppliers`: Supplier penyedia bahan baku & kemasan.
- `units`: Satuan pengurusan bahan baku (`pcs`, `kg`, `m`, `roll`, dll).
- `products`: Master produk hijab.
- `product_variants`: Varian produk per warna/ukuran dengan `sku` UNIQUE.
- `materials`: Master bahan baku (kain, benang, label, kemasan).
- `material_stocks`: Current stock record per material (`material_id` UNIQUE).
- `bom_items`: Master resep/struktur bahan per varian (`UNIQUE(variant_id, material_id)`).

### B. TRANSACTION DATA
Tabel transaksi operasional bisnis:
- `orders`: Sales order dari customer.
- `order_items`: Detail barang yang dipesan + snapshot `unit_selling_price`.
- `production_batches`: Batch kerja produksi yang dibuat dari order item.
- `purchases`: Purchase order bahan baku ke supplier.
- `purchase_items`: Detail barang pada purchase order.
- `production_results`: Catatan hasil produksi fisik (jahit/finishing).
- `qc_results`: Hasil pemeriksaan kualitas per production result (`good_qty` & `reject_qty`, `production_result_id` UNIQUE).
- `shipments`: Catatan pengiriman pesanan.
- `invoices`: Tagihan penjualan ke customer (`order_id` UNIQUE).
- `payments`: Catatan pembayaran invoice (`DP`, `PELUNASAN`, `LAINNYA`).
- `business_expenses`: Pengeluaran operasional & marketing (opsional terikat `order_id`).

### C. SNAPSHOT / HISTORY / AUDIT DATA
Tabel snapshot historis & audit log yang **TIDAK BOLEH** diperlakukan sebagai master data:
- `batch_materials`: Snapshot hasil kalkulasi MRP per batch (`UNIQUE(batch_id, material_id)`).
- `stock_movements`: Audit log mutasi stok masuk/keluar beserta snapshot biaya (`unit_cost_snapshot` & `total_cost_snapshot`).
- `order_status_histories`: Histori pergerakan status order / kartu kanban.
- `order_tracking_tokens`: Secure token terenkripsi (`token_hash` UNIQUE SHA-256) untuk tracking publik.
- `batch_costs`: Direct production cost per batch untuk rekap HPP.
- `order_financials`: Financial snapshot / cache hasil kalkulasi finansial order (`order_id` UNIQUE).

---

## 2. Diagram Hubungan Antar Tabel (Entity Relationships)

```
User (1) ── (0..1) Customer (1) ── (0..*) Order
                                       │
                                       ├─ (1..*) OrderItem (1) ── (0..*) ProductionBatch
                                       │                                 │
                                       │                                 ├─ (0..*) BatchMaterial ── (1) Material
                                       │                                 ├─ (0..*) BatchCost
                                       │                                 └─ (0..*) ProductionResult (1) ── (0..1) QCResult
                                       │
                                       ├─ (0..*) OrderStatusHistory
                                       ├─ (0..*) Shipment
                                       ├─ (0..*) OrderTrackingToken
                                       ├─ (0..*) BusinessExpense ── (0..1) User (Creator)
                                       ├─ (0..1) Invoice (1) ── (0..*) Payment
                                       └─ (0..1) OrderFinancial

Product (1) ── (1..*) ProductVariant (1) ── (0..*) BomItem ── (1) Material
                                                                  │
                                                                  ├─ (1..1) MaterialStock
                                                                  └─ (0..*) StockMovement
Supplier (1) ── (0..*) Purchase (1) ── (1..*) PurchaseItem ── (1) Material
Unit (1) ── (0..*) Material
```

---

## 3. Aturan Foreign Key & Integrity Constraints

1. **Unique Constraints Wajib**:
   - `users.email` UNIQUE
   - `product_variants.sku` UNIQUE
   - `material_stocks.material_id` UNIQUE
   - `bom_items(variant_id, material_id)` UNIQUE
   - `orders.order_no` UNIQUE
   - `production_batches.batch_no` UNIQUE
   - `batch_materials(batch_id, material_id)` UNIQUE
   - `qc_results.production_result_id` UNIQUE
   - `invoices.invoice_no` UNIQUE
   - `order_financials.order_id` UNIQUE
   - `order_tracking_tokens.token_hash` UNIQUE (SHA-256 string)
   - `customers.user_id` UNIQUE
   - `document_sequences(prefix, period)` UNIQUE

2. **Tabel Pendukung Penomoran Dokumen (`document_sequences`)**:
   - `id`: Big increments
   - `prefix`: `VARCHAR(10)` (contoh: 'SO', 'BAT', 'PO', 'INV')
   - `period`: `CHAR(6)` (format `YYYYMM`, contoh: '202608')
   - `last_sequence`: `UNSIGNED INT` default 0 (1..9999)
   - `timestamps`

3. **Delete Behavior**:
   - Master data (`User`, `Customer`, `Material`, `ProductVariant`) menggunakan **soft status (`active = false`)** atau `nullOnDelete()` untuk menjaga keutuhan riwayat transaksi.
   - Hapus fisik master **DILARANG** bila sudah memiliki riwayat transaksi/mutasi.

---

## 4. Spesifikasi Tabel Production Batches (`production_batches`)

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| `id` | BIGINT (PK) | Auto increment primary key |
| `order_item_id` | FOREIGN KEY (BIGINT) | Relasi ke `order_items.id` (1 OrderItem dapat memiliki $\ge 1$ batch) |
| `batch_no` | VARCHAR(255) UNIQUE | Nomor batch terstandarisasi `BAT-YYYYMM-XXXX` (immutable) |
| `target_qty` | DECIMAL(18,4) | Target kuantitas kerja batch ($\le$ sisa kuantitas belum dijadwalkan) |
| `status` | VARCHAR(50) | Status tahapan batch (`BatchStatus::BAHAN` saat inisiasi) |
| `planned_start_date` | DATE (Nullable) | Tanggal rencana mulai pengerjaan |
| `planned_end_date` | DATE (Nullable) | Tanggal rencana selesai pengerjaan |
| `notes` | TEXT (Nullable) | Catatan instruksi khusus batch (maks 1000 karakter) |
| `created_at`, `updated_at` | TIMESTAMPS | Waktu pembuatan & pembaruan |

