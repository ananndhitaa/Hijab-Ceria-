# BUSINESS RULES DOCUMENTATION — Hijab ERP System

## 1. Master Selling Price & Sales Order Pricing Rules

- **Semantik Master Price**:
  - `product_variants.base_selling_price` bertindak sebagai **DEFAULT / REFERENCE SELLING PRICE** (bukan harga transaksi wajib yang kaku).
  - Nilainya bersifat **NULLABLE**. Ketika varian produk baru dibuat, nilainya default `NULL`.
  - Nilai `NULL` secara eksplisit mengindikasikan bahwa **harga master belum ditentukan/diset oleh Admin**.
  - Nilai `0.00` **DILARANG** digunakan sebagai pengganti "belum diisi".
  - Saat input Sales Order nanti, jika `base_selling_price` bernilai `NULL`, sistem **DILARANG** diam-diam menggunakan `0` sebagai fallback.
  - **HARD SELLING PRICE FLOOR**: Ketika HPP Acuan tersedia, harga jual master (`base_selling_price`) **HARUS STRICTLY LEBIH TINGGI daripada HPP / Modal Acuan Variant** ($\text{Master Price} > \text{Reference HPP}$). Harga jual sama dengan atau lebih rendah dari HPP ditolak keras *server-side* dengan pesan: `"Harga jual harus lebih tinggi dari HPP / Modal Acuan (Rp... per pcs)."`.

- **Audit Status Harga Varian Existing (Read-Only Audit, Bukan Auto-Overwrite)**:
  - Existing Variant yang dibuat sebelum aturan HPP diterapkan diaudit secara sistematis dan **TIDAK DITIMPA OTOMATIS**.
  - Setiap Varian diturunkan status evaluasi harganya:
    1. **VALID**: HPP tersedia dan $\text{Harga Jual Master} > \text{HPP}$ $\rightarrow$ `"Harga jual di atas HPP"`.
    2. **PERLU REVISI**: HPP tersedia dan $\text{Harga Jual Master} \le \text{HPP}$ $\rightarrow$ `"Harga jual saat ini sama dengan atau lebih rendah dari HPP. Perbarui harga jual sebelum Variant digunakan untuk order baru."`.
    3. **BELUM DAPAT DIVERIFIKASI**: HPP belum tersedia (BOM belum lengkap, modal material belum tersedia, atau rincian biaya langsung belum lengkap). Varian tetap diizinkan ada di katalog tanpa menghapus harga lama, namun sistem tidak mengklaim harga valid terhadap HPP $\rightarrow$ `"HPP belum tersedia sehingga harga jual belum dapat diverifikasi."`.
    4. **HARGA BELUM DITETAPKAN**: Harga jual master bernilai `NULL` $\rightarrow$ `"Harga jual belum ditetapkan."`.

- **Master Price vs Transaction Price (Fleksibilitas Sales Order)**:
  - Harga Master Variant adalah harga acuan/default yang otomatis terisi pada antarmuka Sales Order.
  - Staf/Admin berwenang menyesuaikan `unit_selling_price` transaksi pada Sales Order (boleh sama, lebih tinggi, atau lebih rendah dari harga master).
  - **Syarat Mutlak Transaksi**: Selama $\text{Transaction Price} > \text{Reference HPP}$, harga transaksi dinyatakan **VALID**.
  - Anti-tampering mencegah bypass terhadap *floor* HPP ($\text{Price} \le \text{HPP}$), BUKAN mewajibkan $\text{unit\_selling\_price} == \text{base\_selling\_price}$.

- **Sales Order Prerequisite (Larangan Menjual Tanpa HPP Acuan)**:
  - Untuk Sales Order baru/pembaruan, Reference HPP Variant **WAJIB TERSEDIA**.
  - Jika Reference HPP Variant belum tersedia, pesanan diblokir *server-side* dengan pesan:
    `"HPP / Modal Acuan variant belum tersedia. Lengkapi BOM dan modal bahan terlebih dahulu sebelum produk dijual."`

- **Historical Sales Order Immutability**:
  - Nilai `order_items.unit_selling_price` pada pesanan yang sudah tercatat bersifat **IMMUTABLE** (tidak dapat berubah seumur hidup transaksi).
  - Perubahan master price di masa depan, perubahan MWA stok bahan, maupun perhitungan ulang HPP acuan/aktual **TIDAK BOLEH MENGUBAH HARGA HISTORIS ORDER**.

- **Alur UX Tombol Kembali pada Sales Order Status KOMUNIKASI (Koreksi Order Tanpa Duplikasi)**:
  - Pada halaman detail Sales Order (`/admin/orders/{order}`), jika order masih berstatus **`KOMUNIKASI`**, tombol kembali di header berlabel **"Kembali Edit Order"** dan mengarah langsung ke route edit order yang sama (`route('admin.orders.edit', $order)`), **BUKAN** ke daftar Sales Order (`/admin/orders`).
  - **Tujuan Bisnis & Operasional**: Memungkinkan Admin mengoreksi rincian pesanan jika salah input atau tidak sengaja menekan "Lanjut", sebelum mengunci status kesepakatan menjadi `DEAL`.
  - **Integritas Data Transaksi**:
    - Tombol kembali ke edit tidak membuat nomor Sales Order baru dan tidak mengubah Order ID.
    - Form edit memuat seluruh data berjalan (*prefilled*): customer, tanggal order, catatan, varian, kuantitas, dan harga satuan transaksi.
    - Penyimpanan koreksi (`PUT /admin/orders/{order}`) memperbarui item secara atomik tanpa menduplikasi baris `OrderItem`.
    - Status pesanan tetap **`KOMUNIKASI`** (tidak otomatis berubah menjadi `DEAL`).
    - Validasi floor harga jual terhadap Reference HPP tetap aktif dan ditegakkan.
    - Aksi **[ Konfirmasi Deal ]** tetap berdiri sebagai aksi terpisah yang dieksekusi secara sadar oleh Admin melalui form konfirmasi pada halaman detail pesanan.
  - Untuk status selain `KOMUNIKASI` (misal `DEAL`, `BAHAN`, `SELESAI`), tombol "Kembali" tetap mengarah ke daftar Sales Order (`/admin/orders`).

### 1.1 Pemisahan HPP / Modal Acuan vs HPP Aktual Produksi pada Sales Order

- **Authoritative Concept (Dua HPP yang Terpisah)**:
  1. **HPP / Modal Acuan Variant (Sebelum Produksi)**:
     - **Sumber**: `VariantCostCalculationService`
     - **Formula**: $\text{BOM} \times \text{Current MWA Material} + \text{Standard Direct Production Costs} = \text{Reference HPP per pcs}$.
     - **Fungsi**: Menentukan batas bawah harga jual (*selling-price floor*), estimasi modal pesanan, dan estimasi margin sebelum produksi.
     - **Ketersediaan**: Sudah tersedia sebelum pesanan diproduksi (prasyarat simpan order).
  2. **HPP Aktual Pesanan / Modal Produksi Aktual (Setelah Produksi Selesai)**:
     - **Sumber**: `OrderHppCalculationService` & `BatchCostCalculationService`
     - **Dasar**: Realisasi pemakaian bahan aktual, snapshot alokasi, alokasi tambahan, retur sisa bahan, biaya tenaga kerja/finishing aktual, dan *Good Qty* hasil QC final.
     - **Fungsi**: Realisasi HPP final, laba kotor aktual, dan profitabilitas final pesanan.
     - **Prinsip Mutlak**: Sistem **DILARANG** mencampurkan HPP Acuan dengan HPP Aktual.

- **Penyederhanaan UI Sales Order (Sales Order UI Simplification)**:
  - **Prinsip Operasional**: Sales Order berfokus murni pada transaksi pemesanan operasional ERP yang bersih dan profesional, bukan halaman analisis HPP / profitabilitas.
  - **Form Create & Edit (`admin/orders/create` & `admin/orders/edit`)**:
    - Setiap item cukup menampilkan kolom transaksi utama: `Varian Produk` | `Qty` | `Harga Jual` | `Subtotal`.
    - Baris referensi finansial (`HPP Acuan`, `Harga Master`, `Margin`) ditiadakan dari tampilan form.
    - Validasi *server-side* dan *client-side guard* tetap **WAJIB**: $\text{Transaction Selling Price} > \text{Reference HPP}$.
    - Notifikasi kesalahan hanya ditampilkan ketika dipicu:
      - Harga tidak valid: `"Harga jual harus lebih tinggi dari HPP Acuan."`
      - HPP variant belum tersedia: `"HPP Acuan variant belum tersedia. Lengkapi data biaya produk terlebih dahulu."`
  - **Struktur Antarmuka Detail Sales Order (`admin/orders/{order}`)**:
    - Tabel Item Pesanan cukup menampilkan kolom transaksi esensial: `No` | `Produk & Varian` | `SKU` | `Rute Produksi` | `Qty` | `Harga Jual` | `Subtotal`.
    - Teks sekunder di bawah harga satuan (`HPP Acuan Rp...` dan `Margin Rp...`) dihilangkan.
    - Seksi `RINGKASAN NILAI PESANAN` beserta kartu profitabilitas (`Pendapatan`, `Estimasi HPP`, `Estimasi Laba`, `REALISASI PRODUKSI`, `HPP Aktual`, `Laba Aktual`, `Selisih HPP`, dan badge `Acuan Variant`) sepenuhnya ditiadakan dari Sales Order.
    - Informasi transaksi utama dipertahankan secara bersih pada footer tabel dan ringkasan pesanan: **Total Keseluruhan** (Kuantitas pcs dan Nilai Transaksi Rp...).
  - **Penyimpanan & Lokasi Analisis HPP**:
    - Modul HPP, margin, dan profitabilitas tetap aktif sepenuhnya di backend dan dapat diakses pada halaman khusus yang relevan:
      1. **Master Produk & Varian** (`/admin/variants/{id}`): HPP Acuan, Rincian pembentuk HPP, Harga Master, dan Margin reference.
      2. **Produksi**: HPP Aktual per batch pada detail batch produksi.
      3. **Keuangan & Laporan**: Laporan Laba Rugi, Revenue, HPP, Gross Profit, dan Profitabilitas Pesanan.

---

## 2. Material Requirement Planning (MRP) & Batch Material Concepts

- **Empat Konsep Fundamental Kebutuhan & Persediaan Bahan**:
  1. **MRP REQUIRED (`batch_materials.required_qty`)**:
     $$\text{required\_qty} = \text{BOM.qty\_per\_unit} \times \text{production\_batch.target\_qty}$$
     Snapshot kebutuhan bahan baku per batch produksi. Bersifat **immutable** (tidak pernah berubah seumur hidup batch).
  2. **MRP HISTORICAL SHORTAGE (`batch_materials.shortage_qty`)**:
     $$\text{shortage\_qty} = \max(\text{required\_qty} - \text{stock\_at\_calc}, 0)$$
     Snapshot kekurangan bahan pada saat MRP pertama kali dihitung/di-generate. Bersifat **immutable** sebagai catatan audit historis.
  3. **WAREHOUSE LIVE STOCK (`material_stocks.qty_on_hand`)**:
     Jumlah fisik material yang saat ini benar-benar berada di gudang penyimpanan.
  4. **BATCH ALLOCATION (`batch_materials.allocated_qty`)**:
     Jumlah kuantitas material yang telah **dikeluarkan secara fisik dari gudang dan diserahkan ke Batch produksi**.
- **Batch Readiness Semantics**:
  - Untuk batch yang **belum dialokasikan**, status kecukupan bahan dinilai dari *live shortage* terhadap stok gudang (`material_stocks.qty_on_hand`).
  - Untuk batch yang **sudah dialokasikan penuh** (`allocated_qty == required_qty`), status kesiapan bahan adalah **`TERALOKASI`** (efektif shortage = `0.0000`). Penurunan stok gudang setelah pengeluaran bahan **TIDAK BOLEH** membuat batch yang sudah teralokasi kembali berstatus `KURANG`.

---

## 3. Inventory & Costing (Moving Weighted Average & Physical Allocation)

- **Costing Method**: Moving Weighted Average (MWA). FIFO dan LIFO DILARANG.
- **Formula MWA saat Stock IN (Penerimaan Purchase)**:
  $$\text{New Avg Cost} = \frac{(\text{Old Stock} \times \text{Old Avg Cost}) + (\text{Incoming Qty} \times \text{Purchase Price})}{\text{Old Stock} + \text{Incoming Qty}}$$
- **Stock OUT saat Alokasi Gudang ke Batch (Step G5)**:
  - Pengurangan persediaan fisik gudang (`material_stocks.qty_on_hand`) terjadi pada saat **Gudang melakukan konfirmasi Alokasi Bahan (Full Allocation)**.
  - Setiap bahan pada batch yang dialokasikan dicatat tepat satu record `StockMovement` bertipe `MovementType::OUT` dengan snapshot biaya `unit_cost_snapshot = MaterialStock.avg_unit_cost` saat pengeluaran.
  - **Stock OUT TIDAK mengubah `avg_unit_cost`** (harga rata-rata persediaan tetap identik sebelum dan sesudah alokasi).
  - Snapshot biaya pada pergerakan `OUT` bersifat permanen/terkunci dan tidak berubah jika di masa depan ada pembelian baru yang mengubah `avg_unit_cost`.
- **Aturan Pemakaian Aktual Produksi (`used_qty`) — Larangan Stock OUT Ganda**:
  - Pada saat alokasi awal, `allocated_qty = required_qty` dan `used_qty` tetap bernilai `0.0000`.
  - Ketika peran Produksi mencatat pemakaian aktual (`used_qty`), kuantitas yang telah dialokasikan **DILARANG membuat record StockMovement OUT kedua** (karena stok fisik gudang sudah dikeluarkan pada saat alokasi awal).
  - Rekonsiliasi pemakaian bahan aktual menjaga invariant mutlak: **NO DOUBLE STOCK OUT**.
- **Negative Stock Prevention**:
  Stok fisik **TIDAK BOLEH NEGATIF**. Pengeluaran stok wajib divalidasi `qty_on_hand >= requested_qty` dengan database row locking (`lockForUpdate()`) dalam transaksi.

---

## 3.1 Actual Material Reconciliation: Pengembalian Sisa & Tambahan Bahan (Revisi Pembimbing — Tahap 2)

- **Fondasi & Definisi Model Rekonsiliasi**:
  - `required_qty`: Kebutuhan bahan baku teoritis hasil kalkulasi MRP ($BOM \times Target$). Bersifat permanen (*immutable*).
  - `allocated_qty`: Kuantitas bahan fisik yang telah dikeluarkan dari gudang ke batch produksi (alokasi awal + akumulasi alokasi tambahan).
  - `used_qty`: Kuantitas bahan aktual yang benar-benar terpakai/dikonsumsi oleh proses jahit produksi.
  - $\text{return\_qty} = \max(\text{allocated\_qty} - \text{used\_qty}, 0)$: Kuantitas sisa bahan alokasi yang tidak terpakai dan otomatis kembali ke gudang saat finalisasi.
  - $\text{shortage} = \max(\text{actual\_needed} - \text{allocated\_qty}, 0)$: Kuantitas kekurangan bahan yang wajib ditambahkan dari gudang sebelum pemakaian dapat difinalisasi.

- **Kondisi 1: Kebutuhan Aktual Sama Dengan Alokasi ($\text{used\_qty} == \text{allocated\_qty}$)**:
  - Seluruh bahan yang dialokasikan terpakai habis.
  - Tidak ada mutasi persediaan baru (`StockMovement` tidak bertambah).
  - Nilai `used_qty` tersimpan normal sebesar `allocated_qty`.

- **Kondisi 2: Kebutuhan Aktual Lebih Sedikit Dari Alokasi ($\text{used\_qty} < \text{allocated\_qty}$)**:
  - Terjadi surplus sisa bahan produksi: $\text{return\_qty} = \text{allocated\_qty} - \text{used\_qty} > 0$.
  - **Otomasi Pengembalian Bahan ke Gudang**: Sisa bahan secara otomatis dikembalikan ke saldo persediaan gudang saat tombol simpan hasil produksi dieksekusi secara otoritatif (`ProductionResultService::recordFinalResult`).
  - **Larangan Pengembalian Prematur**: Sistem **DILARANG** mengembalikan bahan hanya karena pengguna mengetikkan angka lebih kecil pada form yang belum disubmit. Pengembalian hanya terjadi pada saat transaksi finalisasi hasil produksi.
  - **Mutasi Stok Tercatat**: Dibuat record `StockMovement` bertipe `MovementType::IN` dengan:
    - `reference_type`: `'production_batches'`
    - `reference_id`: ID Batch Produksi
    - `qty`: `return_qty`
    - `qty_before` $\rightarrow$ `qty_after`: Saldo bertambah akurat
    - `notes`: `"Pengembalian sisa bahan ... ke gudang"`
    - `created_by`: ID aktor autentikasi
  - **Keamanan Moving Weighted Average (MWA)**:
    - Pengembalian bahan sisa **DILARANG DIANGGAP SEBAGAI PEMBELIAN BARU**.
    - Nilai modal per unit (`avg_unit_cost`) pada `MaterialStock` **TIDAK DIUBAH / TIDAK DIHITUNG ULANG**, melainkan tetap mempertahankan harga rata-rata historis berjalan agar tidak mendistorsi valuasi modal persediaan.
  - **Idempotensi & Imutabilitas**: Transaksi finalisasi hanya dapat dieksekusi tepat satu kali per batch (batch berpindah status ke `QC`). Upaya submit ulang ditolak sehingga tidak mungkin terjadi pengembalian ganda (*duplicate return*).

- **Kondisi 3: Kebutuhan Aktual Melebihi Alokasi ($\text{actual\_needed} > \text{allocated\_qty}$)**:
  - Produksi membutuhkan bahan lebih banyak dari yang telah dikeluarkan gudang.
  - **Pencegahan Simpan Langsung**: Sistem **DILARANG** memfinalisasi hasil produksi jika input pemakaian melebihi kuantitas alokasi saat ini. Upaya simpan ditolak keras oleh validasi server-side (`StoreProductionResultRequest` dan `ProductionResultService`).
  - **Tombol Tambah Bahan dari Gudang**: Antarmuka form pemakaian bahan menampilkan informasi kekurangan:
    $$\text{"Kekurangan: X [satuan]"}$$
    dan menyediakan tombol aksi:
    $$\mathbf{[\text{ Tambah Bahan dari Gudang }]}$$
  - **Eksekusi Alokasi Tambahan (`MaterialAllocationService::allocateAdditionalMaterial`)**:
    - Staf Produksi dapat mengeksekusi penambahan bahan langsung dari konteks batch produksi.
    - Sistem mengunci baris `ProductionBatch`, `BatchMaterial`, dan `MaterialStock` secara pesimis (`lockForUpdate()`).
    - Validasi kecukupan fisik gudang: $\text{qty\_on\_hand} \ge \text{additional\_qty}$.
    - Saldo stok fisik gudang berkurang: $\text{qty\_on\_hand} = \text{qty\_on\_hand} - \text{additional\_qty}$.
    - Kuantitas alokasi batch bertambah: $\text{allocated\_qty} = \text{allocated\_qty} + \text{additional\_qty}$.
    - Mutasi persediaan dicatat: `StockMovement` bertipe `MovementType::OUT` (`reference_type = 'production_batches'`, `notes = 'Tambahan alokasi bahan...'`).
    - Tidak ada mutasi OUT kedua saat pemakaian nantinya difinalisasi.
  - **Pencegahan Stok Negatif (*Insufficient Stock Guard*)**:
    - Jika stok gudang tidak mencukupi ($\text{qty\_on\_hand} < \text{additional\_qty}$), transaksi **DIBLOKIR TOTAL**.
    - Pesan kesalahan eksplisit: `"Stok Gudang tidak mencukupi. Kekurangan X [satuan]."`.
    - Sistem **DILARANG** membuat stok gudang bernilai negatif, dilarang membuat PO fiktif secara otomatis, dan dilarang merekayasa angka persediaan.
    - Pengadaan bahan baru tetap harus melalui alur Procurement reguler (PO $\to$ Confirm $\to$ Receive $\to$ Stock IN).
  - **Finalisasi Pemakaian Setelah Alokasi Tambahan**: Setelah alokasi tambahan berhasil masuk ke batch, Produksi dapat menginput pemakaian aktual hingga batas alokasi yang baru dan menyelesaikan batch ke tahap QC.

- **Kemandirian Rekonsiliasi Multi-Material**:
  - Setiap bahan dalam `batch_materials` direkonsiliasi secara independen (misal: Kain sisa kembali ke gudang, Benang pas, Label butuh tambahan dari gudang).
  - Seluruh kalkulasi kuantitas desimal menggunakan pustaka `BCMath` 4 desimal.

- **Semantik Real-Time ERP**:
  - Setelah transaksi alokasi tambahan atau pengembalian berhasil, halaman yang dibuka atau di-refresh (Produksi, Gudang Material, Gudang Perencanaan MRP, Mutasi Stok) langsung membaca *live state* terbaru dari database tanpa delay.
  - Snapshot historis MRP (`stock_at_calc`, `required_qty`) tetap dipertahankan sebagai bukti historis perencanaan awal dan tidak ditimpa oleh live stock.

---

## 4. Official Purchase Lifecycle & Receiving Rules (Klarifikasi Otoritatif User/Pembimbing)

- **Official Procurement Lifecycle**:
  1. `DRAFT`:
     - PO masih dalam tahap penyusunan rincian material, kuantitas, dan estimasi harga oleh Gudang.
     - Belum resmi dipesan ke supplier.
     - **DILARANG dibayar** (pencatatan pembayaran ditolak server-side).
     - **DILARANG diterima** (penerimaan fisik ditolak server-side).
  2. `ORDERED`:
     - PO resmi dipesan ke supplier oleh Gudang (`confirmPurchase`).
     - Aksi pencatatan pembayaran supplier (`PurchasePayment`) aktif bagi Admin.
     - Barang **BELUM BOLEH DITERIMA** sampai syarat pelunasan pembayaran supplier terpenuhi (`outstanding_amount == 0.00`).
  3. `SUPPLIER PAYMENT` (`PurchasePayment`):
     - **Waktu Pembayaran**: Supplier dibayar **KETIKA BAHAN DIPESAN / PO DIKONFIRMASI**, BUKAN saat barang diterima di gudang.
     - **Event Finansial**: Mengurangi Kas operasional, TIDAK mengubah stok fisik gudang, TIDAK membuat `StockMovement`, dan TIDAK mengubah `MWA`.
     - Mendukung pembayaran bertahap (*partial payment*) untuk rekam audit dan cicilan, namun penerimaan barang tetap terkunci hingga lunas penuh.
     - **Hard Cash Guard**: Pembayaran pada/setelah tanggal cut-off saldo awal tidak boleh melebihi kas tersedia (`amount <= current available cash`). Kas operasional perusahaan tidak boleh negatif.
  4. `FULLY PAID` (Derived Condition):
     - Kondisi finansial terderivasi ketika `paid_amount == total_amount` (`outstanding_amount == 0.00`).
     - Tidak memerlukan nilai enum status terpisah; dievaluasi via `Purchase::isFullyPaid()`.
  5. `RECEIVE PURCHASE` (Full Receiving):
     - **Hard Server-Side Guard**: Penerimaan PO hanya diizinkan jika PO berstatus `ORDERED` **DAN** telah berstatus finansial `FULLY PAID` (`isFullyPaid() === true`).
     - Jika PO belum lunas (`outstanding_amount > 0.00`), eksekusi `PurchaseReceivingService::receivePurchase()` **DIBLOKIR KERAS SERVER-SIDE** dengan pengecualian domain:
       $$\text{"Barang belum dapat diterima karena pembayaran supplier belum lunas."}$$
     - **Event Fisik Persediaan**: Menambah kuantitas fisik gudang (`material_stocks.qty_on_hand`), mencatat tepat satu record `StockMovement` bertipe `MovementType::IN` untuk tiap item, menghitung ulang Moving Weighted Average (`MWA`), dan **TIDAK mengurangi kas lagi** (mencegah double cash-out).
  6. `RECEIVED`:
     - Seluruh kuantitas fisik material telah diterima masuk ke stok gudang (`purchase_items.qty_received = purchase_items.qty`).
     - Status terminal fisik persediaan.
  7. `CANCELLED`:
     - PO dibatalkan sebelum material diproses. Tidak boleh dibayar dan tidak boleh diterima.

