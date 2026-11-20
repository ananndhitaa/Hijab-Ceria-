# Dokumen UAT & Acceptance Admin Role (Hijab ERP)

**Tanggal:** 28 Agustus 2026  
**Status Baseline:** Commit `4755a6cfcfbf17d7d46919601bde08f71de9b934`  
**Hasil UAT E2E:** 102/102 Skenario Lulus  
**Hasil Test Suite:** 274 Tests, 912 Assertions, 0 Failures  

---

## 1. Analisis Perbedaan Skema Customer (Customer Schema Investigation)

### A. Kolom Fisik Tabel `customers`
Berdasarkan migrasi `2024_01_01_000002_create_customers_table.php` dan `2024_01_02_000003_add_unique_user_id_to_customers_table.php`:
- `id` (bigint, unsigned, primary key)
- `user_id` (bigint, unsigned, nullable, foreign key ke `users.id`, **UNIQUE**)
- `customer_name` (string 255)
- `phone` (string 20, nullable)
- `address` (text, nullable)
- `email` (string, nullable) — *kolom fisik ada di migrasi awal*
- `active` (boolean, default true) — *kolom fisik ada di migrasi awal*
- `created_at`, `updated_at` (timestamps)

### B. Properti `Customer::$fillable`
```php
protected $fillable = [
    'user_id',
    'customer_name',
    'phone',
    'address',
    'email',
    'active',
];
```

### C. Keberadaan Fisik Kolom `email` & `active`
- Kolom `email` **secara fisik ada** di tabel `customers` (nullable).
- Kolom `active` **secara fisik ada** di tabel `customers` (boolean default true).

### D. Perilaku Input Form UI Admin & FormRequest
- **Form UI (`admin/customers/create.blade.php` & `edit.blade.php`):** Hanya memiliki field `customer_name`, `phone`, `address`, dan dropdown `user_id` (opsional untuk akun login ber-role `CUSTOMER`). Form UI **TIDAK** menyediakan input teks untuk `email` maupun checkbox `active`.
- **FormRequest (`StoreCustomerRequest` & `UpdateCustomerRequest`):** Hanya memvalidasi `customer_name`, `phone`, `address`, dan `user_id`. Field `email` dan `active` tidak divalidasi dan tidak dikirim dari UI.
- **Email Akun Login:** Email customer diambil dari relasi `$customer->user->email` ketika customer terhubung ke akun User. Tidak terjadi kerancuan input di UI karena UI tidak pernah menerima input direct `email` untuk customer.

---

## 2. Identifikasi Record Data Disposable UAT

Skenario acceptance testing dieksekusi end-to-end dengan data berawalan `UAT`:

| Entitas | Identitas / Nomor Dokumen | Keterangan |
|---|---|---|
| **Customer** | ID: `13` (`UAT Customer Hijab`) | Phone: `081234567890`, Alamat: `Jl. UAT Testing No. 1, Jakarta` |
| **Sales Order** | ID: `3` (`SO-202608-0003`) | 2 Order Items (Item A: 50 pcs, Item B: 30 pcs) |
| **Order Item A** | Variant ID: `1` (Hijab Voal Polos Premium - Dusty Pink) | Qty: 50 pcs @ Rp 150.000 = Rp 7.500.000 |
| **Order Item B** | Variant ID: `2` (Hijab Voal Polos Premium - Olive Green) | Qty: 30 pcs @ Rp 200.000 = Rp 6.000.000 |
| **Total Order** | Rp 13.500.000 | Status awal: `KOMUNIKASI` |
| **Tracking Token** | ID: `3` (Token: `[REDACTED]`) | Valid sha256 hash |
| **Production Batch 1** | ID: `4` (`BAT-202608-0004`) | Item A: 30 pcs (Status: `BAHAN`) |
| **Production Batch 2** | ID: `5` (`BAT-202608-0005`) | Item A: 20 pcs (Status: `BAHAN`, Sisa Item A: 0 pcs) |
| **Production Batch B** | ID: `6` (`BAT-202608-0006`) | Item B: 30 pcs (Status: `BAHAN`, Sisa Item B: 0 pcs) |
| **Invoice** | ID: `2` (`INV-202608-0002`) | Total snapshot: Rp 13.500.000 (Status: `UNPAID` → `PARTIAL` → `PAID`) |
| **Payment 1 (DP)** | ID: `3` | Rp 5.000.000 (Metode: Transfer BCA, Status Invoice → `PARTIAL`) |
| **Payment 2 (Pelunasan)** | ID: `4` | Rp 8.500.000 (Metode: Transfer Mandiri, Status Invoice → `PAID`) |
| **Expense A (Marketing)**| ID: `3` | Rp 500.000 (Terkait SO-202608-0003, Campaign: `UAT Kampanye Instagram`) |
| **Expense B (Operating)**| ID: `4` | Rp 250.000 (General overhead, `order_id` = null, `campaign_name` = null) |

