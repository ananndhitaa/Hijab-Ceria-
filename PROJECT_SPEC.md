# PROJECT SPECIFICATION — Hijab ERP System

> **Judul Akademik**: Rancang Bangun Sistem Perencanaan Kebutuhan Bahan dan Perhitungan Hasil Produksi Hijab Berbasis Web

## 1. Ringkasan & Ruang Lingkup Sistem

NAYARA merupakan **Multi-Tenant SaaS ERP** untuk perusahaan konveksi hijab independen. Setiap outlet merupakan tenant terpisah; kewenangan platform (`SUPER_ADMIN`) dipisahkan dari kewenangan operasional tenant. Aplikasi ini **bukan ERP General-Purpose** dan fokus akademik utamanya adalah:
1. **Material Requirement Planning (MRP)**:
   BOM → Kebutuhan bahan per order/batch → Stok tersedia → Shortage → Purchasing → Stock IN → Allocation → Actual material usage.
2. **Production Result Calculation**:
   Target produksi → Hasil aktual → QC Good/Reject (Tanpa Rework) → Target vs Actual → Histori hasil produksi.

Selain dua fokus utama di atas, aplikasi dilengkapi modul pendukung:
- Papan Kanban status order
- Tracking status order customer (token aman tanpa login dan portal customer langganan)
- Perhitungan HPP batch & order
- Penjualan, profit, ROI, MER, dan EBITDA periodik pada lingkup tenant
- Invoice & Payment tracking
- Dashboard Eksekutif Owner dan dashboard operasional tenant
- Public storefront, publikasi produk, kuota etalase, langganan Premium, iklan toko/produk, refund, serta layanan setup/pelatihan
- Dashboard dan tata kelola platform oleh `SUPER_ADMIN`, terpisah dari transaksi internal tenant

Pengunjung publik dapat melihat etalase dan menghubungi toko melalui WhatsApp. Sistem bukan marketplace dengan checkout publik.

---

## 2. Pengecualian & Pembatasan Sistem (System Non-Goals)

Sistem secara tegas **TIDAK MEMILIKI** dan **TIDAK BOLEH MENAMBAHKAN**:
- ❌ Rework / rework_qty / rework_status / rework table / rework workflow (Hasil QC murni GOOD dan REJECT).
- ❌ Payroll / Penggajian Karyawan.
- ❌ General Ledger / Jurnal Akuntansi Ganda.
- ❌ Public retail checkout / keranjang belanja dan marketplace commission.
- ❌ Biaya pendaftaran tenant.
- ❌ Advanced Production Scheduling.
- ❌ Complex Multi-Warehouse (stok tenant dikelola dalam satu gudang terpusat per material).
- ❌ Akses `SUPER_ADMIN` ke detail Sales Order, pembayaran, HPP, stok, atau mutasi operasional tenant. Super Admin hanya memperoleh metrik agregat platform yang memang disediakan untuk analitik global.

---

## 3. Matriks Peran & Hak Akses (Role Matrix)