- **Pemisahan Peran & Antarmuka (Role Separation & UI)**:
  - **Gudang**:
    - Membuat dan mengedit draft PO, mengonfirmasi PO (`ORDERED`), dan menerima material setelah lunas.
    - Menampilkan status finansial PO secara *read-only*: Total PO, Sudah Dibayar, Sisa Pembayaran, dan Badge Status Pembayaran (`BELUM DIBAYAR` / `BAYAR SEBAGIAN` / `LUNAS`).
    - Saldo kas operasional perusahaan **TIDAK BOLEH DIEKSPOS** ke Gudang. Gudang hanya melihat status pemenuhan pembayaran spesifik PO tersebut.
    - Jika PO berstatus `ORDERED` dan belum lunas, tombol "Terima Barang" dalam kondisi **DISABLED (Terkunci)** disertai tooltip/helper: *"Pembayaran supplier harus lunas sebelum material dapat diterima."*.
    - Tombol "Terima Barang" hanya menjadi **ENABLED** jika status pembayaran telah `LUNAS`.
  - **Admin**:
    - Bertanggung jawab atas arus kas operasional dan mencatat pembayaran supplier melalui tombol `[ Catat Pembayaran Supplier ]`.
    - Formulir pencatatan menampilkan sisa tagihan PO, kas tersedia saat ini, batas pembayaran maksimum, serta indikator kecukupan kas.

- **Penanganan PO Historis (Legacy Received PO)**:
  - PO lama yang telah berstatus `RECEIVED` sebelum fitur pembayaran supplier tersedia tetap **VALID**.
  - Sistem **DILARANG** membatalkan, merollback, mengurangi stok fisik, atau menghitung ulang MWA dari PO historis tersebut.
  - PO historis diperlakukan sebagai data warisan (*legacy data*) yang memerlukan rekonsiliasi pembayaran bertahap di masa mendatang.
  - Semantik tanggal pembayaran historis: Pembayaran supplier di masa lalu terjadi saat bahan dipesan, sehingga tanggal pembayaran historis mengikuti tanggal pemesanan aktual PO (`purchase_date`). Rekonsiliasi otomatis tidak dijalankan tanpa persetujuan eksplisit user.

- **Larangan & Invariant Penting**:
  - **TIDAK ADA PARTIAL RECEIVING**: Penerimaan fisik selalu penuh 1-langkah setelah pembayaran lunas.
  - **NO DOUBLE CASH-OUT**: Event pembayaran mengurangi kas; event penerimaan hanya mengubah stok dan MWA. Penerimaan tidak pernah membuat pengeluaran kas kedua.
  - **NO DOUBLE RECEIVING**: Penerimaan hanya dapat dieksekusi tepat satu kali per PO di bawah perlindungan database row lock.
  - Penerimaan barang **TIDAK mengubah** status batch produksi (tetap `BAHAN`) maupun status Sales Order.
  - Penerimaan barang **TIDAK melakukan alokasi stok otomatis**. Alokasi material dikerjakan secara terpisah pada modul Produksi/Gudang.
  - Snapshot historis MRP (`batch_materials.required_qty`, `stock_at_calc`, `shortage_qty`) tetap bersifat *immutable*.

---

## 5. Production, Quality Control (QC), and Batch Progress (Revisi Pembimbing — Tahap 1)

- **Production Lifecycle & Target Date Rules**:
  - **Penetapan Target Tanggal Selesai**: Hanya Admin yang berwenang menentukan target tanggal selesai (`planned_end_date`) pada saat pembuatan Production Batch. Field ini wajib diisi (`required`).
  - **Akses Peran Produksi**: Tim Produksi dapat melihat target kuantitas dan target tanggal penyelesaian batch pada antrean (`index`) dan detail batch (`show`). Produksi **DILARANG** mengubah target tanggal. Visual indicator `Terlambat` ditampilkan jika target telah terlewati dan batch belum selesai.
  - **Akses Peran Gudang**: Tim Gudang dapat melihat target tanggal selesai pada konteks Perencanaan Bahan (MRP) sebagai informasi urgensi persiapan material. Gudang **DILARANG** mengubah target tanggal.
- **Authoritative Business Rule: Kuantitas Hasil Produksi Aktual (`actual_qty`)**:
  - **Batch target is a production planning baseline, not a hard production cap. Actual production quantity may be below, equal to, or above the target. QC classification is based on final actual production quantity.**
  - *Catatan:* Aturan terbaru ini secara definitif menggantikan (*supersedes*) implementasi lama yang membatasi `actual_qty <= target_qty`.
  - Invarian validasi kuantitas hasil aktual: $\text{actual\_qty} > 0$ (berupa angka positif, tidak boleh negatif atau nol).
  - Target kuantitas batch (`target_qty`) bersifat *immutable planning baseline* dan tidak boleh diubah saat hasil aktual dicatat, guna menjaga integritas evaluasi performa produksi dan perbandingan variansi:
    $$\text{variance} = \text{actual\_qty} - \text{target\_qty}$$
  - Hasil aktual yang melebihi target ($\text{actual\_qty} > \text{target\_qty}$) tidak mengubah kuantitas Sales Order, Order Item, harga jual, maupun nilai invoice tagihan pelanggan.
  - FormRequest (`StoreProductionResultRequest`) dan domain service (`ProductionResultService`) hanya memberlakukan validasi numerik positif tanpa guard batas maksimum target.
  - Pada UI form pencatatan hasil produksi, input `actual_qty` tidak memiliki HTML constraint `max` berdasarkan target batch. Estimasi selisih ditampilkan secara informatif/netral.
- **Quality Control (QC) Rules & Invariants**:
  - **Good Qty Guard**: $0 \le \text{good\_qty} \le \text{actual\_qty}$. Jumlah produk lolos QC (Good) tidak boleh melebihi total kuantitas hasil produksi aktual.
  - **Kalkulasi Otomatis Reject**: Staff Produksi/QC hanya menginput `good_qty`. Kuantitas produk cacat/afkir dihitung otomatis oleh server:
    $$\text{reject\_qty} = \text{actual\_qty} - \text{good\_qty}$$
    Payload `reject_qty` dari client diabaikan atau tidak diterima; server selalu menghitung ulang secara kanonik.
  - **Final Invariant**: $\text{good\_qty} + \text{reject\_qty} = \text{actual\_qty}$.
  - **Otomasi & Semantik Tanggal QC (`qc_date` vs `qc_at`) (Klarifikasi Authoritative Pembimbing)**:
    - **Tanggal Bisnis QC (`qc_date`)**: Otomatis mengikuti tanggal ketika produksi mulai dibuat/dikerjakan (`ProductionBatch.getProductionStartDate()` yang bersumber dari `planned_start_date`, dengan fallback ke `production_results.result_date` atau `created_at` dalam zona bisnis WIB).
    - **Pintu Kelayakan Tanggal QC (*QC Eligibility Date Gate*)**: QC **TIDAK BOLEH** dilakukan lebih awal dari tanggal produksi mulai. QC baru boleh dilakukan pada atau setelah tanggal produksi (`today in Asia/Jakarta >= production_start_date`). Upaya akses atau penyimpanan QC sebelum tanggal produksi ditolak keras di server-side (`DomainException: "QC belum dapat dilakukan sebelum tanggal produksi."`) dan tombol aksi dinonaktifkan di UI.
    - **Waktu Pencatatan Audit (`qc_at`)**: Actual audit timestamp saat record QC disimpan (`now()` tersimpan dalam UTC, ditampilkan dalam WIB `Asia/Jakarta`).
    - **Pemisahan Semantik Mutlak**: $\text{qc\_date} \ne \text{date}(\text{qc\_at})$. Contoh: Produksi mulai 10 Oktober 2026, QC disubmit pada 12 Oktober 2026 $\rightarrow$ `qc_date` = 10 Oktober 2026, `qc_at` = 12 Oktober 2026 (timestamp actual).
    - **Bukan Target Selesai**: `planned_end_date` adalah target batas waktu penyelesaian batch (deadline), dan **DILARANG DIGUNAKAN** sebagai `qc_date`.
    - **Staff Produksi Dilarang Menginput Manual**: Kedua field `qc_date` dan `qc_at` tidak disediakan input form manual dan terlindungi dari manipulasi payload request.
- **Independensi Progres Batch di Kanban & Order Status Aggregation**:
  - Setiap Production Batch berpindah status secara mandiri (`BAHAN` $\rightarrow$ `PRINTING` $\rightarrow$ `PRODUKSI` $\rightarrow$ `QC` $\rightarrow$ `PACKING` $\rightarrow$ `SELESAI`) tanpa menunggu sibling batch lain dalam satu order selesai.
  - Pada Kanban Board, setiap batch yang telah selesai langsung ditampilkan berstatus `Batch Selesai` pada kartu order terkait.
  - **Sales Order Aggregation Preserved**: Status Sales Order tetap merupakan agregasi keseluruhan dan **DILARANG** berpindah ke tahap final (`SELESAI`) hanya karena satu batch selesai sebelum seluruh fulfillment terpenuhi.
- **Pengemasan (Packing)**:
  - Proses packing hanya menggunakan kuantitas produk baik (`good_qty`). Produk reject tidak dikemas.
  - Penyelesaian Packing memindahkan batch ke `SELESAI` via `BatchWorkflowService::completePacking`.

---

## 6. HPP & Profitabilitas Pesanan (Owner O2 & O3 Foundation)

- **Komponen HPP Batch**:
  $$\text{Total HPP Batch} = \text{Material Cost} + \text{Biaya Jasa Printing} + \text{Tenaga Kerja Langsung} + \text{Biaya Proses Pengemasan} + \text{Biaya Produksi Lainnya}$$
  - **Material Cost**: $\sum(\text{BatchMaterial.used\_qty} \times \text{StockMovement OUT unit\_cost\_snapshot})$. Snapshot bersifat permanen dan tidak terpengaruh perubahan `avg_unit_cost` di kemudian hari.
  - **Direct Costs**: Biaya langsung non-material dihitung otomatis dari tarif master yang di-snapshot permanen pada `production_batches` (`direct_labor_cost_per_unit_snapshot`, `packaging_process_cost_per_good_unit_snapshot`, `other_direct_cost_per_unit_snapshot`, dan `printing_cost_per_unit_snapshot` jika varian membutuhkan printing).
- **Kesiapan HPP Batch (*Batch HPP Readiness*)**:
  HPP Batch hanya berstatus **`READY` (Final)** jika memenuhi seluruh invarian berikut secara berurutan:
  1. `ProductionBatch.status === BatchStatus::SELESAI` (bukan status aktif atau `CANCELLED`).
  2. `material_usage_recorded_at IS NOT NULL` (pemakaian bahan aktual telah dicatat oleh Produksi).
  3. Snapshot alokasi bahan valid (tepat 1 record `OUT` per `BatchMaterial`, harga modal $\ge 0$).
  4. `QcResult` definitif ditemukan pada batch (`QC_RESULT_MISSING` dicegah).
  5. Snapshot tarif biaya langsung lengkap pada batch (`hasCompleteCostSnapshots()`). HPP dihitung otomatis oleh sistem tanpa konfirmasi manual Admin.
- **HPP per Good Pcs (Batch)**:
  $$\text{hpp\_per\_good\_pcs} = \begin{cases} \text{Total Batch HPP} / \text{QcResult.good\_qty} & \text{jika } \text{good\_qty} > 0 \\ \text{NULL} & \text{jika } \text{good\_qty} = 0 \text{ (All-Reject Batch)} \end{cases}$$
  - Dilarang keras menggunakan `target_qty` atau `actual_qty` sebagai pembagi `hpp_per_good_pcs`.
- **Agregasi HPP Pesanan (*Order HPP Aggregation*)**:
  $$\text{Total HPP Pesanan} = \sum(\text{Total HPP dari seluruh ProductionBatch non-CANCELLED})$$
  - **Termasuk dalam agregasi**: Batch split, batch original, batch produksi pengganti (*replacement batch*), dan batch gagal QC (*all-reject batch*). Biaya reject yang terjadi tetap dibebankan ke pesanan.
  - **Dikecualikan**: Batch berstatus `CANCELLED` (tidak menyumbang biaya, total HPP, maupun kuantitas good).
- **Kesiapan HPP Pesanan (*Order HPP Readiness*)**:
  HPP Pesanan hanya berstatus **`READY`** jika:
  1. Tidak ada kekurangan pemenuhan produksi (`remaining_to_plan == 0` pada semua item pesanan).
  2. Seluruh batch non-cancelled yang dibutuhkan telah berstatus `SELESAI`.
  3. Seluruh batch yang disertakan telah berstatus HPP `READY`.
- **HPP per Good Pcs (Pesanan)**:
  $$\text{Order HPP per Good Pcs} = \frac{\text{Total HPP Pesanan}}{\text{Total Good Output (QC)}}$$
  - Pembagi adalah total riil produk lolos QC ($\sum \text{good\_qty}$). Jika terjadi surplus produksi (misal order 20, good output 22), pembagi menggunakan kuantitas riil 22 tanpa mengubah `qty_ordered` atau pendapatan Sales Order.
- **Pendapatan Pesanan (*Order Revenue Authority*) — Step O3**:
  $$\text{Revenue Order} = \sum(\text{order\_items.qty\_ordered} \times \text{order\_items.unit\_selling\_price})$$
  - `unit_selling_price` adalah snapshot transaksi pada saat Sales Order disepakati. Perubahan harga master katalog (`base_selling_price`), nilai invoice, maupun nominal pembayaran yang diterima **DILARANG** mengubah nilai Revenue Order.
- **Laba Kotor & Margin (*Gross Profit & Gross Margin*) — Step O3**:
  $$\text{Gross Profit} = \text{Revenue Order} - \text{Total HPP Pesanan}$$
  $$\text{Gross Margin (\%)} = \begin{cases} (\text{Gross Profit} / \text{Revenue}) \times 100 & \text{jika } \text{Revenue} > 0 \\ \text{NULL} & \text{jika } \text{Revenue} == 0 \end{cases}$$
  - Nilai Gross Profit negatif diperbolehkan (sebagai informasi bisnis yang sah, tidak di-clamp ke 0).
  - Jika HPP Pesanan belum `READY`, nilai Gross Profit dan Gross Margin berstatus **Belum Final (`NULL`)**.
- **Atribusi Beban Operasional Pesanan (*Order-Attributed Operating Expense*) — Step O3**:
  $$\text{Attributed Operating Expense} = \sum(\text{BusinessExpense.amount WHERE order\_id == Order.id AND expense\_type == OPERATING})$$
  - **CRITICAL**: Hanya beban bertipe **`OPERATING`** dengan tautan eksplisit `order_id` yang dikurangkan dalam perhitungan Net Profit pesanan.
  - Beban bertipe `MARKETING`, `INTEREST`, `TAX`, `DEPRECIATION`, dan `AMORTIZATION` **DILARANG** dikurangkan di level pesanan (dialokasikan untuk metrik periodik di Step O4).
  - Beban global (`order_id IS NULL`) **DILARANG** dialokasikan secara proporsional ke pesanan individual.
- **Laba Bersih & Margin Pesanan (*Order Net Profit & Net Margin*) — Step O3**:
  $$\text{Net Profit Order} = \text{Gross Profit} - \text{Attributed Operating Expense}$$
  $$\text{Net Margin (\%)} = \begin{cases} (\text{Net Profit} / \text{Revenue}) \times 100 & \text{jika } \text{Revenue} > 0 \\ \text{NULL} & \text{jika } \text{Revenue} == 0 \end{cases}$$
  - Nilai Net Profit negatif diperbolehkan. Jika HPP Pesanan belum `READY`, Net Profit dan Net Margin berstatus **Belum Final (`NULL`)**.
- **Agregasi Ringkasan Finansial di Indeks Profitabilitas (*Index Summaries Scope*)**:
  - `TOTAL REVENUE`: Penjumlahan pendapatan seluruh Sales Order aktif (valid, non-cancelled) yang sesuai dengan filter pencarian dan tanggal, **tidak bergantung** pada status kesiapan HPP pesanan.
  - `TOTAL LABA KOTOR FINAL` & `TOTAL LABA BERSIH FINAL`: Penjumlahan laba kotor dan laba bersih **hanya** dari pesanan yang berstatus `READY`. Jika tidak ada pesanan berstatus profit final pada hasil filter, ringkasan menampilkan tanda `—` dan penjelasan bantuan (*bukan Rp 0*) untuk mencegah misinterpretasi laba Rp 0.

---

## 6.1 Performa Keuangan Periodik & Eksekutif Dashboard (Owner O4 Foundation)

- **Otoritas Tanggal Entitas (*Date Authorities*)**:
  - **Pendapatan & HPP Pesanan**: Menggunakan `orders.order_date` inklusif dalam rentang waktu (`Asia/Jakarta`).
  - **Beban Usaha (*Business Expenses*)**: Menggunakan `business_expenses.expense_date` inklusif dalam rentang waktu (`Asia/Jakarta`), baik dengan `order_id` maupun tanpa `order_id` (beban umum/global).
  - **Penerimaan Kas (*Cash Collection*)**: Menggunakan `payments.payment_date` inklusif dengan status `CONFIRMED`.
- **Kesiapan Profitabilitas Periode (*Period Profit Readiness*)**:
  - Profitabilitas periode berstatus **`READY` (Final)** hanya jika **seluruh** pesanan non-cancelled pada periode tersebut memiliki status HPP `READY`.
  - Jika terdapat minimal 1 pesanan yang HPP-nya belum final (`unready_orders_count > 0`), nilai Revenue Periode tetap disajikan, namun metrik: **HPP Periode, Laba Kotor, Gross Margin, Laba Bersih, Net Margin, Total Biaya, ROI, dan EBITDA** berstatus **`BELUM FINAL` (`NULL`)**.
- **Pendapatan Periode (*Revenue Period*)**:
  $$\text{Revenue Period} = \sum(\text{Order Revenue dari seluruh Order non-CANCELLED dengan } \text{order\_date} \in [\text{start\_date}, \text{end\_date}])$$
- **Total Beban Usaha Periode (*Total Period Expenses*)**:
  $$\text{Total Expenses} = \text{Marketing} + \text{Operating} + \text{Interest} + \text{Tax} + \text{Depreciation} + \text{Amortization}$$
  - Setiap baris `BusinessExpense` dihitung tepat satu kali berdasarkan `expense_date`. Biaya langsung batch (`BatchCost`) yang telah masuk HPP **DILARANG** dimasukkan ulang ke dalam `BusinessExpense` (mencegah double-counting).
- **Laba Kotor & Margin Periode (*Period Gross Profit & Gross Margin*)**:
  $$\text{Gross Profit Period} = \text{Revenue Period} - \text{HPP Period}$$
  $$\text{Gross Margin (\%)} = \begin{cases} (\text{Gross Profit} / \text{Revenue}) \times 100 & \text{jika } \text{Revenue} > 0 \\ \text{NULL} & \text{jika } \text{Revenue} == 0 \end{cases}$$
- **Laba Bersih & Margin Periode (*Period Net Profit & Net Margin*)**:
  $$\text{Net Profit Period} = \text{Gross Profit Period} - \text{Total Period Expenses}$$
  $$\text{Net Margin (\%)} = \begin{cases} (\text{Net Profit} / \text{Revenue}) \times 100 & \text{jika } \text{Revenue} > 0 \\ \text{NULL} & \text{jika } \text{Revenue} == 0 \end{cases}$$
- **Total Biaya Usaha Periode (*Total Cost Period*)**:
  $$\text{Total Cost Period} = \text{HPP Period} + \text{Total Period Expenses}$$
- **Return on Investment (*ROI Period*)**:
  $$\text{ROI (\%)} = \begin{cases} (\text{Net Profit Period} / \text{Total Cost Period}) \times 100 & \text{jika } \text{Total Cost Period} \neq 0 \\ \text{NULL} & \text{jika } \text{Total Cost Period} == 0 \end{cases}$$
  - **Eksklusif Periodik**: ROI hanya dihitung di tingkat periode usaha dan **DILARANG** dihitung per Sales Order.
- **Marketing Efficiency Ratio (*MER Period*)**:
  $$\text{MER} = \begin{cases} \text{Revenue Period} / \text{Marketing Spend Period} & \text{jika } \text{Marketing Spend} > 0 \\ \text{NULL} & \text{jika } \text{Marketing Spend} == 0 \end{cases}$$
  - Ditampilkan dalam format rasio pengali (contoh: `15,00x`). Jika Revenue = 0 dan Marketing > 0, MER = `0.0000`.
- **EBITDA Periode (*Earnings Before Interest, Tax, Depreciation, Amortization*)**:
  $$\text{EBITDA} = \text{Revenue Period} - \text{HPP Period} - \text{Marketing Expense} - \text{Operating Expense}$$
  $$\text{Rekonsiliasi} = \text{Net Profit Period} + \text{Interest} + \text{Tax} + \text{Depreciation} + \text{Amortization}$$
- **Penerimaan Kas (*Payment Received / Cash Collection*)**:
  $$\text{Payment Received} = \sum(\text{Payment.amount WHERE status == CONFIRMED AND } \text{payment\_date} \in [\text{start\_date}, \text{end\_date}])$$
  - Merupakan metrik arus kas masuk dan **BUKAN** komponen dari perhitungan Laba Bersih maupun Revenue.
- **Zero Persistence & Side-Effect Free Reads**:
  - Seluruh metrik periodik dihitung secara langsung via `PeriodFinancialService`. Tidak ada penulisan data ke tabel `order_financials` atau entitas lainnya saat Owner mengakses Dashboard maupun Kinerja Keuangan.
- **Batasan Akses Peran**:
  - Halaman Dashboard dan Kinerja Keuangan (`/owner/dashboard`, `/owner/performance`) eksklusif untuk peran **OWNER** (*read-only*). Role lain menerima status 403 Forbidden.

---

## 7. Keamanan Tracking Token Customer

- Token akses tracking publik dibuat secara acak (cryptographically secure, 32 bytes / 64 hex chars).
- **DILARANG** menyimpan raw token di database maupun session jangka panjang/log.
- Token di-hash menggunakan **SHA-256** dan hanya nilai hash yang disimpan pada `order_tracking_tokens.token_hash`.
- Tracking token dapat digunakan berulang kali oleh Customer selama masih aktif (bukan token sekali pakai bagi pelanggan).
- Raw tracking link hanya ditampilkan/diflash sekali (*one-time flash*) saat pembuatan atau regenerasi Sales Order agar Admin dapat menyalinnya.
- Pada halaman detail, setelah refresh / flash lewat, sistem menampilkan status `[AKTIF]` dengan tombol `[Regenerasi Tautan]`. Jika tautan hilang/lupa, Admin membuat tautan baru melalui regenerasi; token lama otomatis dinonaktifkan (`revoked_at = now()`).
- Form input publik `/track` menerima format token murni (64 hex) maupun complete NAYARA tracking URL (`http(s)://.../track/{token}`) dengan ekstraksi otomatis.
- Validasi publik mencakup pencarian berdasarkan `hash('sha256', $rawToken)` dan pemeriksaan `revoked_at` serta `expires_at`. Data sensitif internal (HPP, supplier, catatan internal, profit/margin) dilarang bocor ke publik.

---

## 8. Format Penomoran Dokumen Otomatis (Document Auto-Numbering Rules)

- **Format Standar Per Jenis Dokumen**:
  - Sales Order (`orders.order_no`): `SO-YYYYMM-XXXX` (contoh: `SO-202608-0001`)
  - Production Batch (`production_batches.batch_no`): `BAT-YYYYMM-XXXX` (contoh: `BAT-202608-0001`)
  - Purchase Order (`purchases.purchase_no`): `PO-YYYYMM-XXXX` (contoh: `PO-202608-0001`)
  - Invoice (`invoices.invoice_no`): `INV-YYYYMM-XXXX` (contoh: `INV-202608-0001`)