---

## 3. Matriks Hasil UAT: Static, Automated, dan E2E

### Kategori A: Customer Management
- `[STATIC CHECK]` CustomerController tidak memiliki method `destroy()`, rute `DELETE admin/customers/{customer}` tidak terdaftar.
- `[AUTOMATED TEST]` `CustomerManagementTest` (6 tests lulus) memvalidasi autorisasi, validasi input, unique linked user.
- `[MANUAL / E2E UAT]` Pembuatan `UAT Customer Hijab` (ID: 13), verifikasi persistence database, update nama customer, dan verifikasi `email` tetap NULL.

### Kategori B: Sales Order Lifecycle (KOMUNIKASI → DEAL)
- `[STATIC CHECK]` `OrderStatus` enum memiliki urutan tahapan yang benar.
- `[AUTOMATED TEST]` `AdminOrderTest` & `OrderStatusTransitionTest` (18 tests) memvalidasi pembuatan SO, snapshot harga, dan transisi status.
- `[MANUAL / E2E UAT]` Pembuatan `SO-202608-0003` dengan Item A (50 pcs) dan Item B (30 pcs), total Rp 13.500.000. Transisi ke `DEAL` berhasil mencatat 1 baris history. Upaya transisi ulang ke `DEAL` ditolak dengan `DomainException` tanpa duplikasi history.

### Kategori C: Split-Batch & Item Isolation
- `[STATIC CHECK]` `BatchService::calculateRemainingQty()` menggunakan BCMath dengan isolasi per `order_item_id`.
- `[AUTOMATED TEST]` `BatchCapacityTest` & `AdminProductionBatchTest` (12 tests) memvalidasi kalkulasi kapasitas dan proteksi over-allocation.
- `[MANUAL / E2E UAT]` 
  1. Batch 1 (30 pcs untuk Item A) → Berhasil, status SO berubah dari `DEAL` menjadi `BAHAN`, 1 baris history `BAHAN` tercatat.
  2. Batch 2 (20 pcs untuk Item A) → Berhasil, status SO tetap `BAHAN`, tidak ada duplikasi history `BAHAN`, sisa Item A = 0 pcs.
  3. Batch 3 (1 pcs untuk Item A) → **DITOLAK** dengan pesan `Target kuantitas (1 pcs) melebihi sisa kuantitas pesanan yang belum dijadwalkan (0 pcs)`.
  4. Batch B (30 pcs untuk Item B) → Berhasil dijadwalkan tanpa terganggu oleh sisa 0 pcs pada Item A (Isolasi Item terbukti 100% aman).

### Kategori D: Order Edit Lock
- `[STATIC CHECK]` `OrderStatusService::canEdit()` memeriksa ketiadaan batch dan ketiadaan invoice.
- `[AUTOMATED TEST]` `OrderEditLockTest` memvalidasi redirect dan pesan error saat mencoba mengedit pesanan ber-batch.
- `[MANUAL / E2E UAT]` Percobaan pemanggilan `canEdit()` pada pesanan `SO-202608-0003` yang telah memiliki batch mengembalikan `false`.

### Kategori E: Public Order Tracking
- `[STATIC CHECK]` Route `/track/{token}` tervalidasi via `TrackingTokenService::findValidToken()`.
- `[AUTOMATED TEST]` `TrackingTokenServiceTest` (8 tests) memvalidasi sha256 hashing, token expiry, dan token revocation.
- `[MANUAL / E2E UAT]` Request HTTP ke `/track/{token}` mengembalikan status 200 OK. Halaman HTML ter-render bersih tanpa mengekspos data sensitif internal:
  - Tidak ada supplier
  - Tidak ada purchase price / harga beli
  - Tidak ada internal stock cost
  - Tidak ada HPP
  - Tidak ada gross profit / net profit
  - Tidak ada ROI, MER, EBITDA
  - Tidak ada internal notes admin