| Role | Akses Utama | Batasan Keamanan Wajib |
|------|-------------|------------------------|
| **SUPER_ADMIN** | Review pendaftaran tenant, direktori/status tenant, tagihan platform, iklan, refund, analitik global agregat, broadcast, audit, monitoring, dan kebijakan platform | Tidak mengakses atau memutasi detail Sales Order, pembayaran, HPP, stok, atau transaksi operasional tenant |
| **OWNER** | Dashboard eksekutif, analitik finansial tenant, HPP, profitabilitas, kinerja, karyawan, publikasi etalase, langganan, iklan, dan permintaan layanan | Read-only untuk analitik operasional/finansial; tidak membuat batch, menyetujui PO, atau memutasi transaksi operasional |
| **ADMIN** | Master Data, Sales Order dari WhatsApp, Customer, Produk, Varian, BOM, harga jual, pembuatan Batch Produksi, Invoice, Payment, persetujuan PO, Kanban, Expense, dan laporan | Tidak dapat melihat analitik finansial eksklusif Owner seperti EBITDA/ROI; tidak melakukan mutasi fisik stok atau pencatatan QC |
| **GUDANG** | Inventory, Material, Unit, MRP Calculation, Supplier, draf Purchase Order, penerimaan barang, Stock Movement, dan alokasi bahan | Tidak membuat Batch Produksi, tidak mengubah harga jual, dan tidak melihat profit/financial report |
| **PRODUKSI** | Antrean dan eksekusi Batch Produksi yang diterbitkan Admin, Printing, Finishing, Hasil Produksi, QC Good/Reject, Packing, dan Shipment | Tidak membuat Batch Produksi, tidak mengubah harga pembelian material, dan tidak mengakses data keuangan |
| **CUSTOMER** | Tracking order miliknya (token aman) / histori order dan invoice (pelanggan ber-akun) | **DILARANG BUKA**: supplier, harga beli, stok internal, HPP, profit, ROI, MER, EBITDA, dan expense internal; tidak membuat Sales Order sendiri |
---

## 4. Presisi Data & Format Standar (Precision & Standard Formats)

- **Quantity / Stock**: `DECIMAL(18,4)` (misal: `qty_ordered`, `qty_on_hand`, `good_qty`, `reject_qty`, `qty_per_unit`)
- **Mata Uang (Rupiah)**: `DECIMAL(18,2)` (misal: `unit_price`, `unit_selling_price`, `total_amount`, `revenue_amount`, `hpp_amount`, `gross_profit`, `net_profit`)
- **Persentase & Rasio**: `DECIMAL(12,4)` (misal: `gross_margin`, `net_margin`, `roi_percent`, `mer_value`)

### Format Penomoran Dokumen Otomatis:
- **Sales Order**: `SO-YYYYMM-XXXX` (misal: `SO-202608-0001`)
- **Production Batch**: `BAT-YYYYMM-XXXX` (misal: `BAT-202608-0001`)
- **Purchase Order**: `PO-YYYYMM-XXXX` (misal: `PO-202608-0001`)
- **Invoice**: `INV-YYYYMM-XXXX` (misal: `INV-202608-0001`)
- *Ketentuan Arsitektur*:
  1. `YYYYMM` menggunakan waktu sistem saat dokumen dibuat (timezone `Asia/Jakarta`). Tidak diturunkan dari input `order_date` yang editable.
  2. `XXXX` adalah sequence urut 4-digit (0001–9999). Jika sequence melampaui 9999, sistem melempar exception overflow secara eksplisit.
  3. Sequence di-reset ke `0001` setiap pergantian bulan.
  4. Masing-masing prefix memiliki sequence terpisah yang dikelola melalui tabel `document_sequences`.
  5. Nomor dokumen wajib **UNIQUE** dan bersifat **IMMUTABLE** (tidak dapat diubah setelah disimpan).
  6. Generator wajib **concurrency-safe** menggunakan row-level lock dalam database transaction dan berpartisipasi dalam transaksi atomik pembuatan dokumen.
  7. Dilarang menggunakan random number sebagai nomor dokumen resmi.

---

## 5. Sumber Kebenaran (Source of Truth)