- **Aturan Generasi**:
  1. `YYYYMM` menggunakan waktu sistem saat dokumen dibuat (timezone `Asia/Jakarta`). Dilarang mengambil dari input `order_date` yang editable.
  2. `XXXX` adalah sequence urut 4 digit dengan padding nol (`0001` s/d `9999`).
  3. Jika nomor urut bulanan melampaui 9999, sistem wajib melempar `\OverflowException` secara eksplisit (tidak boleh menghasilkan 5 digit diam-diam).
  4. Sequence di-reset ke `0001` setiap pergantian bulan.
  5. Sequence dihitung secara terpisah per jenis dokumen via tabel pendukung `document_sequences` dengan constraint `UNIQUE(prefix, period)`.
  6. Generator wajib **concurrency-safe** menggunakan transaksi dan row lock (`lockForUpdate()`) dan berpartisipasi dalam transaksi atomik pembuatan dokumen utama (rollback atomik jika pembuatan order gagal).
  7. Nomor dokumen wajib **UNIQUE** pada masing-masing tabel target.
  8. **DILARANG** menggunakan angka acak (*random number*) sebagai nomor dokumen.
  9. **DILARANG** mengubah nomor dokumen yang sudah resmi tersimpan (*immutable*).

---

## 9. Aturan Status & Pengeditan Sales Order (Phase 2B)

- **Status Awal**: Setiap Sales Order baru dimulai pada status `KOMUNIKASI`.
- **Transisi Manual Admin**:
  - Pada Fase 2B, Admin **HANYA** diizinkan melakukan transisi status manual `KOMUNIKASI → DEAL` via aksi Konfirmasi Deal.
  - Transisi manual ke status selanjutnya (`BAHAN`, `PRINTING`, dst.) **DILARANG** di modul Sales Order karena status tersebut dipicu oleh aksi modul operasional masing-masing di fase selanjutnya.
- **Konkurensi Konfirmasi Deal**:
  - Eksekusi `confirmDeal()` wajib menggunakan transaksi database dengan row lock (`lockForUpdate()`) pada tabel `orders`.
  - Sistem memverifikasi status adalah `KOMUNIKASI` di bawah lock sebelum mengubah ke `DEAL` dan mencatat 1 riwayat pada `order_status_histories`.
- **Aturan Pengeditan Order**:
  - Order hanya dapat diedit saat berstatus `KOMUNIKASI` atau `DEAL`.
  - Jika Order sudah memiliki Invoice (`invoices.order_id`), pengubahan/pengeditan order **DITOLAK** dengan pesan *"Pesanan tidak dapat diubah karena invoice sudah diterbitkan."*
  - Jika item pesanan telah memiliki relasi ke `ProductionBatch` hilir, pengubahan/penghapusan item order **DITOLAK**.
  - `order_no` bersifat *read-only* dan tidak dapat diubah saat pengeditan.
- **Aturan Harga Jual & Snapshot Transaksi (PRD v1.6)**:
  - **Master Price Authority**: `product_variants.base_selling_price` adalah satu-satunya otoritas harga jual produk. Admin **DILARANG** mengedit/menimpa harga satuan pada form Sales Order.
  - **Snapshot Transaksi Otomatis**: Saat item dibuat atau varian diubah, sistem menyalin harga master ke `order_items.unit_selling_price`.
  - **Proteksi Varian Tanpa Harga**: Varian dengan `base_selling_price IS NULL` atau $\le 0$ ditolak (*"Harga jual varian belum ditetapkan. Atur harga pada Master Produk terlebih dahulu."*).
  - **Preservasi Snapshot Saat Edit**: Item lama yang variannya tidak diubah mempertahankan harga snapshot historisnya (tidak terpengaruh meski harga master varian tersebut berubah atau menjadi NULL).
  - **Anti-Tampering Backend**: Server selalu mengabaikan input harga dari browser dan menggunakan harga snapshot yang valid.

---

## 10. Invoice & Payment Management Rules (Phase 2C)

- **Syarat Penerbitan Invoice**:
  - Invoice hanya dapat diterbitkan untuk Sales Order yang telah mencapai status minimal `DEAL` (status yang diizinkan: `DEAL`, `BAHAN`, `PRINTING`, `PRODUKSI`, `QC`, `PACKING`, `DIKIRIM`, `SELESAI`).
  - Dilarang membuat invoice saat Order berstatus `KOMUNIKASI`.
  - Satu Order hanya memiliki tepat satu Invoice (`invoices.order_id` UNIQUE).
- **Snapshot Finansial & Penguncian Order**:
  - `invoices.total_amount` adalah snapshot total nominal dari $\sum(\text{order\_items.qty\_ordered} \times \text{order\_items.unit\_selling\_price})$.
  - Setelah Invoice dibuat, Sales Order menjadi terkunci (*immutable*) terhadap pengeditan data order item, kuantitas, harga satuan, maupun customer.
- **Perhitungan Finansial Desimal-Aman (Decimal-Safe / BCMath)**:
  - Dilarang menggunakan tipe `float`/`double` untuk kalkulasi moneter. Seluruh kalkulasi menggunakan string angka berpresisi 2 desimal (`DECIMAL(18,2)` dan fungsi `bcadd`, `bcsub`, `bccomp`).
  - `paid_amount` = $\sum(\text{payments.amount WHERE status = CONFIRMED})$.
  - `outstanding_balance` = $\max(0, \text{invoice.total\_amount} - \text{paid\_amount})$.
  - Pembayaran berstatus `PENDING` atau `CANCELLED` **TIDAK DIHITUNG** ke dalam `paid_amount`.
- **Proteksi Overpayment (Concurrency-Safe)**:
  - Pembayaran baru berstatus `CONFIRMED` **DILARANG** melebihi sisa tagihan (`outstanding_balance`).
  - Pengecekan overpayment wajib dieksekusi di bawah database row-level lock (`Invoice::lockForUpdate()`) di dalam transaksi.
- **Payment Auto-Amount & Validation Rule (PRD v1.4)**:
  - **DP (Uang Muka)**:
    - Formula nominal: $\text{expected\_dp} = \min\left(\text{bcdiv}(\text{invoice.total\_amount}, 2, 2), \text{outstanding\_balance}\right)$.
    - Input nominal di UI bersifat *readonly* (auto-populated) dengan label bantuan *"50% dari total tagihan"*.
    - Backend menolak pembayaran berjenis DP jika nominal yang dikirimkan tidak sama persis dengan $\text{expected\_dp}$ (Pesan: *"Nominal DP harus sebesar 50% dari total tagihan."*).
  - **PELUNASAN**:
    - Formula nominal: $\text{expected\_pelunasan} = \text{outstanding\_balance}$.
    - Input nominal di UI bersifat *readonly* (auto-populated) dengan label bantuan *"Sesuai sisa tagihan"*.
    - Backend menolak pembayaran berjenis PELUNASAN jika nominal yang dikirimkan tidak sama persis dengan sisa tagihan saat transaksi diproses di bawah lock (Pesan: *"Nominal pelunasan harus sesuai dengan sisa tagihan."*).
  - **LAINNYA**:
    - Input nominal di UI bersifat *editable* dengan label bantuan *"Masukkan nominal pembayaran"*.
    - Backend memvalidasi $0 < \text{amount} \le \text{outstanding\_balance}$.
- **Standardisasi Metode Pembayaran (PRD v1.5)**:
  - Metode pembayaran sah: `Transfer Bank`, `Tunai`, `QRIS`.
  - Semua varian nama bank (`Transfer BCA`, `Transfer Mandiri`, `Transfer BNI`, `Transfer BRI`, dll.) dinormalisasi dan ditampilkan secara konsisten sebagai `Transfer Bank`.
  - Seluruh form Admin menggunakan pilihan dropdown `<select>` terkontrol.
- **Ekspor CSV Invoice & Laporan Penjualan (PRD v1.5)**:
  - Format output UTF-8 BOM (`\xEF\xBB\xBF`) dengan sanitasi proteksi spreadsheet formula injection (`=`, `+`, `-`, `@`).
  - Nilai numerik tanpa prefix 'Rp' untuk menjaga kompatibilitas olah data.
  - Laporan Penjualan Admin (`/admin/reports/sales`) berdasar rentang `orders.order_date` (Asia/Jakarta), mengikutsertakan pesanan berstatus $\ge$ `DEAL` (eksklusi `KOMUNIKASI`), membedakan secara tegas antara **Total Pendapatan** ($\sum \text{qty} \times \text{harga}$) dengan **Pembayaran Diterima** ($\sum \text{pembayaran confirmed}$).
- **Siklus Status Invoice**:
  - Jika `paid_amount == 0`: `UNPAID`
  - Jika $0 < \text{paid\_amount} < \text{invoice.total\_amount}$: `PARTIAL`
  - Jika $\text{paid\_amount} \ge \text{invoice.total\_amount}$: `PAID`
  - Jika Invoice berstatus `CANCELLED`: status `CANCELLED` dipertahankan dan invoice menolak pencatatan pembayaran baru.
- **Pencatatan & Pembatalan Pembayaran**:
  - Pembayaran manual oleh Admin dicatat langsung dengan status `CONFIRMED`.
  - Pembatalan pembayaran mengubah status menjadi `CANCELLED` (tanpa penghapusan fisik record) dan memicu rekalkulasi status Invoice di bawah row lock Invoice terlebih dahulu.
  - Pembatalan berulang pada pembayaran yang sudah `CANCELLED` ditolak secara idempoten.

---

## 11. Business Expense & Marketing Spend Management Rules

- **Kategori & Penggolongan Pengeluaran (`ExpenseType`)**:
  - `MARKETING`: Biaya promosi, periklanan berbayar, influencer, dsb.
  - `OPERATING`: Beban operasional reguler bisnis (sewa, utilitas, konsumsi, ATK).
  - `INTEREST`: Beban bunga pembiayaan atau pinjaman.
  - `TAX`: Beban perpajakan usaha.
  - `DEPRECIATION`: Beban penyusutan aset tetap fisik.
  - `AMORTIZATION`: Beban amortisasi aset tidak berwujud.
- **Atribusi Terhadap Pesanan & Pelanggan**:
  - Pengeluaran dapat diatribusikan ke satu Sales Order tertentu melalui foreign key `business_expenses.order_id`.
  - Jika `order_id` bernilai `NULL`, pengeluaran dicatat sebagai beban umum bisnis non-pesanan (*general periodic expense*).
- **Penanganan Kampanye Marketing (`campaign_name`)**:
  - Field `campaign_name` opsional dan hanya berlaku apabila `expense_type === MARKETING`.
  - Apabila Admin memilih jenis pengeluaran non-MARKETING, nilai `campaign_name` dinormalisasi secara otomatis menjadi `NULL` pada level Request.
- **Ketentuan Audit Trail & Immutability**:
  - Record pengeluaran mencatat `created_by` (ID Admin pembuat).
  - Penghapusan fisik record (*hard delete*) **DILARANG** dan route `destroy` dikecualikan dari resource controller.
  - Nominal pengeluaran wajib bertipe desimal aman `DECIMAL(18,2)` dan bernilai $> 0$.

---

## 12. Production Batch Creation & Automatic MRP Snapshot Rules

- **Kepemilikan & Otorisasi**:
  - Pembuatan Production Batch dimiliki oleh role `ADMIN`.
- **Syarat Kelayakan Status Order**:
  - Batch hanya dapat dibuat apabila status Order adalah `DEAL` atau `BAHAN`.
  - Pembuatan batch **DITOLAK** apabila status Order adalah `KOMUNIKASI`, `PRINTING`, `PRODUKSI`, `QC`, `PACKING`, `DIKIRIM`, atau `SELESAI`.
- **Syarat Prasyarat Produksi (Routing & BOM Valid)**:
  - Varian produk wajib memiliki konfigurasi rute produksi eksplisit (`product_variants.requires_printing IS NOT NULL`).
  - Varian produk wajib memiliki minimal satu resep BOM aktif (`bom_items` count > 0) dengan material aktif dan kuantitas valid (`qty_per_unit > 0`).
  - Pembuatan batch **DITOLAK** jika BOM belum tersedia atau material berstatus nonaktif.
- **Kalkulasi & Snapshot Kebutuhan Bahan Otomatis (Automatic Batch MRP)**:
  - Saat Batch dibuat, sistem membaca resep BOM master varian produk secara otomatis dan menghitung `batch_materials`.
  - Formula: $\text{required\_qty} = \text{BOM qty\_per\_unit} \times \text{batch.target\_qty}$ menggunakan BCMath `DECIMAL(18,4)`.
  - Snapshot historis: `stock_at_calc` (stok gudang saat pembuatan batch) dan `shortage_qty` ($\max(\text{required\_qty} - \text{stock\_at\_calc}, 0)$).
  - Kolom alokasi awal: `allocated_qty = 0.0000` dan `used_qty = 0.0000`.
  - Snapshot bersifat **IMMUTABLE**: Perubahan resep BOM master di masa mendatang tidak mengubah `required_qty` batch yang sudah ada.
  - MRP murni bersifat perencanaan: Pembuatan MRP **TIDAK MENGUBAH** stok gudang (`material_stocks`), tidak mengubah `avg_unit_cost`, dan **TIDAK MEMBUAT** `StockMovement`.
- **Transisi Status Order & Catatan Riwayat**:
  - Pembuatan batch pertama pada Order berstatus `DEAL` secara otomatis mengubah status pesanan menjadi `BAHAN` (`OrderStatus::BAHAN`).
  - Tepat satu entri `OrderStatusHistory` dengan status `BAHAN` dicatat oleh Admin pembuat batch.
  - Pembuatan batch lanjutan pada Order yang sudah berstatus `BAHAN` **TIDAK MENAMBAHKAN** duplikat riwayat status `BAHAN`.
- **Makna Tahap BAHAN**:
  - Tahap BAHAN menandakan batch sedang dalam tahap pemenuhan dan alokasi kebutuhan bahan baku oleh Gudang (bukan menunggu pembuatan BOM).
- **Dukungan Split Batch & Kapasitas Kuantitas**:
  - Satu `OrderItem` dapat dipecah menjadi beberapa batch (`OrderItem hasMany ProductionBatches`).
  - Setiap split batch menghitung snapshot kebutuhan bahannya sendiri secara independen dari BOM master saat batch tersebut dibuat.
  - Target kuantitas batch baru wajib memenuhi: `target_qty > 0` dan `target_qty <= remaining_qty`.
  - Formula sisa kuantitas: $\text{remaining\_qty} = \text{order\_item.qty\_ordered} - \sum(\text{target\_qty batch aktif})$.
  - Batch berstatus `BatchStatus::CANCELLED` **DIKECUALIKAN** dari kalkulasi kuantitas terpakai.
  - Semua kalkulasi kuantitas menggunakan aritmatika desimal aman (`DECIMAL(18,4)` via BCMath `bcsub`, `bcadd`, `bccomp`).
- **Keamanan Konkurensi & Transaksi Atomik (Atomic Batch Creation)**:
  - Eksekusi pembuatan batch dibungkus dalam satu transaksi database dengan urutan lock eksplisit:
    1. Lock baris `Order` (`lockForUpdate()`).
    2. Verifikasi kelayakan status Order (`DEAL` atau `BAHAN`).
    3. Lock baris `OrderItem` (`lockForUpdate()`).
    4. Verifikasi kepemilikan `OrderItem` (`lockedItem.order_id === lockedOrder.id`).
    5. Hitung kuantitas terpakai dari batch non-CANCELLED di bawah lock.
    6. Verifikasi `target_qty <= remaining_qty`.
    7. Verifikasi rute produksi varian (`requires_printing`).
    8. Validasi resep BOM varian dan peroleh koleksi data BOM terotorisasi (mencegah celah konsistensi TOCTOU).
    9. Alokasi nomor dokumen `BAT-YYYYMM-XXXX` via `DocumentNumberGenerator`.
    10. Simpan record `ProductionBatch` dengan status awal `BatchStatus::BAHAN` dan snapshot rute `requires_printing`.
    11. Buat record snapshot `BatchMaterial` untuk setiap item BOM yang telah divalidasi.
    12. Verifikasi invarian: batch baru wajib memiliki minimal satu record `BatchMaterial`.
    13. Eksekusi transisi status Order ke `BAHAN` dan catat riwayat status jika sebelumnya `DEAL`.
    14. Commit transaksi.
  - Jika kalkulasi atau pembuatan snapshot bahan gagal, seluruh transaksi di-rollback (tidak meninggalkan batch yatim, parsial material, maupun perubahan status order palsu).
- **Independensi Terhadap Invoice**:
  - Keberadaan `Invoice` mengunci pengeditan data master Sales Order, namun **TIDAK MENGHALANGI** pembuatan batch produksi.
- **Pemisahan Peran Gudang & Produksi**:
  - Sistem menghitung kebutuhan bahan secara otomatis pada saat batch dibuat.
  - Role **Gudang** memonitor kebutuhan, membuat Purchase Order jika stok kurang, menerima barang, dan melakukan alokasi bahan secara fisik.
  - Role **Produksi** hanya dapat memulai pengerjaan batch setelah seluruh bahan baku dialokasikan penuh oleh Gudang.

---

## 13. Order Kanban Board Rules (Admin Role Step 3)

- **Sumber Kebenaran & Kolom Papan**:
  - Kanban Board beroperasi langsung pada kolom `orders.status` (tanpa tabel terpisah / duplicate state).
  - Papan menampilkan seluruh 9 tahapan resmi `OrderStatus` secara horizontal: `KOMUNIKASI`, `DEAL`, `BAHAN`, `PRINTING`, `PRODUKSI`, `QC`, `PACKING`, `DIKIRIM`, `SELESAI`.
- **Hak Mutasi Status & Drag/Drop**:
  - Drag and drop pada Kanban Admin hanya diizinkan untuk perpindahan dari kolom `KOMUNIKASI` ke kolom `DEAL`.
  - Perpindahan manual ke status operasional lainnya (`BAHAN`, `PRINTING`, dst.) **DITOLAK**.
  - Kolom selain `KOMUNIKASI` tidak bersifat draggable untuk transisi status.
- **Konkurensi & Keamanan Permintaan AJAX**:
  - Transisi status menggunakan endpoint `admin.orders.confirm-deal` yang memanggil `OrderStatusService::confirmDeal()` dengan database row lock (`lockForUpdate()`).
  - Request AJAX yang valid mengembalikan HTTP 200 JSON `{success: true, order_id, order_no, status: "DEAL", message: "..."}`.
  - Request yang kedaluwarsa/tidak valid (misal order sudah bukan `KOMUNIKASI`) ditolak dengan HTTP 422 JSON `{success: false, message: "..."}` dan tidak membuat entri `OrderStatusHistory` baru.
- **Aksesibilitas & Fallback Non-Drag**:
  - Setiap card berstatus `KOMUNIKASI` menyediakan tombol langsung *"Konfirmasi Deal"* sebagai alternatif drag and drop untuk aksesibilitas, navigasi keyboard, dan perangkat layar sentuh/mobile.
- **Integrasi Produksi**:
  - Card berstatus `DEAL` menampilkan indikator *"Menunggu Batch"* dan tautan *"Buat Batch"* jika `BatchService::canCreateForOrder($order)` bernilai true.
  - Card berstatus `BAHAN` menampilkan jumlah batch dan tautan *"Buat Batch Tambahan"* jika masih memiliki kuantitas tersisa yang belum dijadwalkan.
- **Privasi Data Finansial**:
  - Card Kanban Admin tidak menampilkan metrik HPP, Gross Profit, Net Profit, ROI, MER, maupun EBITDA milik Owner.

---

## 14. Integrated Order Detail Hub Rules (Admin Role Step 4)

- **Integrasi Menyeluruh Satu Halaman**:
  - Detail Order (`admin.orders.show`) mengintegrasikan 8 domain operasional:
    1. Header & Contextual Actions (`Edit`, `Konfirmasi Deal`, `Buat Batch`, `Buat Invoice`, `Lihat di Kanban`)
    2. Informasi Customer & Detail Order
    3. Rincian Item Pesanan & Snapshot Harga Transaksi
    4. Batch Produksi & Pemantauan Kapasitas Per Item
    5. Faktur Tagihan (Invoice) & Rincian Pembayaran (Payments)
    6. Biaya & Pengeluaran Terkait (Attributed Expenses)
    7. Pelacakan Publik (Customer Tracking)
    8. Riwayat & Kronologi Status (Timeline)
- **Atribusi Pengeluaran & Kalkulasi Desimal**:
  - Hanya menampilkan `business_expenses` yang memiliki `order_id` sesuai Order saat ini.
  - Pengeluaran umum (`order_id = NULL`) dan pengeluaran milik Order lain **DILARANG** muncul di halaman ini.
  - Total pengeluaran teratribusi dihitung secara desimal aman menggunakan BCMath (`DECIMAL(18,2)`).
- **Keamanan Token Pelacakan**:
  - `token_hash` tidak pernah ditampilkan dalam bentuk teks mentah dan tidak dapat direkonstruksi menjadi tautan asli.
  - Tautan asli sekali pakai (`session('raw_tracking_url')`) hanya ditampilkan sesaat setelah pembuatan token baru.
- **Isolasi Metrik Keuangan Pemilik**:
  - Halaman ini dilarang memuat model `order_financials` dan dilarang menampilkan metrik HPP, Margin Kotor, Laba Bersih, ROI, MER, atau EBITDA.

---

## 15. Admin Dashboard & Sidebar Navigation Rules (Admin Role Step 5)

- **Fokus Dashboard Operasional**:
  - Dashboard Admin (`admin.dashboard`) adalah pusat monitoring alur kerja operasional dan transaksi harian.
  - Menampilkan 6 KPI primer:
    1. `Total Order`: Total seluruh Sales Order dalam database.
    2. `Order Aktif`: Pesanan yang belum berstatus `SELESAI`.
    3. `Menunggu Deal`: Pesanan berstatus `KOMUNIKASI`.
    4. `Menunggu Batch`: Pesanan berstatus `DEAL` yang belum memiliki Production Batch (`whereDoesntHave('items.productionBatches')`).
    5. `Invoice Belum Lunas`: Faktur tagihan berstatus `UNPAID` atau `PARTIAL` (`whereIn('status', ['UNPAID', 'PARTIAL'])`).
    6. `Sisa Tagihan`: Total nilai piutang tagihan yang dihitung secara desimal aman menggunakan `InvoiceService` dan BCMath.
- **Ringkasan 9 Tahap Pipeline OrderStatus**:
  - Menampilkan ringkasan distribusi pesanan di seluruh 9 tahapan resmi `OrderStatus::kanbanColumns()`.
  - Dihitung menggunakan single grouped aggregate query untuk efisiensi database.
- **Navigasi Sidebar Tanpa Dead Links & Active-State Eksklusif**:
  - Navigasi Admin bersih dari link placeholder atau `href="#"`.
  - Active state bersifat eksklusif: `admin.orders.batches.*` hanya mengaktifkan menu *Batch Produksi* dan tidak mengaktifkan *Sales Order* secara bersamaan.
- **Privasi Data Finansial & Ketelitian Nilai Uang**:
  - Dashboard dan Sidebar Admin dilarang memuat metrik profitabilitas eksekutif (HPP, Gross Profit, Net Profit, ROI, MER, EBITDA).
  - Seluruh nilai nominal harga dan biaya wajib diformat menggunakan `MoneyFormatter::rupiah(...)` tanpa fungsi `number_format()` pada angka nominal uang.

---

## 16. Controlled Stock Opname & Inventory Adjustment Rules (Gudang Role Step G6A)

- **Hakikat & Batasan Stock Opname**:
  - Stock Opname adalah mekanisme **koreksi kuantitas fisik bahan baku** terkontrol berdasarkan hasil audit/penghitungan riil di gudang.
  - Stock Opname **HANYA** menghasilkan mutasi stok bertipe `MovementType::ADJUSTMENT`.
  - **DILARANG** menyediakan form mutasi manual bebas (`IN` atau `OUT` sembarangan). Mutasi `IN` hanya berasal dari penerimaan Purchase Order resmi (Step G4), dan `OUT` hanya berasal dari alokasi bahan ke Batch Produksi (Step G5).
- **Otoritas Input Klien vs Server**:
  - Otoritas pengguna/klien **dibatasi secara mutlak** pada dua field: `physical_qty` (kuantitas fisik hasil hitung) dan `notes` (catatan/alasan audit).
  - Seluruh field lainnya diderivasi secara otoritatif oleh server di bawah transaksi dan row-level lock:
    - `qty_before`: Kuantitas fisik terkunci saat transaksi diproses (`MaterialStock.qty_on_hand`).
    - `qty_after`: Kuantitas target hasil opname (`physical_qty`).
    - `qty`: Selisih absolut positif antarkuantitas ($|\text{qty\_after} - \text{qty\_before}|$). Arah pergerakan direfleksikan oleh perbandingan `qty_before` &rarr; `qty_after`.
    - `unit_cost_snapshot`: Snapshot harga pokok rata-rata saat penyesuaian (`MaterialStock.avg_unit_cost`).
    - `total_cost_snapshot`: Estimasi dampak nilai moneter ($\text{qty} \times \text{unit\_cost\_snapshot}$ berpresisi `DECIMAL(18,2)`).
    - `movement_date`: Tanggal kalender fisik saat transaksi dieksekusi (timezone `Asia/Jakarta`).
    - `created_by`: ID user role Gudang yang terotentikasi.