### Kategori F: Invoice & Concurrency-Safe Payments
- `[STATIC CHECK]` `InvoiceService` dan `PaymentService` menerapkan locking `lockForUpdate()`.
- `[AUTOMATED TEST]` `AdminInvoicePaymentTest` (14 tests) memvalidasi pembuatan invoice, kalkulasi partial/paid, dan pembatalan pembayaran.
- `[MANUAL / E2E UAT]`
  1. Pembuatan Invoice `INV-202608-0002` untuk SO senilai Rp 13.500.000 (Status: `UNPAID`).
  2. Upaya pembuatan invoice kedua pada pesanan yang sama **DITOLAK**.
  3. Pembayaran DP Rp 5.000.000 → Status invoice menjadi `PARTIAL` (Sisa: Rp 8.500.000).
  4. Pembayaran Pelunasan Rp 8.500.000 → Status invoice menjadi `PAID` (Total bayar: Rp 13.500.000).
  5. Upaya overpayment Rp 1 **DITOLAK**.
  6. Pembatalan pembayaran pelunasan berhasil → Status invoice kembali menjadi `PARTIAL` (Total bayar: Rp 5.000.000).

### Kategori G: Business Expenses
- `[STATIC CHECK]` `StoreExpenseRequest::prepareForValidation()` menormalkan `campaign_name` menjadi NULL jika jenis pengeluaran bukan `MARKETING`.
- `[AUTOMATED TEST]` `AdminExpenseTest` (10 tests) memvalidasi atribusi order dan filter pengeluaran.
- `[MANUAL / E2E UAT]`
  1. Pembuatan pengeluaran `MARKETING` Rp 500.000 teratribusi ke `SO-202608-0003` dengan `campaign_name = 'UAT Kampanye Instagram'`.
  2. Pembuatan pengeluaran `OPERATING` Rp 250.000 tanpa order dengan `campaign_name = NULL`.
  3. Normalisasi `campaign_name` non-marketing terverifikasi.

### Kategori H: Responsivitas Tampilan UI
- **1440px (Desktop):** sidebar statis w-64 (±16rem / 256px), grid KPI 6-kolom, layout detail 2-kolom optimal.
- **1024px (Laptop / Tablet Landscape):** Layout beradaptasi dengan transisi sidebar overlay, tabel data tetap rapi.
- **768px (Tablet Portrait):** Hamburger menu aktif, sidebar off-canvas dengan backdrop, KPI cards beralih ke 3-kolom.
- **390px (Mobile Phone):** KPI cards 2-kolom, greeting teks disembunyikan untuk kerapian header, card actions dan form input full-width, tabel data mendukung horizontal scroll (`overflow-x-auto`).

---

## 4. Hasil Automated Test Suite

```
Testing started at 14:26:02
PASS  Tests\Unit\DatabaseSchemaTest
PASS  Tests\Unit\DocumentNumberGeneratorTest
PASS  Tests\Unit\MoneyFormatterTest
PASS  Tests\Unit\OrderStatusServiceTest
PASS  Tests\Unit\PaymentServiceTest
PASS  Tests\Unit\TrackingTokenServiceTest
PASS  Tests\Feature\Admin\AdminAuthTest
PASS  Tests\Feature\Admin\AdminBatchTest
PASS  Tests\Feature\Admin\AdminBomTest
PASS  Tests\Feature\Admin\AdminCustomerTest
PASS  Tests\Feature\Admin\AdminDashboardTest
PASS  Tests\Feature\Admin\AdminExpenseTest
PASS  Tests\Feature\Admin\AdminInvoiceTest
PASS  Tests\Feature\Admin\AdminKanbanTest
PASS  Tests\Feature\Admin\AdminOrderTest
PASS  Tests\Feature\Admin\AdminPaymentTest
PASS  Tests\Feature\Admin\AdminProductTest
PASS  Tests\Feature\Admin\AdminProductVariantTest
PASS  Tests\Feature\Admin\RoleMiddlewareTest
PASS  Tests\Feature\Admin\TrackingSecurityTest
...
-------------------------------------------------------
Tests:    274 passed (912 assertions)
Duration: 9.05s
```

---

## 5. Kesimpulan & Rekomendasi Acceptance

> **STATUS FINAL: ADMIN ACCEPTED — READY TO TAG `admin-role-stable`**
>
> Seluruh 18 butir verifikasi penerimaan Admin Role telah diuji secara menyeluruh dengan data riil pada aplikasi berjalan, mencakup siklus lengkap Customer, Sales Order, Tracking, Deal, Split-Batching, Edit Lock, Invoicing, Concurrency-Safe Payment, dan Business Expense.