1. Spesifikasi project ini dan dokumen di folder `docs/` adalah **Source of Truth utama**.
2. Bila terdapat requirement yang belum dikunci, catat di [`docs/open-decisions.md`](file:///d:/Magang/hijab/docs/open-decisions.md) dan **JANGAN MENEBAK** atau membuat rule sendiri.

---

## 6. Aturan Keamanan Database & Proteksi Data (Safety Rules)

- **DILARANG** menjalankan `php artisan migrate:fresh` atau `php artisan db:wipe` setelah project mulai masuk ke PHASE 2 dan seterusnya tanpa perintah eksplisit dari user.
- Semua perubahan skema database dari PHASE 2 onwards wajib menggunakan **Corrective Migration** (`php artisan make:migration alter_...`).

---

## 6.1. Aturan Harga Jual & Snapshot Transaksi Sales Order (PRD v1.6)

- **Master Price Source**: `product_variants.base_selling_price` adalah satu-satunya sumber kebenaran harga jual produk.
- **Snapshot Otomatis**: Pembuatan Sales Order menyalin `product_variants.base_selling_price` secara otomatis ke `order_items.unit_selling_price` sebagai snapshot transaksi. Admin **DILARANG** mengedit/menimpa harga satuan pada Sales Order.
- **Proteksi Backend Anti-Tampering**: Server mengabaikan input harga dari browser dan strictly menggunakan harga master variant yang valid ($> 0$).
- **Varian Tanpa Harga**: Varian dengan harga master NULL atau $\le 0$ ditolak dari pembuatan/penggantian item pesanan.
- **Preservasi Snapshot Saat Edit**: Pengeditan Sales Order yang masih *editable* mempertahankan snapshot harga item lama jika variannya tidak diubah (bahkan jika harga master varian tersebut telah berubah atau menjadi NULL). Perubahan varian menerapkan harga master varian baru.
- **Isolasi Riwayat**: Perubahan harga master di masa depan tidak pernah mengubah `order_items` historis.

---

## 7. Aturan Modul Invoice & Payment (Phase 2C)

- **Penerbitan Invoice**:
  - Invoice diterbitkan dari Sales Order yang telah mencapai status minimal `DEAL`. Ditolak saat order masih berstatus `KOMUNIKASI`.
  - Kardinalitas: 1 Order memiliki tepat 1 Invoice (`invoices.order_id` UNIQUE).
  - Snapshot Nominal: `invoices.total_amount` adalah snapshot total nominal dari $\sum(\text{order\_items.qty\_ordered} \times \text{order\_items.unit\_selling\_price})$.
- **Penguncian Sales Order**:
  - Begitu Invoice diterbitkan, Sales Order terkunci (*immutable*) dari pengeditan item, kuantitas, harga, maupun customer.
- **Kalkulasi & Proteksi Pembayaran**:
  - Perhitungan moneter wajib decimal-safe (BCMath string berpresisi 2 desimal). Dilarang menggunakan float PHP.
  - `paid_amount` = $\sum(\text{payments.amount WHERE status = CONFIRMED})$.
  - `outstanding_balance` = $\max(0, \text{invoice.total\_amount} - \text{paid\_amount})$.
  - Pembayaran `PENDING` dan `CANCELLED` **TIDAK MENGURANGI** `outstanding_balance`.
- **Payment Auto-Amount Rule (PRD v1.4)**:
  - **`DP` (Uang Muka)**: Nominal wajib $50\%$ dari `total_amount` (`bcdiv(total_amount, '2', 2)`), atau sisa tagihan jika `outstanding_balance < 50%`. Backend memvalidasi kesesuaian nominal secara ketat di bawah row lock.
  - **`PELUNASAN`**: Nominal wajib sama persis dengan `outstanding_balance` saat transaksi diproses. Setelah diverifikasi, status Invoice otomatis menjadi `PAID`.
  - **`LAINNYA`**: Nominal manual yang diinput bebas oleh pengguna dengan batasan $0 < \text{amount} \le \text{outstanding\_balance}$.
- **Standardisasi Metode Pembayaran (PRD v1.5)**:
  - Metode canonical: `Transfer Bank`, `Tunai`, `QRIS`.
  - Seluruh varian bank transfer (`Transfer BCA`, `Transfer Mandiri`, `Transfer BNI`, `Transfer BRI`, dll.) wajib dicatat dan ditampilkan sebagai `Transfer Bank`. Rincian nomor rekening/bank dicatat pada `reference_no` atau `notes`.
  - Modal pencatatan pembayaran menggunakan input terkontrol `<select>`.
- **Ekspor CSV Invoice (PRD v1.5)**:
  - Ekspor item transaksi tabular flat dengan format UTF-8 BOM (`\xEF\xBB\xBF`) dan proteksi formula-injection spreadsheet.
  - Nilai numerik moneter berupa angka bersih (`13500000.00`) tanpa simbol "Rp".
  - Pembatasan otoritas: dilarang mengekspor metrik finansial Owner (HPP, margin, profit, ROI, MER, EBITDA).
- **Laporan Pendapatan Penjualan / Sales Report (PRD v1.5)**:
  - Berbasis tanggal pesanan (`orders.order_date`) dalam zona waktu `Asia/Jakarta`.
  - **Pendapatan (Revenue)**: $\sum(\text{order\_items.qty\_ordered} \times \text{order\_items.unit\_selling\_price})$ untuk order berstatus minimal `DEAL` (status `KOMUNIKASI` di-exclude). Dihitung dari snapshot harga jual, bukan dari pembayaran.
  - **Pembayaran Diterima**: $\sum(\text{confirmed payments})$ dari invoice pesanan terkait dalam periode filter.
  - **Sisa Tagihan**: $\sum(\text{outstanding})$ dari invoice pesanan terkait yang telah diterbitkan (pesanan tanpa invoice berstatus 'Belum dibuat' dan sisa tagihan '—').
  - Ekspor CSV Laporan Penjualan (`admin.reports.sales.export-csv`) menggunakan formula dan data yang identik dengan tampilan web.
- **Pencatatan & Pembatalan Pembayaran**:
  - Pembayaran manual oleh Admin dicatat langsung dengan status `CONFIRMED`.
  - Pembatalan pembayaran mengubah status menjadi `CANCELLED` (tanpa penghapusan fisik record) dan memicu rekalkulasi status Invoice di bawah row lock Invoice terlebih dahulu.
  - Pembatalan berulang pada pembayaran yang sudah `CANCELLED` ditolak secara idempoten.

---

## 8. Aturan Modul Biaya & Pengeluaran Bisnis (Admin Business Expense)

- **Kategori Pengeluaran (`ExpenseType`)**:
  - `MARKETING`: Biaya iklan berbayar (Meta Ads, TikTok Ads, endorsement) dan promosi.
  - `OPERATING`: Beban operasional umum bisnis (listrik, internet, alat tulis kantor, perlengkapan).
  - `INTEREST`: Beban bunga pinjaman perbankan / modal kerja.
  - `TAX`: Beban pajak penghasilan / PPN / PPh.
  - `DEPRECIATION`: Beban depresiasi/penyusutan aset fisik mesin jahit / peralatan.
  - `AMORTIZATION`: Beban amortisasi aset tidak berwujud.
- **Atribusi Order & Kampanye**:
  - `order_id` bersifat opsional (`NULL` untuk pengeluaran rutin/umum bisnis, terisi jika secara spesifik diatribusikan ke satu Sales Order tertentu).
  - `campaign_name` bersifat opsional dan hanya berlaku untuk jenis pengeluaran `MARKETING`.
  - Pengeluaran selain `MARKETING` dinormalisasi secara otomatis menjadi `campaign_name = NULL` untuk menjaga integritas data.
- **Integritas Record & Presisi Moneter**:
  - Nominal disimpan pada kolom `amount` berpresisi `DECIMAL(18,2)` dan wajib $> 0$.
  - Format tampilan menggunakan `App\Support\MoneyFormatter::rupiah(...)`.
  - Penghapusan fisik record (`destroy` / hard delete) dilarang untuk menjaga audit trail keuangan bisnis.

---

## 9. Aturan Pembuatan Production Batch & Handoff (Admin Role Step 2)

- **Kepemilikan & Status Awal**:
  - Pembuatan Production Batch dimiliki oleh role `ADMIN`.
  - Batch dimulai dengan status `BatchStatus::BAHAN` dan nomor format `BAT-YYYYMM-XXXX`.
- **Syarat Kelayakan Status Order**:
  - Batch hanya dapat dibuat untuk Order dengan status `DEAL` atau `BAHAN`.
  - Status lain (`KOMUNIKASI`, `PRINTING`, `PRODUKSI`, `QC`, `PACKING`, `DIKIRIM`, `SELESAI`) dilarang membuat batch.
- **Handoff Transisi Status Order & Riwayat**:
  - Batch pertama yang dibuat pada Order berstatus `DEAL` memicu transisi `DEAL → BAHAN` dan mencatat tepat 1 entri `OrderStatusHistory` berstatus `BAHAN`.
  - Batch lanjutan saat Order sudah berstatus `BAHAN` tetap diizinkan namun tidak membuat duplikat riwayat status.
- **Split Batch & Validasi Kapasitas**:
  - Satu `OrderItem` dapat dipecah ke beberapa batch (split batch).
  - Target kuantitas baru wajib $> 0$ dan $\le$ sisa kuantitas belum dijadwalkan (`qty_ordered - SUM(active batch target_qty)`).
  - Batch berstatus `CANCELLED` tidak mengurangi kapasitas kuantitas.
  - Perhitungan menggunakan aritmatika desimal aman (`DECIMAL(18,4)` via BCMath).
- **Keamanan Konkurensi**:
  - Seluruh alur pembuatan batch menggunakan row lock berurutan: `Order` $\to$ `OrderItem` di dalam satu transaksi database atomik.
- **Batas Peran (STOP ADMIN - No MRP)**:
  - Admin hanya bertugas membuat batch kerja produksi.
  - Admin **TIDAK** menjalankan kalkulasi MRP, tidak membuat `batch_materials`, mutasi stok, maupun Purchase Order. Hal tersebut merupakan tugas role **GUDANG** pada fase selanjutnya.

---

## 10. Aturan Kanban Board Pesanan (Admin Role Step 3)

- **Sumber Kebenaran & 9 Kolom Resmi**:
  - Kanban Board menggunakan nilai `orders.status` secara langsung tanpa tabel atau duplikasi status tambahan.
  - Menampilkan tepat 9 kolom terurut sesuai `OrderStatus::kanbanColumns()` (`KOMUNIKASI`, `DEAL`, `BAHAN`, `PRINTING`, `PRODUKSI`, `QC`, `PACKING`, `DIKIRIM`, `SELESAI`).
- **Batasan Mutasi Status Manual Admin**:
  - Admin **HANYA** diizinkan melakukan transisi manual dari `KOMUNIKASI → DEAL` (via drag/drop atau tombol fallback 'Konfirmasi Deal').
  - Transisi status operasional selanjutnya (`DEAL → BAHAN` hingga `SELESAI`) tidak dapat di-drag/drop secara bebas dan wajib melalui alur modul/role yang berwenang.
- **Integrasi Produksi pada Card**:
  - Card berstatus `DEAL` yang memenuhi syarat pembuatan batch menampilkan indikator *"Menunggu Batch"* dan tombol *"Buat Batch"*.
  - Card berstatus `BAHAN` menampilkan konteks jumlah batch dan tombol *"Buat Batch Tambahan"* jika masih memiliki kuantitas belum dijadwalkan.
- **Performa & Pembatasan SELESAI**:
  - Kolom `SELESAI` menampilkan maksimal 20 pesanan terbaru saat tanpa filter pencarian/tanggal untuk efisiensi DOM, dengan counter header yang tetap merefleksikan total keseluruhan data.

---

---

## 12. Dashboard & Navigasi Final Admin (Admin Role Step 5)

- **Hierarki & Struktur Dashboard Operasional**:
  - Halaman `resources/views/dashboard/admin.blade.php` bertindak sebagai ringkasan pemantauan harian Admin yang berfokus pada operasional dan pergerakan pesanan.
  - Memuat 6 KPI Primer: Total Order, Order Aktif, Menunggu Deal (KOMUNIKASI), Menunggu Batch (DEAL tanpa batch aktif), Invoice Belum Lunas (UNPAID & PARTIAL), dan Sisa Tagihan (Piutang via BCMath & MoneyFormatter).
  - Memuat seksi Perlu Tindakan (*Action Required*), Ringkasan 9 Tahap Pipeline OrderStatus, Daftar Sales Order Terbaru, Daftar Batch Produksi Terbaru, Pengeluaran Bisnis Terbaru (dengan metrik total bulan berjalan), Ringkasan Master Data, dan Aksi Cepat.
- **Struktur & Isolasi Navigasi Sidebar**:
  - Navigasi Admin dibagi ke 4 grup aktif tanpa placeholder atau `href="#"`:
    1. **Dashboard**: Dashboard (`admin.dashboard`)
    2. **Pesanan**: Sales Order (`admin.orders.*`), Kanban Pesanan (`admin.kanban.*`), Batch Produksi (`admin.batches.*`, `admin.orders.batches.*`)
    3. **Master Data**: Customer (`admin.customers.*`), Produk & BOM (`admin.products.*`, `admin.variants.*`)
    4. **Keuangan Admin**: Invoice & Tagihan (`admin.invoices.*`, `admin.payments.*`), Biaya & Pengeluaran (`admin.expenses.*`)
  - Logika active-state eksklusif: `admin.orders.batches.*` secara spesifik mengaktifkan menu Batch Produksi dan tidak mengaktifkan Sales Order secara ganda.
- **Batasan Privasi Finansial & Keamanan Desimal**:
  - Dashboard Admin secara ketat tidak memuat analitik laba Owner (HPP, Gross Profit, Net Profit, ROI, MER, EBITDA, atau `order_financials`).
  - Seluruh nilai nominal mata uang diformat menggunakan `\App\Support\MoneyFormatter::rupiah(...)` tanpa konversi `float`.

---

## 13. Public Order Tracking & Token Recovery (PRD v1.6 Hardening)

- **Keamanan Hash-Only & Non-Persistensi Raw Token**:
  - Raw tracking token dihasilkan secara acak (32 bytes / 64 hex chars).
  - Hanya SHA-256 `token_hash` yang disimpan di basis data `order_tracking_tokens`. Raw token dilarang disimpan di DB maupun log permanen.
- **Siklus Hidup Token & Akses Berulang**:
  - Token tracking aktif dapat diakses berulang kali oleh pelanggan (bukan token satu kali pakai bagi customer).
  - Raw URL hanya diflash satu kali di sesi Admin sesaat setelah pembuatan order atau regenerasi token.
  - Saat halaman dimuat ulang (refresh), tampilan kembali ke status `Token Tracking [AKTIF]` tanpa menampilkan raw token.
- **Mekanisme Regenerasi Token (Token Recovery)**:
  - Jika tautan hilang atau lupa, Admin dapat melakukan regenerasi melalui tombol `[Regenerasi Tautan]` pada Order Detail dengan modal konfirmasi NAYARA.
  - Regenerasi otomatis me-revoke (`revoked_at = now()`) seluruh token aktif sebelumnya untuk order tersebut dan menerbitkan token baru.
  - Tautan lama otomatis menjadi tidak valid (404), dan tautan baru dapat disalin oleh Admin.
- **Normalisasi Input Form `/track`**:
  - Form input publik `/track` menerima format raw token (64 hex) maupun URL lengkap (`http(s)://.../track/{token}`).
  - Sistem mengekstrak token 64 hex secara otomatis dan menolak input tidak valid dengan pesan ramah *"Token atau tautan pelacakan tidak valid."*.
- **Isolasi Data Publik**:
  - Halaman tracking publik strictly tidak mengekspos catatan internal, stok gudang, supplier, HPP, margin, profit, atau metrik finansial internal.