- **Invarian Harga Pokok Rata-Rata (`avg_unit_cost`)**:
  - Stock Opname **TIDAK MENGUBAH** nilai `MaterialStock.avg_unit_cost` (harga satuan persediaan tetap identik sebelum dan sesudah penyesuaian).
  - Stock Opname tidak meminta input harga beli baru dan tidak menghitung ulang Moving Weighted Average.
- **Kebijakan Persediaan Bernilai Nol (Zero-Cost Inventory Policy)**:
  - Kebijakan inventaris sistem mengizinkan `avg_unit_cost = 0.0000` (misal stok awal master atau bahan tanpa biaya) dan hanya menolak nilai negatif (`avg_unit_cost < 0`).
  - Stock Opname memperbaiki kuantitas fisik dan tidak melakukan revaluasi persediaan. Jika harga rata-rata material bernilai nol, nilai tersebut tetap dipertahankan (`0.0000`), dengan `unit_cost_snapshot = 0.0000` dan `total_cost_snapshot = 0.00`.
- **Integritas & Proteksi Data Korup**:
  - Jika `MaterialStock` tidak ditemukan, transaksi ditolak dengan `DomainException`.
  - Jika `qty_on_hand < 0` atau `avg_unit_cost < 0` terdeteksi di database, transaksi ditolak sebagai pelanggaran integritas inventaris (stok korup dilarang diperbaiki lewat opname reguler).
  - Validasi catatan: field `notes` wajib diisi, whitespace ditrim, dan dilarang hanya berisi spasi kosong (maks. 500 karakter).
- **Perilaku Tanpa Perubahan (No-Op Rule)**:
  - Apabila $\text{physical\_qty} == \text{current qty\_on\_hand}$, kondisi ini adalah operasi valid tanpa perubahan (*no-op*).
  - `StockOpnameService` mengembalikan `null`, tidak melakukan update pada `MaterialStock`, dan tidak membuat record `StockMovement`.
  - Controller menampilkan pesan informatif: *"Stok fisik sesuai dengan stok sistem. Tidak ada penyesuaian."*
- **Aritmatika Desimal Aman (BCMath)**:
  - Seluruh kalkulasi kuantitas dan selisih menggunakan `DECIMAL(18,4)` via `bccomp`, `bcsub`, dan `bcmul`. Dilarang menggunakan fungsi `abs()` atau `float`/`double` untuk aritmatika bisnis.
- **Preservasi MRP & Status Hilir**:
  - Stock Opname hanya memutasi kolom `material_stocks.qty_on_hand`.
  - Batch yang **belum dialokasikan** secara natural mengevaluasi ulang status kecukupan bahan (*live shortage*) berdasarkan stok baru.
  - Batch yang **sudah dialokasikan penuh** tetap berstatus **`TERALOKASI`**.
  - Snapshot historis MRP (`batch_materials.required_qty`, `stock_at_calc`, `shortage_qty`), status alokasi (`allocated_qty`, `used_qty`), serta status Production Batch, Sales Order, dan Purchase Order **TIDAK PERNAH DIUBAH** oleh Stock Opname.

---

## 17. Production Routing & Multi-Batch Order Aggregation Rules (Produksi Role Step P1)

- **Master Production Route (`product_variants.requires_printing`)**:
  - Menyimpan konfigurasi rute produksi tingkat master varian produk.
  - Nilainya bersifat **NULLABLE BOOLEAN** (`NULL` = belum ditetapkan, `TRUE` = dengan printing, `FALSE` = tanpa printing).
  - Admin wajib memilih secara eksplisit saat membuat atau mengedit varian produk.
- **Immutable Batch Route Snapshot (`production_batches.requires_printing`)**:
  - Menyimpan snapshot rute pengerjaan batch saat batch pertama kali dibuat (`BatchService::createBatch`).
  - Nilai disalin dari `ProductVariant.requires_printing`. Jika master bernilai `NULL`, pembuatan batch ditolak.
  - Snapshot bersifat **permanen dan immutable** (perubahan pada master Product Variant di masa depan tidak mengubah rute batch yang sudah berjalan).
- **Inisialisasi Legacy Batch**:
  - Batch legacy dengan snapshot `NULL` diinisialisasi satu kali saat aksi Mulai pertama kali dari master yang terkonfigurasi.
- **Transisi Operasional Bersyarat (Conditional BAHAN Transition)**:
  - Batch berstatus `BAHAN` hanya dapat dimulai jika **semua material telah dialokasikan penuh oleh Gudang** (`allocated_qty == required_qty > 0`).
  - Jika `requires_printing === true`: `BAHAN` $\rightarrow$ `PRINTING`.
  - Jika `requires_printing === false`: `BAHAN` $\rightarrow$ `PRODUKSI`.
  - Penentuan status selanjutnya sepenuhnya otoritatif di sisi server (klien tidak dapat meng-override).
- **Prinsip Agregasi Status Order Multi-Batch (Lowest Unfinished Operational Stage)**:
  - Status Sales Order mencerminkan tahapan operasional batch yang paling tertinggal / belum selesai:
    $$\text{Order Status} = \min \Big( \text{kandidat operasional seluruh batch non-CANCELLED \& sisa order belum dijadwalkan} \Big)$$
  - Pemetaan peringkat kandidat operasional:
    - Sisa kuantitas pesanan belum dibuatkan batch (`remaining > 0`) $\rightarrow$ `BAHAN` (Peringkat 1)
    - Batch `BAHAN` $\rightarrow$ `BAHAN` (Peringkat 1)
    - Batch `PRINTING` $\rightarrow$ `PRINTING` (Peringkat 2)
    - Batch `PRODUKSI` $\rightarrow$ `PRODUKSI` (Peringkat 3)
    - Batch `QC` $\rightarrow$ `QC` (Peringkat 4)
    - Batch `PACKING` $\rightarrow$ `PACKING` (Peringkat 5)
    - Batch `SELESAI` $\rightarrow$ `PACKING` (Peringkat 5)
  - **Batasan Tahapan Pengiriman (Shipment Boundary)**:
    - Agregasi produksi hanya beroperasi pada rentang status `BAHAN` s.d. `PACKING`.
    - Batch `SELESAI` **tidak** mengubah Order menjadi `SELESAI` (Order tetap `PACKING` sampai modul Pengiriman/Shipment memproses).
    - Status `DIKIRIM` dan `SELESAI` dimiliki secara eksklusif oleh alur kerja Pengiriman (Shipment) dan **dilarang ditarik mundur** oleh sinkronisasi batch.

---

## 18. Printing Stage Completion Rules (Produksi Role Step P2)

- **Alur Penyelesaian Printing (`PRINTING` $\rightarrow$ `PRODUKSI`)**:
  - Batch yang memiliki snapshot rute `requires_printing === true` dan berada dalam status `PRINTING` dapat menyelesaikan tahap printing dan bertransisi ke `PRODUKSI`.
  - Batch tanpa printing (`requires_printing === false`) **DILARANG** masuk atau menyelesaikan tahap Printing.
- **Otoritas Mutlak Snapshot Batch**:
  - Keputusan alur pengerjaan batch didasarkan secara eksklusif pada snapshot `production_batches.requires_printing`.
  - Perubahan nilai `requires_printing` pada master Product Variant setelah batch berjalan **TIDAK MENGUBAH** rute batch historis yang sedang aktif.
- **Integritas Persediaan Nol Efek Samping (Zero Inventory Mutation)**:
  - Penyelesaian tahap printing **TIDAK MENGUBAH** stok gudang (`material_stocks.qty_on_hand`), tidak membuat `StockMovement` baru, dan tidak mengubah `allocated_qty` maupun `used_qty`.
  - Persediaan fisik telah dikeluarkan oleh Gudang pada tahap Alokasi Bahan (Step G5) dan pencatatan konsumsi aktual (`used_qty`) dimiliki oleh tahap Produksi berikutnya.
- **Keamanan & Otoritas Server (Tamper Resistance)**:
  - Transaksi dijalankan di bawah database row-level lock (`lockForUpdate()`).
  - Endpoint P2 hanya memproses transisi `PRINTING` $\rightarrow$ `PRODUKSI`. Permintaan ganda (*double-submit*) atau manipulasi status dari klien ditolak secara aman tanpa memajukan batch ke tahap selanjutnya (`QC`/`PACKING`).
  - Agregasi status Sales Order otomatis disinkronkan melalui `OrderStatusService` sesuai prinsip *lowest unfinished operational stage*.

---

## 19. Production Execution & Actual Result Recording Rules (Produksi Role Step P3)

- **Alur Pencatatan Hasil Produksi (`PRODUKSI` $\rightarrow$ `QC`)**:
  - Pencatatan hasil produksi fisik (`ProductionResult`) hanya diizinkan saat Batch berada dalam status `PRODUKSI`.
  - Setelah hasil produksi aktual berhasil dicatat, status Batch secara otomatis bertransisi ke `QC`.
  - Agregasi status Sales Order otomatis disinkronkan melalui `OrderStatusService`.
- **Kardinalitas Tunggal (One Final Production Result per Batch)**:
  - Setiap batch produksi memiliki tepat satu record hasil produksi final (`ProductionResult`).
  - Pengiriman ulang (*double-submit*) atau pencatatan ganda ditolak di level service di bawah transaksi terkunci (`lockForUpdate()`).
  - Hasil produksi final yang telah dicatat tidak menyediakan fitur edit/delete bebas di UI karena kuantitas ini menjadi acuan tahap pemeriksaan Quality Control (QC).
- **Aturan Kuantitas Aktual (`actual_qty`) & Variansi**:
  - `actual_qty` wajib bernilai numerik positif $> 0$ dengan format presisi desimal `DECIMAL(18,4)` via BCMath.
  - Sistem **TIDAK MEMBATASI** `actual_qty` harus selalu $\le \text{target\_qty}$. Sistem secara sah mendukung hasil aktual di bawah target (*under target*), tepat sesuai target (*exact target*), maupun melebihi target (*over target*).
  - Selisih/variansi kuantitas dihitung secara terderivasi:
    $$\text{variance\_qty} = \text{actual\_qty} - \text{target\_qty}$$
- **Otoritas Server atas Kolom Sensitif**:
  - `batch_id` dan `recorded_by` sepenuhnya diderivasi secara otoritatif oleh server dari URL/batch terkunci dan ID user role `PRODUKSI` yang terotentikasi.
  - Payload manipulatif dari klien yang mencoba mengubah status batch, order status, atau user pencatat akan diabaikan.
- **Integritas Persediaan Nol Efek Samping (Zero Inventory Mutation)**:
  - Pencatatan hasil produksi mengukur output fisik barang jadi (pcs), bukan pemakaian material.
  - P3 **TIDAK MENGUBAH** `MaterialStock.qty_on_hand`, `MaterialStock.avg_unit_cost`, `batch_materials.allocated_qty`, maupun `batch_materials.used_qty` (`used_qty` tetap bernilai `0.0000`).
  - P3 **TIDAK MEMBUAT** `StockMovement` (tidak ada Stock OUT ganda).
- **Pemisahan Tanggung Jawab QC (No Automatic QC Result)**:
  - P3 hanya memindahkan batch ke tahap `QC` dan **TIDAK MEMBUAT** record `QcResult` (`good_qty`, `reject_qty`, `qc_date` dikelola secara eksklusif oleh Step P4).

---

## 20. Quality Control (QC) Result Rules (Produksi Role Step P4)

- **Alur Pemeriksaan QC (`QC` $\rightarrow$ `PACKING`)**:
  - Pemeriksaan QC (`QcResult`) hanya diizinkan saat Batch berada dalam status `QC` dan memiliki record `ProductionResult`.
  - Setelah hasil QC dicatat, status Batch secara otomatis bertransisi ke `PACKING`.
  - Agregasi status Sales Order otomatis disinkronkan melalui `OrderStatusService` (tetap berada di bawah batas tahapan pengiriman).
- **Persamaan Mutlak QC (Core QC Equation - Locked)**:
  - Jumlah produk Good dan Reject **WAJIB SAMA PERSIS** dengan kuantitas hasil produksi fisik (`ProductionResult.actual_qty`):
    $$\text{good\_qty} + \text{reject\_qty} = \text{production\_result.actual\_qty}$$
  - Divalidasi secara eksak menggunakan BCMath dengan presisi `DECIMAL(18,4)`.
  - Baik *under-classification* (`good + reject < actual`) maupun *over-classification* (`good + reject > actual`) akan ditolak keras oleh server.
- **Aturan Good & Reject & Penolakan REWORK**:
  - `good_qty >= 0` dan `reject_qty >= 0`. Nilai nol ($0$) adalah sah untuk salah satu kolom (*all-good* atau *all-reject*).
  - Kasus *all-reject* (`good = 0, reject = actual`) tetap memajukan batch ke `PACKING`.
  - Sistem **TIDAK MENGENAL** status atau kuantitas *rework*, *repair*, *pending*, atau *hold*. Klasifikasi output QC bersifat biner mutlak: **GOOD** atau **REJECT**.
- **Kardinalitas & Perlindungan Pengiriman Ganda (Concurrency & Immutability)**:
  - Tepat satu record `QcResult` per `ProductionResult` (dilindungi oleh constraint database `UNIQUE(production_result_id)` dan validasi transaksi di level service).
  - `ProductionResult` bersifat *immutable* (tidak diubah oleh pencatatan QC).
  - Hasil QC final yang telah dicatat tidak menyediakan fitur edit/delete bebas di UI.
- **Validasi & Penugasan Tanggal Pemeriksaan QC (`qc_date`) & Audit Timestamp (`qc_at`)**:
  - `qc_date` ditetapkan otomatis oleh sistem mengikuti tanggal produksi mulai dikerjakan (`ProductionBatch.getProductionStartDate()`), bukan tanggal submit form.
  - **Hard Gate Kelayakan**: QC tidak boleh dilakukan sebelum tanggal produksi mulai (`today in Asia/Jakarta >= production_start_date`). Percobaan simpan sebelum tanggal produksi melempar `DomainException: "QC belum dapat dilakukan sebelum tanggal produksi."`.
  - `qc_at` mencatat timestamp actual eksekusi submission (`now()` UTC, display WIB) untuk keperluan jejak audit (*audit trail*).
  - Target tanggal selesai (`planned_end_date`) adalah deadline dan **TIDAK DIGUNAKAN** sebagai `qc_date`.
- **Integritas Persediaan Nol Efek Samping (Zero Inventory Mutation)**:
  - Pencatatan QC tidak memutasi stok gudang (`material_stocks`), tidak membuat `StockMovement`, dan tidak mengubah `used_qty` (tetap `0.0000`).

---

## 21. Packing Completion & Batch Operational Completion (Produksi Role Step P5)

- **Alur Penyelesaian Packing (`PACKING` $\rightarrow$ `SELESAI`)**:
  - Penyelesaian Packing menandakan bahwa siklus hidup operasional pemotongan, penjahitan, QC, dan pengemasan fisik untuk Production Batch terkait telah selesai penuh.
  - Hanya diizinkan jika Batch berada dalam status `PACKING`, memiliki `ProductionResult`, dan memiliki `QcResult` yang valid.
- **Validasi Integritas Data QC**:
  - Sebelum menyelesaikan packing, sistem memvalidasi ulang integritas kalkulasi QC (`good_qty >= 0`, `reject_qty >= 0`, dan $\text{good\_qty} + \text{reject\_qty} = \text{ProductionResult.actual\_qty}$).
  - Jika data QC rusak atau tidak seimbang, transisi ditolak keras tanpa koreksi otomatis.
- **Kelayakan Batch All-Reject**:
  - Batch dengan kondisi *all-reject* (`good_qty = 0, reject_qty = actual_qty`) **TETAP DIIZINKAN** menyelesaikan tahap Packing menuju `SELESAI`. Status `SELESAI` pada batch menandakan berakhirnya siklus kerja produksi fisik batch tersebut, bukan pemenuhan pesanan penjualan pelanggan.
- **Batasan Tahapan Pengiriman (Shipment Boundary)**:
  - Batch `SELESAI` **TIDAK MENGUBAH** status Sales Order menjadi `SELESAI` atau `DIKIRIM`.
  - Agregasi status Sales Order untuk batch `SELESAI` memetakan kandidat status Order ke `PACKING`.
  - Alur Pengiriman (Modul Shipment) secara eksklusif memiliki hak dan wewenang untuk memindahkan pesanan ke `DIKIRIM` dan `SELESAI`.
- **Integritas Persediaan Nol Efek Samping (Zero Inventory Mutation)**:
  - Penyelesaian packing tidak memutasi stok gudang (`material_stocks`), tidak membuat `StockMovement`, dan tidak mengubah `used_qty`.

---

## 22. Actual Material Usage Integration & Atomic Production Result (Produksi Role Step P6A)

- **Penyimpanan Pemakaian Bahan Aktual (`batch_materials.used_qty`)**:
  - Kuantitas pemakaian bahan riil disimpan pada kolom `batch_materials.used_qty` dengan presisi `DECIMAL(18,4)`.
  - Dicatat secara atomik bersamaan dengan pencatatan hasil produksi fisik final (`ProductionResult`) di form Catat Hasil Produksi.
- **Marker Pencatatan Tingkat Batch (`material_usage_recorded_at` & `material_usage_recorded_by`)**:
  - Kolom `production_batches.material_usage_recorded_at` (timestamp) dan `production_batches.material_usage_recorded_by` (foreignId users) berfungsi sebagai penanda definitif bahwa pemakaian bahan telah dikonfirmasi oleh staf Produksi.
  - Nilai pemakaian aktual $0$ (`used_qty = 0`) adalah sah dan dapat dibedakan dari data yang belum dicatat (`material_usage_recorded_at IS NULL`).
- **Batasan Nilai Pemakaian (`used_qty` Validation)**:
  - Setiap bahan wajib diisi kuantitas pemakaian aktualnya: $0 \le \text{used\_qty} \le \text{allocated\_qty}$.
  - Pemakaian tidak boleh melebihi kuantitas yang telah dialokasikan oleh Gudang (`allocated_qty`) karena sistem tidak memiliki alur pengeluaran bahan susulan tak terkontrol.
- **Integritas Persediaan Nol Efek Samping (Zero Secondary Stock OUT)**:
  - Pengeluaran fisik stok bahan dari gudang telah terjadi secara penuh pada saat Gudang melakukan Alokasi Bahan (`StockMovement OUT`).
  - Pencatatan pemakaian aktual di Produksi **TIDAK MENGURANGI** stok gudang lagi (`MaterialStock.qty_on_hand` tidak berubah), tidak membuat `StockMovement OUT` ganda, dan tidak mengubah `avg_unit_cost`.
- **Perlakuan Selisih Bahan yang Tidak Terpakai (Unused Allocation)**:
  - Selisih $\text{allocated\_qty} - \text{used\_qty}$ dihitung secara turunan (*derived*) dan ditampilkan pada UI sebagai informasi.
  - Selisih bahan yang tidak terpakai **TIDAK OTOMATIS** dikembalikan ke stok gudang (tidak ada `StockMovement IN` otomatis).
- **Persiapan Perhitungan Biaya Produksi (HPP Preparation)**:
  - Nilai `batch_materials.used_qty` dipersiapkan untuk kalkulasi biaya bahan baku riil pada modul HPP (menggunakan harga modal snapshot saat alokasi).
  - Tampilan Produksi tidak mengekspos harga beli, harga modal rata-rata, maupun kalkulasi HPP finansial.
- **Perlakuan Data Historis (Legacy Records)**:
  - Data historis (sebelum P6A) yang memiliki `ProductionResult` namun `material_usage_recorded_at IS NULL` ditampilkan dengan status informasi *"Pemakaian bahan aktual belum tercatat pada data ini"*, dan tidak otomatis di-backfill sebagai $0$ terkonfirmasi.

---

## 23. Production Coverage & Replacement Capacity Rules (Produksi / Admin Revision)

- **Formula Kapasitas Penjadwalan Batch Baru (Production Coverage Formula)**:
  - Kapasitas sisa kuantitas yang dapat dijadwalkan (`remaining_to_plan`) dihitung secara dinamis dari cakupan produksi aktual:
    $$\text{confirmed\_good\_qty} = \sum \text{QcResult.good\_qty (untuk batch yang telah selesai QC)}$$
    $$\text{pending\_production\_qty} = \sum \text{ProductionBatch.target\_qty (untuk batch aktif yang belum ada final QcResult)}$$
    $$\text{production\_coverage} = \text{confirmed\_good\_qty} + \text{pending\_production\_qty}$$
    $$\text{remaining\_to\_plan} = \max(\text{qty\_ordered} - \text{production\_coverage}, 0)$$
- **Produksi Tambahan / Penggantian Unit Reject (No Rework)**:
  - Produk yang di-reject pada tahap QC tidak mengenal proses *rework* atau perbaikan pada batch yang sama.
  - Defisit pemenuhan akibat reject secara otomatis membuka kapasitas `remaining_to_plan > 0` untuk dibuatkan Batch Produksi pengganti (*replacement batch*) oleh Admin.
  - Setiap batch pengganti merupakan `ProductionBatch` standar yang melewati seluruh alur otomatisasi MRP, alokasi bahan di Gudang, produksi, QC, dan pengemasan.
- **Agregasi Status Pesanan saat Terjadi Defisit Reject**:
  - Jika seluruh batch yang ada telah mencapai `SELESAI` namun $\text{confirmed\_good\_qty} < \text{qty\_ordered}$ (`remaining_to_plan > 0`), status Sales Order secara otomatis kembali ke **`BAHAN`** dengan catatan riwayat: *"Hasil QC belum memenuhi qty pesanan; produksi tambahan diperlukan."*
  - Admin Kanban menampilkan pesanan ini pada kolom `BAHAN` agar penjadwalan batch pengganti segera dilakukan.
- **Surplus Kuantitas Good**:
  - Jika $\text{confirmed\_good\_qty} > \text{qty\_ordered}$, pesanan dianggap terpenuhi secara penuh ($\text{fulfilled} = \text{qty\_ordered}$).
  - Kelebihan unit Good tidak mengubah kuantitas pesanan pelanggan (`qty_ordered`), tidak menambah total transaksi/invoice, dan tidak membuat Sales Order baru.

---

## 24. Shipment & Delivery Operational Rules (Produksi Role Step P6C / PRO-08)

- **Kepemilikan Peran (Role Ownership - PRO-08)**:
  - Alur Pengiriman (*Shipment / Pengiriman*) dimiliki dan dikelola secara eksklusif oleh role **`PRODUKSI`** (staf Produksi / QC).
  - Admin berperan sebagai pemantau (*read-only observer* pada Kanban), Gudang tidak memiliki kontrol pengiriman, dan Pelanggan hanya dapat memantau status publik.
- **Syarat Kelayakan Pengiriman (Shipment Eligibility Gate - Strict Full Fulfillment & Commercial Settlement)**:
  - Pengiriman (*Shipment*) hanya dapat disiapkan untuk Sales Order yang memenuhi seluruh kriteria berikut:
    1. Status operasional Sales Order berada pada status **`PACKING`**.
    2. Seluruh batch produksi aktif yang terkait telah selesai penuh (**`SELESAI`**).
    3. Seluruh item pesanan (`OrderItem`) telah terpenuhi secara utuh oleh output QC Good: $\text{confirmed\_good\_qty} \ge \text{qty\_ordered}$.
    4. Tidak ada sisa kuantitas yang belum dijadwalkan (`remaining_to_plan == 0`) dan tidak ada batch yang sedang berjalan.
    5. Syarat Komersial: Invoice pesanan telah diterbitkan dan pembayaran telah **LUNAS** (seluruh tagihan tertutupi oleh pembayaran berstatus `CONFIRMED`, `outstanding_balance == 0`). Uang muka (DP) atau bayar sebagian **DILARANG** membuat atau mengirimkan shipment.
    6. Pesanan belum memiliki pengiriman aktif lain (`PENDING`, `PACKED`, atau `SHIPPED`).
  - Sistem **TIDAK MENDUKUNG** pengiriman sebagian (*short shipment* / parsial) maupun pengiriman sebelum pelunasan komersial. Pesanan dengan kekurangan unit Good atau invoice belum lunas dilarang untuk dikirim.
- **Siklus Hidup Status Pengiriman & Transisi Sales Order**:
  - **`PENDING` / `PACKED`**: Draf pengiriman telah disiapkan (memilih kurir, layanan, perkiraan biaya). Status Sales Order **TETAP `PACKING`**.
  - **`SHIPPED`**: Paket telah diserahkan ke kurir/ekspedisi (nomor resi dicatat). Status Sales Order secara otomatis bertransisi **`PACKING` $\rightarrow$ `DIKIRIM`** melalui `OrderStatusService`, mencatat tepat 1 entri `OrderStatusHistory`.
  - **`DELIVERED`**: Paket telah tiba dan diterima oleh pelanggan. Status Sales Order secara otomatis bertransisi **`DIKIRIM` $\rightarrow$ `SELESAI`** melalui `OrderStatusService`, mencatat tepat 1 entri `OrderStatusHistory`.
- **Penanganan Status `RETURNED`**:
  - Status `RETURNED` didukung pada tingkat enum dan model `ShipmentStatus` untuk fleksibilitas masa depan.
  - Sesuai batasan P6C, sistem **TIDAK MENYEDIAKAN** tombol aksi atau regresi status Order otomatis untuk `RETURNED` (perilaku regresi pesanan ditunda hingga keputusan bisnis formal ditetapkan).
- **Snapshot Alamat Tujuan Pengiriman**:
  - Nilai `shipments.recipient_address` dicatat sebagai snapshot saat pengiriman dibuat (menggunakan input form atau master alamat Customer saat itu).
  - Perubahan master alamat pelanggan di kemudian hari **TIDAK MENGUBAH** alamat historis pada shipment yang sudah berjalan/selesai.
- **Integritas Finansial & Persediaan Nol Efek Samping**:
  - Kolom `shipments.shipping_cost` berfungsi sebagai pencatatan operasional logistik Produksi.
  - Alur pengiriman **TIDAK MENGUBAH** persediaan gudang (`MaterialStock` / `StockMovement`), tidak mengubah tagihan (`Invoice`), pembayaran (`Payment`), pengeluaran operasional (`BusinessExpense`), maupun struktur HPP.
- **Pelacakan Pelanggan (Customer Public Tracking Privacy)**:
  - Halaman pelacakan publik hanya menampilkan tahapan umum: **`Dikemas` $\rightarrow$ `Dikirim` $\rightarrow$ `Selesai`**.
  - Rincian internal (catatan defek QC, biaya ongkir, HPP) dijaga kerahasiaannya dan tidak diekspos ke publik.
- **Perlindungan Konkurensi & Pengiriman Ganda (Idempotency)**:
  - Seluruh transisi status pengiriman dilindungi oleh transaksi database dan row-level locking (`lockForUpdate`).
  - Pengiriman ganda (*double-submit*) pada tombol Kirim atau Selesai ditangani secara idempoten tanpa membuat duplikasi riwayat status (*zero duplicate history*).

---

## 25. Public Order Tracking & Permanent Token Rules

- **Pembuatan Token Permanen**:
  - Tracking token dibuat secara otomatis satu kali saat Sales Order dibuat oleh Admin.
  - Token bersifat **permanen** (`expires_at = NULL`) dan **tidak pernah kedaluwarsa secara otomatis** (*never-expiring semantics*).
  - Tautan pelacakan tetap valid selama Sales Order terkait masih ada di sistem.
- **Kekebalan Mutasi (Token Immutability)**:
  - Token tracking bersifat **immutable** (stabil dan tidak boleh berubah).
  - Token tidak boleh digenerate ulang atau direvoke hanya karena:
    1. Perubahan/transisi status pesanan (`KOMUNIKASI`, `DEAL`, `BAHAN`, `PRINTING`, `PRODUKSI`, `QC`, `PACKING`, `DIKIRIM`, `SELESAI`).
    2. Pembuatan atau pembaruan Production Batch.
    3. Kalkulasi MRP atau alokasi bahan baku.
    4. Pencatatan hasil produksi atau hasil inspeksi QC.
    5. Penyelesaian packing produk.
    6. Penerbitan invoice, pembayaran sebagian (DP), atau pelunasan.
    7. Pembuatan atau pembaruan pengiriman (shipment).
    8. Pembaruan data order biasa (catatan, customer, atau item).
  - Method `TrackingTokenService::createForOrder` memproteksi token yang sudah aktif dan melempar `DomainException` jika dipanggil ulang untuk order yang telah memiliki token aktif.
- **Akses Tanpa Otentikasi (Public Access)**:
  - Akses halaman pelacakan publik (`/track/{token}`) tidak membutuhkan login akun pengguna.
  - Admin/Customer Service memberikan tautan tracking kepada pelanggan saat konfirmasi pesanan.
  - Form pencarian `/track` menerima token raw 64-karakter hex maupun tautan URL lengkap (`/track/{token}`).
- **Pengecekan Keabsahan & Respons Error**:
  - Token yang tidak ditemukan di basis data atau tidak valid mengembalikan HTTP 404 dengan pesan ramah: *"Link tracking tidak valid atau pesanan tidak ditemukan."*.
  - Sistem tidak menampilkan kata "kedaluwarsa" karena tracking NAYARA tidak menerapkan mekanisme kedaluwarsa otomatis.
  - Token hanya menjadi tidak valid jika Sales Order dihapus atau dilakukan regenerasi token secara eksplisit oleh Admin melalui tombol konfirmasi modal di Order Detail Hub.
- **Kerahasiaan Data ERP Internal (Zero Cost Leakage)**:
  - Halaman pelacakan publik **strictly tidak mengekspos**:
    - Harga Pokok Produksi (HPP)
    - Harga modal / harga beli bahan baku
    - Identitas pemasok (supplier)
    - Moving Weighted Average (MWA)
    - Biaya tenaga kerja internal atau tarif proses
    - Margin laba kotor, laba bersih, atau analitik profit
    - Biaya operasional (business expenses)
    - Catatan internal staf/Admin yang bersifat rahasia

---

## PURCHASE & STOCK REPLENISHMENT

### Prinsip Utama
- **Stok TIDAK bertambah saat Purchase Order dibuat (DRAFT).**
- **Stok TIDAK bertambah saat PO dikonfirmasi (ORDERED).**
- **Stok HANYA bertambah saat Receive Purchase (RECEIVED).**
- Pembelian ulang bahan baku **WAJIB** menggunakan Purchase Order baru.
- Setiap perubahan stok tercatat sebagai **StockMovement** dengan:
  - Timestamp (`created_at` — tanggal & waktu actual)
  - Qty sebelum & sesudah (`qty_before`, `qty_after`)
  - Referensi dokumen (`reference_type`, `reference_id`)
  - Actor/user (`created_by`)
- **Tombol generic "Tambah Stok" / "Update Stok" yang langsung mengubah `qty_on_hand` DILARANG.**

### Alur Pembelian Ulang Bahan
```
Create Material → stock = 0
Create PO       → stock TETAP
Confirm PO      → stock TETAP
Receive PO      → stock BERTAMBAH
                → Moving Weighted Average diperbarui
                → StockMovement IN otomatis dibuat
                → Purchase History otomatis tersedia
```

### Jenis Mutasi Stok (MovementType)
| Jenis | Trigger | Efek |
|-------|---------|------|
| **IN** | Receive Purchase | Stock bertambah |
| **OUT** | Production Allocation | Stock berkurang |
| **ADJUSTMENT** | Stock Opname | Koreksi stok fisik |

### Tanggal Traceability
| Event | Source of Truth |
|-------|----------------|
| Tanggal PO | `purchases.purchase_date` |
| Tanggal barang diterima | `stock_movements.created_at` (WHERE reference_type = 'purchase_items') |
| Tanggal stok berubah | `stock_movements.created_at` |
| MRP dihitung | `batch_materials.calculated_at` |
| Material dialokasikan | `stock_movements.created_at` (WHERE reference_type = 'batch_materials') |

### Stock Opname
- Hanya untuk **ADJUSTMENT** (koreksi perbedaan stok sistem vs fisik).
- **BUKAN** cara untuk menambah stok karena pembelian.
- `avg_unit_cost` **TIDAK berubah** saat stock opname.

### Moving Weighted Average (MWA)
- Dihitung ulang saat Receive Purchase dengan formula BCMath (bukan float).
- Tidak berubah saat alokasi (OUT) maupun stock opname (ADJUSTMENT).

---

## PRODUCTION DOWN PAYMENT GATE

### Prinsip Utama
Alur bisnis NAYARA Make-to-Order:
```
Customer memesan melalui WhatsApp
→ Admin membuat Sales Order
→ Admin konfirmasi DEAL
→ Admin membuat Invoice
→ Customer membayar DP
→ Admin mengonfirmasi pembayaran
→ BARU Admin boleh membuat Production Batch
→ Gudang MRP
→ Produksi
```

### Business Rules & Constraints
1. **DEAL Saja Tidak Cukup**:
   - Status DEAL merupakan komitmen komersial awal, namun **TIDAK CUKUP** untuk memulai produksi fisik / pembuatan batch.
   - Production Batch creation diblokir secara server-side dan antarmuka UI sebelum pembayaran awal / Down Payment (DP) diterima dan dikonfirmasi.
2. **Kriteria Validitas Down Payment (DP Gate)**:
   - Order memiliki invoice aktif (`invoices.order_id = orders.id` dan status bukan `CANCELLED`).
   - Order memiliki minimal satu record `payments` dengan status `CONFIRMED`.
   - Total pembayaran terkonfirmasi $\sum \text{Payment.amount} > 0$ dihitung menggunakan desimal presisi tinggi (`BCMath`, `bccomp > 0`).
   - Tidak ada batas persentase minimal DP (tidak mensyaratkan 30%, 50%, atau angka arbitrer lainnya).
   - Pembayaran berstatus `PENDING`, `CANCELLED`, atau `REJECTED` **TIDAK** dihitung sebagai DP valid.
3. **Partial vs Full Payment**:
   - Invoice `PARTIAL` dengan confirmed DP $> 0$: **Produksi DIIZINKAN**.
   - Invoice `PAID` (Lunas): **Produksi DIIZINKAN**.
   - Invoice `UNPAID` / belum ada invoice: **Produksi DITOLAK**.
4. **Pemisahan Tegas Antara Production Gate dan Shipment Gate**:
   - **Production DP Gate**: Membutuhkan confirmed DP $> 0$.
   - **Shipment Payment Gate**: Membutuhkan **PELUNASAN PENUH** (`InvoiceService::isOrderFullyPaid($order) === true` atau status `PAID`).
   - DP Partial $\rightarrow$ Produksi boleh jalan, namun barang jadi tidak boleh dikirim sampai invoice lunas terkonfirmasi.
5. **Entry Point & Server-Side Enforcement**:
   - Halaman Admin SO Detail: Tombol utama header "Buat Batch Produksi" dan tombol per-item card "+ Buat Batch" berstatus disabled jika syarat DP belum terpenuhi. Teks visual "Menunggu DP", "Menunggu Pembayaran DP", dan callout kuning pembayaran ditiadakan dari area Batch Produksi sehingga seksi ini murni berfokus pada informasi operasional (Dipesan, Terjadwal, Sisa, Batch, Belum Dijadwalkan).
   - **Prinsip Satu Action Satu Fungsi**: Tombol aksi `[ Kelola Pembayaran ]` dan seluruh informasi pembayaran dikonsolidasikan secara eksklusif pada seksi Tagihan / Invoice sebagai satu-satunya titik informasi dan pengelolaan pembayaran customer.
   - Endpoint GET `admin.orders.batches.create`: Jika DP belum confirmed, diarahkan kembali ke `admin.orders.show` dengan pesan error flash.
   - Endpoint POST `admin.orders.batches.store`: Divalidasi di layer service (`BatchService::createBatch`), melempar `DomainException('Batch produksi belum dapat dibuat karena pembayaran DP belum diterima.')` jika ada direct crafted request.
6. **Multi-batch & Historical Batches**:
   - DP Gate bekerja di level Sales Order. Setelah order memenuhi syarat DP, pembuatan batch berikutnya (multi-batch) tetap diizinkan selama sisa kuantitas (`remaining_order_qty`) masih mencukupi.
   - Batch historis yang dibuat sebelum DP gate diberlakukan tidak dihapus/di-rollback (database integrity & historical immutability terjaga).

---

## 7. PACKING, READY TO SHIP, & SHIPMENT CONSISTENCY

### Prinsip Utama
Sistem membedakan secara tegas antara fase domain pesanan (*domain/order phase*), status turunan operasional (*derived presentation state*), dan kejadian fisik penyerahan barang (*shipment dispatch event*):

1. **PACKING (Domain Order Phase)**:
   - Merepresentasikan fase pesanan ketika seluruh fabrikasi (potong, print, jahit, finishing) dan inspeksi QC telah selesai.
   - Status internal `orders.status` tetap `OrderStatus::PACKING` sejak batch memasuki packing hingga barang secara fisik diserahkan ke kurir (`OrderStatus::DIKIRIM`).

2. **Kondisi 5 Tahap Pengemasan & Pengiriman**:
   - **Kondisi A: PACKING BELUM SELESAI (`PACKING_IN_PROGRESS`)**:
     - Masih terdapat batch aktif yang belum `BatchStatus::SELESAI`, atau hasil QC unit `Good` belum memenuhi kuantitas pesanan.
     - Label Operasional: `Sedang Dikemas`.
     - Customer Facing (Tracking & Portal): `Pengemasan Produk` berstatus "Sedang dalam tahapan ini".
     - Izin Pengiriman: Tidak boleh / belum eligible.
   - **Kondisi B: PACKING SELESAI + BELUM LUNAS (`WAITING_PAYMENT`)**:
     - Seluruh batch aktif telah selesai (`BatchStatus::SELESAI`) dan Good Qty lengkap, namun invoice belum lunas (`!isFullyPaid()`).
     - Label Operasional (Kanban & Admin): `Menunggu Pelunasan`.
     - Customer Portal: `Pengemasan Produk` berstatus "Selesai", dengan pesan konteks: *"Pesanan selesai dikemas dan menunggu penyelesaian pembayaran."*
     - Public Tracking: `Pengemasan Produk` berstatus "Selesai", dengan pesan konteks netral: *"Pesanan telah selesai dikemas."* (tanpa mengekspos sisa tagihan/privasi keuangan).
     - Tahap `DIKIRIM`: Tetap upcoming (tidak aktif prematur).
     - Antrean Gudang/Produksi: Tombol pengiriman dikunci (*disabled*) dengan label "Menunggu Pelunasan".
   - **Kondisi C: PACKING SELESAI + SUDAH LUNAS (`READY_TO_SHIP`)**:
     - Seluruh batch aktif `SELESAI`, Good Qty lengkap, dan invoice lunas terkonfirmasi (`isFullyPaid() === true`).
     - Label Operasional (Kanban, Admin, Gudang): `Siap Dikirim`.
     - Customer Facing (Tracking & Portal): `Pengemasan Produk` berstatus "Selesai", dengan pesan konteks: *"Pesanan telah selesai dikemas dan siap dikirim."*
     - Tahap `DIKIRIM`: Tetap upcoming (belum aktif hingga kurir menerima paket).
     - Antrean Produksi/Logistik: Tombol "Siapkan Pengiriman" aktif (*enabled*).
   - **Kondisi D: PENGIRIMAN DICATAT / DIKIRIM (`SHIPPED`)**:
     - Ekspedisi dicatat dan aksi `markAsShipped()` dieksekusi.
     - Status internal `orders.status` bertransisi ke `OrderStatus::DIKIRIM`.
     - Customer Facing (Tracking & Portal): Tahap `Pesanan Dikirim` menjadi aktif ("Sedang dalam tahapan ini"), menampilkan rincian kurir, nomor resi, dan waktu pengiriman.
   - **Kondisi E: PENGIRIMAN DITERIMA / SELESAI (`COMPLETED`)**:
     - Paket telah diterima oleh pelanggan dan aksi `markAsDelivered()` dieksekusi.
     - Status shipment bertransisi ke `ShipmentStatus::DELIVERED` dan mencatat `delivered_at`.
     - Status internal `orders.status` bertransisi secara otoritatif ke `OrderStatus::SELESAI` via `OrderStatusService::transitionToSelesaiFromShipment()`.
     - Mencatat riwayat status `OrderStatusHistory` dengan timestamp penerimaan riil (`delivered_at`).
     - Customer Facing (Tracking & Portal): Tahap `Pesanan Dikirim` dan `Pesanan Selesai` keduanya berstatus "Selesai" (dot hijau `bg-emerald-600`), menampilkan pesan konfirmasi final: *"Pesanan telah selesai dan diterima oleh customer."* (bukan "Sedang dalam tahapan ini").

---

## 8. TIMEZONE & AUDIT TIMESTAMP RULES

### Prinsip Utama
1. **Business Timezone Canonical**:
   - Zona waktu bisnis operasional NAYARA adalah **Asia/Jakarta (WIB / UTC+7)**.
   - Semua tanggal dan jam audit yang dilihat oleh user internal (Gudang, Produksi, Admin, Owner) maupun eksternal (Customer Tracking & Portal) harus ditampilkan konsisten dalam zona waktu tersebut dengan penanda `WIB`.

2. **Storage vs Display Separation**:
   - **Storage (Penyimpanan)**: Mengikuti standar UTC Laravel/database tanpa mutasi massal pada baris historis.
   - **Display (Presentasi)**: Dikonversi secara terpusat ke `Asia/Jakarta` via helper `App\Support\DateTimeFormatter`.
   - **DILARANG** melakukan aritmatika manual `+7` jam (`addHours(7)` / `+ 7`) pada template Blade atau controller. Konversi zona waktu harus selalu menggunakan metode resmi Carbon `setTimezone('Asia/Jakarta')`.

3. **Penanganan Field DATE-Only vs Datetime (Timestamps)**:
   - **DATE-Only**: Field tanggal murni seperti `orders.order_date`, `purchases.purchase_date`, `invoices.invoice_date`, `expenses.expense_date`, `production_results.result_date`, dan `qc_results.qc_date` **TIDAK BOLEH** mengalami konversi zona waktu yang dapat menggeser tanggal kalender bisnis.
   - **Datetime / Audit Timestamps**: Field jejak audit seperti `batch_materials.calculated_at`, `batch_materials.allocated_at`, `stock_movements.created_at`, `purchases.created_at`, `shipments.shipped_at`, `shipments.delivered_at`, dan `order_status_histories.changed_at` dikonversi secara presisi ke zona waktu `Asia/Jakarta` dengan akhiran `WIB`.

---

## 9. FINAL ORDER COMPLETION & TERMINAL STATE RULES

### Prinsip Bisnis Otoritatif
1. **Shipment DITERIMA Menyelesaikan Sales Order**:
   - Event fisik diterimanya barang oleh customer (`ShipmentStatus::DELIVERED`) adalah kejadian otoritatif yang menutup daur hidup Sales Order (`OrderStatus::SELESAI`).
   - Tidak diperkenankan menyelesaikan Sales Order secara manual tanpa adanya riwayat delivery event yang valid dari modul Shipment.
   - Urutan transisi: `PACKING` $\to$ `READY_TO_SHIP` (derived) $\to$ `DIKIRIM` (dispatched) $\to$ `DITERIMA` (delivered) $\to$ `ORDER SELESAI`.

2. **SELESAI adalah Terminal State**:
   - `OrderStatus::SELESAI` adalah status akhir definitif. Tidak ada lagi tahapan proses setelah `SELESAI`.
   - Pada Public Tracking (`/track/{token}`) dan Customer Portal (`/customer/orders/{id}`):
     - Seluruh sembilan tahapan (dari Pesanan Diterima hingga Pesanan Selesai) berstatus "Selesai" dengan dot hijau (`bg-emerald-600`).
     - Tahap akhir "Pesanan Selesai" menampilkan label `Selesai` dan banner status: *"Pesanan telah selesai dan diterima oleh customer."*
     - **DILARANG KERAS** menampilkan teks *"Sedang dalam tahapan ini"* atau ring aktif ungu pada tahapan `Pesanan Selesai`.

3. **Idempotensi & Transisi Backend**:
   - Pemanggilan `ShipmentService::markAsDelivered()` dilindungi oleh transaksi database dan row-level locking (`lockForUpdate()`).
   - Jika shipment sudah berstatus `DELIVERED`, request berulang bersifat idempoten: tidak membuat duplikat riwayat status, tidak mengubah nilai `delivered_at`, dan tidak memicu efek samping tambahan.
   - **Self-Healing Capability**: Jika terjadi anomali di mana shipment telah `DELIVERED` namun status Order tertinggal di `DIKIRIM`, pemanggilan `markAsDelivered()` memulihkan status order ke `SELESAI` menggunakan timestamp `delivered_at` aktual secara idempoten.

4. **Kanban & Detail Konsistensi**:
   - Pesanan yang telah `SELESAI` ditempatkan pada kolom `SELESAI` di Admin Kanban Board.
   - Pada halaman detail pengiriman Produksi (`produksi.shipments.show`), status pengiriman menampilkan `Terkirim / Pengiriman Selesai` dan tombol aksi *"Tandai Diterima"* disembunyikan.
   - Permanent tracking token tetap aktif dan tidak berubah; pelanggan tetap dapat mengakses histori pelacakan dengan tautan awal.

---

## 10. REVISI PEMBIMBING — TAHAP 3: HPP / MODAL & HARGA JUAL

### 10.1 Prinsip Fundamental & Arahan Pembimbing
1. **HPP adalah MODAL / BIAYA (COST), Bukan Harga Jual**:
   - HPP (*Harga Pokok Penjualan*) merepresentasikan seluruh pengorbanan sumber daya ekonomi (bahan baku riil dan biaya operasional langsung) yang dikeluarkan untuk menghasilkan unit produk jadi siap jual.
   - HPP dihitung secara sistematis oleh sistem dari transaksi operasional riil, bukan diasumsikan atau diinput manual sebagai harga jual.
2. **Sumber Biaya Bahan Baku & Pengakuan Persediaan**:
   - Rincian harga beli diinputkan saat pembuatan Purchase Order (`PurchaseItem.unit_price`).
   - Namun, nilai modal persediaan **BARU DIAKUI DAN VALID** saat bahan diterima secara fisik oleh Gudang (`PurchaseReceivingService::receivePurchase`), di mana sistem menghitung ulang *Moving Weighted Average* (MWA) persediaan (`MaterialStock.avg_unit_cost`).
   - Purchase Order yang belum diterima (*draft* / *ordered*) **DILARANG KERAS** dihitung sebagai biaya persediaan berjalan atau HPP aktual.
3. **Persediaan Bahan Baku (Raw Material) Bukan Produk Ritel**:
   - Bahan baku adalah inventaris manufaktur, bukan komoditas ritel yang dijual ke pelanggan umum.
   - Pada data Material **TIDAK ADA HARGA JUAL**. Yang ditampilkan kepada Admin dan Gudang adalah:
     - Kuantitas Stok Fisik (`MaterialStock.qty_on_hand`)
     - Modal Rata-rata / Average Unit Cost (`MaterialStock.avg_unit_cost`)
     - Harga Beli Terakhir (`PurchaseItem.unit_price` dari penerimaan PO terakhir)
     - Total Nilai Persediaan Fisik (`qty_on_hand * avg_unit_cost`)

### 10.2 Integrasi Rekonsiliasi Bahan Tahap 2 terhadap HPP Batch
Sistem menjaga akurasi biaya bahan baku yang dibebankan ke batch produksi dengan memperhitungkan seluruh alur Rekonsiliasi Bahan Tahap 2:
1. **Alokasi Awal (Initial Allocation)**:
   - Dikeluarkan dari gudang saat alokasi batch (`reference_type = 'batch_materials'`), mencatat snapshot biaya `unit_cost_snapshot`.
2. **Alokasi Tambahan (Additional Allocation)**:
   - Jika proses produksi membutuhkan bahan tambahan, dikeluarkan via alokasi tambahan (`reference_type = 'production_batches'`), mencatat snapshot harga modal pada saat mutasi tambahan tersebut terjadi. Snapshot ini bersifat independen dan tidak dipaksa menggunakan harga alokasi awal.
3. **Pengembalian Sisa Bahan (Unused Material Return)**:
   - Sisa bahan alokasi yang tidak terpakai ($\text{return\_qty} = \text{allocated\_qty} - \text{used\_qty}$) dikembalikan ke gudang secara fisik (`StockMovement IN`).
   - Sisa bahan yang dikembalikan **TIDAK DIHITUNG SEBAGAI BIAYA BATCH**. Biaya bahan pada HPP batch murni dihitung dari pemakaian aktual (`used_qty`).
   - Valuasi pemakaian aktual dilakukan secara berurutan terhadap lapisan alokasi pengeluaran (*chronological issue layers FIFO*).
   - Pengembalian bahan sisa **TIDAK MUNCUL PADA RIWAYAT PEMBELIAN GUDANG** (`gudang.purchases.history`), melainkan hanya pada kartu stok mutasi persediaan internal.
4. **Larangan Double Cost & Double Stock OUT**:
   - Pencatatan pemakaian bahan aktual (`used_qty`) **TIDAK MEMBUAT STOCK MOVEMENT OUT KEDUA**. Stok gudang telah dipotong saat alokasi.

### 10.3 Formulasi HPP Batch Produksi & Finalitas Biaya
1. **Formula Komprehensif Biaya Batch Produksi**:
   $$\text{Total HPP Batch} = \text{Biaya Bahan Aktual} + \text{Biaya Printing} + \text{Biaya Tenaga Kerja Langsung} + \text{Biaya Proses Kemasan} + \text{Biaya Langsung Lainnya}$$
   - **Biaya Bahan Aktual**: Dihitung dari `used_qty` terhadap snapshot biaya pengeluaran bahan.
   - **Biaya Printing**: $\text{actual\_qty} \times \text{printing\_cost\_per\_unit\_snapshot}$ (jika varian memerlukan printing).
   - **Biaya Tenaga Kerja Langsung (Labor)**: $\text{actual\_qty} \times \text{direct\_labor\_cost\_per\_unit\_snapshot}$.
   - **Biaya Proses Kemasan (Packaging Process)**: $\text{good\_qty} \times \text{packaging\_process_cost_per_good_unit_snapshot}$.
   - **Biaya Langsung Lainnya (Other Direct Cost)**: $\text{actual\_qty} \times \text{other\_direct_cost_per_unit_snapshot}$.
2. **HPP per Good Unit**:
   $$\text{HPP per Good Unit} = \frac{\text{Total HPP Batch}}{\text{Good Qty (Lolos QC)}}$$
   - **Good Qty** adalah kuantitas produk jadi yang lolos inspeksi QC (`QcResult.good_qty`).
   - Produk reject bukan merupakan output yang dapat dijual (*unsaleable*), sehingga seluruh beban produksi batch dialokasikan ke unit yang memenuhi standar kualitas (Good unit).
   - **DILARANG** membagi HPP dengan Target Qty atau Actual Qty (sebelum QC).
3. **Finalitas HPP (Pencegahan False Zero / Rp0)**:
   - Sebelum seluruh tahapan operasional batch selesai dan QC terekam, sistem **DILARANG** menampilkan HPP = Rp0.
   - Status yang ditampilkan secara tegas adalah **"HPP Belum Final"**.
   - HPP Aktual menjadi final hanya jika:
     1. Status batch adalah `SELESAI`.
     2. Pemakaian bahan aktual telah terekam (`material_usage_recorded_at` terisi).
     3. Hasil produksi aktual (`ProductionResult`) dan QC (`QcResult`) telah tercatat secara valid.
     4. Snapshot tarif biaya pada batch lengkap.

### 10.4 Pemisahan Biaya Estimasi vs HPP Aktual pada Master Varian
Pada tampilan Admin Produk dan Varian (`admin.products.show`, `admin.variants.show`):
1. **Harga Jual Master (`base_selling_price`)**:
   - Harga patokan standar yang ditentukan oleh Admin/manajemen.
2. **Estimasi Biaya Produksi (BOM + Standard Rates)**:
   - Dihitung dari formula BOM (menggunakan MWA gudang terkini) ditambah tarif standar biaya langsung varian (printing, labor, packaging, other).
   - Berfungsi sebagai proyeksi acuan sebelum batch produksi berjalan.
3. **HPP Aktual Terakhir (Historical Batch Realization)**:
   - Diambil dari batch produksi yang telah selesai dan memiliki HPP final paling mutakhir untuk varian bersangkutan.
   - Jika belum ada batch yang selesai, ditampilkan label informatif: **"Belum tersedia"**.
4. **HPP Referensi (Reference Cost)**:
   - Menggunakan HPP Aktual Terakhir jika tersedia, atau jatuh kembali (*fallback*) ke Estimasi Biaya Produksi.

### 10.5 Aturan Penentuan Harga Jual & Perlindungan Margin
1. **Harga Jual adalah Keputusan Bisnis / Admin**:
   - Sistem **TIDAK MENENTUKAN HARGA JUAL SECARA OTOMATIS**.
   - Sistem **TIDAK MENGGUNAKAN PERSENTASE MARKUP OTOMATIS** (tidak menciptakan persentase buatan 10%, 20%, dst.).
   - Harga jual pada Sales Order (`order_items.unit_selling_price`) merupakan **TRANSACTION SNAPSHOT** yang disepakati melalui negosiasi WhatsApp / pemesanan customer, dan bersifat *immutable* terhadap perubahan harga master di masa depan.
2. **Aturan Harga Jual vs HPP / Modal ("Harga Jual Lebih Mahal dari HPP")**:
   - Sistem menerapkan mekanisme **WARNING / VISUAL NOTIFICATION GUARD**, BUKAN *hard block* destruktif:
     - Jika $\text{Harga Jual} > \text{HPP / Modal Referensi}$: Ditampilkan indikator margin positif berwarna hijau ($+\text{Rp X.XXX}$).
     - Jika $\text{Harga Jual} \le \text{HPP / Modal Referensi}$: Ditampilkan peringatan visual berwarna amber/merah:
       $$\text{"⚠️ Harga jual tidak lebih tinggi dari HPP/modal (potensi margin tipis/rugi)."}$$
   - **Rasional Implementasi Warning (Bukan Hard Block)**:
     - Bisnis riil pakaian/hijab sering kali melibatkan promosi clearance, diskon grosir skala besar, atau negosiasi strategis tertentu yang sah secara keputusan manajemen. Sistem tidak boleh memblokir transaksi secara kaku tanpa instruksi eksplisit, namun wajib memberikan peringatan transparan agar Admin sadar terhadap dampak margin.

### 10.6 Ringkasan Keuangan Pesanan Selesai (Revenue, HPP, Gross Profit)
1. Pada detail Sales Order Admin (`admin.orders.show`), Admin dan Owner dapat melihat kartu **HPP / Modal & Profitabilitas Pesanan**:
   - **Total Pendapatan (Revenue)**: $\sum (\text{qty} \times \text{unit\_selling\_price})$.
   - **HPP / Total Modal**: Diambil dari `OrderProfitabilityService`, menjumlahkan biaya batch produksi riil terkait pesanan setelah seluruh batch berstatus final. Jika belum selesai, ditampilkan status *"HPP Belum Final"*.
   - **Laba Kotor (Gross Profit)**:
     $$\text{Laba Kotor} = \text{Revenue} - \text{HPP}$$
   - **Gross Margin (%)**: $\frac{\text{Gross Profit}}{\text{Revenue}} \times 100\%$.
2. Perhitungan keuangan wajib menggunakan *canonical service* (`OrderProfitabilityService` / `OrderHppCalculationService`), **DILARANG** dihitung secara independen di dalam file Blade template.

### 10.7 Matriks Hak Akses & Privasi Finansial
| Peran (Role) | HPP / Modal Varian | Biaya Beli Bahan Gudang | HPP & Margin Order | Laba Eksekutif (Net Profit/EBITDA) |
| :--- | :---: | :---: | :---: | :---: |
| **Admin** | ✅ Boleh | ❌ Terbatas | ✅ Boleh (HPP & Laba Kotor) | ❌ Dibatasi (Owner Only) |
| **Owner** | ✅ Boleh | ✅ Boleh | ✅ Boleh (Lengkap) | ✅ Boleh (EBITDA, ROI, MER) |
| **Gudang** | ❌ Tidak | ✅ Boleh (PO & MWA) | ❌ Tidak | ❌ Tidak |
| **Produksi** | ❌ Tidak | ❌ Tidak | ❌ Tidak | ❌ Tidak |
| **Customer** | ❌ **DILARANG** | ❌ **DILARANG** | ❌ **DILARANG** | ❌ **DILARANG** |
| **Public Tracking** | ❌ **DILARANG** | ❌ **DILARANG** | ❌ **DILARANG** | ❌ **DILARANG** |

---

## 11. Catatan Keuangan Admin: Uang Masuk, Uang Keluar, dan Beban Usaha (Revisi Pembimbing — TAHAP 4)

### 11.1 Definisi & Filosofi Sistem
1. **Bukan Aplikasi Akuntansi Penuh (Non-Full Accounting System)**:
   - NAYARA adalah sistem ERP operasional konveksi hijab, **BUKAN** aplikasi akuntansi penuh (*full accounting system*).
   - Sistem **TIDAK MENGGUNAKAN** jurnal umum, entri debit/kredit double-entry, buku besar (*general ledger*), neraca (*balance sheet*), atau akun COA (*Chart of Accounts*) kompleks.
2. **Catatan Keuangan Operasional (Operational Cashflow Ledger)**:
   - Catatan Keuangan Admin (`admin.financial-records.index`) merupakan *consolidated ledger view* turunan (*derived*) dari transaksi otoritatif yang sudah ada.
   - Menjawab pertanyaan bisnis praktis Admin harian:
     - Hari ini uang masuk berapa? Berasal dari pembayaran/order mana?
     - Hari ini uang keluar berapa? Digunakan untuk beban apa?
     - Berapa saldo bersih arus kas operasional pada periode tertentu?

### 11.2 Uang Masuk (Incoming Cashflow)
1. **Sumber Otoritatif**:
   - Hanya pembayaran customer yang telah berstatus **`PaymentStatus::CONFIRMED`** yang dihitung dan ditampilkan sebagai **UANG MASUK**.
2. **Pengecualian Mutlak**:
   - Pembayaran berstatus `PENDING` **TIDAK BOLEH** dihitung atau ditampilkan sebagai uang masuk.
   - Pembayaran berstatus `CANCELLED` atau `REJECTED` **TIDAK BOLEH** dihitung atau ditampilkan.
3. **Pembayaran Bertahap (DP & Pelunasan)**:
   - Pembayaran DP yang telah dikonfirmasi tercatat sebagai 1 transaksi Uang Masuk.
   - Pembayaran pelunasan yang dikonfirmasi di kemudian hari tercatat sebagai 1 transaksi Uang Masuk baru yang terpisah.
   - Sistem **DILARANG** menggabungkan transaksi historis menjadi satu entri buatan (*fake entry*).

### 11.3 Uang Keluar: Beban Usaha (Outgoing Cashflow)
1. **Sumber Otoritatif**:
   - Seluruh pengeluaran beban usaha riil (`BusinessExpense`) dicatat sebagai **UANG KELUAR**.
   - Kategori beban mengikuti enum resmi yang didukung sistem:
     - `OPERATING` (Beban Operasional harian, listrik, internet, sewa, ATK)
     - `MARKETING` (Beban Pemasaran & iklan digital)
     - `INTEREST` (Beban Bunga)
     - `TAX` (Beban Pajak)
     - `DEPRECIATION` (Penyusutan)
     - `AMORTIZATION` (Amortisasi)
2. **Validasi Server-Side Beban Usaha**:
   - Nominal pengeluaran wajib $> 0$ (`amount > 0`). Nominal Rp0 atau negatif ditolak secara server-side.
   - Tanggal pengeluaran (`expense_date`) wajib diisi dan diperlakukan sebagai *DATE-only* tanpa pergeseran zona waktu.
   - Kolom `created_by` diambil otomatis dari Admin yang terautentikasi (`$request->user()->id`), tidak mempercayai input form sembarangan.
3. **Jejak Audit & Larangan Hapus Fisik**:
   - Mengikuti prinsip audit finansial NAYARA, beban usaha **TIDAK MEMILIKI FITUR DESTROY/DELETE FISIK** (`except(['destroy'])`).
   - Koreksi dilakukan melalui modul Edit (`admin.expenses.edit`) dan perubahan langsung merefleksikan nilai mutakhir pada Catatan Keuangan secara otomatis (*derived view*).

### 11.4 Perlakuan Pengadaan Bahan Baku & Pembayaran Supplier (Revisi Pembimbing Tahap 4B)
1. **Pemisahan Pengadaan dan Kas (PO vs Cash Outflow)**:
   - Pembuatan Purchase Order (`Purchase`) **TIDAK MENGURANGI KAS SECARA PREMATUR**.
   - Penerimaan barang di gudang (`receivePurchase`) **TIDAK MENGURANGI KAS**. Penerimaan hanya memutasi stok fisik (`MaterialStock.qty_on_hand`), mencatat `StockMovement IN`, dan memperbarui harga rata-rata bahan (MWA).
   - Pengurangan kas pada pengadaan bahan baku terjadi **HANYA DAN EKSKLUSIF** saat pencatatan Pembayaran Supplier (`PurchasePayment`) dilakukan secara otoritatif oleh Admin.
2. **Entitas Pembayaran Supplier (`PurchasePayment`)**:
   - Struktur data: `purchase_id`, `amount`, `payment_date`, `payment_method`, `reference_no`, `notes`, `created_by`.
   - Mendukung pembayaran bertahap/parsial (*multiple partial payments per PO*).
   - Jejak audit transparan: Setiap pembayaran tercatat sebagai 1 baris terpisah dan menjumlahkan `paid_amount` serta mengurangi `outstanding_amount` pada PO.
3. **Guard dan Validasi Pembayaran Supplier**:
   - Nominal pembayaran wajib $> 0$ (`amount > 0`).
   - Nominal pembayaran tidak boleh melebihi sisa tagihan PO (`amount <= outstanding_amount`). Kelebihan bayar (*overpayment*) ditolak secara server-side.
   - PO yang dibatalkan (`CANCELLED`) dilarang keras untuk dicatat pembayarannya.
   - Pembayaran supplier dilakukan oleh Admin terautentikasi.
4. **Integritas Persediaan Nol Efek Samping (Zero Stock/MWA Mutation)**:
   - Pencatatan `PurchasePayment` murni merupakan kejadian finansial kas keluar.
   - `PurchasePayment` **TIDAK MENGUBAH** stok fisik bahan di gudang, tidak memicu penerimaan PO, dan tidak mengubah MWA persediaan.
5. **Pencegahan Double-Counting**:
   - Pencatatan `PurchasePayment` otomatis muncul di Catatan Keuangan sebagai kategori `Pembayaran Supplier` (Uang Keluar).
   - Sistem **DILARANG** membuat entri `BusinessExpense` untuk pembayaran PO yang sama. HPP dan arus kas keluar pengadaan tetap terpisah.

### 11.5 Saldo Awal Kas & Formula Kas Tersedia (Authoritative Financial Cut-Off)
1. **Entitas Saldo Awal Kas (`OpeningCashBalance`)**:
   - Menetapkan likuiditas operasional awal perusahaan pada suatu **Cut-Off Date** resmi (`opening_balance`, `opening_date`, `notes`, `created_by`).
   - Saldo Awal Kas merepresentasikan posisi kas nyata pada **AWAL HARI** `opening_date`.
   - Artinya, seluruh transaksi keuangan yang terjadi sebelum `opening_date` telah terangkum dan terefleksi di dalam saldo awal tersebut.
   - Saldo awal kas **BUKAN PENJUALAN**, bukan pendapatan komersial, dan bukan keuntungan usaha.
   - Hanya Admin yang memiliki hak akses untuk mengatur saldo awal kas.
2. **Formula Kas Tersedia Sekarang (Current Cash Position)**:
   - Jika Saldo Awal Kas **belum ditentukan**:
     - Kas Tersedia adalah **UNKNOWN / BELUM TERSEDIA** (`NULL`).
     - Sistem **DILARANG** menampilkan angka palsu `Rp0`.
     - Pembuatan pembayaran supplier diblokir sampai saldo awal ditetapkan.
   - Jika Saldo Awal Kas **telah ditentukan**:
     $$\text{Kas Tersedia} = \text{Saldo Awal Kas} + \sum \text{Customer Payments}_{\ge \text{opening\_date}} - \sum \text{Supplier Payments}_{\ge \text{opening\_date}} - \sum \text{Expenses}_{\ge \text{opening\_date}}$$
     Dihitung dengan presisi tinggi menggunakan `BCMath` skala 2.
     Seluruh transaksi dengan tanggal $< \text{opening\_date}$ adalah transaksi **historis** dan **DILARANG** dihitung ulang ke dalam Kas Tersedia.

### 11.6 Analisis Kecukupan Kas & Hard Cash Guard Pembayaran Supplier
1. **Kalkulasi Kewajiban PO**:
   - Untuk setiap PO aktif (non-cancelled), sistem menghitung:
     $$\text{Total PO} = \sum (\text{qty} \times \text{unit\_price})$$
     $$\text{Sudah Dibayar} = \sum \text{PurchasePayment.amount}$$
     $$\text{PO Belum Dibayar (Outstanding)} = \text{Total PO} - \text{Sudah Dibayar}$$
2. **Status Kecukupan Kas**:
   - Jika Saldo Awal Kas belum ditentukan:
     - Status: **`BELUM DAPAT DIHITUNG`** (bukan `KAS TIDAK CUKUP`).
   - Jika Saldo Awal Kas telah ditentukan:
     - Jika $\text{Kas Tersedia} \ge \text{PO Belum Dibayar}$:
       - Status: **`CUKUP`**
       - Proyeksi Kas Setelah Pelunasan: $\text{Kas Tersedia} - \text{PO Belum Dibayar}$
     - Jika $\text{Kas Tersedia} < \text{PO Belum Dibayar}$:
       - Status: **`TIDAK_CUKUP`**
       - Kekurangan Kas: $\text{PO Belum Dibayar} - \text{Kas Tersedia}$
3. **Hard Cash Guard Pembayaran Supplier (Server-Side Enforced)**:
   - Pembayaran supplier operasional ($\text{payment\_date} \ge \text{opening\_date}$) **MUTLAK TIDAK BOLEH MELEBIHI KAS TERSEDIA**.
   - Setiap pembayaran supplier wajib memenuhi:
     $$\text{amount} > 0$$
     $$\text{amount} \le \text{PO Outstanding}$$
     $$\text{amount} \le \text{Kas Tersedia}$$
   - Maksimal pembayaran yang diizinkan: $\min(\text{PO Outstanding}, \text{Kas Tersedia})$.
   - Kas operasional **TIDAK BOLEH MENJADI NEGATIF**.
   - Jika $\text{amount} > \text{Kas Tersedia}$, sistem menolak request di server-side dengan pesan:
     *"Kas tidak mencukupi untuk pembayaran supplier. Kas tersedia: Rp {kas_tersedia}."*
4. **Proteksi Konkurensi & Row-Level Locking**:
   - Seluruh eksekusi pembayaran supplier dibungkus dalam transaksi database atomik:
     1. Lock baris `Purchase` (`lockForUpdate()`).
     2. Verifikasi status PO tidak `CANCELLED`.
     3. Hitung ulang sisa tagihan PO di bawah lock.
     4. Lock baris `OpeningCashBalance` terbaru (`lockForUpdate()`).
     5. Hitung ulang `Kas Tersedia` riil di bawah lock.
     6. Verifikasi guards dan simpan record `PurchasePayment`.

### 11.7 Pembayaran Supplier Historis (Historical Purchase Reconciliation)
1. **Dukungan Rekonsiliasi PO Lama**:
   - Sistem **DILARANG** berasumsi otomatis bahwa PO lama yang berstatus `RECEIVED` pasti sudah lunas secara kas (`RECEIVED != PAID`).
   - Admin dapat mencatat pembayaran historis dengan tanggal pembayaran aktual riil sebelum tanggal saldo awal ($\text{payment\_date} < \text{opening\_date}$).
2. **Karakteristik Pembayaran Historis**:
   - Mengurangi sisa tagihan PO (`outstanding_amount`) dan memperbarui status pelunasan PO secara akurat.
   - Muncul dalam riwayat pembayaran supplier di detail PO dan catatan keuangan umum.
   - **TIDAK MENGURANGI Kas Tersedia Sekarang** (karena efek kasnya telah terangkum dalam Saldo Awal Kas pada cut-off date).
   - **TIDAK DIKENAKAN Hard Cash Guard** (pembayaran telah terjadi di masa lalu sebelum cut-off saldo awal).
   - Tidak memengaruhi stok fisik material dan tidak memengaruhi harga modal rata-rata (MWA).

### 11.8 Catatan Penjualan & Piutang Customer (Customer Receivables)
1. **Penjualan vs Kas Masuk**:
   - **Total Penjualan** mengukur nilai komersial bruto dari Sales Order yang valid (status `DEAL`, `BAHAN`, `PRODUKSI`, `QC`, `PACKING`, `DIKIRIM`, `SELESAI`). Dihitung dari $\sum (\text{qty\_ordered} \times \text{unit\_selling\_price})$.
   - Order dengan status `KOMUNIKASI` (inquiry/penawaran belum deal) atau `CANCELLED` **DILARANG** masuk ke dalam Total Penjualan.
   - **Sudah Dibayar Customer**: Akumulasi pembayaran yang berstatus `CONFIRMED` (termasuk pembayaran historis sebelum saldo awal).
   - **Belum Dibayar Customer**: $\text{Total Penjualan} - \text{Sudah Dibayar Customer}$.
   - Pembayaran customer historis ($\text{payment\_date} < \text{opening\_date}$) tetap mengurangi piutang customer, namun **tidak menambahkan kas lagi** ke Kas Tersedia setelah saldo awal.

### 11.9 Modal Produksi (HPP) vs Saldo Kas
1. **Pemisahan Konseptual Modal dan Kas**:
   - **HPP / Modal Produksi** adalah biaya manufaktur internal barang jadi yang berasal dari konsumsi aktual bahan baku riil (MWA) dan upah langsung/operasional batch.
   - **Kas** adalah uang tunai fisik/bank yang likuid dan tersedia untuk dibelanjakan.
   - Nilai HPP tidak diharapkan sama dengan saldo kas dan tidak mengurangi saldo kas secara langsung.
2. **Integrasi Mesin HPP Kanonikal (Tahap 3)**:
   - Ringkasan HPP pada Catatan Keuangan menggunakan layanan kanonikal `OrderHppCalculationService` dan `PeriodFinancialService`.
   - Pesanan/batch yang belum selesai QC ditampilkan secara transparan sebagai *"HPP Belum Final"* (jumlah pesanan dalam proses produksi/QC), **DILARANG** menampilkan nilai Rp0 secara menyesatkan.

### 11.10 Arus Bersih vs Kas Tersedia Sekarang
1. **Pemisahan Tegas Dua Metrik**:
   - **Arus Bersih Operasional (Periode)**: $\text{Uang Masuk Periode} - \text{Uang Keluar Periode}$.
   - **Kas Tersedia Sekarang**: Dihitung dari Saldo Awal Kas pada cut-off date ditambah kas masuk dikurangi kas keluar sejak cut-off date.
2. **Larangan Pelabelan Net Profit**:
   - Arus kas dan kas tersedia **DILARANG KERAS** dilabeli sebagai *"Laba Bersih"* atau *"Net Profit"*.
   - NAYARA tetap beroperasi sebagai aplikasi ERP operasional yang ringkas, bersih, dan tangguh tanpa kompleksitas akuntansi ganda (*non-full accounting*).

### 11.11 Hak Akses & Keamanan
- **Admin**: Akses penuh ke Ringkasan Keuangan Operasional, pengaturan Saldo Awal Kas, pencatatan Pembayaran Supplier PO, dan pencatatan Beban Usaha.
- **Owner**: Akses dashboard performa eksekutif dan analitik bisnis.
- **Gudang**: Dilarang mengakses ringkasan kas operasional, penjualan customer, saldo kas, dan pembayaran supplier (403 Forbidden). Gudang tetap fokus pada pengadaan (PO) dan penerimaan fisik bahan. Halaman PO show Gudang menyajikan riwayat pembayaran supplier secara informatif.
- **Produksi**: Dilarang mengakses modul keuangan (403 Forbidden).
- **Customer / Publik**: Dilarang mengakses modul keuangan; pelacakan publik (`/track/{token}`) tidak mengekspos data kas, pembayaran PO, saldo kas, maupun HPP.

---

## 12. HPP Acuan Variant, Rincian Pembentuk HPP, dan Hard Selling Price Floor

### 12.1 Pemisahan Dua Jenis HPP
Sistem membedakan secara tegas dua jenis HPP:

1. **HPP / Modal Acuan Variant (Planning & Pricing Cost)**:
   - Dihitung secara **OTOMATIS** oleh sistem melalui `VariantCostCalculationService`.
   - **Admin TIDAK menginput total HPP secara manual.**
   - Digunakan sebelum produksi/order untuk membantu Admin menentukan harga jual.
   - Formula:
     $$\text{HPP Acuan} = \text{Subtotal Bahan BOM} + \text{Biaya Printing Standar} + \text{Biaya Tenaga Kerja Standar} + \text{Biaya Packing/Proses Standar} + \text{Biaya Produksi Tambahan}$$
   - Subtotal Bahan BOM per pcs dihitung dari:
     $$\sum (\text{BOM.qty\_per\_unit} \times \text{MaterialStock.avg\_unit\_cost})$$
     menggunakan Moving Weighted Average (MWA) persediaan live gudang.
   - Menggunakan presisi mutlak `BCMath` (skala 2/4), **DILARANG** menggunakan floating-point.
   - **Perlakuan Missing Cost Data**: Jika BOM belum diatur, persediaan material belum memiliki MWA valid, atau biaya langsung wajib belum diisi, sistem menampilkan status transparan:
     *"HPP / Modal Acuan belum dapat dihitung."* disertai alasan spesifik (misal: "BOM belum ditentukan", "Modal material belum tersedia", "Biaya tenaga kerja standar belum diisi").
     Sistem **DILARANG KERAS** menampilkan angka palsu `Rp0` atau menciptakan nilai estimasi default.

2. **HPP Aktual Produksi Terakhir (Actual Realization Cost)**:
   - Dihitung oleh `BatchCostCalculationService` hanya setelah batch selesai diproduksi (`status = BatchStatus::SELESAI`) dan lulus inspeksi QC.
   - Menggunakan kuantitas pemakaian aktual (`used_qty`), snapshot biaya alokasi bahan (`StockMovement` OUT), snapshot tarif biaya langsung batch, dan kuantitas produk jadi lolos QC (`good_qty`).
   - Diambil dari batch selesai terbaru menggunakan urutan kanonikal `ProductionBatch::orderByLatestCompletion()`.
   - Hanya digunakan untuk evaluasi realisasi pasca-produksi. **DILARANG** mencampuradukkan HPP Acuan dengan HPP Aktual.

### 12.2 Rincian Pembentuk HPP (Structured Breakdown)
Admin dapat melihat rincian terstruktur komponen pembentuk HPP:
1. **Rincian Bahan (BOM Detail)**:
   - Nama Bahan, Qty BOM per pcs, Satuan, Modal Rata-rata / MWA per unit (`MaterialStock.avg_unit_cost`), dan Subtotal per bahan.
2. **Rincian Biaya Produksi Langsung**:
   - Biaya Printing Standar per pcs (jika `requires_printing = true`).
   - Biaya Tenaga Kerja Standar per pcs.
   - Biaya Packing/Proses Standar per pcs.
   - Biaya Produksi Tambahan per pcs.
3. **Grand Total Exact Match**:
   - Grand total dari rincian $(\text{Subtotal Bahan} + \text{Subtotal Biaya Produksi})$ **HARUS EXACT MATCH** dengan HPP Acuan yang dikembalikan oleh `VariantCostCalculationService`. Dilarang melakukan rekalkulasi independen di template Blade.
4. **Pencegahan Double-Counting Kemasan (Packaging Guard)**:
   - Material kemasan fisik (misal: plastik polybag klip, hangtag, stiker, label woven) dimasukkan ke dalam BOM material.
   - Biaya `packaging_process_cost_per_good_unit` murni merepresentasikan **biaya proses / upah jasa packing**, BUKAN biaya bahan kemasan yang sudah tercatat di BOM.

### 12.3 Hard Selling Price Floor (Server-Side Enforced)
1. **Aturan Mutlak**:
   $$\text{Harga Jual} > \text{HPP / Modal Acuan Variant}$$
   Aturan berlaku ketat (*strictly greater*):
   - $\text{Harga Jual} > \text{HPP}$ $\rightarrow$ **VALID**
   - $\text{Harga Jual} = \text{HPP}$ $\rightarrow$ **DITOLAK**
   - $\text{Harga Jual} < \text{HPP}$ $\rightarrow$ **DITOLAK**
2. **Server-Side Enforcement**:
   - Validasi dijalankan pada `StoreProductVariantRequest`, `UpdateProductVariantRequest`, `StoreOrderRequest`, dan `UpdateOrderRequest`.
   - Dilindungi lapis kedua (*defense-in-depth*) pada level Domain Guard di `ProductVariantController` dan `OrderController`. Crafted HTTP POST/PUT tetap ditolak dengan pesan kesalahan ramah:
     *"Harga jual harus lebih tinggi dari HPP / Modal Acuan (Rp... per pcs)."* untuk Master Variant, dan
     *"Harga jual produk tidak boleh sama dengan atau lebih rendah dari HPP / Modal."* untuk Sales Order.
   - **TIDAK ADA** toleransi peringatan saja (*no warning-only*), tidak ada override diam-diam, dan tidak ada penyesuaian markup otomatis.
3. **Variant Tanpa HPP Acuan**:
   - Jika HPP Acuan belum dapat dihitung (karena BOM belum lengkap), varian tetap dapat disimpan sesuai arsitektur `base_selling_price` yang nullable, namun sistem tidak mengklaim bahwa harga telah tervalidasi terhadap HPP.

### 12.4 Kekebalan Data Historis (Historical Immutability)
1. Pembelian bahan baru yang mengubah nilai MWA gudang akan langsung memperbarui kalkulasi HPP Acuan Variant terkini untuk perencanaan harga masa depan.
2. Namun, data transaksi historis:
   - `order_items.unit_selling_price` pada pesanan yang sudah tercatat, dan
   - HPP aktual pada batch produksi yang telah selesai,
   bersifat **MUTLAK KEBAL (IMMUTABLE)** dan tidak berubah terhadap fluktuasi MWA maupun perubahan tarif master di masa depan.

### 12.5 Kalkulasi Margin
- **Margin Nominal**: $\text{Harga Jual} - \text{HPP Acuan}$
- **Margin Persentase**: $\frac{\text{Margin Nominal}}{\text{Harga Jual}} \times 100\%$
- Dihitung menggunakan `BCMath` skala 2. Jika HPP Acuan belum tersedia, status margin dinyatakan "Belum tersedia".

---

## 13. Owner Read-Only Financial Summary & Authoritative Supervisor ROI

### 13.1 Authoritative Role Separation
1. **ADMIN (Pencatatan Keuangan Operasional)**:
   - Mengelola Catatan Keuangan operasional (`/admin/financial-records`).
   - Melakukan input dan konfirmasi penerimaan kas customer (`Payment`).
   - Melakukan input pembayaran supplier untuk Purchase Order (`PurchasePayment`).
   - Mengelola dan menginput beban/pengeluaran usaha (`BusinessExpense`).
   - Menetapkan dan mengelola Saldo Awal Kas operasional (`OpeningCashBalance`).
   - Memiliki hak akses penuh untuk transaksi mutasi keuangan operasional harian.

2. **OWNER (Monitoring Eksekutif & Pengambilan Keputusan Bisnis — READ-ONLY)**:
   - **MUTLAK READ-ONLY**: Owner **TIDAK PERNAH** melakukan input transaksi kas, tidak mencatat pembayaran customer, tidak mencatat pembayaran supplier, tidak membuat/mengedit beban usaha, dan tidak mengubah saldo awal kas.
   - Mengakses halaman eksekutif:
     - **Ringkasan Keuangan** (`/owner/financial-summary` alias `/owner/performance`).
     - **Dashboard Owner** (`/owner/dashboard`).
     - **Monitoring HPP Produksi** (`/owner/hpp`).
     - **Profitabilitas Pesanan** (`/owner/profitability`).
   - Menggunakan metrik keuangan dan kecukupan kas untuk monitoring kinerja usaha dan pertimbangan keputusan approval Purchase Order bahan baku.
   - Peran lain (Gudang, Produksi, Customer) dilarang keras mengakses modul finansial Owner (HTTP 403 Forbidden).

### 13.2 10 Metrik Utama Ringkasan Keuangan Owner
Halaman Ringkasan Keuangan menyajikan minimal 10 metrik terstruktur tanpa menghitung ulang business logic di Blade:
1. **Total Penjualan**: Akumulasi nilai Sales Order non-cancelled yang valid dalam periode.
2. **Pendapatan Diterima**: Total kas masuk riil dari customer dengan status `CustomerPayment.status == CONFIRMED` dalam periode.
3. **Piutang Pelanggan**: Kewajiban riil pelanggan yang belum terbayar untuk pesanan dalam periode terpilih, dihitung secara otoritatif per invoice/order ($\sum(\text{Total Invoice} - \text{Pembayaran CONFIRMED})$, mengabaikan pembayaran PENDING/CANCELLED). Aman dari distorsi lintas periode (*cross-period safe*).
4. **Total Pengeluaran**: Total arus kas keluar aktual ($\text{Supplier Payments} + \text{Business Expenses}$).
5. **Kas Saat Ini**: Posisi kas operasional mutlak terkini dari `FinancialRecordService::calculateCurrentCash()` (Saldo Awal Rp100.000.000 + penerimaan post-cutoff - pengeluaran post-cutoff). Bukan metrik periodik semu (label antarmuka: "Kas Saat Ini", helper: "Saldo kas operasional terkini").
6. **HPP / Modal Produksi**: Akumulasi modal produksi pesanan selesai (ditampilkan final bila semua batch selesai, atau disertai nilai HPP final tersedia dan jumlah pesanan yang belum final).
7. **Laba Kotor**: $\text{Total Penjualan} - \text{HPP Final}$ (akrual profitabilitas).
8. **Laba Bersih**: $\text{Laba Kotor} - \text{Total Beban Usaha}$ (akrual profitabilitas).
9. **ROI**: Rasio perbandingan arus kas aktual sesuai arahan pembimbing.
10. **Komitmen PO & Kewajiban Supplier**: Total nilai PO, PO terbayar, sisa komitmen kewajiban supplier ($\text{Total PO} - \text{PO Paid}$), proyeksi sisa kas, dan status kecukupan kas (`CUKUP` / `TIDAK CUKUP` / `BELUM DAPAT DIHITUNG`).

### 13.3 Formula ROI Authoritative Sesuai Arahan Pembimbing
Pembimbing menetapkan bahwa ROI untuk monitoring bisnis NAYARA adalah perbandingan arus uang aktual:
$$\text{ROI} = \frac{\text{Pendapatan Diterima}}{\text{Total Pengeluaran}} \times 100\%$$
- **Formula Proyek**:
  $$\text{ROI} = \frac{\text{confirmed\_customer\_receipts}}{\text{actual\_business\_cash\_outflow}} \times 100$$
- **Pendapatan Diterima**:
  - Sumber: `Payment` berstatus `CONFIRMED` dengan `payment_date` dalam periode terpilih.
  - Pembayaran `PENDING` dan `CANCELLED` **DIKECUALIKAN**.
- **Total Pengeluaran**:
  - Sumber: `PurchasePayment` (pembayaran supplier riil) dalam periode + `BusinessExpense` (beban usaha riil) dalam periode.
  - Penerimaan fisik material di gudang (`StockMovement`) dan pembuatan PO **BUKAN** peristiwa pengeluaran kas.
  - **Pencegahan Double Counting**: HPP **DILARANG** ditambahkan ke penyebut Total Pengeluaran ROI karena pembelian bahan baku sudah tercatat sebagai arus kas keluar melalui `PurchasePayment`.
- **Zero-Denominator Rule**:
  - Jika $\text{Total Pengeluaran} = 0$, ROI bernilai `null` dan ditampilkan sebagai **"Belum dapat dihitung"** (helper: *"Belum ada pengeluaran pada periode ini."*).
  - Dilarang keras menampilkan division by zero, 0%, Infinity, atau NaN.
- **Label Display**: Wajib menggunakan label **ROI** (bukan MER, bukan Efisiensi, bukan Return Ratio).
- **Format Display**: Format persentase dua desimal standar (contoh: `150,00%`).
- **Status Formula Lama**: Formula teoritis lama $(\text{Net Profit} / \text{Total Cost}) \times 100$ dinyatakan **OBSOLETE & SUPERSEDED** dan tidak digunakan lagi untuk metrik ROI pada proyek ini.

### 13.4 Pemisahan Konsep Metrik (Metric Separation)
Sistem memisahkan secara tegas tiga dimensi analisis finansial:
1. **CASH FLOW (Arus Kas)**:
   - Pendapatan Diterima (kas masuk riil dari customer).
   - Pembayaran Supplier & Beban Usaha (kas keluar riil).
   - Kas Tersedia (posisi kas cut-off terkini).
2. **PROFITABILITY (Profitabilitas Akrual)**:
   - Total Penjualan (revenue transaksi order valid).
   - HPP Final (modal barang yang diproduksi).
   - Laba Kotor (Gross Profit = Penjualan - HPP Final).
   - Laba Bersih (Net Profit = Laba Kotor - Beban Usaha).
3. **SUPERVISOR ROI (Efisiensi Kas)**:
   - $\text{Pendapatan Diterima} / \text{Total Pengeluaran Aktual} \times 100\%$.

### 13.5 Dukungan Filter Periode
Seluruh metrik Ringkasan Keuangan dan ROI mendukung pemilihan periode:
- `today` (Hari ini)
- `last_7_days` (7 Hari Terakhir)
- `this_month` (Bulan ini)
- `last_month` (Bulan lalu)
- `all` (Semua / All-time berjalan)
- `custom` (Rentang tanggal bebas dengan validasi start_date <= end_date)

---

## 21. Final Consolidation Modul Keuangan Admin (Pusat Cash Record Operasional)

### 21.1 Konsolidasi Navigasi Sidebar Keuangan Admin
1. **Pusat Navigasi Final**:
   - Menu Keuangan Admin disederhanakan menjadi 3 menu utama:
     - **Catatan Keuangan** (`/admin/financial-records`)
     - **Invoice & Tagihan** (`/admin/invoices`)
     - **Biaya & Pengeluaran** (`/admin/expenses`)
2. **Pembersihan Menu Redundan**:
   - Menu *"Pembayaran Supplier"* dan *"Laporan Penjualan"* dihapus dari sidebar navigasi.
   - Route lama (`admin.purchases.payments.*` dan `admin.reports.sales.*`) **DIPERTAHANKAN** secara utuh untuk menjaga backward compatibility workflow dan testing.
   - Pembayaran supplier PO diakses dan dimonitor terpusat melalui antarmuka **Catatan Keuangan** pada antrean aksi *"PO Menunggu Pembayaran"*.

### 21.2 Catatan Keuangan Sebagai Pusat Arus Kas Operasional Terderivasi (Derived Ledger)
1. **Prinsip Anti-Duplikasi Ledger**:
   - Sistem **DILARANG KERAS** membuat tabel generic duplicate ledger (seperti `financial_records`, `cash_transactions`, `general_ledger`).
   - Riwayat Transaksi dibentuk secara terderivasi langsung (*dynamically derived*) dari sumber-sumber kanonikal:
     - **UANG MASUK**:
       1. Customer Payment berstatus `CONFIRMED` (`Payment`).
       2. Manual Non-Invoice Cash Inflow (`BusinessIncome`).
     - **UANG KELUAR**:
       1. Realisasi Pembayaran Supplier PO (`PurchasePayment`).
       2. Beban Usaha & Biaya Supplier Non-PO (`BusinessExpense`).
2. **Aksi Header Catatan Keuangan**:
   - Ditambahkan tombol aksi terstandarisasi:
     1. `[ + Tambah Pemasukan ]` $\rightarrow$ membuka form pencatatan kas masuk non-invoice.
     2. `[ + Tambah Beban Usaha ]` $\rightarrow$ membuka form pencatatan beban usaha (`redirect_to=financial-records`).
   - Tombol menggunakan style NAYARA (`rounded-lg`, tinggi 36-40px, font 13-14px semibold, warna biru utama NAYARA (#093FB4)).

### 21.3 Manual Cash Inflow (`BusinessIncome`)
1. **Entitas & Ruang Lingkup**:
   - Model kanonikal: `BusinessIncome` (tabel: `business_incomes`).
   - Fields: `id`, `income_no` (`INC-YYYYMM-XXXX`), `income_date`, `category`, `amount` (`DECIMAL(18,2)`), `notes`, `created_by`, timestamps.
   - Kategori standar: `Pemasukan Lain`, `Modal Tambahan`, `Pendapatan Non-Penjualan`.
   - **Batas Kewenangan Mutlak**: Pembayaran customer dari pesanan **DILARANG** diinput lewat form Tambah Pemasukan (tetap wajib melalui flow Invoice & CustomerPayment).
2. **Dampak Finansial**:
   - **YES** memengaruhi Uang Masuk Admin (masuk ke kartu agregat kas masuk periode).
   - **YES** memengaruhi Kas Saat Ini (jika tanggal transaksi $\ge$ opening cut-off).
   - **NO** menambah Total Penjualan (omzet penjualan tetap murni berasal dari Sales Order).
   - **NO** mengubah numerator ROI supervisor (ROI tetap $\text{Confirmed Customer Receipts} / \text{Actual Cash Outflow}$).

### 21.4 Penyatuan Kategori Supplier: "Pembayaran Supplier" (Authoritative Single Category)
1. **Penyatuan Kategori Supplier**:
   - Seluruh pengeluaran terkait supplier pada Catatan Keuangan menggunakan SATU jenis/label resmi: **"Pembayaran Supplier"**.
   - Kategori terpisah "Biaya Supplier" **DIHAPUS** dari seluruh antarmuka (filter, dropdown beban usaha, selector) guna mencegah kebingungan dan duplikasi.
   - Aturan sebelumnya yang memisahkan "Pembayaran Supplier" dan "Biaya Supplier" dinyatakan **SUPERSEDED** oleh klarifikasi final pengguna.
2. **Authoritative Source (`PurchasePayment`)**:
   - Sumber resmi (*canonical source*) untuk Pembayaran Supplier tetap murni berasal dari `PurchasePayment` (pembayaran PO bahan baku approved Owner).
   - Dilarang membuat rekaman duplikat pada `BusinessExpense` untuk pembayaran yang sama (*anti double-counting*).
   - Form Tambah Beban Usaha tidak lagi menyediakan kategori "Biaya Supplier".

### 21.5 Filter Bertingkat Dependen & Pencarian Multi-Sumber (Composable Search)
1. **Filter Arus Kas (`flow`) & Jenis Transaksi (`type`)**:
   - Pilihan Jenis Transaksi otomatis menyesuaikan Arus Kas:
     - `flow=in` (Uang Masuk) $\rightarrow$ hanya menampilkan kategori pemasukan (`Semua Jenis Pemasukan`, `Uang Muka (DP)`, `Pelunasan`, `Pemasukan Lain`, `Modal Tambahan`, `Pendapatan Non-Penjualan`).
     - `flow=out` (Uang Keluar) $\rightarrow$ hanya menampilkan kategori pengeluaran (`Semua Jenis Pengeluaran`, `Pembayaran Supplier`, `Operasional Usaha`, `Marketing / Pemasaran`, `Bunga`, `Pajak`, `Penyusutan`, `Amortisasi`). Tidak ada kategori "Biaya Supplier".
     - `flow=all` (Semua Arus) $\rightarrow$ menampilkan seluruh jenis terstruktur dalam `optgroup label="PEMASUKAN"` dan `optgroup label="PENGELUARAN"`, di mana kategori supplier hanya muncul tepat satu kali sebagai "Pembayaran Supplier".
2. **Auto-Reset Incompatible Type**:
   - Jika pengguna berpindah antara Uang Masuk $\leftrightarrow$ Uang Keluar, nilai Jenis Transaksi yang tidak kompatibel otomatis di-reset ke `all` baik di client-side (Alpine.js) maupun server-side validation.
3. **Pencarian Multi-Sumber (Search All Sources)**:
   - Input pencarian mendukung pencarian global pada Customer Payment (nama customer, nomor SO, nomor invoice, referensi payment, tipe DP/Pelunasan, notes), Business Income (kategori, no referensi `INC-`, notes), Purchase Payment (nama supplier, nomor PO, referensi payment, kategori Pembayaran Supplier, notes), dan Business Expense (kategori, deskripsi, campaign, ID `EXP-`).
   - Seluruh filter (Periode, Tanggal Custom, Search, Arus Kas, dan Jenis Transaksi) dapat digabungkan secara composable tanpa konflik state.

## 22. Relasi Master Supplier ↔ Material & Validasi Pengadaan (Purchase Order)

### 22.1 Konsep Kardinalitas Many-to-Many
1. **Relasi Many-to-Many**:
   - Satu Supplier dapat menyediakan banyak Material (`Supplier::materials()`).
   - Satu Material dapat memiliki lebih dari satu Supplier alternatif (`Material::suppliers()`).
   - Contoh nyata audit: Material "Kain Ceruty Premium" disuplai oleh 2 mitra: **PT Sumber Kain** dan **PT Barang Hijab**.
2. **Struktur Database**:
   - Tabel pivot kanonikal: `supplier_materials`.
   - Kolom: `id`, `supplier_id` (FK `suppliers.id` CASCADE), `material_id` (FK `materials.id` CASCADE), timestamps.
   - Unique Composite Constraint: `[supplier_id, material_id]` mencegah duplikasi pasangan relasi.

### 22.2 Pemisahan Master Capability vs Transaction History
1. **Master Capability (`supplier_materials`)**:
   - Menentukan kapabilitas/katalog material yang saat ini resmi disuplai oleh masing-masing vendor.
   - Dikelola oleh Role Gudang & Admin melalui form Tambah/Edit Supplier.
2. **Transaction History (`purchases` & `purchase_items`)**:
   - Mencatat histori faktual transaksi pengadaan yang pernah dilakukan ("pernah membeli apa dari siapa").
   - Pembelian masa lalu bersifat historis dan tidak boleh diubah/dirusak oleh perubahan relasi master di masa depan.
3. **Immutability Transaksi**:
   - Menghapus material dari master supplier (`detach`/`sync`) **TIDAK MENGHAPUS** master Material, tidak menghapus Supplier, tidak menghapus Purchase/PurchaseItem lampau, dan tidak memodifikasi stok maupun average cost (MWA).

### 22.3 Safe & Idempotent Backfill dari Histori Pembelian Nyata
1. **Dasar Backfill**:
   - Relasi awal diisi otomatis murni dari histori Purchase Order nyata non-cancelled (`SupplierMaterialBackfillService`).
   - Tidak ada data relasi yang dikarang atau di-hardcode.
   - Dataset audit valid menghasilkan tepat **14 unique relations** (PT Sumber Kain = 8 material, PT Benang = 1 material, PT Barang Hijab = 5 material).
2. **Idempotensi**:
   - Backfill aman dijalankan berulang kali tanpa membuat duplikasi relasi (`existing++`, `inserted=0`).

### 22.4 Integrasi Validasi Pembuatan & Perubahan Purchase Order (PO)
1. **Alur UX Gudang (Supplier-First Flow)**:
   - Pengguna Gudang wajib memilih Supplier terlebih dahulu.
   - Pilihan Material pada baris item PO otomatis difilter HANYA menampilkan material yang terdaftar pada supplier terpilih.
   - Tidak ada toggle bypass "Tampilkan Semua Material".
   - Jika supplier diganti di tengah pengisian dan baris material yang sudah dipilih tidak disuplai oleh vendor baru, pilihan material otomatis dibersihkan (*reset*) dan notifikasi peringatan ditampilkan ke pengguna.
2. **Validasi Server-Side Mutlak**:
   - Form Request (`StorePurchaseRequest` dan `UpdatePurchaseRequest`) mewajibkan setiap `items.*.material_id` valid terhadap `Supplier::materials()`.
   - Pelanggaran ditolak keras dengan pesan ramah operasional: `"Material :name tidak terdaftar pada supplier :supplier."` tanpa mengekspos exception SQL.
3. **Prasyarat Penambahan Material Baru**:
   - Jika vendor mulai memasok material baru, Gudang/Admin wajib menambahkan material tersebut ke Master Supplier ("Material Disuplai") terlebih dahulu sebelum dapat digunakan pada pembuatan PO.

### 22.5 Tampilan Antarmuka (UI) Gudang
1. **Daftar Supplier**:
   - Kolom "Material Disuplai" menampilkan ringkasan kompak maksimal 2 material pertama.
   - Jika lebih dari 2 material, sistem menampilkan badge halus `+N lainnya`.
   - Fitur pencarian supplier diperluas untuk mencocokkan nama supplier sekaligus nama material yang disuplai (`orWhereHas('materials')`).
2. **Detail Supplier**:
   - Menampilkan kartu tabel "Material Disuplai" lengkap dengan nama material dan satuan (`unit`).
3. **Detail Material**:
   - Menampilkan kartu informasi "Supplier Terdaftar" yang mencantumkan seluruh vendor penyedia material bersangkutan.
4. **Otorisasi**:
   - Role **GUDANG** dan **ADMIN** berwenang mengelola master supplier dan relasi material.
   - Role **PRODUKSI** dan **CUSTOMER** dilarang mengelola supplier (HTTP 403 Forbidden).

---

## 23. Customer Prospect Flow & Performa Karyawan (Admin Performance)

### 23.1 Alur Bisnis Prospek WhatsApp Customer
1. **Kanal Utama WhatsApp**:
   - Mayoritas interaksi, diskusi model/warna, dan pemesanan berlangsung via WhatsApp.
   - Customer yang baru bertanya (misal: *"Kak, Voal Navy 20 pcs berapa?"*) tetap dicatat oleh Admin ke sistem NAYARA.
2. **Input Customer Tanpa Sales Order**:
   - Admin berwenang menambahkan customer baru secara mandiri tanpa harus langsung membuat Sales Order.
   - Field minimal pencatatan: Nama Customer/Toko, Nomor WhatsApp, Tanggal Masuk (`first_contact_date`), dan Catatan Kebutuhan.
   - Customer yang baru bertanya tidak memerlukan akun portal login customer (`user_id = null`).
3. **Pencegahan Duplikasi WhatsApp**:
   - Nomor WhatsApp dinormalisasi (menghapus spasi, strip, prefix `+62`/`62` menjadi `08...`).
   - Jika nomor WhatsApp sudah terdaftar, sistem menolak pembuatan/pembaruan dengan pesan ramah:
     `"Nomor WhatsApp ini sudah terdaftar pada customer [Nama Customer]."`

### 23.2 Status Customer derived (Hanya "Belum Deal" dan "Deal")
1. **Aturan Status Derivatif Mutlak**:
   - Status Customer **DILARANG** dipilih secara manual oleh Admin.
   - Status dihitung secara otoritatif (*derived*) berdasarkan transaksi:
     - **`BELUM DEAL`**: Customer belum memiliki Sales Order dengan pembayaran DP (`CustomerPayment`) berstatus `CONFIRMED` > 0. Customer yang baru bertanya atau ordernya masih menunggu DP berstatus `BELUM DEAL` (badge amber soft).
     - **`DEAL`**: Customer telah memiliki minimal 1 (satu) Sales Order resmi dengan CustomerPayment berstatus `CONFIRMED` > 0 (badge emerald green).
2. **Ketiadaan Status "Tidak Jadi" / CRM Funnel**:
   - Sistem **TIDAK MENGIMPLEMENTASIKAN** status *"Tidak Jadi"*, *"Lost"*, *"Rejected"*, *"Cold"*, *"Warm"*, atau stage CRM kompleks lainnya.
   - Prospek yang pernah bertanya namun tidak pernah melakukan pembayaran DP tetap berstatus **`BELUM DEAL`**. Hal ini diperlukan agar rasio konversi Admin dapat dihitung secara jujur dan akurat.
3. **Pembayaran Belum Dikonfirmasi**:
   - Pembayaran dengan status `PENDING` atau `CANCELLED` **TIDAK MENJADIKAN** customer Deal.

### 23.3 Atribusi PIC Admin
1. **PIC Otomatis**:
   - Saat Admin menginput customer baru, field `handled_by_user_id` otomatis diisi dengan ID Admin yang sedang login (`auth()->id()`). Admin tidak diminta memilih namanya sendiri.
2. **Integritas Data Historis**:
   - Customer historis yang belum memiliki PIC dilaporkan secara transparan pada ringkasan sistem (`unassigned_count`) dan **TIDAK DIHUBUNGKAN SECARA ACAK** ke Admin mana pun.

### 23.4 Eksklusivitas Metrik Performa pada Role Owner
1. **Isolasi Role Admin**:
   - Admin berfokus murni pada pekerjaan operasional pencatatan customer dan Sales Order.
   - Admin **DILARANG KERAS** melihat kartu performa, persentase Deal, ranking konversi, maupun menu performa dirinya sendiri.
2. **Halaman Performa Karyawan (Owner Only)**:
   - Akses rute `/owner/employee-performance` dan rinciannya hanya diizinkan untuk Role **OWNER** (Read-Only). Role Admin, Gudang, Produksi, dan Customer ditolak (HTTP 403 Forbidden).
   - Menghadirkan navigasi tab dual: **"Performa Admin"** dan **"Performa Produksi"**.
3. **Formula Persentase Deal Admin**:
   $$\text{Persentase Deal} = \frac{\text{Jumlah DISTINCT customer Deal yang ditangani Admin}}{\text{Total Customer yang ditangani Admin}} \times 100\%$$
   - **Repeat Orders**: Customer yang melakukan repeat order berkali-kali tetap dihitung sebagai **1 Customer Deal** dalam evaluasi konversi. Total order dicatat terpisah sebagai metrik pendukung.
4. **Penanganan Denominator Nol**:
   - Jika Admin belum menangani customer sama sekali (`total_customers = 0`), nilai rasio konversi bernilai `null` dan ditampilkan di antarmuka sebagai text:
     **`"Belum Ada Data"`** (bukan 0%, bukan NaN, dan bukan Infinity).
5. **Cohort Periode Berdasarkan Tanggal Masuk**:
   - Filter periode evaluasi performa dikelompokkan berdasarkan **Tanggal Masuk Customer** (`first_contact_date`), mencerminkan kohort prospek yang pertama kali diterima pada rentang waktu terpilih.

## 24. Performa Karyawan Produksi (Owner Only)

### 24.1 Penetapan PIC Produksi pada Batch
1. **Atribusi Karyawan Produksi**:
   - Kolom `production_employee_id` pada tabel `production_batches` mereferensikan `users.id` dengan role **`PRODUKSI`**.
   - Admin dapat memilih PIC Karyawan Produksi saat pembuatan batch (`/admin/orders/{order}/batches/create`).
   - Penetapan PIC bersifat opsional (dapat bernilai `NULL`).
   - Validasi ketat menolak user dengan role selain `PRODUKSI` (misal Admin, Gudang, Customer).
2. **Integritas Data Historis (Unassigned Batches)**:
   - Batch produksi pada data historis yang belum memiliki PIC Produksi tetap bernilai `NULL` (tidak diasumsikan atau dihubungkan secara acak).
   - Batch yang belum memiliki PIC dilaporkan secara transparan pada ringkasan KPI Owner (`unassigned_batches_count`) dengan pesan peringatan informatif.

### 24.2 Metrik Ketepatan Waktu Produksi (Time Performance)
1. **Dasar Evaluasi Tanggal**:
   - Membandingkan **Target Selesai Produksi** (`ProductionBatch.planned_end_date`) dengan **Tanggal Selesai Aktual** (`ProductionResult.result_date`).
   - Menggunakan perbandingan kalender tanggal mutlak (`startOfDay`):
     - $\text{result\_date} < \text{planned\_end\_date}$ $\rightarrow$ **Lebih Cepat** (`LEBIH_CEPAT`, badge label *"X hari lebih cepat"*).
     - $\text{result\_date} == \text{planned\_end\_date}$ $\rightarrow$ **Tepat Waktu** (`TEPAT_WAKTU`, badge label *"Tepat waktu"*).
     - $\text{result\_date} > \text{planned\_end\_date}$ $\rightarrow$ **Terlambat** (`TERLAMBAT`, badge label *"Terlambat X hari"*).
2. **Kategori On-Time**:
   - Batch yang selesai **pada hari target** ATAU **sebelum hari target** keduanya dikategorikan sebagai **Tepat Waktu / Cepat** (`diffInDays >= 0`).
3. **Formula Ketepatan Waktu Karyawan**:
   $$\text{Ketepatan Waktu} = \frac{\text{Jumlah Batch Tepat Waktu atau Lebih Cepat}}{\text{Jumlah Batch Selesai yang Memiliki Target Selesai}} \times 100\%$$
4. **Penanganan Denominator Nol**:
   - Jika karyawan belum menyelesaikan batch dengan target tanggal (`batches_with_target = 0`), persentase bernilai `null` dan ditampilkan sebagai **`"Belum Ada Data"`**.

### 24.3 Metrik Ketepatan Kuantitas Produksi (Quantity Performance)
1. **Dasar Evaluasi Kuantitas**:
   - Membandingkan **Target Kuantitas Batch** (`ProductionBatch.target_qty`) dengan **Hasil Aktual Produksi Fisik** (`ProductionResult.actual_qty`).
   - Menggunakan kuantitas fisik aktual pengerjaan produksi, bukan QC good qty, bukan finishing qty, dan bukan shipped qty.
2. **Formula Pencapaian Kuantitas per Batch**:
   $$\text{Pencapaian Batch} = \frac{\text{actual\_qty}}{\text{target\_qty}} \times 100\%$$
   - Dapat melebihi 100% jika produksi menghasilkan output lebih banyak dari target (tidak dicap/dibatasi).
3. **Formula Agregat Ketepatan Jumlah Karyawan (Sum over Sum)**:
   $$\text{Ketepatan Jumlah Agregat} = \frac{\sum \text{actual\_qty}}{\sum \text{target\_qty}} \times 100\%$$
   - **PENTING**: Dihitung dari total kuantitas aktual dibagi total target kuantitas di seluruh batch selesai pada periode, **BUKAN** rata-rata dari persentase batch.
4. **Penanganan Denominator Nol**:
   - Jika karyawan belum memiliki target kuantitas (`total_target_qty = 0`), persentase bernilai `null` dan ditampilkan sebagai **`"Belum Ada Data"`**.

### 24.4 Akses & Hak Istimewa (Strictly Owner-Only)
1. **Isolasi Role**:
   - Ringkasan performa dan detail pengerjaan karyawan Produksi hanya dapat diakses oleh Role **OWNER** di `/owner/employee-performance` dan `/owner/employee-performance/production/{user}`.
   - Karyawan Produksi **DILARANG KERAS** melihat KPI performa, ranking, maupun perbandingan antar-staf (menerima HTTP 403 Forbidden).
2. **Filter Periode Evaluasi**:
   - Beroperasi berdasarkan **Tanggal Selesai Produksi Aktual** (`ProductionResult.result_date`) dengan preset: Hari Ini (`today`), Bulan Ini (`this_month`), Bulan Lalu (`last_month`), Semua Waktu (`all`), dan Rentang Tanggal Kustom (`custom`).

## 25. Master Karyawan & Integrasi PIC Produksi

### 25.1 Arsitektur Master Karyawan Internal
1. **Reuse Model User Eksisting**:
   - Master karyawan menggunakan model `App\Models\System\User` eksisting tanpa membuat tabel duplicate `employees`.
   - Menggunakan enum status `App\Enums\UserStatus` (`ACTIVE` / `INACTIVE`).
2. **Kewenangan Role Owner**:
   - Hanya **OWNER** yang berwenang mengakses menu Manajemen Karyawan (`/owner/employees`), menambah karyawan baru, mengedit data profil, dan mengaktifkan/menonaktifkan akun karyawan.
   - Role ADMIN, GUDANG, PRODUKSI, dan CUSTOMER menerima respon **HTTP 403 Forbidden** jika mencoba mengakses route manajemen karyawan.
3. **Batasan Role Karyawan**:
   - Role karyawan yang dapat dikelola oleh Owner dibatasi secara ketat hanya:
     - `ADMIN`
     - `GUDANG`
     - `PRODUKSI`
   - Role `OWNER` dan `CUSTOMER` dikecualikan dari scope formulir pembuatan maupun pengubahan karyawan.
   - Akun Owner diproteksi dari pengubahan role, penghapusan, atau penonaktifan melalui halaman karyawan.
   - Akun Customer dikelola secara independen melalui modul prospek/master WhatsApp.

### 25.2 Status Keaktifan & Keamanan Data Historis
1. **Kebijakan Nonaktif (Soft-Deactivation, No Hard Delete)**:
   - Karyawan tidak boleh dihapus secara permanen (`hard delete`) jika pernah memiliki rekam jejak operasional (PIC batch, pembuat transaksi, audit trail).
   - Pengelolaan status menggunakan **Aktif** (`ACTIVE`) dan **Nonaktif** (`INACTIVE`).
2. **Perilaku Akun Nonaktif**:
   - Karyawan dengan status nonaktif diblokir dari proses login (diautentikasi oleh `LoginController`).
   - Karyawan nonaktif disembunyikan dari dropdown penugasan PIC pada batch produksi baru.
   - Seluruh relasi historis (batch produksi, pesanan, riwayat status) tetap utuh dan menampilkan nama karyawan terkait.

### 25.3 Penugasan PIC Produksi & Guard Hasil Produksi
1. **Pemisahan Tanggung Jawab Operasional**:
   - **OWNER**: Menentukan master akun karyawan dan memantau performa.
   - **ADMIN**: Memilih PIC Karyawan Produksi yang aktif saat membuat Production Batch operasional (`/admin/orders/{order}/batches/create`).
2. **Guard Hasil Produksi (Production Result Guard)**:
   - Sebelum hasil produksi dicatat (`ProductionResultService::recordFinalResult` dan form `ProductionResultController::create`), sistem memvalidasi bahwa batch telah memiliki atribut PIC Produksi (`production_employee_id`).
   - Jika PIC Produksi belum ditentukan, pencatatan hasil produksi diblokir dengan pesan validasi ramah:
     *"PIC Produksi belum ditentukan. Tetapkan karyawan Produksi sebelum mencatat hasil produksi."*
   - Hal ini mencegah terulangnya masalah batch tanpa PIC di masa mendatang.

### 25.4 Resolusi PIC Batch Historis (Owner Only)
1. **Penetapan Manual Tanpa Auto-Assign**:
   - Batch historis tanpa PIC Produksi tidak pernah diasumsikan atau di-assign secara acak oleh sistem.
   - Owner diberikan antarmuka khusus di `/owner/employee-performance/unassigned-batches` untuk memilih dan menetapkan PIC Produksi berdasarkan pengetahuan riil operasional.
2. **Integritas Atribut Batch**:
   - Penetapan PIC historis hanya memperbarui kolom `production_employee_id`.
   - Tidak ada perubahan pada target kuantitas, hasil aktual, tanggal hasil, target selesai, Sales Order, Invoice, persediaan/stok bahan, maupun hasil QC.
3. **Peringatan Dinamis pada Dashboard Performa**:
   - Halaman Performa Karyawan Owner menampilkan banner kompak dengan CTA:
     *"{N} batch historis belum memiliki PIC Produksi. [Lengkapi PIC]"*.
   - Jika seluruh batch historis telah memiliki PIC ($N = 0$), banner peringatan disembunyikan sepenuhnya.

### 25.5 Tata Kelola Password Karyawan Terpusat pada Owner & Penghapusan Permanen Reset Password Admin
1. **Otoritas Tunggal Owner atas Akun Karyawan Internal**:
   - Owner merupakan satu-satunya role otoritatif yang mengelola akun karyawan internal, mencakup: pembuatan akun karyawan, pengubahan profil/role, pengaktifan/penonaktifan, dan peresetan kata sandi (*password*).
2. **Penghapusan Permanen Fitur Reset Password Admin**:
   - Role Admin **TIDAK LAGI** memiliki fitur Reset Password.
   - Role Admin **TIDAK BOLEH** mengganti atau mereset password akun karyawan lain maupun memproses tiket reset password.
   - Seluruh route mandiri reset password Admin (`/admin/password-resets*`), controller terkait (`PasswordResetRequestController`), antarmuka Blade, serta antrean tindakan dashboard Admin telah dihapus secara permanen.
   - Upaya akses langsung oleh Admin ke route lama menghasilkan respon **HTTP 404 Not Found**.
3. **Pembersihan Sidebar Admin**:
   - Menu "Reset Password" dihapus secara permanen dari navigasi Admin.
   - Header bagian "MANAJEMEN USER" pada sidebar Admin dihapus secara permanen karena tidak memiliki menu anak lain.
4. **Sentralisasi Reset Password Karyawan ke Owner → Karyawan**:
   - Pengelolaan password karyawan internal dipusatkan pada halaman detail karyawan (`/owner/employees/{employee}`) via aksi modal kompak "Reset Password" dan halaman edit karyawan (`/owner/employees/{employee}/edit`).
   - Tidak dibuat menu terpisah "Reset Password" di sidebar Owner untuk menjaga agar seluruh fungsi akun karyawan terpusat di modul Karyawan.
5. **Cakupan Role yang Dapat Direset**:
   - Reset password karyawan oleh Owner hanya berlaku untuk akun karyawan internal: `ADMIN`, `GUDANG`, dan `PRODUKSI`.
   - Akun `OWNER` dan `CUSTOMER` diproteksi secara mutlak dan diblokir dengan **HTTP 403 Forbidden** jika dicoba direset melalui flow karyawan.
6. **Keamanan Kata Sandi (Password Security)**:
   - Password baru di-hash menggunakan engine kanonikal Laravel (`Hash::make`).
   - Validasi kata sandi: `required`, `confirmed`, minimal 8 karakter (`min:8`).
   - Kata sandi lama tidak pernah ditampilkan di antarmuka dan kata sandi baru tidak pernah dicatat (log) dalam bentuk teks polos (*plaintext*).
   - Seluruh sesi login berjalan milik karyawan yang direset langsung dibatalkan (*session invalidation*) dan `remember_token` dihapus.

---

## 26. Final Purchase Order (PO) Workflow Revision (Gudang Submission, Admin Approval & Payment with Transfer Proof, Auto-Ordered, and Physical Receiving)

### 26.1 Alur Pengajuan PO oleh Gudang ke Admin
1. **Pembuatan PO Berstatus DRAFT**:
   - Staff Gudang membuat Purchase Order melalui antarmuka Gudang (`/gudang/purchases/create`).
   - PO yang baru dibuat berada dalam status operasional `DRAFT` dan `approval_status = NOT_SUBMITTED`.
2. **Pengajuan ke Admin ("Ajukan ke Admin")**:
   - Gudang mengajukan PO yang telah siap dengan menekan tombol "Ajukan ke Admin" pada detail PO (`/gudang/purchases/{purchase}`).
   - Transisi mengubah `approval_status` menjadi `PENDING_APPROVAL`.
   - Seluruh label, dialog, flash message, dan teks antarmuka Gudang telah diperbarui secara konsisten dari "Ajukan ke Owner" menjadi "Ajukan ke Admin".

### 26.2 Otoritas Mutlak Persetujuan PO pada Role Admin (Owner Approval Dihapus Permanen)
1. **Pusat Persetujuan PO Admin**:
   - Role **ADMIN** memegang wewenang tunggal dan penuh untuk meninjau, menyetujui, meminta revisi, atau menolak pengajuan PO di modul `/admin/purchases/approvals`.
   - Opsi keputusan Admin:
     - **Setujui (`APPROVED`)**: PO disetujui untuk kemudian diproses pembayarannya oleh Admin.
     - **Minta Revisi (`REVISION_REQUIRED`)**: Catatan revisi dikirim kembali ke Gudang untuk diperbaiki.
     - **Tolak (`REJECTED`)**: PO dibatalkan dengan alasan penolakan.
2. **Penghapusan Hak Persetujuan Owner**:
   - Role **OWNER** tidak lagi memiliki menu, rute, atau hak eksekusi persetujuan PO (`owner.purchase-approvals.*` telah dihapus).
   - Antrean persetujuan PO pada dashboard Owner dikosongkan dan menu "Persetujuan PO" dipindahkan secara eksklusif ke sidebar Admin.
3. **Prioritas Antrean Dashboard Admin**:
   - PO yang menunggu persetujuan (`PENDING_APPROVAL`) secara otomatis muncul pada baris pertama (Prioritas 1) dalam "Antrean Aksi Cepat" dan kartu statistik Section C di Dashboard Admin.

### 26.3 Pembayaran Supplier oleh Admin & Kewajiban Bukti Transfer
1. **Antrean Pembayaran Supplier**:
   - Admin mengakses daftar PO yang telah disetujui (`APPROVED`) dan belum lunas di antarmuka `/admin/purchases/payments`.
2. **Kewajiban Bukti Transfer (`transfer_proof`)**:
   - Setiap pencatatan pembayaran supplier (`store` pada `PurchasePaymentController`) **WAJIB** menyertakan file bukti transfer (*transfer proof*).
   - Spesifikasi validasi file: format dokumen/gambar yang sah (`pdf`, `jpg`, `jpeg`, `png`) dengan ukuran maksimal 5 MB (`max:5120`).
   - File disimpan secara aman di storage disk `local` pada direktori `transfer-proofs/` dan path penyimpanannya dicatat pada kolom `purchase_payments.transfer_proof_path`.
3. **Pratinjau Bukti Transfer Aman**:
   - Bukti transfer dapat diunduh/dilihat melalui rute terotentikasi `/admin/purchases/payments/{payment}/proof`.
   - Akses dibatasi secara ketat hanya untuk pengguna berwenang (Role ADMIN dan OWNER).

### 26.4 Transisi Otomatis ke Status `ORDERED` (Penghapusan Konfirmasi Supplier Manual)
1. **Otomatisasi Status `ORDERED`**:
   - Begitu pembayaran supplier mencapai status **LUNAS** (`outstanding_amount == 0`), sistem secara otomatis mengubah status operasional Purchase Order dari `DRAFT` menjadi `ORDERED` (`Dipesan`).
2. **Penghapusan Aksi Konfirmasi ke Supplier oleh Gudang**:
   - Tombol, modal, dan route `gudang.purchases.confirm` telah dihapus secara permanen dari UI Gudang.
   - Method `PurchaseOrderService::confirmPurchase()` dinyatakan usang (*obsolete*) dan memicu `DomainException` jika dipanggil, memastikan alur tidak dapat di-bypass secara manual.

### 26.5 Pemisahan Event Finansial dan Mutasi Fisik Persediaan (Receiving Goods)
1. **Integritas Nol Efek Samping pada Pembayaran**:
   - Pembayaran supplier merupakan *financial cash-out event* murni yang mencatat pengeluaran kas (`PurchasePayment`) dan mengurangi kas tersedia.
   - Pembayaran supplier **TIDAK MENGUBAH** stok fisik material (`material_stocks.qty_on_hand`), **TIDAK MENGUBAH** nilai rata-rata persediaan (`avg_unit_cost` / MWA), dan **TIDAK MEMBUAT** `StockMovement`.
2. **Penerimaan Barang Fisik oleh Gudang (`ORDERED` $\rightarrow$ `RECEIVED`)**:
   - Staf Gudang menerima barang fisik di `/gudang/purchases/{purchase}/receive` hanya jika PO berstatus `ORDERED` dan telah lunas.
   - Mutasi fisik penambahan stok (`qty_on_hand`), pencatatan `StockMovement` (tipe `IN`), dan perhitungan ulang Moving Weighted Average (MWA) terjadi **SECARA EKSKLUSIF** saat Gudang mengeksekusi penerimaan barang (`PurchaseReceivingService::receivePurchase`).
   - Penerimaan barang fisik tidak mengurangi kas lagi (*zero double cash-out*).

---

