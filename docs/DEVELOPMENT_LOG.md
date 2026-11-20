# NAYARA Development Logbook



**Project:** NAYARA — Hijab ERP

**Path:** `D:\Magang\hijab`

**Tujuan Dokumen:** Catatan kronologis terverifikasi mengenai alur pembangunan sistem dari awal, kendala teknis/bisnis yang dihadapi, akar penyebab, solusi rekayasa, dan hasil implementasi berdasarkan bukti commit git dan codebase repositori.



---



## 1. Log Kronologis Pengembangan Terverifikasi



Format Logbook:

| Tanggal | Modul | Pekerjaan | Kendala | Penyebab | Solusi | Hasil |

| :--- | :--- | :--- | :--- | :--- | :--- | :--- |

| **2026-08-28** | Admin Core | Finalisasi arsitektur Admin Role, Sales Order, dan PRD v1.6 Acceptance | Risiko regresi pada data transaksi pesanan dan ketidaksesuaian status dokumen saat concurrency | Perubahan status order belum menerapkan row lock pesimis dalam transaksi database | Implementasi `DB::transaction()` dengan pessimistic locking (`lockForUpdate()`) dan enum backed casting | Admin Role lulus uji penerimaan PRD v1.6 dengan integritas data tinggi |

| **2026-08-28** | Gudang Master | Pembangunan pondasi master data Bahan Baku (Material), Satuan (Unit), dan Pemasok (Supplier) | Potensi penghapusan tidak sengaja pada data master yang masih terikat transaksi aktif | Kurangnya proteksi relasi integritas referensial di level aplikasi | Tambahkan pemeriksaan ketergantungan relasi sebelum aksi destroy/delete serta filter status aktif | Master data Gudang stabil dan aman dari aksi penghapusan fatal |

| **2026-08-28** | Gudang MRP | Pembangunan fondasi kalkulasi Material Requirements Planning (MRP) Batch | Potensi salah hitung kebutuhan bahan akibat pembulatan pecahan desimal | Operasi aritmatika native PHP float rentan floating-point precision error | Gunakan pustaka fungsi `BCMath` dengan presisi 4 desimal untuk seluruh kuantitas bahan | Kebutuhan bahan terhitung presisi sesuai resep BOM per unit dikalikan target batch |

| **2026-08-28** | Procurement | Pembangunan modul Purchase Order (PO) bahan baku | Stok gudang berpotensi bertambah terlalu dini saat PO baru dibuat | Logika bisnis belum memisahkan antara pengajuan pembelian dengan penerimaan fisik barang | Terapkan aturan ketat: Pembuatan PO tidak mengubah stok fisik gudang (`qty_on_hand` tetap 0) | Saldo persediaan gudang tetap valid dan mencerminkan stok fisik riil di rak |

| **2026-08-29** | Procurement | Implementasi alur penerimaan pembelian penuh (*Full Receiving*) | Risiko penambahan stok berulang kali (*Double Receiving*) jika tombol submit ditekan dua kali | Status PO belum divalidasi secara transaksional dengan row lock sebelum memproses mutasi barang | Batasi hanya status `ORDERED` yang bisa diterima, tolak status `RECEIVED`, dan kunci baris transaksi | Penerimaan PO menghasilkan tepat 1 mutasi `IN` dan stok bertambah akurat tanpa risiko duplikasi |

| **2026-08-29** | Inventory Valuation | Implementasi kalkulasi Moving Weighted Average (MWA) saat stok masuk | Nilai modal persediaan bisa menjadi distortif jika pembelian baru langsung menimpa harga lama | Metode FIFO/LIFO terlalu kompleks dan tidak mencerminkan harga rata-rata berjalan | Terapkan formula MWA: $\text{Avg} = \frac{(\text{Stok Lama} \times \text{Avg Lama}) + (\text{Qty Masuk} \times \text{Harga Beli})}{\text{Stok Baru}}$ | Nilai rata-rata harga pokok bahan baku terbarui secara konsisten setiap kali barang masuk |

| **2026-08-29** | Gudang Stok | Implementasi audit trail StockMovement dan Stock Opname terkontrol | Kesulitan melacak perubahan saldo stok historis dan tanggal pastinya | Mutasi fisik gudang belum mencatat saldo sebelum, saldo sesudah, dan dokumen pemicu | Buat tabel `stock_movements` yang mencatat tanggal, `qty_before`, `qty_after`, jenis mutasi (`IN`/`OUT`/`ADJUSTMENT`), dan referensi | Sistem mampu menjawab pertanyaan audit: "Kapan stok berubah, dari berapa ke berapa, dan oleh transaksi apa" |

| **2026-08-29** | Gudang Alokasi | Implementasi alokasi bahan baku ke Batch Produksi (*Full Allocation*) | Terjadinya stok fisik minus (*negative stock*) jika gudang mengalokasikan bahan yang kurang | Kuantitas stok gudang tidak divalidasi terhadap kuantitas yang diminta saat tombol alokasi ditekan | Validasi `qty_on_hand >= required_qty` di bawah row-lock; catat tepat 1 mutasi `OUT` per bahan | Stok gudang berkurang aman, dan harga modal bahan dibekukan permanen sebagai snapshot |

| **2026-08-29** | Produksi Core | Implementasi alur pencatatan pemakaian bahan riil dan hasil produksi aktual | Potensi pengurangan stok gudang dua kali (*Double Stock Deduction*) saat Produksi mencatat bahan terpakai | Bahan fisik sudah dikeluarkan saat alokasi gudang, jika pemakaian riil mencatat `OUT` lagi stok akan terpotong ganda | Tetapkan kaidah: Pencatatan pemakaian bahan aktual (`used_qty`) **DILARANG** membuat mutasi `OUT` baru | Stok gudang tidak terpotong ganda, dan variansi efisiensi pemakaian bahan tercatat rapi |

| **2026-08-29** | Produksi QC | Implementasi alur inspeksi Quality Control (QC) Good dan Reject | Inkonsistensi jumlah barang jadi antara yang lolos, cacat, dan total aktual yang dijahit | Belum adanya aturan matematis yang mengikat antara total hasil jahit dan hasil QC | Wajibkan persamaan mutlak: $\text{good\_qty} + \text{reject\_qty} == \text{actual\_qty}$ menggunakan BCMath | Data QC valid 100%, menghasilkan angka produk layak jual (*Good*) yang siap dikemas |

| **2026-08-29** | Produksi Akhir | Implementasi pengemasan (*Packing*) dan penyelesaian batch | Batch produksi bisa diselesaikan tanpa melewati tahapan operasional yang sah | Transisi status batch belum membatasi urutan alur (*finite state machine*) | Kunci alur urutan status: `BAHAN` $\to$ `PRINTING` (opsional) $\to$ `PRODUKSI` $\to$ `QC` $\to$ `PACKING` $\to$ `SELESAI` | Batch selesai secara sah dan siap diproses ke tahap pengiriman produk |

| **2026-08-30** | Produksi Planning | Penyesuaian kapasitas perencanaan batch terhadap cacat QC | Target produksi pesanan berpotensi kurang jika ada barang reject di batch sebelumnya | Sistem lama menganggap seluruh target batch pasti menjadi barang jadi sempurna | Perbarui formula perencanaan sisa order: pertimbangkan akumulasi unit `Good` riil dari QC | Admin dapat menjadwalkan batch pengganti untuk menutup kekurangan unit akibat reject |

| **2026-09-01** | HPP Manufaktur | Implementasi mesin kalkulasi HPP Batch dan integritas biaya | HPP batch menampilkan angka semu Rp 0 ketika batch belum tuntas dikerjakan | Sistem lama mengembalikan nilai 0 jika data biaya belum lengkap | HPP hanya berstatus `READY` jika batch `SELESAI`, pemakaian bahan tercatat, dan QC final; jika belum, bernilai `null` | Mencegah pelaporan keuangan palsu (*fake Rp 0*) sebelum proses produksi benar-benar selesai |

| **2026-09-01** | Owner Analytics | Pembangunan fondasi monitoring HPP, Profitabilitas Pesanan, dan Kinerja Finansial Periode | Owner kesulitan melihat laba kotor riil per pesanan dan tren bisnis berjalan | Data biaya manufaktur dan beban operasional belum diagregasi secara terstruktur | Buat modul analisa laba kotor ($\text{Revenue} - \text{HPP}$) per pesanan dan analisa periode (EBITDA, ROI, MER) | Owner memiliki visibilitas penuh terhadap performa finansial konveksi berbasis pesanan |

| **2026-09-10** | Customer Portal | Implementasi portal autentikasi mandiri pelanggan (*Customer Self-Service*) | Risiko kebocoran data rahasia (*cost data leakage*) seperti HPP, harga modal bahan, dan nama supplier ke pelanggan | Tampilan riwayat pesanan pelanggan sempat berpotensi mengekspos relasi operasional internal | Isolasi query pelanggan dengan IDOR guard (`$customer->orders()`) dan bersihkan view dari seluruh atribut modal/biaya | Pelanggan dapat melacak tahapan pesanan, invoice, dan resi secara transparan tanpa membocorkan rahasia biaya internal |

| **2026-09-18** | Core Baseline | Pembersihan baseline development, penambahan Template BOM, dan Histori Pembelian | Admin harus mengetik ulang resep bahan baku yang sama untuk varian produk sejenis | Belum adanya master template resep bahan baku yang dapat diterapkan berulang kali | Rancang entitas `BomTemplate` dan `BomTemplateItem` dengan fitur terapkan (*apply*) ke varian produk | Efisiensi input data resep meningkat drastis, integritas baseline testing bersih 100% |

| **2026-09-23** | Keamanan Akun | Implementasi tata kelola reset password yang dikelola Admin | Kesulitan pengguna saat lupa password tanpa server email SMTP eksternal yang stabil | Sistem reset password konvensional bergantung pada pengiriman token via email internet | Sediakan fitur admin-managed forgot password: Admin dapat memverifikasi dan mereset password pengguna | Pemulihan akses akun operasional bengkel kerja berjalan lancar tanpa kendala dependensi luar |

| **2026-09-28** | Antarmuka UI | Polishing antarmuka halaman Pembelian Bahan dan Reset Password | Tampilan tabel pembelian dan kartu form belum selaras dengan bahasa desain modern NAYARA | Komponen visual masih menggunakan layout dasar yang kurang terstruktur | Terapkan bahasa desain NAYARA (palet warna magenta `#8E1B5C`, badge status tegas, kartu berbayang lembut) | Antarmuka operasional terasa profesional, konsisten, dan mudah digunakan |

| **2026-09-29** | Master Costing | Dekopling tarif biaya produksi varian dari form pembuatan varian master | Admin dipaksa mengisi tarif ongkos jahit, printing, dan kemasan saat membuat katalog varian baru, padahal pesanan belum tentu ada | Asumsi sistem lama menganggap seluruh tarif biaya sudah pasti diketahui di muka | Jadikan tarif produksi pada master varian bersifat `NULLABLE`; validasi kelengkapan tarif dipindahkan ke saat pembuatan Batch Produksi | Admin dapat leluasa mendaftarkan katalog varian baru sesuai prinsip bisnis Make-to-Order |

| **2026-09-29** | Identifikasi SKU | Implementasi pembuatan SKU otomatis berbasis server (*Auto SKU*) | Admin harus mengetik SKU secara manual (`HJB-VP-001`), rentan salah ketik, duplikasi nomor, atau inkonsistensi format | Belum adanya mekanisme generator otomatis sequence SKU di level server | Bangun `SkuGeneratorService` yang mengunci sequence di database dan menghasilkan format kanonikal unik | SKU varian digenerate otomatis, konsisten, dan terbebas dari kesalahan manusia (*human error*) |

| **2026-09-29** | Kode Produk | Koreksi semantik singkatan Kode Produk (`VP` = Voal Premium) | Singkatan kode produk otomatis dari sistem membaca "Voal Polos" menjadi `VP`, tidak sesuai arti bisnis "Voal Premium" | Ekstraksi singkatan teks otomatis tidak memahami konteks terminologi dagang NAYARA | Ubah alur: sistem memberikan saran kode semantik pada form pendaftaran produk, Admin mengonfirmasi/mengedit sebelum disimpan permanen | Kode produk memiliki makna bisnis yang valid dan dapat diaudit secara andal (`HJB-VP-DP-001`) |

| **2026-09-29** | Antarmuka Gudang | Polishing antarmuka modul Gudang (Material, Satuan, Supplier, Perencanaan Bahan) | Form input dan tabel master gudang belum serasi dengan antarmuka Admin dan Produksi | Masih menggunakan elemen styling mentah sebelum standardisasi komponen | Refactor view Gudang menggunakan komponen desain terpadu NAYARA, breadcrumb jelas, dan kartu informatif | Tampilan operasional Gudang rapi, intuitif, dan siap untuk demonstrasi dan evaluasi penguji |

| **2026-09-29** | Requirement Alignment | Klarifikasi kebutuhan alur Customer dan QC sesuai proses bisnis konveksi NAYARA | Interpretasi awal menganggap Customer perlu membuat pengajuan pesanan sendiri dan Rework perlu diimplementasikan | Catatan requirement sebelumnya belum membedakan alur bisnis utama dengan portal customer langganan, serta belum menegaskan Reject sebagai cacat final | • Sales Order dikunci tetap dibuat Admin dari order WhatsApp.<br>• Customer Portal difokuskan pada customer langganan untuk history/tracking/shipment.<br>• QC menggunakan Good + Reject = Actual.<br>• Rework tidak digunakan | Requirement terbukti lebih selaras dengan proses bisnis konveksi NAYARA, kode existing 100% valid tanpa perubahan |

| **2026-09-30** | Shipment / Payment Gate | Penegakan syarat pelunasan invoice sebelum pembuatan shipment dan pengiriman order | Order dapat tampil berstatus "DIKIRIM" pada Kanban Admin meskipun invoice baru dibayar DP/sebagian | `ShipmentService::getEligibility` dan `markAsShipped` sebelumnya hanya memvalidasi pemenuhan fisik produksi (Good Qty QC) tanpa memeriksa status komersial pelunasan pembayaran | • Pisahkan pemenuhan fisik packing dari izin pengiriman.<br>• Tambahkan metode `isOrderFullyPaid` berbasis BCMath desimal pada `InvoiceService`.<br>• Enforce server-side guard pada `ShipmentService` dan `OrderStatusService` (tolak pengiriman berstatus belum lunas).<br>• Tampilkan indikator "Menunggu Pelunasan" pada Kanban Admin dan antrean Produksi.<br>• Kunci public tracking dan portal customer agar tidak prematurely berstatus DIKIRIM. | Order berbayar sebagian tertahan aman di status `PACKING` dengan label "Menunggu Pelunasan"; shipment hanya dapat dibuat setelah invoice lunas terkonfirmasi (`PAID`) |

| **2026-09-30** | Stock Replenishment & Traceability | Finalisasi alur pembelian ulang bahan dan traceability tanggal stok | Istilah "update stok" dapat disalahartikan sebagai edit qty manual; UI belum menampilkan perbedaan tanggal PO vs tanggal penerimaan dan waktu exact mutasi stok | Kolom `movement_date` bertipe `date` sehingga kehilangan presisi waktu (jam/menit); Purchase History belum menampilkan tanggal PO terpisah dari tanggal terima | • Stock replenishment dikunci melalui alur Procurement: PO → Receive → StockMovement IN.<br>• Purchase History diperkaya kolom Tanggal PO, Tanggal Diterima (date+time), dan Dicatat Oleh.<br>• Stock Movements menampilkan tanggal & waktu aktual dari `created_at` (bukan `movement_date`).<br>• MRP detail menampilkan timestamp & actor alokasi bahan dari StockMovement OUT.<br>• Kolom "Stok Gudang" pada MRP diklarifikasi menjadi "Stok Gudang Saat Ini" vs "Stok Saat Perencanaan".<br>• Shortcut "Beli Bahan" ditambahkan di halaman Material Detail (link ke Create PO).<br>• Tombol generic "Tambah Stok" / "Update Stok" DILARANG.<br>• Dokumentasi business-rules.md diperbarui dengan rule Purchase & Stock Replenishment. | Seluruh pertanyaan traceability pembimbing dapat dijawab: tanggal PO, tanggal terima, tanggal stok berubah (date+time), qty before/after, referensi dokumen, actor, MRP calc date, allocation date |

| **2026-09-30** | Production / Payment Gate | Penegakan syarat pembayaran Down Payment (DP) sebelum pembuatan Production Batch | Order berstatus DEAL dapat langsung dibuatkan Batch Produksi meskipun invoice belum diterbitkan atau DP belum dibayar | `BatchService::canCreateForOrder` dan `createBatch` sebelumnya hanya memeriksa status DEAL/BAHAN dan kapasitas sisa tanpa mengecek pembayaran DP | • Tegaskan alur Make-to-Order: WA → SO → DEAL → Invoice → Confirmed DP → Batch → MRP → Produksi.<br>• Tambahkan `hasConfirmedDownPayment` dan `getDownPaymentStatus` pada `InvoiceService` berbasis BCMath desimal.<br>• Enforce server-side guard pada `BatchService::createBatch` (melempar DomainException jika belum ada DP confirmed).<br>• Guard controller `ProductionBatchController@create` dan `@store`.<br>• Disable tombol "Buat Batch Produksi" di header dan per-item card pada Order Detail Hub jika DP belum confirmed.<br>• Pisahkan syarat produksi (DP confirmed > 0) dengan syarat pengiriman/shipment (harus FULLY PAID). | Order DEAL tertahan di antarmuka & backend hingga DP terkonfirmasi; batch produksi hanya dapat dibuat setelah minimal satu pembayaran CONFIRMED > 0 diterima |

| **2026-09-30** | Fulfillment / UI Consistency | Penyelarasan konsistensi status Pengemasan (PACKING), Siap Dikirim, dan Pengiriman (DIKIRIM) | Public Tracking menampilkan status "Sedang dalam tahapan ini" pada tahap Pengemasan padahal batch fisik sudah selesai dan invoice lunas | Timeline publik dan portal pelanggan mengevaluasi status hanya berdasarkan nilai enum `OrderStatus::PACKING` tanpa mengecek penyelesaian batch fisik | • Bangun metode turunan `isPackingCompleted()`, `getFulfillmentDisplayState()`, dan `getFulfillmentPresentation()` pada `ShipmentService` & `Order`.<br>• Sinkronkan 5 antarmuka: Admin Detail, Kanban Admin, Public Tracking, Customer Portal, dan Shipment page.<br>• Tandai tahap "Pengemasan Produk" sebagai "Selesai" jika seluruh batch telah SELESAI.<br>• Tampilkan context "Siap Dikirim" dan tahan tahap "Pesanan Dikirim" tetap upcoming hingga pengiriman fisik terjadi.<br>• Pertahankan privasi publik (tanpa mengekspos sisa tagihan/nominal pembayaran). | Seluruh antarmuka operasional dan pelanggan menampilkan status yang 100% konsisten, akurat, dan transparan sesuai fakta operasional di lapangan |

| **2026-09-30** | Audit Trail / Timezone | Perbaikan konversi dan display timezone seluruh timestamp audit NAYARA | Halaman Material Planning menampilkan "Dihitung pada: 30 September 2026, 04:00" padahal waktu kejadian sekitar 11:00 WIB (selisih 7 jam) | Nilai datetime UTC di database ditampilkan langsung ke antarmuka Blade tanpa konversi ke zona waktu operasional lokal (Asia/Jakarta / WIB) | • Tambahkan konfigurasi `display_timezone => Asia/Jakarta` di `config/app.php`.<br>• Bangun helper sentral `App\Support\DateTimeFormatter` (`formatDateTime`, `formatLongDateTime`, `formatShortDateTime`, `formatTime`, `formatDateWib`, `formatDate`).<br>• Pisahkan field DATE-only (tidak digeser) dari field Datetime.<br>• Larang manipulasi manual `+7` / `addHours(7)`.<br>• Pertahankan raw timestamp historis di database tetap UTC.<br>• Perbarui seluruh tampilan audit: MRP, Alokasi, Mutasi Stok, Penerimaan PO, Shipment, Tracking, dan Histori Order. | Seluruh jejak waktu audit di seluruh modul sistem tampil konsisten dalam WIB (UTC+7) dengan data historis database 100% terjaga |

| **2026-10-07** | Produksi & QC | Implementasi Revisi Pembimbing Tahap 1 & Klarifikasi Semantik Tanggal QC: Target Tanggal Produksi, Actual Qty <= Target, QC Good <= Actual, Auto Reject, Tanggal QC Mengikuti Tanggal Mulai Produksi, Auto QC Timestamp (WIB), dan Progres Batch Independen di Kanban | Target tanggal tidak terlihat oleh operator jahit; staf produksi dapat menginput actual melebihi target dan merekayasa tanggal QC; reject diinput manual; progres batch di kartu order saling memblokir | Belum ada visibilitas `planned_end_date` di antarmuka produksi/gudang; validasi `actual_qty <= target_qty` belum ada di server-side; input reject dan tanggal QC terbuka manual di form; Kanban mengelompokkan pesanan tanpa rincian status independen tiap batch | • Wajibkan `planned_end_date` saat admin membuat batch, tampilkan read-only pada daftar/detail batch Produksi dan Gudang MRP, lengkapi indikator keterlambatan.<br>• Enforce server-side guard `actual_qty <= target_qty` (0 < actual <= target) di Request & Service.<br>• Enforce `good_qty <= actual_qty` dan kunci `reject_qty = actual_qty - good_qty` otomatis di server-side.<br>• Tetapkan `qc_date` otomatis mengikuti tanggal produksi mulai (`planned_start_date`), terapkan server-side gate (QC tidak dapat dilakukan sebelum tanggal produksi).<br>• Catat `qc_at` (UTC/WIB) otomatis saat simpan sebagai actual audit timestamp.<br>• Tampilkan status independen tiap batch di kartu Kanban order admin. | Seluruh poin revisi pembimbing tahap 1 dan klarifikasi tanggal QC terpenuhi 100%, teruji aman dengan feature test komprehensif dan integrasi penuh. |

| **2026-10-07** | Produksi & Gudang | Implementasi Revisi Pembimbing Tahap 2: Rekonsiliasi Pemakaian Bahan Aktual (Surplus Otomatis Kembali ke Gudang, Tambah Bahan dari Gudang untuk Shortage, Invariant No Double Stock Out, MWA Costing Safety) | Sisa bahan produksi tidak otomatis kembali ke stok gudang; tim produksi tidak dapat menambah bahan saat kebutuhan aktual melebihi alokasi yang tersedia | Sistem sebelumnya hanya menyimpan `used_qty` tanpa mutasi pengembalian surplus atau mekanisme alokasi tambahan; tidak ada aksi Tambah Bahan dari Gudang di antarmuka produksi | • Implementasikan otomasi pengembalian sisa bahan (`allocated - used`) ke stok gudang saat finalisasi hasil produksi (`ProductionResultService`) dengan StockMovement IN (`reference_type = production_batches`).<br>• Jaga invariant MWA: pengembalian bahan tidak menghitung ulang `avg_unit_cost` gudang.<br>• Cegah simpan pemakaian > alokasi di Domain Service.<br>• Bangun fitur `Tambah Bahan dari Gudang` (`MaterialAllocationService::allocateAdditionalMaterial`) dengan StockMovement OUT dan guard kecukupan stok gudang (no negative stock).<br>• Perbarui antarmuka form hasil produksi, detail batch, detail MRP gudang, dan mutasi stok.<br>• Jaga invariant NO DOUBLE STOCK OUT. | Rekonsiliasi bahan aktual dan gudang berjalan presisi, sisa bahan otomatis kembali ke stok, tambahan bahan tercatat aman, jejak mutasi lengkap, seluruh test suite lulus 100%. |

| **2026-10-07** | Keuangan & Harga | Implementasi Revisi Pembimbing Tahap 3: HPP / Modal vs Harga Jual (Penghitungan HPP Realisasi dari Pembelian Riil MWA, Integrasi Multi-Alokasi & Return Bahan Tahap 2, HPP per Good Unit QC, Pemisahan Estimasi vs HPP Aktual, Indikator Warning Harga Jual <= HPP, dan Hak Akses Finansial) | Kerancuan konsep HPP sebagai harga jual vs modal; multialokasi dan return bahan Tahap 2 berpotensi salah hitung biaya batch; pembagi HPP sempat menggunakan actual bukan Good; harga jual rentan di-markup otomatis atau menimpa order historis | Belum adanya layer penghitungan biaya bahan multi-issue di HPP service; estimasi BOM belum terpisah dari HPP historis di view master; validasi harga jual vs modal belum memiliki visual warning di form order Admin | • Perbarui BatchCostCalculationService dengan multi-issue layer FIFO (alokasi awal + tambahan, return bahan tidak dibebankan).<br>• Hitung HPP per Good unit lolos QC.<br>• Tampilkan status 'HPP Belum Final' jika belum selesai (tidak false Rp 0).<br>• Bangun VariantCostCalculationService untuk estimasi BOM vs HPP aktual terakhir.<br>• Terapkan visual warning guard saat harga jual <= HPP/modal tanpa auto-markup.<br>• Pertahankan snapshot harga order.<br>• Tambahkan ringkasan keuangan pesanan di Admin Show.<br>• Isolasi data HPP dari Customer dan Public Tracking. | Seluruh 20 requirements Revisi Pembimbing Tahap 3 terpenuhi 100%, 1.007 tests (4.864 assertions) lulus tanpa kegagalan. |

| **2026-10-07** | Keuangan Admin | Implementasi Revisi Pembimbing Tahap 4: Catatan Keuangan Admin (Uang Masuk, Uang Keluar, Beban Usaha, Arus Kas Bersih Operasional, Integrasi Otoritatif Non-Akuntansi Ganda, dan Opsi A Pengadaan) | Admin kesulitan melihat rekap uang masuk harian dari pembayaran order, uang keluar untuk beban operasional, dan arus kas bersih tanpa modul akuntansi penuh yang rumit | Belum tersedianya konsolidasi catatan kas operasional untuk Admin; pembayaran terkonfirmasi dan beban usaha tersebar terpisah; risiko salah mengartikan arus kas sebagai laba bersih | • Bangun `FinancialRecordService` yang mengonsolidasi pembayaran customer berstatus CONFIRMED (Uang Masuk) dan BusinessExpense (Uang Keluar).<br>• Terapkan Opsi A: PO bahan baku tidak dihitung uang keluar prematur karena sistem tidak memiliki buku kas supplier (biaya material dikapitalisasi ke stok MWA & HPP).<br>• Hitung Arus Bersih Operasional (`Total Masuk - Total Keluar`) dengan BCMath desimal presisi tinggi dan larang keras pelabelan sebagai Laba Bersih.<br>• Integrasikan CRUD Beban Usaha langsung dari shortcut Catatan Keuangan dengan redirect balik.<br>• Sediakan filter tanggal, jenis arus kas, dan pencarian referensi.<br>• Lindungi audit trail (tanpa delete fisik beban).<br>• Terapkan isolasi role (Admin boleh, Gudang/Produksi/Customer 403 Forbidden). | Catatan keuangan operasional Admin aktif dengan visibilitas uang masuk & keluar harian yang presisi, 1.015 tests (4.938 assertions) lulus 100% tanpa kegagalan. |

| **2026-10-07** | Keuangan Operasional | Implementasi Revisi Pembimbing Tahap 4B: Ringkasan Keuangan Operasional Admin (Penjualan, Kas, Pembayaran PO Supplier, Analisis Kecukupan Kas Pelunasan PO, Saldo Awal Kas, dan Pemisahan Modal/HPP) | Admin membutuhkan gambaran keuangan operasional ringkas: total penjualan & sisa tagihan customer, saldo kas tersedia vs saldo awal, kemampuan kas melunasi PO supplier, serta modal produksi (HPP) tanpa membuat sistem akuntansi penuh | Sistem sebelumnya belum memiliki pencatatan saldo awal kas dan pembayaran supplier riil (PurchasePayment); belum ada komputasi piutang penjualan (Belum Dibayar Customer) dan kecukupan kas terhadap sisa utang PO; HPP belum dirangkum di Catatan Keuangan | • Rancang entitas aditif `OpeningCashBalance` untuk menetapkan likuiditas awal operasional perusahaan.<br>• Rancang entitas aditif `PurchasePayment` untuk mencatat pembayaran kas supplier riil per PO (mendukung pembayaran bertahap/parsial, guard amount <= outstanding, guard PO non-cancelled, tanpa efek samping terhadap stok/MWA).<br>• Hitung Formula Kas Tersedia: `Saldo Awal + Total Masuk - Total Pembayaran PO - Total Beban Usaha` (BCMath).<br>• Bangun Analisis Kecukupan Kas PO: komputasi otomatis status `KAS CUKUP` (proyeksi sisa kas) vs `KAS TIDAK CUKUP` (kekurangan dana) sebagai informasi peringatan transparan.<br>• Sajikan Catatan Penjualan: Total Penjualan (order deal/komersial), Sudah Dibayar (confirmed payments), dan Belum Dibayar Customer.<br>• Integrasikan Modal Produksi (HPP) menggunakan engine kanonikal Tahap 3 (menampilkan count 'HPP Belum Final' jika masih berjalan).<br>• Pertahankan ledger Tahap 4A dan kartu Arus Bersih Operasional.<br>• Lindungi keamanan otorisasi: Admin penuh, Gudang/Produksi/Customer/Publik terisolasi. | Ringkasan Keuangan Operasional Admin selesai sempurna, 1.023 tests (5.021 assertions) lulus 100% tanpa kegagalan. |

| **2026-10-08** | Keuangan Operasional | Koreksi Tahap 4B: Financial Cut-Off, Historical Supplier Payment, & Hard Cash Guard | Transaksi historis sebelum saldo awal berisiko terhitung ganda ke kas saat ini; PO lama sebelum fitur ada tidak dapat direkonsiliasi tanpa mengganggu kas berjalan; pembayaran supplier dapat melebihi kas riil | Saldo awal belum menerapkan semantik batas tanggal (*cut-off*); asumsi implicit PO lama belum terbayar; ketiadaan proteksi saldo kas operasional di sisi server | • Tetapkan semantik batas tanggal: Saldo Awal merepresentasikan posisi kas pada awal hari `opening_date`. Hanya transaksi berpenanggalan bisnis $\ge$ `opening_date` yang memengaruhi Kas Tersedia.<br>• Rekonsiliasi PO historis: Izinkan pencatatan pembayaran supplier historis (`payment_date < opening_date`) untuk melunasi utang PO lama tanpa memotong Kas Tersedia berjalan.<br>• Enforce Server-Side Hard Cash Guard: Pembayaran supplier pada/setelah cut-off dibatasi `min(outstanding, current_cash)` dengan row-level lock (`lockForUpdate`), kas dilarang menjadi negatif.<br>• Status Belum Ada Saldo Awal: Kas Tersedia menjadi `null` ("Belum tersedia"), analisis kecukupan PO menjadi `BELUM_DAPAT_DIHITUNG`, dan pembuatan pembayaran supplier diblokir. | Integritas kas operasional terproteksi mutlak tanpa risiko double-counting, PO lama dapat direkonsiliasi akurat, seluruh 1.029 tests (5.092 assertions) lulus 100%. |

| **2026-10-08** | Procurement & Keuangan | Koreksi Purchase Lifecycle: Pembayaran Supplier Sebelum Penerimaan Material (*Payment-Before-Receive*) | Risiko penerimaan material gudang terjadi sebelum supplier dibayar; antarmuka gudang mengizinkan penerimaan tanpa verifikasi pelunasan; pencatatan pembayaran supplier berpotensi tertukar dengan penerimaan stok | Alur sebelumnya belum menempatkan pelunasan pembayaran supplier sebagai prasyarat wajib sebelum material dapat diterima | • Tegaskan siklus otoritatif: DRAFT → ORDERED → SUPPLIER PAYMENT → FULLY PAID → RECEIVE → STOCK IN.<br>• Enforce server-side guard pada `PurchaseReceivingService`: PO wajib berstatus `ORDERED` dan `isFullyPaid() === true` (`outstanding == 0.00`), tolak penerimaan jika belum lunas.<br>• Pertahankan isolasi finansial vs inventori: Pembayaran mengurangi kas (tanpa mutasi stok/MWA); Penerimaan menambah stok & MWA (tanpa mutasi kas).<br>• Tampilkan status finansial di detail PO Gudang secara read-only (Total, Terbayar, Sisa, Badge) tanpa mengekspos saldo kas perusahaan.<br>• Kunci tombol "Terima Barang" (disabled + helper) hingga pembayaran lunas.<br>• Lindungi data PO historis `RECEIVED` tanpa rollback. | Alur procurement dan keuangan terpadu kokoh sesuai fakta bisnis konveksi, penerimaan terlindungi pelunasan pembayaran, seluruh 1.051 tests (5.199 assertions) lulus 100%. |

| **2026-10-08** | Keuangan & Harga | Pre-UAT Final HPP Price Rule Verification + Audit Existing Variant Prices | Potensi salah interpretasi bahwa harga master harus sama persis dengan harga transaksi SO, atau harga lama ditimpa otomatis | Master price sebelumnya dianggap kaku atau harga varian lama berisiko ditimpa otomatis saat sistem diuji coba | • Tegaskan master price adalah default/reference price (transaksi SO fleksibel selama > Reference HPP).<br>• Enforce audit read-only 5 status: VALID, PERLU REVISI, BELUM DAPAT DIVERIFIKASI, HARGA BELUM DITETAPKAN (tanpa auto-overwrite).<br>• Blokir order baru jika HPP variant belum tersedia.<br>• Pertahankan imutabilitas harga SO historis.<br>• Tambahkan feature test komprehensif PreUatHppPriceRuleAuditTest (17 skenario). | Status harga 24 varian existing terverifikasi aman (22 VALID, 2 HARGA BELUM DITETAPKAN, 0 PERLU REVISI), aturan harga jual dan HPP terproteksi penuh di seluruh modul. |

| **2026-10-08** | Sales Order & UX | Perbaikan UX Tombol Kembali pada Detail Sales Order Status KOMUNIKASI | Admin salah input atau tidak sengaja menekan Lanjut/Simpan pada form SO, saat menekan tombol Kembali malah terlempar ke daftar pesanan | Tombol Kembali di detail order sebelumnya selalu mengarah ke `admin.orders.index` untuk seluruh status tanpa membedakan tahap KOMUNIKASI | • Ubah tombol Kembali pada status KOMUNIKASI menjadi `Kembali Edit Order` yang mengarah ke `admin.orders.edit` order yang sama.<br>• Data existing prefilled lengkap (customer, tanggal, catatan, varian, qty, harga transaksi).<br>• Simpan edit mempertahankan ID & order_no tanpa duplikasi OrderItem.<br>• Status tetap KOMUNIKASI, Konfirmasi Deal tetap aksi terpisah.<br>• Status non-KOMUNIKASI tetap kembali ke index.<br>• Tambahkan feature test `OrderKomunikasiBackCorrectionTest` (7 skenario). | Alur input dan koreksi Sales Order lancar dan aman dari salah input, data order dan item terjaga presisi. |
| **2026-10-08** | Sales Order UX | Eliminasi tombol Kelola Pembayaran duplikat pada area Batch Produksi | Duplikasi tombol [Kelola Pembayaran] pada dua seksi sekaligus (Batch Produksi & Tagihan) membingungkan Admin | Seksi batch memiliki tautan pembayaran yang redundan dengan seksi Tagihan/Invoice | Hapus tombol Kelola Pembayaran dari area Batch Produksi, pusatkan satu aksi di seksi Tagihan/Invoice | Antarmuka detail Sales Order bersih, terfokus, dan mengikuti prinsip Single Source of Action |
| **2026-10-08** | Sales Order UX | Final Cleanup Area Batch Produksi — Eliminasi Seluruh Teks & Callout Menunggu DP | Area Batch Produksi masih menampilkan callout kuning dan teks "Menunggu DP" / "Menunggu Pembayaran DP" | Asumsi lama bahwa seksi batch perlu menjelaskan alasan komersial belum dapat membuat batch | Hapus seluruh visual/teks terkait DP dari seksi Batch Produksi (callout kuning, teks menunggu DP); tombol Buat Batch tetap disabled dengan tooltip netral jika syarat DP belum dipenuhi; pertahankan hard server-side DP gate | Area Batch Produksi fokus murni pada kuantitas fisik dan status produksi; seluruh urusan pembayaran customer tertata rapi di seksi Tagihan / Invoice |
| **2026-10-09** | Executive & Finance | Implementasi & Finalisasi Ringkasan Keuangan Owner Read-Only + Formula Authoritative ROI Sesuai Arahan Pembimbing | Perbedaan interpretasi peran Owner vs Admin dalam keuangan, dan formula ROI teoritis lama (Net Profit / Cost) tidak sesuai definisi pembimbing yang berbasis arus kas riil | Kebutuhan pembimbing agar Owner hanya memantau secara read-only tanpa input transaksi, serta ROI mengukur perbandingan Pendapatan Diterima terhadap Total Pengeluaran kas riil | • Rancang Ringkasan Keuangan Owner 10 metrik terstruktur secara read-only tanpa tombol aksi mutasi.<br>• Implementasikan formula ROI Authoritative: (Pendapatan Diterima / Total Pengeluaran) * 100% menggunakan BCMath.<br>• Tangani Zero-Denominator: ROI null -> "Belum dapat dihitung" tanpa division by zero.<br>• Cegah double-counting HPP pada penyebut pengeluaran kas.<br>• Sediakan filter periode lengkap (today, last_7_days, this_month, last_month, all, custom).<br>• Terapkan isolasi role (Owner & Admin permitted, Gudang/Produksi/Customer 403 Forbidden). | Ringkasan Keuangan dan ROI Owner 100% selaras dengan arahan pembimbing, data historis dan saldo awal Rp100.000.000 terjaga utuh, 1.157 tests lulus tanpa kegagalan. |
| **2026-10-09** | Executive & Finance | Pre-UAT Audit Owner Financial Summary: Semantik Piutang Pelanggan Lintas Periode & Label Kas Saat Ini | Potensi distorsi piutang pada kasus lintas periode (order September, pembayaran Oktober) akibat formula agregat `sales - receipts`, serta kerancuan istilah Kas Tersedia yang disangka metrik periodik semu | Rumus sebelumnya mengurangkan penerimaan kas periode dari omzet periode tanpa mengikat ke invoice terkait; label "Kas Tersedia" belum menegaskan posisi kas mutlak terkini | • Ubah kalkulasi Piutang Pelanggan di `PeriodFinancialService` menjadi berbasis invoice/order outstanding riil ($\sum(\text{Total Invoice} - \text{Pembayaran CONFIRMED})$) untuk pesanan dalam periode.<br>• Pembayaran PENDING/CANCELLED diabaikan dari pengurang piutang.<br>• Kasus lintas periode aman 100% tanpa distorsi omzet September vs penerimaan Oktober.<br>• Perbarui label antarmuka: "Kas Tersedia" $\to$ "Kas Saat Ini", helper: "Saldo kas operasional terkini".<br>• Tambahkan regression test Case 30 di `OwnerFinancialSummaryFeatureTest`. | Piutang pelanggan presisi secara otoritatif, bebas distorsi lintas periode, 1.158 tests lulus 100% tanpa kegagalan. |

---



## 2. Catatan Rinci Masalah & Solusi Utama (*Known Issues & Resolution*)



### Isu 1: Stok Tidak Boleh Bertambah Saat Pembuatan PO

- **Masalah:** Pada perancangan awal sistem inventori, terdapat kecenderungan untuk langsung mencatat penambahan stok begitu surat pesanan pembelian (*Purchase Order*) dibuat atau disetujui.

- **Dampak:** Terjadi penggelembungan stok semu (*phantom stock*) di gudang. Operator gudang melihat bahan baku seolah-olah sudah ada di rak, padahal fisik barang masih di perjalanan atau belum dikirim oleh pemasok.

- **Solusi yang Diterapkan:**

  - Pembuatan PO (`DRAFT`) dan pengiriman PO (`ORDERED`) dipastikan bernilai netral terhadap persediaan gudang (`qty_on_hand` tidak bertambah).

  - Penambahan stok fisik gudang secara ketat **hanya terjadi saat konfirmasi Penerimaan Barang fisik (*Receiving*)**.



---



### Isu 2: Pencegahan Penerimaan Ganda (*Double Receiving*)

- **Masalah:** Jika koneksi jaringan melambat atau operator gudang melakukan klik ganda (*double click*) pada tombol "Terima Barang", aplikasi berpotensi menjalankan fungsi penerimaan sebanyak dua kali untuk satu PO yang sama.

- **Dampak:** Saldo persediaan gudang bertambah dua kali lipat dari barang yang sebenarnya diterima, merusak akurasi stok fisik dan nilai persediaan rata-rata berjalan (MWA).

- **Solusi yang Diterapkan:**

  - Metode `receivePurchase` pada [`PurchaseReceivingService`](file:///d:/Magang/hijab/app/Services/Inventory/PurchaseReceivingService.php) dibungkus dalam transaksi database dengan row-lock pesimis (`lockForUpdate()`).

  - Validasi status mutlak: Hanya PO dengan status `ORDERED` yang diizinkan untuk diterima. Jika status telah berpindah menjadi `RECEIVED`, eksekusi kedua akan langsung ditolak dengan pesan kesalahan (*DomainException*).



---



### Isu 3: Input Biaya Master Varian Produk Bersifat Opsional

- **Masalah:** Pada sistem konveksi hijab Make-to-Order, Admin seringkali merancang desain motif hijab baru atau memperkenalkan katalog warna sebelum mengetahui siapa penjahit yang akan mengerjakan atau berapa tarif ongkos jahit yang disepakati untuk pesanan tertentu di masa depan. Jika form Create Varian mewajibkan input tarif ongkos kerja dan kemasan, proses katalogisasi terhambat.

- **Solusi yang Diterapkan:**

  - Kolom tarif langsung pada `product_variants` dijadikan `NULLABLE` (`direct_labor_cost_per_unit`, `printing_cost_per_unit`, `packaging_process_cost_per_good_unit`, `other_direct_cost_per_unit`).

  - Titik validasi kelengkapan biaya dipindahkan ke saat **Admin membuat Batch Produksi** di [`BatchService`](file:///d:/Magang/hijab/app/Services/Production/BatchService.php). Sistem menolak pembuatan batch jika tarif varian belum dilengkapi, sehingga menjamin bahwa seluruh batch yang berjalan memiliki snapshot biaya yang valid tanpa membatasi fleksibilitas katalog master.



---



### Isu 4: Semantik Kuantitas Layak Jual (*Good Quantity*) vs Biaya Kemasan

- **Masalah:** Terdapat kerancuan terminologi pada kolom tarif biaya kemasan, di mana label form sempat tertulis menyerupai input kuantitas barang jadi (*Good pcs*), padahal varian master belum diproduksi dan belum memiliki barang fisik.

- **Solusi yang Diterapkan:**

  - Penegasan semantik arsitektur: Kuantitas barang jadi yang layak jual (*Good Quantity*) **sepenuhnya merupakan hasil observasi fisik pada tahap Quality Control (QC)** setelah proses jahit selesai.

  - Nilai pada master varian ditegaskan sebagai **Tarif Biaya Proses Pengemasan per Unit Good** (*Packaging Process Cost Rate per Good Unit*), yang nantinya akan dikalikan dengan realisasi `good_qty` hasil QC saat menghitung HPP akhir batch.



---



### Isu 5: Otomasi SKU Human-Readable yang Semantik

- **Masalah:** Input SKU manual berisiko tinggi terhadap salah ketik, nomor ganda, dan kebingungan Admin saat varian produk bertambah banyak. Namun, generator otomatis standar yang menghasilkan kode acak seperti `SKU-000001` tidak memiliki arti bisnis dan menyulitkan operator gudang saat membaca label fisik kain/hijab.

- **Solusi yang Diterapkan:**

  - Membangun konvensi SKU semantik NAYARA:

    $$\text{Format: } \mathbf{HJB\text{-}[PRODUCT\_CODE]\text{-}[VARIANT\_CODE]\text{-}[SEQUENCE]}$$

    Contoh: `HJB-VP-DP-001` (*Hijab - Voal Premium - Dusty Pink - Varian 001*).

  - Pembuatan sequence nomor urut dikunci per produk menggunakan database lock untuk menjamin integritas keunikan dan mencegah nomor loncat saat banyak transaksi bersamaan.



---



### Isu 6: Rekomendasi Kode Produk Semantik (`VP` = Voal Premium)

- **Masalah:** Algoritma pembuat singkatan otomatis awal mengambil huruf depan dari kata produk "Hijab Voal Polos Premium" dan menghasilkan `VP` dengan logika "Voal + Polos". Secara bisnis NAYARA, `VP` harus merepresentasikan "Voal Premium" (menonjolkan kelas bahan premium, bukan polos).

- **Solusi yang Diterapkan:**

  - Sistem tidak lagi memaksakan singkatan otomatis secara tertutup saat menyimpan produk.

  - Pada halaman Create Produk, sistem menampilkan saran singkatan semantik (`VP`) pada antarmuka, namun **memberikan hak kepada Admin untuk mengonfirmasi atau menyesuaikan kode produk tersebut sebelum disimpan pertama kali**. Setelah disimpan, kode produk bersifat permanen (*immutable*) untuk menjaga keutuhan jejak audit SKU.



---



### Isu 7: Pemisahan Ketat Peran Pembuat Batch Produksi

- **Masalah:** Dalam alur pabrikasi Make-to-Order yang tertib, divisi Gudang dan divisi Produksi tidak boleh sembarangan membuat surat perintah kerja (Batch) sendiri tanpa adanya pesanan penjualan yang sah (*DEAL*).

- **Solusi yang Diterapkan:**

  - Route pembuatan batch (`/orders/{order}/batches/create` dan `store`) secara eksklusif ditempatkan di bawah kendali **ADMIN**.

  - Route Gudang dan Produksi diverifikasi 100% bersih dari fungsi pembuatan batch. Gudang bertugas menyiapkan bahan berdasarkan batch yang diterbitkan Admin, dan Produksi bertugas mengeksekusi jahit sesuai kuantitas target batch tersebut.



---



### Isu 8: Alur Pemesanan WhatsApp & Portal Customer Langganan

- **Masalah:** Sempat terjadi salah tafsir yang menganggap bahwa sistem ERP harus menyediakan form keranjang belanja (*e-commerce checkout*) atau formulir pengajuan Sales Order mandiri oleh pelanggan di Customer Portal.

- **Akar Masalah:** Kurangnya pembedaan antara proses bisnis maklun konveksi konvensional dengan portal retail digital.

- **Solusi yang Diterapkan:**

  - Menegaskan dua kondisi pelanggan:

    1. **Customer Biasa / Baru:** Berkomunikasi dan memesan melalui WhatsApp; Admin yang mencatat dan membuat Sales Order di sistem.

    2. **Customer Langganan:** Memiliki hak akses login ke Customer Portal khusus untuk melacak progres, memeriksa tagihan, dan melihat nomor resi pengiriman (*Tracking & History*).

  - Alur otoritatif menetapkan bahwa **Sales Order tetap 100% dibuat oleh Admin** berdasarkan kesepakatan harga dan spesifikasi di WhatsApp.



---



### Isu 9: Eliminasi Alur Rework & Penegasan Reject sebagai Cacat Final

- **Masalah:** Proposal akademik awal sempat menyebut frasa "Good / Reject / Rework". Muncul pertanyaan apakah sistem perlu membangun antrean pengerjaan ulang (*Rework Queue*) dan pengujian QC ulang.

- **Akar Masalah:** Kerancuan antara industri garmen massal skala pabrik besar dengan konveksi hijab Make-to-Order. Pada jilbab/hijab, kain yang cacat printing atau salah potong/jahit tidak dapat dibongkar kembali secara rapi tanpa merusak serat kain halus (voal/silk).

- **Solusi yang Diterapkan:**

  - Mengunci aturan bisnis final: **Zero Rework (Reject adalah Cacat Final)**.

  - Produk cacat langsung dibukukan sebagai `reject_qty` afkir yang tidak mengurangi HPP total batch.

  - Mempertahankan invarian matematis: $\text{good\_qty} + \text{reject\_qty} == \text{actual\_qty}$.



---



### Isu 10: Penegakan Syarat Pelunasan Pembayaran Sebelum Pengiriman (*Shipment Payment Gate*)

- **Masalah:** Pada Kanban Admin, pesanan `SO-202609-0001` (Toko Aisyah Hijab) yang baru membayar uang muka / DP (status invoice: `PARTIAL` / "Bayar Sebagian") telah muncul pada kolom `DIKIRIM`.

- **Akar Masalah:**

  1. Pada implementasi awal `ShipmentService::getEligibility()`, sistem hanya memeriksa pemenuhan teknis fisik produksi (`status === OrderStatus::PACKING`, seluruh batch `SELESAI`, dan akumulasi Good Qty QC $\ge$ Qty Dipesan). Validasi kelayakan komersial (status pelunasan invoice) belum disertakan.

  2. Fungsi pembuatan shipment (`createShipment`), eksekusi pengiriman (`markAsShipped`), dan transisi order (`OrderStatusService::transitionToDikirimFromShipment`) belum memiliki server-side guard untuk memverifikasi bahwa invoice telah lunas sebelum order berpindah ke status `DIKIRIM`.

  3. Akibatnya, operator Produksi dapat menyiapkan shipment dan mengisi resi kurir segera setelah packing selesai, meskipun customer baru membayar DP.

- **Solusi yang Diterapkan:**

  1. **Pemisahan Konseptual:** Menyelesaikan produksi dan packing (`isProductionFulfilled`) hanya membuktikan bahwa barang sudah siap secara fisik di area kemas, **bukan izin komersial untuk mengirimkan barang**.

  2. **Definisi Otoritatif LUNAS:** Menambahkan `InvoiceService::isOrderFullyPaid(Order $order)` dengan perhitungan string desimal aman `BCMath` (`calculateOutstandingBalance($invoice) === '0.00'`). Hanya pembayaran berstatus `CONFIRMED` yang dihitung; status `PENDING` atau `CANCELLED` tidak diakui sebagai lunas.

  3. **Backend Guard Server-Side:**

     - Pada `ShipmentService::getEligibility`: Menambahkan kunci `$isPaymentSettled`. Jika invoice belum ada atau belum lunas, `$isEligible` bernilai `false` dengan pesan penolakan yang ramah: `"Pesanan belum dapat dikirim karena pembayaran belum lunas."`

     - Pada `ShipmentService::createShipment`: Menolak pembuatan draf shipment jika belum lunas dengan melempar `DomainException`.

     - Pada `ShipmentService::markAsShipped`: Memverifikasi ulang status pelunasan di bawah row lock pesimis database.

     - Pada `OrderStatusService::transitionToDikirimFromShipment`: Memblokir langsung setiap upaya perpindahan status ke `DIKIRIM` jika pesanan belum lunas.

  4. **Antarmuka Kanban Admin:** Order yang sudah selesai packing namun pembayarannya belum lunas tetap berada di kolom **PACKING** dengan badge `"Inv: Bayar Sebagian"` dan badge peringatan `"Menunggu Pelunasan"`. Order baru berpindah ke kolom **DIKIRIM** setelah shipment benar-benar dibuat dan dikirimkan oleh Produksi.

  5. **Antrean Pengiriman Produksi:** Pada tab "Antrean Siap Kirim" (`/produksi/shipments`), pesanan yang belum lunas menampilkan status `"Menunggu Pelunasan"` dan tombol aksi `"Siapkan Pengiriman"` dinonaktifkan (*disabled*) dengan ikon gembok terkunci.

  6. **Customer Tracking & Portal:** Halaman pelacakan publik (`/track/{token}`) dan Customer Portal secara konsisten menampilkan progres pesanan berhenti pada tahap **"Pengemasan Produk"**, dan tidak melompat ke tahap "Pesanan Dikirim".

  7. **Koreksi Data Development:** Data pesanan manual `SO-202609-0001` dikembalikan secara bersih ke status `PACKING` (shipment prematur dan riwayat `DIKIRIM` dihapus tanpa mengubah HPP, alokasi material, maupun database seed).



---



### Isu 11: Perbaikan Token Pelacakan Publik Permanen & Pencegahan Token Salah Dianggap Expired

- **Masalah:** Tautan pelacakan publik (`/track/{token}`) yang sebelumnya valid untuk Sales Order `SO-202609-0001` (Toko Aisyah Hijab) menghasilkan respon HTTP 404 dengan pesan *"Token tracking tidak valid atau sudah kedaluwarsa."*.

- **Akar Masalah:**

  1. Pada implementasi awal `TrackingTokenService::createForOrder()`, fungsi tersebut secara tanpa syarat mengeksekusi invalidasi token aktif lama (`OrderTrackingToken::where('order_id', $order->id)->whereNull('revoked_at')->update(['revoked_at' => now()])`) setiap kali dipanggil, tanpa memeriksa apakah pesanan sudah memiliki token aktif.

  2. Saat eksekusi skrip pengujian UI verifikasi sebelumnya (`verify_ui_render.php`), method `createForOrder($order)` terpanggil pada `SO-202609-0001` (Order ID: 1), sehingga token asli otoritatif (ID 1, hash `6ad109c7...199181ae`) yang sebelumnya dipegang oleh customer/user tertandai `revoked_at = 2026-09-29 17:18:51` dan menerbitkan token baru (ID 2).

  3. Ketika tautan asli dibuka di browser, `findValidToken()` menolak token karena `revoked_at !== null`, dan route closure di `routes/web.php` menampilkan pesan kesalahan default yang menyebut frasa "atau sudah kedaluwarsa", padahal token tersebut memiliki `expires_at = NULL` dan tidak pernah kedaluwarsa karena waktu.

- **Solusi yang Diterapkan:**

  1. **Enforce Never-Expiring Semantics:**

     - Menegaskan bahwa `expires_at = NULL` berarti token permanen dan valid selamanya selama Sales Order masih ada di sistem.

     - Menambahkan query scope `scopeValid()` pada model `OrderTrackingToken` yang memastikan token tanpa `expires_at` (`whereNull('expires_at')`) atau waktu kedaluwarsa di masa depan (`where('expires_at', '>', now())`) dan belum direvoke (`whereNull('revoked_at')`) selalu diakui valid.

     - Memperbarui `findValidToken()` untuk memastikan token hanya valid bila Sales Order terkait masih ada.

  2. **Enforce Token Immutability:**

     - Menjadikan `createForOrder()` aman dan idempoten: jika order sudah memiliki token aktif (`$order->hasActiveTrackingToken()`), fungsi melempar `DomainException` dan mencegah mutasi/revokasi yang tidak disengaja.

     - Menetapkan bahwa regenerasi dan revokasi token hanya dapat dilakukan secara sengaja melalui `regenerateForOrder()`, yang terhubung langsung ke tombol konfirmasi eksplisit Admin di Order Detail Hub.

     - Memastikan seluruh transisi status pesanan (`KOMUNIKASI` s.d. `SELESAI`), pembuatan batch produksi, MRP, pemotongan stok, penerbitan invoice, pembayaran (DP/lunas), packing, maupun pengiriman tidak mengubah ataupun merevoke tracking token.

  3. **Penyempurnaan Pesan UI (404 Copy):**

     - Mengubah pesan 404 pada `routes/web.php` dari `"Token tracking tidak valid atau sudah kedaluwarsa."` menjadi `"Link tracking tidak valid atau pesanan tidak ditemukan."` guna menghilangkan kesan palsu bahwa sistem menerapkan kedaluwarsa waktu otomatis.

     - Menambahkan penanganan tampilan visual ramah jika sewaktu-waktu order berstatus dibatalkan tanpa mengembalikan 404 error.

  4. **Restorasi Bersih Data SO-202609-0001:**

     - Menghapus token duplikat hasil pengujian (ID 2).

     - Mengembalikan token asli otoritatif (ID 1, hash `6ad109c7...199181ae`) menjadi aktif (`revoked_at = NULL`, `expires_at = NULL`).

     - Mengembalikan data pesanan development ke kondisi baseline resmi: status `PACKING`, invoice `PARTIAL` (DP 50%), 0 pengiriman, dan tanpa mengubah data stok/BOM/HPP.

  5. **Verifikasi & Regresi:**

     - Menambahkan 14 skenario tes baru pada `OrderTrackingFeatureTest` dan `TrackingTokenServiceTest`.

     - Seluruh 878 unit & feature tests PASS dengan 4.242 assertions dan 0 failures.



---



### Isu 12: Penegakan Payment / Down Payment Gate Sebelum Pembuatan Production Batch

- **Masalah:** Pada sistem konveksi Make-to-Order NAYARA, pesanan yang baru disetujui (status `DEAL`) sebelumnya dapat langsung dijadwalkan ke proses produksi fisik (pembuatan `ProductionBatch`) tanpa memverifikasi apakah pelanggan telah membayar uang muka (Down Payment).

- **Akar Masalah:**

  1. `BatchService::canCreateForOrder()` dan `canCreateForOrderItem()` sebelumnya hanya mengecek status pesanan (`OrderStatus::DEAL` atau `OrderStatus::BAHAN`) dan sisa kuantitas pemesanan, tanpa memeriksa status faktur komersial maupun riwayat pembayaran.

  2. Pada antarmuka Admin Order Detail Hub (`admin/orders/show.blade.php`), tombol aksi "Buat Batch Produksi" di header dan tombol "+ Buat Batch" pada kartu per-item langsung aktif begitu pesanan masuk ke status `DEAL`.

  3. Kurangnya server-side guard pada `BatchService::createBatch()`, sehingga crafted POST langsung ke endpoint pembuatan batch dapat lolos meskipun order belum memiliki invoice atau pembayaran sama sekali.

- **Solusi yang Diterapkan:**

  1. **Definisi Otoritatif DP (Down Payment Gate):**

     - Menambahkan fungsi authoritative `InvoiceService::hasConfirmedDownPayment(Order $order): bool` dan `InvoiceService::getDownPaymentStatus(Order $order): array`.

     - Order dinyatakan sah memenuhi syarat DP jika dan hanya jika:

       - Memiliki invoice aktif (bukan berstatus `CANCELLED`).

       - Memiliki minimal satu record pembayaran dengan status `CONFIRMED`.

       - Total pembayaran terkonfirmasi $\sum \text{amount} > 0$ dihitung menggunakan presisi tinggi `BCMath` (`bccomp > 0`).

     - Tidak mensyaratkan persentase minimal tertentu (tidak ada batas semu 30% atau 50%).

     - Pembayaran berstatus `PENDING`, `CANCELLED`, atau `REJECTED` **TIDAK** dihitung sebagai DP valid.

     - Pembayaran yang langsung lunas (`PAID`) maupun pembayaran sebagian (`PARTIAL`) dengan confirmed DP $> 0$ sama-sama memenuhi syarat untuk memulai produksi.

  2. **Server-Side Backend Guard:**

     - Pada `BatchService::createBatch()`, ditambahkan validasi server-side tegas: jika order belum memiliki confirmed DP, sistem melempar `DomainException('Batch produksi belum dapat dibuat karena pembayaran DP belum diterima.')`.

     - Pada `BatchService::canCreateForOrder()` dan `BatchService::canCreateForOrderItem()`, ditambahkan pemeriksaan `$this->invoiceService->hasConfirmedDownPayment($order)`.

     - Pada `ProductionBatchController@create`: jika DP belum terkonfirmasi, Admin diarahkan kembali (*redirect*) ke detail pesanan dengan pesan peringatan flash.

     - Pada `ProductionBatchController@store`: penolakan `DomainException` ditangkap dan dialihkan kembali dengan pesan error yang ramah.

  3. **Antarmuka Admin Sales Order Detail Hub:**

     - Tombol "Buat Batch Produksi" di header dan tombol "+ Buat Batch" pada tiap kartu varian dinonaktifkan (*disabled*) jika order belum memiliki confirmed DP.

     - Tampilan badge kontekstual: "Menunggu DP" dan tooltip informatif.

     - Spanduk peringatan alur kontekstual:

       - Jika invoice belum dibuat: menampilkan panduan *"Terbitkan invoice dan konfirmasi pembayaran DP sebelum membuat batch"* dengan tombol cepat *"Terbitkan Invoice Sekarang"*.

       - Jika invoice sudah ada namun belum ada pembayaran: menampilkan *"Menunggu pembayaran DP"* dengan link cepat ke kartu invoice/pembayaran.

       - Jika pembayaran sudah dicatat namun masih PENDING: menampilkan *"Pembayaran DP menunggu konfirmasi"* dengan link langsung untuk meninjau pembayaran.

     - Dekopling evaluasi kapasitas: Pesanan yang belum memiliki DP dan belum pernah dibuatkan batch secara akurat menampilkan status kosong *"Belum ada batch produksi yang dijadwalkan"*, bukan secara keliru menampilkan *"Semua item telah terjadwal"*.

  4. **Pemisahan Tegas Production Gate vs Shipment Gate:**

     - **Izin Produksi:** Cukup confirmed DP $> 0$ (Invoice `PARTIAL` atau `PAID`).

     - **Izin Pengiriman (Shipment):** Tetap mutlak mensyaratkan **LUNAS PENUH** (`InvoiceService::isOrderFullyPaid($order) === true` atau status `PAID`).

     - Produksi dapat berjalan saat DP diterima, namun pengiriman barang tetap terkunci hingga pelunasan selesai.

  5. **Keutuhan Data Historis & Database Safety:**

     - Tidak ada migrasi baru, tidak ada `migrate:fresh`, tidak ada pemotongan tabel (*truncate*).

     - Data development `SO-202609-0001` (Toko Aisyah Hijab) dan `SO-202609-0002` (Toko Fatimah Hijab) tetap utuh bersama seluruh relasi MRP, stok, dan tracking token permanennya.

  6. **Pengujian Menyeluruh:**

     - Dibuat suite pengujian fitur komprehensif `tests/Feature/Admin/ProductionDownPaymentGateTest.php` (14 skenario pengujian, 89 assertions).

     - Seluruh suite pengujian aplikasi (911 tests, 4.376 assertions) lulus 100% tanpa kegagalan.



---



### Isu 13: Penyelarasan Konsistensi Status Pengemasan (PACKING), Siap Dikirim, dan Pengiriman (DIKIRIM)

- **Masalah:** Pada Sales Order `SO-202609-0001` (Toko Aisyah Hijab), pesanan secara operasional telah menyelesaikan seluruh batch produksi (kedua batch berstatus `SELESAI` dan kuantitas Good Qty terpenuhi) serta invoice telah lunas 100% (`PAID`). Di Kanban Admin dan antrean Pengiriman Produksi, pesanan telah tampil berstatus *"Siap Dikirim"*, namun pada pelacakan publik customer (`/track/{token}`) tahapan "Pengemasan Produk" masih menampilkan teks *"Sedang dalam tahapan ini"*. Hal ini menimbulkan inkonsistensi persepsi antara Admin/Produksi dengan Pelanggan.

- **Akar Masalah:**

  1. Komponen antarmuka pelacakan publik (`tracking/show.blade.php`) dan Customer Portal (`customer/orders/show.blade.php`) mengevaluasi status tahapan secara murni dari perbandingan indeks enum `orders.status` (`$order->status === OrderStatus::PACKING`).

  2. Akibatnya, sistem mengasumsikan bahwa selama status order masih bernilai `PACKING`, pengemasan selalu dianggap masih berjalan, tanpa memeriksa apakah seluruh batch fisik sebenarnya sudah selesai (`BatchStatus::SELESAI`) dan barang sudah siap dikirim.

  3. Belum adanya konsep representasi terpadu (*derived fulfillment display state*) yang menjembatani status domain internal (`OrderStatus`) dengan status operasional ramah pengguna.

- **Solusi yang Diterapkan:**

  1. **Konsep & Arsitektur Status Turunan (*Derived Presentation State*):**

     - Membangun metode `ShipmentService::isPackingCompleted(Order $order): bool` yang memvalidasi bahwa seluruh batch non-cancelled berstatus `SELESAI` dan kuantitas pesanan terpenuhi berdasarkan Good Qty QC.

     - Membangun metode `ShipmentService::getFulfillmentDisplayState(Order $order): string` yang mengembalikan salah satu status turunan:

       - `PACKING_IN_PROGRESS`: Order di fase `PACKING`, namun batch fisik pengemasan masih berjalan.

       - `WAITING_PAYMENT`: Pengemasan fisik telah selesai, namun invoice belum lunas (`!isFullyPaid()`).

       - `READY_TO_SHIP`: Pengemasan fisik telah selesai dan pembayaran telah lunas penuh (`isFullyPaid() === true`), namun barang belum diserahkan ke kurir.

       - `SHIPPED`: Barang telah dicatat nomor resi dan dieksekusi `markAsShipped()` oleh Produksi (`orders.status === OrderStatus::DIKIRIM`).

       - `COMPLETED`: Barang telah diterima oleh pelanggan (`OrderStatus::SELESAI`).

     - Meneruskan metode tersebut ke model [`Order`](file:///d:/Magang/hijab/app/Models/Sales/Order.php) (`isPackingCompleted()`, `getFulfillmentDisplayState()`, `getFulfillmentPresentation()`) sebagai single source of truth yang reusable.

  2. **Penyelarasan Konsisten 5 Antarmuka Utama:**

     - **Detail Sales Order Admin:** Menampilkan badge status operasional `Siap Dikirim` (hijau) atau `Menunggu Pelunasan` (kuning) di samping badge fase utama `PACKING`.

     - **Kanban Board Admin:** Menampilkan badge `Siap Dikirim` (jika lunas) atau `Menunggu Pelunasan` (jika bayar sebagian), dan tetap berada di kolom `PACKING` hingga shipment fisik dibuat.

     - **Antrean Pengiriman Produksi:** Tab "Antrean Siap Kirim" menampilkan badge `Siap Dikirim` dengan tombol aksi `Siapkan Pengiriman` aktif jika lunas, atau terkunci (*disabled*) jika belum lunas.

     - **Pelacakan Publik Customer (`/track/{token}`):**

       - Tahap "Pengemasan Produk" berubah menjadi **Selesai** (ikon centang hijau) jika seluruh batch telah selesai.

       - Menampilkan helper box kontekstual: *"Pesanan telah selesai dikemas dan siap dikirim."* saat lunas, atau teks netral *"Pesanan telah selesai dikemas."* saat bayar sebagian (menjaga privasi finansial pelanggan).

       - Tahap "Pesanan Dikirim" tetap berstatus upcoming (abu-abu) dan tidak aktif prematur sebelum pengiriman riil dilakukan.

       - Menampilkan kartu detail ekspedisi (kurir, nomor resi, waktu dikirim) jika shipment telah terbit.

     - **Customer Portal (`/customer/orders/{order}`):** Menampilkan status yang 100% selaras dengan pelacakan publik, dengan pesan ramah pelanggan terotentikasi: *"Pesanan selesai dikemas dan menunggu penyelesaian pembayaran."*

  3. **Penjaminan Integritas Domain & Data:**

     - Tidak ada penambahan kolom atau modifikasi tabel database.

     - Tidak mengubah Payment Gate (DP $> 0$ untuk produksi) maupun Shipment Gate (harus lunas penuh untuk pengiriman).

     - Data historis development `SO-202609-0001` dan `SO-202609-0002` tetap utuh 100%.

  4. **Pengujian Regresi:**

     - Menambahkan suite pengujian komprehensif `tests/Feature/Sales/PackingReadyToShipStatusConsistencyTest.php` (10 skenario pengujian, 58 assertions).

     - Seluruh test suite aplikasi lulus 100% tanpa kegagalan.



---



### Isu 14: Standardisasi Timezone dan Tampilan Jejak Waktu Audit (Asia/Jakarta / WIB)

- **Masalah:** Pada halaman Perencanaan Bahan (`/gudang/material-planning/{id}`), timestamp kalkulasi tertera *"Dihitung pada: 30 September 2026, 04:00"*, padahal waktu operasional lokal saat eksekusi kalkulasi adalah sekitar pukul 11:00 WIB. Selisih tepat 7 jam mengindikasikan bahwa representasi UTC database langsung dicetak ke antarmuka tanpa konversi zona waktu bisnis.

- **Akar Masalah:**

  1. Default konfigurasi `config('app.timezone')` adalah `UTC`, sehingga Laravel menyimpan seluruh timestamp `created_at`, `updated_at`, dan kolom datetime kustom dalam waktu UTC.

  2. Komponen antarmuka Blade memanggil fungsi pemformatan seperti `->translatedFormat('d F Y, H:i')` atau `->format('H:i')` secara langsung dari objek Carbon tanpa mengubah zona waktu ke `Asia/Jakarta`.

  3. Beberapa halaman secara sporadis menambahkan akhiran statis `" WIB"` pada jam yang sebenarnya masih dalam waktu UTC (misalnya `04:00 WIB` atau `06:45 WIB`).

- **Solusi yang Diterapkan:**

  1. **Konfigurasi Zona Waktu Presentasi (`config/app.php`):**

     - Menetapkan `'display_timezone' => env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta')`.

     - Mempertahankan `'timezone' => 'UTC'` untuk menjaga keutuhan penyimpanan (*canonical database storage*), tanpa perlu mengubah baris historis di database.

  2. **Pembangunan Helper Sentral (`App\Support\DateTimeFormatter`):**

     - Menyediakan metode konversi zona waktu yang teruji:

       - `toDisplayTimezone($date)`: Mengonversi waktu objek/string ke `Asia/Jakarta` secara presisi menggunakan metode bawaan Carbon `setTimezone()`.

       - `formatDateTime($date, ...)`: Menghasilkan format `"30 Sep 2026, 11:00 WIB"`.

       - `formatLongDateTime($date, ...)`: Menghasilkan format `"30 September 2026, 11:00 WIB"`.

       - `formatShortDateTime($date, ...)`: Menghasilkan format `"30/09/2026 11:00 WIB"`.

       - `formatTime($date, ...)`: Menghasilkan format jam `"11:00 WIB"`.

       - `formatDateWib($date, ...)`: Menghasilkan tanggal kalender lokal berdasarkan waktu WIB `"30/09/2026"`.

       - `formatDate($date, ...)`: Memformat field DATE-only murni (`orders.order_date`, `purchases.purchase_date`, dll.) tanpa pergeseran zona waktu.

  3. **Pemberlakuan Ketat Tanpa Aritmatika Manual:**

     - **DILARANG** menggunakan manipulasi manual seperti `addHours(7)` atau `+ 7` di seluruh layer aplikasi dan Blade.

     - Melakukan audit menyeluruh dan menyelaraskan antarmuka: MRP show (Dihitung pada, Dialokasikan pada), Riwayat Mutasi Stok, Detail Pembelian & Riwayat Pembelian, Riwayat Status Order Admin, Detail Pengiriman Produksi, Public Tracking, dan Customer Portal.

  4. **Pengujian Komprehensif:**

     - Menambahkan unit test `tests/Unit/DateTimeFormatterTest.php` (7 skenario).

     - Menambahkan feature test `tests/Feature/Gudang/TimezoneAuditDisplayTest.php` (7 skenario).

     - Seluruh pengujian berjalan deterministik dan lulus 100%.



---



### Isu 15: Penyelarasan Terminal Completion State Pesanan Pasca-Penerimaan Pengiriman (Delivered -> Selesai)

- **Masalah:** Pada halaman Public Tracking (`/track/{token}`) dan Customer Portal (`/customer/orders/{id}`), untuk Sales Order yang pengirimannya telah diterima oleh customer (`ShipmentStatus::DELIVERED`), timeline tahap akhir "Pesanan Selesai" menampilkan teks *"Sedang dalam tahapan ini"* dengan lingkaran ring aktif ungu alih-alih berstatus *"Selesai"*. Hal ini membingungkan pelanggan karena status `SELESAI` merupakan tahap terminal/final yang tidak memiliki proses lanjutan.

- **Akar Masalah:**

  1. Komputasi indeks timeline pada `resources/views/tracking/show.blade.php` dan `resources/views/customer/orders/show.blade.php` mengevaluasi status aktif berdasarkan kesamaan indeks (`$stageIdx === $currentIdx`). Karena `SELESAI` adalah indeks terakhir (`8`), tahap tersebut dianggap sedang berjalan (`$isCurrent = true; $isPast = false;`).

  2. Presentasi `ShipmentService::getFulfillmentPresentation()` untuk state `COMPLETED` belum menyertakan semantik eksplisit `timeline_order_completed => true` dan pesan penutupan standar untuk customer.

  3. `OrderStatusService::transitionToSelesaiFromShipment()` selalu menyetel `changed_at = now()` tanpa menyediakan parameter untuk mencatat waktu riil kejadian fisik pengiriman (`delivered_at`).

- **Solusi yang Diterapkan:**

  1. **Preservasi Timestamp Otoritatif & Idempotensi Self-Healing (`OrderStatusService` & `ShipmentService`):**

     - Memperbarui `OrderStatusService::transitionToSelesaiFromShipment()` agar menerima parameter `?CarbonInterface $changedAt = null`, sehingga `OrderStatusHistory.changed_at` mencatat waktu riil `shipments.delivered_at`.

     - Menyematkan logika self-healing pada `ShipmentService::markAsDelivered()`: jika shipment berstatus `DELIVERED` namun status order tertinggal di `DIKIRIM`, eksekusi memulihkan order ke `SELESAI` secara idempoten tanpa duplikasi history.

     - Menyelaraskan teks pesan pelanggan menjadi: *"Pesanan telah selesai dan diterima oleh customer."* serta menambahkan payload flag `'timeline_order_completed' => true`.

  2. **Penyelarasan Tampilan Timeline Terminal (`tracking/show.blade.php` & `customer/orders/show.blade.php`):**

     - Menetapkan aturan semantik terminal: jika `$order->status === OrderStatus::SELESAI` atau `$fulfillment['state'] === 'COMPLETED'`, seluruh tahapan 1 s.d. 9 berstatus selesai (`$isPast = true; $isCurrent = false;`), menampilkan dot hijau `bg-emerald-600` dan label "Selesai".

     - Menambahkan banner kartu konfirmasi penyelesaian pada tahap Pesanan Selesai: *"Pesanan telah selesai dan diterima oleh customer."*.

     - Menambahkan kolom *"Waktu Diterima"* pada kartu rincian ekspedisi di Public Tracking.

  3. **Pengujian Komprehensif:**

     - Menambahkan feature test `tests/Feature/Produksi/OrderDeliveredTerminalStateTest.php` (11 skenario pengujian, 52 assertions) yang memverifikasi alur transisi `markAsShipped`, `markAsDelivered`, idempotensi berulang, preservasi timestamp riwayat status, rendering timeline selesai pada Public Tracking dan Customer Portal, penempatan kolom Kanban `SELESAI`, kartu detail pengiriman tanpa tombol aksi, keabsahan permanent token, serta integritas payment dan DP gate.

     - Seluruh pengujian berjalan deterministik dan lulus 100%.



---



### Isu 16: Penguatan Disiplin Produksi & QC serta Transparansi Progres Batch (Revisi Pembimbing — Tahap 1)

- **Masalah:** Hasil evaluasi pembimbing mengidentifikasi celah operasional dan integritas data pada lantai produksi konveksi:

  1. Operator produksi dan gudang tidak dapat melihat target tanggal selesai produksi (`planned_end_date`) yang ditetapkan Admin, sehingga tidak memiliki acuan tenggat waktu kerja.

  2. Kuantitas hasil produksi aktual (`actual_qty`) dapat diinput melebihi target batch (`target_qty`), berisiko mengacaukan perencanaan bahan dan kapasitas jahit.

  3. Kuantitas reject (`reject_qty`) dan tanggal inspeksi QC (`qc_date`) diisi secara manual oleh staf produksi, berpotensi memicu kesalahan hitung manusia (*human error*) atau manipulasi tanggal mundur (*backdating*).

  4. Pada papan Kanban Admin, penyelesaian satu batch produksi tidak tercermin langsung jika pesanan memiliki batch lain yang masih berjalan (*sibling batch blocking*).

- **Akar Masalah:**

  1. `planned_end_date` telah ada di tabel `production_batches` tetapi bersifat opsional dan tidak ditampilkan pada dashboard/detail Produksi maupun Gudang MRP.

  2. `StoreProductionResultRequest` dan `ProductionResultService` hanya memvalidasi `numeric|min:0.0001` tanpa batasan terhadap `target_qty` batch.

  3. Form QC membuka input manual untuk `reject_qty` dan `qc_date`; database `qc_results` hanya memiliki kolom `qc_date` bertipe `date` (tanpa timestamp jam/menit kejadian).

  4. Kartu order pada Kanban Admin hanya menampilkan status agregat pesanan tanpa rincian progres mandiri dari masing-masing batch yang terkait.

- **Solusi yang Diterapkan:**

  1. **Visibilitas dan Kewajiban Target Tanggal Produksi:**

     - Menjadikan `planned_end_date` wajib diisi saat Admin membuat batch (`StoreProductionBatchRequest`: `'required', 'date', 'after_or_equal:planned_start_date'`).

     - Menampilkan target tanggal selesai secara read-only pada daftar batch produksi, kartu header detail batch produksi, modal input hasil produksi, antarmuka perencanaan bahan gudang, dan detail alokasi gudang.

     - Menyediakan accessor `is_overdue` pada model `ProductionBatch` untuk menandai badge peringatan "Terlambat" (visual-only, tidak memblokir pengerjaan).

  2. **Validasi Kuantitas Aktual Produksi (Actual Qty <= Target Qty):**

     - Menambahkan aturan validasi server-side `lte:target_qty` pada `StoreProductionResultRequest`.

     - Menambahkan domain guard `bccomp($actualQtyStr, (string) $lockedBatch->target_qty, 4) > 0` pada `ProductionResultService` yang melempar `DomainException("Jumlah hasil produksi aktual tidak boleh melebihi target produksi.")`.

  3. **Otomatisasi Hasil QC, Semantik Tanggal QC, & Jejak Audit (Klarifikasi Authoritative Pembimbing):**

     - **Klarifikasi Aturan Tanggal QC**: Pembimbing mengklarifikasi bahwa tanggal QC (`qc_date`) otomatis mengikuti tanggal ketika produksi mulai dibuat/dikerjakan (`planned_start_date` / `result_date`), bukan tanggal saat form disubmit. QC tidak boleh dilakukan lebih awal dari tanggal produksi mulai (`today in Asia/Jakarta >= production_start_date`).

     - **Pemisahan Konsep `qc_date` vs `qc_at`**:

       - `qc_date`: Business QC date yang otomatis mengikuti tanggal pengerjaan produksi (`$batch->getProductionStartDate()`).

       - `qc_at`: Actual audit timestamp (`now()` UTC, display WIB) saat record QC disimpan untuk jejak audit.

       - Invarian: $\text{qc\_date} \ne \text{date}(\text{qc\_at})$ (contoh: produksi mulai 10 Okt 2026, QC disubmit 12 Okt 2026 $\rightarrow$ `qc_date` = 10 Okt 2026, `qc_at` = 12 Okt 2026 actual timestamp).

       - `planned_end_date` adalah deadline penyelesaian dan **bukan** tanggal QC.

     - **Hard Protection Server-Side**:

       - `QcResultService` dan `StoreQcResultRequest` menolak keras upaya submit QC sebelum tanggal produksi (`DomainException: "QC belum dapat dilakukan sebelum tanggal produksi."`).

       - UI mendisable tombol QC jika tanggal produksi belum tercapai dengan pesan `"QC dapat dilakukan mulai [tanggal produksi]."`.

       - Form QC menampilkan kartu informasi read-only: Tanggal Produksi, Tanggal QC ("Otomatis mengikuti tanggal produksi."), dan Waktu Pencatatan QC ("Dicatat otomatis saat QC disimpan.").

     - **Auto-Kalkulasi Reject & Good Guard**:

       - Menghapus input manual `reject_qty`, `qc_date`, dan `qc_at` dari payload form request `StoreQcResultRequest`.

       - Menambahkan validasi server-side `lte:actual_qty` pada `good_qty`.

       - Menghitung kuantitas reject secara otomatis di server-side: $\text{reject\_qty} = \text{actual\_qty} - \text{good\_qty}$. Nilai reject dari klien diabaikan.

       - Menambahkan kolom `qc_at` (timestamp UTC nullable) pada tabel `qc_results` via migrasi aditif `2026_10_07_000001_add_qc_at_to_qc_results_table.php`.

  4. **Progres Batch Independen di Kanban Admin:**

     - Memperkaya kartu pesanan pada Kanban Admin dengan daftar progres seluruh batch (`batch_no`, `status`, target tanggal selesai).

     - Batch yang telah `SELESAI` langsung menampilkan lencana status hijau `SELESAI` pada kartu order tanpa menunggu batch saudara (*sibling batches*), sementara status agregat Sales Order tetap menunggu pemenuhan menyeluruh.

  5. **Pengujian Komprehensif:**

     - Memperluas feature test `tests/Feature/Produksi/ProductionSupervisorRevisionPhase1Test.php` menjadi 24 skenario pengujian (101 assertions) yang mencakup seluruh poin revisi pembimbing dan klarifikasi semantik tanggal QC.

     - Memastikan seluruh test suite aplikasi (964+ tests) lulus 100% tanpa regresi.



---



### Isu 10: Rekonsiliasi Pemakaian Bahan Aktual dan Proteksi Double Stock OUT (Revisi Pembimbing — Tahap 2)

- **Masalah:** Ketika tim Produksi menginput pemakaian bahan riil (`used_qty`), terdapat dua kondisi variansi fisik di lantai produksi:

  1. Bahan yang terpakai lebih sedikit daripada alokasi yang diserahkan gudang (`used_qty < allocated_qty`). Sisa bahan fisik menumpuk di area jahit tanpa kembali ke saldo inventori gudang.

  2. Bahan yang dibutuhkan melebihi alokasi awal (`actual_needed > allocated_qty`). Produksi tidak memiliki mekanisme resmi untuk mengambil tambahan bahan dari gudang, atau berisiko langsung memotong stok tanpa pencatatan alokasi yang sah.

- **Akar Penyebab & Batasan Arsitektur:**

  - Sebelumnya sistem menganggap pemakaian bahan bersifat pasif (hanya mencatat `used_qty` untuk analisis variansi tanpa mutasi persediaan balik).

  - Terdapat risiko pelanggaran fatal: jika pencatatan pemakaian bahan membuat mutasi `OUT`, akan terjadi pemotongan stok ganda (*double stock OUT*) karena bahan sudah dipotong saat alokasi awal di gudang.

- **Solusi yang Diterapkan:**

  1. **Invariant Mutlak NO DOUBLE STOCK OUT:**

     Kuantitas bahan yang telah dialokasikan tidak pernah dipotong kedua kalinya saat pemakaian riil disimpan.

  2. **Otomasi Pengembalian Surplus ke Gudang (Kondisi 1):**

     - Pada saat finalisasi hasil produksi (`ProductionResultService::recordFinalResult`), jika $\text{allocated\_qty} - \text{used\_qty} > 0$, sistem secara otomatis:

       - Menambah saldo fisik gudang (`material_stocks.qty_on_hand`) sebesar kuantitas surplus.

       - Membuat record `StockMovement` bertipe `MovementType::IN` dengan referensi `production_batches` dan catatan `"Pengembalian sisa bahan..."`.

       - **MWA Safety:** Nilai modal per unit (`avg_unit_cost`) gudang TIDAK dihitung ulang seperti pembelian baru, melainkan mempertahankan harga modal rata-rata historis berjalan agar valuasi persediaan tidak terdistorsi.

       - Idempotensi: Transaksi dikunci pesimis (`lockForUpdate()`) dan hanya dapat dieksekusi sekali saat batch berpindah ke status `QC`.

  3. **Alur Tambahan Bahan dari Gudang (Kondisi 2):**

     - Server-side guard: Pemakaian aktual dilarang melebihi kuantitas alokasi saat ini. Upaya simpan langsung diblokir.

     - Form pemakaian menampilkan selisih kekurangan dan tombol `[ Tambah Bahan dari Gudang ]`.

     - Aksi `Tambah Bahan dari Gudang` (`MaterialAllocationService::allocateAdditionalMaterial`) mengunci stok secara pesimis, memvalidasi ketersediaan gudang (`qty_on_hand >= additional_qty`), memotong stok gudang (`StockMovement::OUT` bertipe `production_batches`), dan menambah `allocated_qty` pada batch.

     - **Pencegahan Stok Negatif:** Jika persediaan gudang tidak mencukupi, transaksi diblokir total dengan pesan `"Stok Gudang tidak mencukupi. Kekurangan X [satuan]."`. Sistem tidak mengizinkan stok minus atau membuat PO fiktif secara otomatis.

     - Setelah tambahan berhasil, Produksi dapat memfinalisasi pemakaian aktual sebesar alokasi baru.

  4. **Pembaruan Antarmuka & Real-Time Semantics:**

     - Form hasil produksi menyajikan tabel ringkas: Material, Kebutuhan MRP, Alokasi, Pemakaian Aktual, Selisih, Status (`Akan dikembalikan` / `Tepat sesuai` / `Kekurangan`), dan Tombol Aksi Tambah Bahan.

     - Detail Perencanaan Bahan Gudang (MRP) menampilkan kolom Pemakaian Aktual saat proses produksi telah selesai.

     - Halaman Mutasi Stok membedakan alokasi awal, alokasi tambahan, dan pengembalian sisa bahan dengan jejak audit lengkap (WIB timestamp, aktor, sebelum $\to$ sesudah, referensi batch).

  5. **Pengujian Regresi Komprehensif:**

     - Dibuat test suite `tests/Feature/Produksi/ProductionMaterialReconciliationTest.php` mencakup 17 skenario mendalam (94 assertions) yang menguji normal case, return surplus, MWA safety, idempotency, additional allocation, insufficient stock blocking, multi-material reconciliation, audit trail, dan HPP compatibility. Seluruh 987 tests di aplikasi lulus 100%.



---



### Isu 11: Arsitektur HPP / Modal, Integrasi Rekonsiliasi Bahan Tahap 2, dan Tata Kelola Harga Jual (Revisi Pembimbing — Tahap 3)

- **Arahan Pembimbing:**

  *"HPP = di tampilan yang dihitung dari rincian yang diinputkan (purchase order), HPP alias modal, harga jual lebih mahal dari HPP (harus ada di admin ketika tambah produk dan material)."*

- **Tantangan Rekayasa & Temuan Audit:**

  1. **HPP vs Harga Jual:** HPP adalah biaya produksi internal (modal/cost), bukan harga jual. Nilai modal bahan baku berasal dari rincian harga beli Purchase Order yang diakui saat Penerimaan (*Receiving*) melalui metode Moving Weighted Average (MWA). PO yang belum diterima dilarang dihitung sebagai biaya persediaan aktif.

  2. **Dampak Rekonsiliasi Bahan Tahap 2 terhadap HPP:** Batch produksi kini mendukung multi-alokasi (alokasi awal + tambahan alokasi) serta pengembalian sisa bahan. Jika HPP menghitung seluruh alokasi yang dikeluarkan (misal 105 m) tanpa mengurangi sisa bahan yang dikembalikan (misal 8 m), HPP batch akan tergelembung secara tidak sah (*overcosted*). Selain itu, alokasi tambahan berpotensi terjadi pada snapshot harga modal berbeda dari alokasi awal.

  3. **HPP per Good Unit:** Pembagi biaya produksi batch adalah kuantitas unit yang lolos QC (`good_qty`), bukan target batch atau actual jahit, karena unit reject tidak bernilai jual.

  4. **Pencegahan Nilai Semu Rp0:** Sebelum batch selesai dan QC tuntas, HPP tidak boleh menampilkan Rp0, melainkan status eksplisit `"HPP Belum Final"`.

  5. **Harga Jual vs HPP:** Harga jual adalah keputusan komersial Admin/manajemen (bukan auto-markup sistem). Perlu perlindungan agar Admin mendapat peringatan jelas jika harga jual $\le$ modal/HPP referensi tanpa merusak transaksi menggunakan *hard block* yang kaku.

- **Solusi Rekayasa yang Diterapkan:**

  1. **Multi-Issue Layer Valuation pada `BatchCostCalculationService`:**

     - Mengembangkan metode evaluasi biaya bahan bertingkat (*chronological issue layers FIFO*): Layer 1 (alokasi awal) dan Layer 2..k (alokasi tambahan berurutan).

     - Menghitung biaya hanya atas pemakaian aktual (`used_qty`). Sisa bahan yang kembali ke gudang otomatis tidak dibebankan ke batch.

     - Menggunakan snapshot biaya pengeluaran masing-masing layer secara independen, menjaga presisi mutlak dengan BCMath.

  2. **Layanan Komputasi Biaya Varian `VariantCostCalculationService`:**

     - Memisahkan secara tegas antara **Estimasi Biaya Produksi** (BOM + tarif standar master) dan **HPP Aktual Terakhir** (dari batch produksi historis yang selesai).

     - Menyediakan metode evaluasi margin `evaluatePriceMargin()` dengan pendeteksi `is_below_cost` dan rekomendasi pesan peringatan.

  3. **Perlindungan Margin Warning (Bukan Hard Block):**

     - Di form pembuatan dan pengeditan Sales Order Admin (`admin.orders.create` & `admin.orders.edit`), sistem menampilkan HPP/Modal Referensi, estimasi margin per unit, dan banner peringatan visual jika harga jual $\le$ HPP/modal.

     - Sistem **tidak melakukan auto-markup** dan **tidak memaksakan persentase minimum buatan** (no 10%, 20%, etc.). Snapshot harga transaksi order tetap *immutable*.

  4. **Penyempurnaan Modul Material (Gudang):**

     - Menegaskan bahwa bahan baku adalah inventaris manufaktur, bukan produk ritel (tidak ada harga jual pada material).

     - Menampilkan Modal Rata-rata (MWA), Harga Beli Terakhir dari penerimaan PO, dan Total Nilai Stok.

     - Memastikan pergerakan pengembalian bahan produksi (`StockMovement IN`) tidak muncul di Riwayat Pembelian Gudang (`gudang.purchases.history`).

  5. **Ringkasan Keuangan Pesanan & Keamanan Privasi:**

     - Pada detail Sales Order Admin (`admin.orders.show`), ditampilkan kartu ringkasan keuangan pesanan: Total Pendapatan (*Revenue*), HPP / Total Modal (bersumber dari `OrderProfitabilityService`), dan Laba Kotor (*Gross Profit*).

     - Seluruh data HPP, modal, biaya produksi, dan margin laba diisolasi ketat dari antarmuka Customer Portal dan Public Tracking (`/track/{token}`).

  6. **Pengujian Regresi:**

     - Dibuat test suite baru `tests/Feature/Finance/SupervisorHppAndSellingPriceRevisionTest.php` yang mencakup 20 skenario terverifikasi (57 assertions).

     - Seluruh test suite sistem (1.007 tests, 4.864 assertions) lulus 100% tanpa kegagalan.



---



### Isu 12: Arsitektur Catatan Keuangan Admin: Uang Masuk, Uang Keluar, Beban Usaha, dan Arus Bersih Operasional (Revisi Pembimbing — Tahap 4)

- **Arahan Pembimbing:**

  11. *"Catatan keuangan di Admin (masuk dan keluar)."*

  12. *"Di role Admin ditambahkan beban usaha di catatan keuangan."*

- **Tantangan Rekayasa & Temuan Audit:**

  1. **Bukan Full Accounting System:** NAYARA adalah sistem ERP operasional konveksi hijab, bukan aplikasi akuntansi penuh. Sistem tidak memerlukan jurnal umum, debit/kredit double-entry, buku besar, neraca, atau chart of accounts (COA) kompleks.

  2. **Uang Masuk Otoritatif:** Hanya pembayaran customer yang benar-benar berstatus `PaymentStatus::CONFIRMED` yang diakui sebagai uang masuk. Pembayaran `PENDING`, `CANCELLED`, atau `REJECTED` dilarang dihitung. Pembayaran bertahap (DP dan Pelunasan) harus tercatat terpisah sesuai waktu konfirmasi riil tanpa mereduksi histori menjadi transaksi palsu.

  3. **Uang Keluar & Beban Usaha:** Beban usaha operasional (`BusinessExpense`) diakui sebagai uang keluar. Admin harus dapat mencatat beban usaha langsung dari konteks Catatan Keuangan. Validasi nominal $> 0$, tanggal required, dan actor authenticated wajib ditegakkan di server-side.

  4. **Audit Pengadaan Bahan (Purchase Order):** Hasil audit terhadap skema database menunjukkan bahwa tabel `purchases` tidak memiliki status pembayaran supplier, field `paid_at`, atau pencatatan pengeluaran kas supplier. Biaya material dikapitalisasi ke dalam persediaan gudang (MWA) dan diakui sebagai HPP saat batch produksi selesai. Menganggap PO sebagai kas keluar tanpa adanya transaksi pembayaran supplier riil akan menciptakan pengeluaran kas semu (*ghost cash outflow*) dan risiko *double-counting* dengan HPP. Oleh karena itu, diputuskan **Opsi A**: Catatan Keuangan hanya mencatat kas riil otoritatif (pembayaran customer dan beban usaha), sedangkan pengadaan bahan disajikan secara terpisah di modul Procurement/Gudang.

  5. **Pemisahan Tegas Arus Kas vs Laba Bersih:** Arus Bersih Operasional ($\text{Total Masuk} - \text{Total Keluar}$) mengukur likuiditas kas tunai, **BUKAN** Laba Bersih (*Net Profit*) akuntansi. Pelabelan Laba Bersih dilarang keras pada modul Catatan Keuangan untuk mencegah kerancuan finansial.

- **Solusi Rekayasa yang Diterapkan:**

  1. **Layanan Derivasi `FinancialRecordService`:**

     - Mengonsolidasikan data secara dinamis dari tabel `payments` (CONFIRMED) dan `business_expenses`.

     - Menghitung summary `total_masuk`, `total_keluar`, dan `arus_bersih` secara presisi menggunakan fungsi `BCMath` desimal.

     - Menyediakan fitur filter rentang tanggal, filter jenis arus kas (Semua / Masuk / Keluar), pencarian teks bebas (referensi, nomor order, customer, keterangan), serta paginasi bawaan.

  2. **Controller & Route Admin:**

     - Mendaftarkan rute `admin.financial-records.index` pada `routes/admin.php` di bawah middleware `['auth', 'role:ADMIN']`.

     - Mengembangkan `FinancialRecordController` untuk menangani rendering dan filtering.

  3. **Antarmuka Catatan Keuangan Admin:**

     - Menampilkan kartu ringkasan visual: **Total Uang Masuk** (aksen emerald), **Total Uang Keluar** (aksen rose), dan **Arus Bersih Operasional** (aksen brand NAYARA).

     - Menambahkan tombol aksi `[ + Tambah Beban Usaha ]` yang terhubung langsung ke formulir `admin.expenses.create` dengan parameter `redirect_to=financial-records`, sehingga Admin diarahkan kembali ke Catatan Keuangan setelah menyimpan data beban.

     - Menyajikan tabel ledger terpadu: Tanggal & Waktu (WIB), Jenis (Badge MASUK/KELUAR), Kategori, Referensi (link interaktif ke invoice/beban), Keterangan, Masuk, dan Keluar.

  4. **Integrasi Sidebar Admin:**

     - Menambahkan tautan menu **"Catatan Keuangan"** di bawah grup menu **Keuangan Admin** pada `resources/views/components/sidebar.blade.php`.

  5. **Keamanan & Kontrol Akses Role:**

     - Hak akses Catatan Keuangan Admin dilindungi ketat di server-side: Admin memiliki akses penuh; peran Produksi, Gudang, dan Customer ditolak dengan respons 403 Forbidden; akses publik diarahkan ke login.

  6. **Pengujian Komprehensif:**

     - Dibuat test suite baru `tests/Feature/Finance/AdminFinancialRecordFeatureTest.php` yang mencakup 8 skenario utama (73 assertions) meliputi: Uang Masuk DP/Pelunasan, penolakan pembayaran pending/cancelled, validasi beban usaha, kepatuhan Opsi A pengadaan, formula Arus Bersih, filter tanggal/jenis, pencegahan hapus fisik beban, dan matriks otorisasi peran.

     - Seluruh test suite sistem (1.015 tests, 4.938 assertions) lulus 100% dengan 0 kegagalan dan 0 error.



---



### Isu 13: Arsitektur Ringkasan Keuangan Operasional: Penjualan, Kas, Pembayaran PO Supplier, dan Modal/HPP (Revisi Pembimbing — Tahap 4B)

- **Arahan Pembimbing:**

  Admin perlu dapat melihat secara sederhana:

  1. Catatan Penjualan: total penjualan, berapa sudah dibayar customer, berapa belum dibayar customer.

  2. Kas: kas tersedia sekarang, sumber kas masuk, sumber kas keluar.

  3. Purchase Order: nilai PO, sudah dibayar supplier berapa, belum dibayar berapa, jika PO dibayar sekarang kas cukup atau tidak (sisa kas atau kekurangan).

  4. Modal: HPP / modal produksi (menggunakan engine HPP Tahap 3, jangan membuat formula baru).

  5. Beban usaha: tetap masuk uang keluar. NAYARA tetap BUKAN full accounting application.

- **Tantangan Rekayasa & Temuan Audit:**

  1. **Pemisahan Konseptual yang Ketat:** Penjualan (transaksi komersial) $\ne$ Kas Masuk (pembayaran riil terkonfirmasi) $\ne$ Modal/HPP (biaya produksi internal). Ketiganya tidak boleh disamakan.

  2. **Kas Tersedia Membutuhkan Saldo Awal:** Arus Bersih ($\text{Masuk} - \text{Keluar}$) tidak cukup disebut "Kas Tersedia" karena perusahaan memiliki likuiditas awal sebelum sistem berjalan. Dibutuhkan entitas saldo awal kas operasional (`OpeningCashBalance`).

  3. **Pengadaan Bahan & Pembayaran Supplier:** Sebelumnya sistem tidak memiliki entitas pembayaran supplier. Pembuatan PO dan penerimaan barang di gudang **TIDAK MENGURANGI KAS**. Diperlukan entitas aditif minimal `PurchasePayment` untuk mencatat pembayaran kas keluar riil kepada supplier, dengan dukungan pembayaran bertahap (*partial payment*) dan server-side guards (nominal $> 0$, tidak melebihi sisa PO, PO non-cancelled).

  4. **Pencegahan Mutasi Stok/MWA oleh Pembayaran:** Pencatatan `PurchasePayment` murni finansial, **DILARANG MENGUBAH** stok fisik gudang (`MaterialStock.qty_on_hand`), tidak membuat `StockMovement`, dan tidak mengubah MWA.

  5. **Analisis Kecukupan Kas PO (Informational Warning):** Jika $\text{Kas Tersedia} \ge \text{PO Outstanding}$, status `KAS CUKUP` dengan proyeksi sisa kas; sebaliknya status `KAS TIDAK CUKUP` dengan nilai kekurangan kas. Status ini bersifat transparansi/warning operasional, bukan hard-block.

- **Solusi Rekayasa yang Diterapkan:**

  1. **Migrasi Database & Model Aditif:**

     - `database/migrations/2026_10_07_000002_create_opening_cash_balances_table.php` & `App\Models\Finance\OpeningCashBalance`.

     - `database/migrations/2026_10_07_000003_create_purchase_payments_table.php` & `App\Models\Inventory\PurchasePayment`.

     - Model `Purchase` diperkaya relasi `payments()`, atribut string aman `paid_amount`, `outstanding_amount`, dan helper `isFullyPaid()`.

  2. **Pengembangan Controller & Route Admin:**

     - `OpeningCashBalanceController` (`edit`, `store`) untuk mengelola saldo awal kas oleh Admin.

     - `PurchasePaymentController` (`create`, `store`) untuk mencatat pembayaran supplier per PO di bawah transaksi database dan row-lock.

  3. **Penyempurnaan Layanan Derivasi `FinancialRecordService`:**

     - Mengintegrasikan `PurchasePayment` ke dalam item ledger KELUAR (`Pembayaran Supplier`) tanpa menduplikasi `BusinessExpense`.

     - Menghitung `sales_summary` (Total Penjualan order komersial, Sudah Dibayar, Belum Dibayar).

     - Menghitung `cash_position` (Saldo Awal, Total Masuk, Total Keluar, Kas Tersedia Sekarang).

     - Menghitung `procurement_summary` (Total PO, PO Dibayar, PO Belum Dibayar, Status Kecukupan Kas `CUKUP`/`TIDAK_CUKUP`, Proyeksi Kas / Kekurangan).

     - Menghitung `modal_summary` (HPP Produksi Final menggunakan `OrderHppCalculationService` & count `HPP Belum Final`).

  4. **Pembaruan Tampilan Admin Financial Records:**

     - Row 1: Catatan Penjualan (Total Penjualan, Sudah Dibayar Customer, Belum Dibayar Customer).

     - Row 2: Posisi Kas & Likuiditas (Saldo Awal Kas, Uang Masuk Periode, Uang Keluar Periode, Arus Bersih Operasional, Kas Tersedia Sekarang).

     - Row 3: Pengadaan & Kecukupan Kas (Total Nilai PO, Sudah Dibayar Supplier, PO Belum Dibayar, Banner Kecukupan Kas).

     - Row 4: Modal Produksi HPP (HPP Final, HPP Belum Final count).

     - Section PO Belum Lunas dengan aksi `[ Catat Pembayaran Supplier ]`.

     - Tabel Rincian Transaksi Kas (ledger terpadu pembayaran customer, beban usaha, dan pembayaran supplier).

  5. **Pengujian Komprehensif:**

     - Dibuat test suite baru `tests/Feature/Finance/OperationalFinancialSummaryAndPurchasePaymentTest.php` mencakup 8 skenario mendalam (81 assertions) yang memvalidasi seluruh 35 kriteria pengujian Phase 24.

     - Seluruh 1.023 tests (5.021 assertions) lulus 100% tanpa kegagalan.



---



### Isu 14: Koreksi Tahap 4B — Financial Cut-Off, Historical Supplier Payment, & Hard Cash Guard

- **Klarifikasi Terbaru Pengguna/Pembimbing:**

  1. **Financial Cut-Off:** Saldo Awal Kas merepresentasikan posisi kas riil pada suatu cut-off date (`opening_date`), yang sudah merangkum seluruh aktivitas keuangan sebelum tanggal tersebut. Transaksi historis sebelum cut-off tidak boleh dihitung dua kali terhadap kas saat ini.

  2. **Rekonsiliasi PO Historis:** Sebelum fitur `PurchasePayment` dibuat, beberapa PO lama di dunia nyata sudah dibayar ke supplier. Jangan mengasumsikan PO lama yang belum memiliki `PurchasePayment` pasti belum dibayar. Namun, jangan mengotomasi status `RECEIVED = PAID`. Admin harus dapat mencatat pembayaran historis dengan tanggal sebenarnya (`payment_date < opening_date`) untuk melunasi PO tanpa mengurangi Kas Tersedia berjalan.

  3. **Hard Cash Guard:** Pembayaran supplier pada/setelah cut-off **DILARANG KERAS** melebihi kas operasional yang tersedia (`min(outstanding, current_cash)`). Ini menggantikan perilaku *warning only* sebelumnya. Kas tidak boleh bernilai negatif.

  4. **Status Tanpa Saldo Awal:** Jika Saldo Awal Kas belum diatur, Kas Tersedia bernilai `UNKNOWN / BELUM TERSEDIA` (`null`), bukan Rp0 semu. Analisis kecukupan PO berstatus `BELUM DAPAT DIHITUNG` (bukan `KAS TIDAK CUKUP`), dan pembuatan pembayaran supplier diblokir.

- **Tantangan Rekayasa & Solusi yang Diterapkan:**

  1. **Semantik Batas Tanggal (Inclusive Start-of-Day):**

     - Ditetapkan bahwa Saldo Awal Kas adalah saldo pada awal hari `opening_date`.

     - Formula Kas Tersedia: $\text{Opening Cash} + \sum \text{Customer Payments } (\ge \text{cut-off}) - \sum \text{Supplier Payments } (\ge \text{cut-off}) - \sum \text{Business Expenses } (\ge \text{cut-off})$ menggunakan BCMath scale 2.

     - Transaksi sebelum `opening_date` ditandai sebagai `Historis` pada ledger, tetap mengurangi piutang penjualan / sisa utang PO, namun tidak memotong Kas Tersedia berjalan.

  2. **Enforcement Server-Side Hard Cash Guard & Pessimistic Locking:**

     - Pada `PurchasePaymentController@store`, transaksi dibungkus `DB::transaction()` dengan pessimistic locking `Purchase::lockForUpdate()` dan `OpeningCashBalance::latest()->lockForUpdate()`.

     - Kas tersedia dihitung ulang di dalam transaksi sebelum insert.

     - Untuk pembayaran tanggal non-historis ($\ge \text{cut-off}$): nominal divalidasi $\le \min(\text{PO outstanding}, \text{current cash})$. Jika melebihi kas, transaksi dibatalkan dengan error: *"Kas tidak mencukupi untuk pembayaran supplier. Kas tersedia: Rp..."*.

     - Untuk pembayaran historis ($< \text{cut-off}$): guard kas dilewati karena pembayaran terjadi sebelum cut-off dan nilainya sudah tercermin di saldo awal kas.

  3. **State Tanpa Saldo Awal:**

     - `FinancialRecordService::calculateCurrentCash` mengembalikan `null`.

     - `PurchasePaymentController@create` memblokir akses dan mengarahkan Admin ke form pengaturan saldo awal dengan pesan peringatan: *"Saldo awal kas belum ditentukan. Atur saldo awal kas sebelum mencatat pembayaran supplier."*

     - View Catatan Keuangan menampilkan badge `Belum ditentukan` dan badge `BELUM DAPAT DIHITUNG`.

  4. **Pembaruan Antarmuka Gudang Purchase Detail:**

     - Menampilkan Section 4B Riwayat Pembayaran Supplier dengan indikator *"Belum ada riwayat pembayaran supplier"* untuk PO yang belum memiliki riwayat bayar, mencegah miskonsepsi `RECEIVED = PAID`.

  5. **Pengujian Regresi & Validasi:**

     - Dibuat test suite baru `tests/Feature/Finance/FinancialCutoffAndSupplierCashGuardTest.php` mencakup 6 skenario komprehensif (71 assertions) untuk menguji seluruh 26 skenario cut-off, no-opening guards, hard cash guard, concurrency, historical PO/customer/expense reconciliation, serta verifikasi regresi lintas-modul (QC, MRP/Stock, MWA/HPP, Payment/Shipment Gates).

     - Seluruh 1.029 tests (5.092 assertions) lulus 100% tanpa kegagalan.



---



### Isu 15: Koreksi Purchase Lifecycle — Pembayaran Supplier Sebelum Penerimaan Material (Payment-Before-Receive)

- **Klarifikasi Otoritatif Pengguna/Pembimbing:**

  1. **Supplier Dibayar Ketika Bahan Dipesan:** Berdasarkan klarifikasi alur bisnis nyata konveksi, supplier kain/bahan baku dibayar saat bahan dipesan / PO dikonfirmasi (`ORDERED`), BUKAN saat barang tiba di gudang.

  2. **Alur Bisnis Final:**

     $$\text{Create PO (DRAFT)} \longrightarrow \text{Confirm to Supplier (ORDERED)} \longrightarrow \text{Pay Supplier (PurchasePayment)} \longrightarrow \text{Supplier Sends Material} \longrightarrow \text{Receive Purchase} \longrightarrow \text{Stock IN \& MWA}$$

  3. **Pemisahan Tegas Efek Finansial vs Inventori:**

     - **Payment Event:** Mengurangi Kas operasional, TIDAK mengubah stok fisik gudang, TIDAK membuat `StockMovement`, dan TIDAK mengubah `MWA`.

     - **Receiving Event:** Menambah stok fisik gudang (`material_stocks.qty_on_hand`), mencatat tepat satu `StockMovement IN`, menghitung ulang `MWA`, dan **TIDAK mengurangi kas lagi** (mencegah double cash-out).

  4. **Hard Server-Side Guard pada Penerimaan:**

     - Sebelum material dapat diterima, seluruh kewajiban pembayaran PO harus lunas (`paid_amount == total_amount` / `outstanding_amount == 0.00`).

     - Jika PO masih belum dibayar atau baru dibayar sebagian, eksekusi penerimaan di `PurchaseReceivingService` **DIBLOKIR KERAS SERVER-SIDE** dengan pesan domain:

       $$\text{"Barang belum dapat diterima karena pembayaran supplier belum lunas."}$$

  5. **Dukungan Pembayaran Bertahap (Partial Payment):**

     - Fitur cicilan pembayaran supplier tetap didukung untuk akurasi pencatatan kas, tetapi penerimaan barang fisik tetap terkunci sampai lunas penuh.

  6. **Pemisahan Peran & Tampilan Antarmuka:**

     - **Gudang:** Melihat status pembayaran PO secara *read-only* (Total PO, Sudah Dibayar, Sisa Pembayaran, Badge Status Pembayaran: `BELUM DIBAYAR` / `BAYAR SEBAGIAN` / `LUNAS`). Saldo kas perusahaan **TIDAK PERNAH DIEKSPOS** ke Gudang. Tombol "Terima Barang" terkunci (*disabled*) dengan tooltip/helper jika belum lunas, dan aktif (*enabled*) jika lunas.

     - **Admin:** Mengelola pembayaran kas supplier melalui tombol `[ Catat Pembayaran Supplier ]`.

  7. **Preservasi Data PO Historis:**

     - PO lama yang sudah berstatus `RECEIVED` sebelum fitur pembayaran ada tetap sah dan tidak diubah/rollback/invalidasi. Data historis tersebut tidak dimodifikasi secara otomatis.

- **Tantangan Rekayasa & Solusi yang Diterapkan:**

  1. **Model Helpers pada `Purchase`:**

     - Menambahkan method `isFullyPaid(): bool` (`bccomp($outstanding, '0.00', 2) === 0 && bccomp($total, '0.00', 2) > 0`).

     - Menambahkan accessor `payment_status_label` (`LUNAS`, `BAYAR SEBAGIAN`, `BELUM DIBAYAR`) dan `payment_status_badge_class`.

  2. **Server-Side Guard pada `PurchaseReceivingService`:**

     - Di dalam transaksi ber-row lock (`lockForUpdate`), dilakukan validasi:

       ```php

       $lockedPurchase->load(['items', 'payments']);

       if (! $lockedPurchase->isFullyPaid()) {

           throw new DomainException('Barang belum dapat diterima karena pembayaran supplier belum lunas.');

       }

       ```

  3. **Penyelarasan Controller & View:**

     - `Gudang/PurchaseController@show`: Evaluasi eligibility penerimaan: `$canReceive = ($purchase->status === PurchaseStatus::ORDERED && $purchase->isFullyPaid())`.

     - `resources/views/gudang/purchases/show.blade.php`: Header action menampilkan tombol `Terima Barang (Terkunci)` jika belum lunas, status callout informatif, kartu status finansial 4 metrik, dan tombol `[ Catat Pembayaran Supplier ]` bagi Admin.

  4. **Pengujian Regresi Komprehensif:**

     - Dibuat feature test khusus `tests/Feature/Finance/PurchaseLifecyclePaymentBeforeReceiveTest.php` yang mencakup seluruh 18 skenario spesifik siklus hidup PO, guard parsial/lunas, isolasi kas vs stok, isolasi pembatalan, validasi data historis, dan hard cash guard.

     - Diperbarui unit test dan feature test receiving lintas-modul (`PurchaseReceivingServiceTest`, `PurchaseReceivingFeatureTest`, `MaterialAllocationServiceTest`, `PurchaseHistoryTest`, `StockReplenishmentTraceabilityTest`, `SupervisorHppAndSellingPriceRevisionTest`, `OperationalFinancialSummaryAndPurchasePaymentTest`).

     - Seluruh **1.051 tests (5.199 assertions)** lulus 100% tanpa ada satu pun kegagalan.



### Isu 16: Finalisasi Requirement Pembimbing — HPP Variant Otomatis + Rincian Pembentuk HPP + Harga Jual Wajib di Atas HPP

- **Klarifikasi & Ketetapan Pembimbing:**

  1. **HPP / Modal Tersedia pada Setiap Variant:** Setiap varian produk wajib memiliki HPP yang dihitung otomatis oleh sistem. Admin dilarang menginput total HPP secara manual.

  2. **Dua Jenis HPP yang Berbeda:**

     - **HPP / Modal Acuan Variant (Planning/Pricing):** Dihitung otomatis oleh `VariantCostCalculationService` dari Subtotal Bahan BOM (menggunakan MWA live persediaan gudang) + Biaya Printing Standar + Biaya Tenaga Kerja Standar + Biaya Packing/Proses Standar + Biaya Produksi Tambahan. Berfungsi sebagai dasar penetapan harga jual sebelum produksi.

     - **HPP Aktual Produksi Terakhir (Actual Cost):** Dihitung oleh `BatchCostCalculationService` setelah batch selesai diproduksi dan lolos QC, bersumber dari snapshot pemakaian bahan riil (`StockMovement` OUT snapshot) dan kuantitas good output QC. Menggunakan query kanonikal `ProductionBatch::orderByLatestCompletion()`.

  3. **Rincian Pembentuk HPP (Structured Breakdown):**

     - Tersedia tombol/collapse `[ Lihat Rincian HPP ]` pada halaman detail varian (`/admin/variants/{id}`).

     - Rincian mencakup:

       - Rincian Bahan BOM: Nama Material, Qty BOM per pcs, Satuan, Modal Rata-rata / MWA per unit, Subtotal per bahan.

       - Rincian Biaya Produksi Langsung: Printing standar, Tenaga Kerja standar, Packing/Proses standar, Biaya Tambahan.

       - Summary: Subtotal Bahan + Subtotal Biaya Produksi = HPP / Modal Acuan per pcs.

       - Grand total breakdown wajib **EXACT MATCH** dengan Reference HPP (presisi mutlak `BCMath`).

  4. **Perlakuan Missing Cost Data (Larangan Angka Palsu Rp0):**

     - Jika BOM belum ditentukan, atau MWA belum tersedia, atau biaya langsung wajib belum diisi, sistem menampilkan pesan transparan *"HPP / Modal Acuan belum dapat dihitung."* disertai alasan spesifik. Sistem **DILARANG** menampilkan `Rp0`.

  5. **Hard Selling Price Floor (Server-Side Enforced):**

     - Aturan mutlak: $\text{Selling Price} > \text{Reference HPP}$ (STRICTLY GREATER).

     - Harga jual sama dengan HPP atau lebih rendah dari HPP **WAJIB DITOLAK SERVER-SIDE** (HTTP 422 / DomainException).

     - Tidak ada warning-only, tidak ada override diam-diam, tidak ada auto markup.

     - Ditegakkan pada Master Variant (`base_selling_price`) dan Sales Order (`unit_selling_price`).

  6. **Pencegahan Double-Counting Biaya Kemasan:**

     - Bahan kemasan fisik (polybag, hangtag, dus, label) dicatat di dalam BOM material.

     - Nilai `packaging_process_cost_per_good_unit` murni merepresentasikan upah/biaya jasa proses packing.

  7. **Kekebalan Transaksi & Batch Historis:**

     - Kenaikan MWA akibat penerimaan purchase baru memperbarui HPP Acuan terkini secara reaktif.

     - Namun `unit_selling_price` pada pesanan lama dan HPP batch pada batch yang telah selesai bersifat **IMMUTABLE** dan tidak berubah.



### Isu 17: Pre-UAT Final HPP Price Rule Verification & Audit Existing Variant Prices

- **Konteks & Ketetapan Otoritatif:**

  1. **Existing Variant Price Audit (No Auto-Overwrite):**

     - Harga varian produk yang dimasukkan sebelum penerapan aturan HPP otomatis **DILARANG DIUBAH / DITIMPA SECARA OTOMATIS**.

     - Sistem melakukan evaluasi turunan (*derived evaluation*) menjadi 5 status audit transparan:

       - `VALID`: HPP tersedia dan $\text{Harga Jual Master} > \text{HPP}$ ("Harga jual di atas HPP").

       - `PERLU REVISI`: HPP tersedia dan $\text{Harga Jual Master} \le \text{HPP}$ ("Harga jual saat ini sama dengan atau lebih rendah dari HPP. Perbarui harga jual sebelum Variant digunakan untuk order baru.").

       - `BELUM DAPAT DIVERIFIKASI`: HPP belum tersedia karena BOM / modal material / tarif langsung belum lengkap ("HPP belum tersedia sehingga harga jual belum dapat diverifikasi.").

       - `HARGA BELUM DITETAPKAN`: Harga jual master bernilai `NULL` ("Harga jual belum ditetapkan.").

  2. **Master Price vs Transaction Price Semantics:**

     - `product_variants.base_selling_price` berfungsi sebagai **DEFAULT / REFERENCE PRICE**, bukan harga transaksi yang kaku.

     - Harga transaksi Sales Order (`order_items.unit_selling_price`) dapat disesuaikan pada saat pemesanan (boleh sama, lebih tinggi, atau lebih rendah dari master price), **selama** memenuhi kriteria mutlak: $\text{Transaction Price} > \text{Reference HPP}$.

     - Anti-tampering mencegah bypass terhadap *floor* HPP ($\text{Price} \le \text{HPP}$), bukan memaksakan kesamaan nominal dengan master price.

  3. **Sales Order Prerequisite (Server-Side Block):**

     - Sales Order baru wajib memiliki Reference HPP yang terhitung lengkap. Jika belum tersedia, diblokir *server-side* dengan pesan: *"HPP / Modal Acuan variant belum tersedia. Lengkapi BOM dan modal bahan terlebih dahulu sebelum produk dijual."*

  4. **Historical Sales Order Immutability:**

     - Seluruh harga transaksi historis pada `order_items.unit_selling_price` bersifat kebal (*immutable*). Perubahan master price atau HPP acuan tidak akan pernah mengubah harga historis.

  5. **Hasil Audit Read-Only Database Existing:**

     - Total 24 varian produk pada basis data:

       - 22 Varian: `VALID` (Harga jual master di atas HPP acuan, margin positif).

       - 2 Varian: `HARGA BELUM DITETAPKAN` (Varian ID 23 dan 24 memiliki master price `NULL`).

       - 0 Varian: `PERLU REVISI`.

       - 0 Varian: `BELUM DAPAT DIVERIFIKASI`.



### Isu 18: Perbaikan UX Tombol Kembali pada Detail Sales Order Status KOMUNIKASI

- **Konteks & Alur Koreksi Order:**

  1. **Akar Masalah UX:** Saat Admin selesai input Sales Order lalu menekan Simpan/Lanjut, order tersimpan dengan status `KOMUNIKASI` dan diarahkan ke Detail Sales Order (`/admin/orders/{order}`). Jika Admin ternyata salah input atau tidak sengaja menekan Lanjut, menekan tombol "Kembali" sebelumnya mengarahkan ke daftar pesanan (`/admin/orders`), sehingga Admin kehilangan konteks order yang baru saja dibuat.

  2. **Solusi Otoritatif:**

     - Untuk Sales Order berstatus `KOMUNIKASI`, tombol Kembali di header Detail Sales Order diubah menjadi **"Kembali Edit Order"** yang mengarah langsung ke route edit order yang sama (`route('admin.orders.edit', $order)`).

     - Seluruh nilai existing ter-prefill lengkap (customer, tanggal, catatan, varian, qty, harga transaksi).

     - Aksi edit tidak membuat nomor Sales Order baru dan tidak menduplikasi OrderItem (ID & order_no tetap sama).

     - Setelah simpan perubahan, Admin kembali ke Detail Sales Order yang sama dengan status tetap `KOMUNIKASI`.

     - Tombol **[ Konfirmasi Deal ]** tetap berdiri sebagai aksi terpisah yang dieksekusi secara eksplisit.

  3. **Preservasi Status Lain:** Untuk status selain `KOMUNIKASI` (misal `DEAL`, `BAHAN`, `SELESAI`), tombol Kembali tetap mengarah ke daftar Sales Order (`/admin/orders`).



### Isu 19: Perbaikan Pemisahan HPP Acuan (Estimasi) vs HPP Aktual Produksi pada Sales Order Create/Edit/Detail

- **Konteks & Bug yang Ditemukan (Manual UAT):**

  1. Pada form Edit Sales Order, varian yang telah memiliki HPP Acuan valid (seperti Geometric Sage SKU `HJB-VM-GS-002` HPP Rp56.160 dan Latte SKU `HJB-SO-LT-001` HPP Rp40.196) secara keliru menampilkan pesan `"HPP / Modal Referensi: Belum tersedia"` dan `"Estimasi Margin: -Rp0 / pcs"`.

  2. Pada Sales Order Detail sebelum produksi selesai, bagian HPP dan laba kotor hanya menampilkan `"HPP Belum Final"` tanpa menyajikan estimasi nilai modal acuan pesanan, sehingga pengguna merasa pesanan tidak memiliki HPP sama sekali.

- **Akar Masalah (Root Cause):**

  1. **Serialisasi Eloquent vs JavaScript Number:** Model `ProductVariant` memiliki accessor `getReferenceCostAttribute(): array`. Saat controller mengeksekusi transformasi `$v->reference_cost = $ref['reference_cost']` dan mengirim koleksi ke view via `Js::from($variants)`, Eloquent memanggil accessor tersebut sehingga atribut `reference_cost` terkirim sebagai objek/array asosiatif (`{reference_cost: "56160.00", source: "ESTIMATED", ...}`).

  2. Pada script Alpine JS di `create.blade.php` dan `edit.blade.php`, kode melakukan `Number(variant.reference_cost)`. Di JavaScript, `Number({ ... })` menghasilkan `NaN`. Nilai `NaN` bersifat *falsy* sehingga ternary `item.reference_cost ? ... : 'Belum tersedia'` menampilkan `"Belum tersedia"`.

  3. Pada komputasi margin, `margin_per_unit = price - NaN` menghasilkan `NaN`. Evaluasi `NaN >= 0` bernilai `false` sehingga JS memilih prefix `"-Rp "` dan memformat `formatNumber(NaN || 0)` menjadi `0`, menghasilkan output cacat `"-Rp0 / pcs"`.

  4. Pada form edit, mapping `$existingItems` sebelumnya belum menyertakan dan menghidrasi `reference_cost` serta `reference_label`.

- **Solusi yang Diterapkan:**

  1. **Pemisahan Konseptual yang Tegas:**

     - **HPP / Modal Acuan Variant (Sebelum Produksi):** Dihitung dari `VariantCostCalculationService` ($BOM \times MWA + \text{Biaya Langsung Standar}$). Menjadi dasar selling-price floor, estimasi modal pesanan, dan estimasi margin sebelum produksi.

     - **HPP Aktual Pesanan / Modal Produksi Aktual (Setelah Produksi):** Dihitung dari `BatchCostCalculationService` & `OrderHppCalculationService` setelah seluruh batch selesai dan QC tuntas ($Good Qty$).

  2. **Perbaikan Serialisasi Controller & Hydration View:**

     - Pada `OrderController@create` dan `OrderController@edit`, ditambahkan payload bersih `$variantsPayload` dengan `reference_cost` bernilai skalar numerik string/null.

     - Model `ProductVariant` diperbarui: accessor `getReferenceCostAttribute()` mengembalikan `?string` (skalar nominal HPP), sedangkan representasi array lengkap diakses melalui `getReferenceCostDataAttribute(): array`.

     - Form edit menghidrasi `reference_cost` dan `reference_label` per item secara atomik pada `$existingItems`.

  3. **Penguatan JavaScript Parser & Margin UI:**

     - Dibuat fungsi helper JS `getVariantReferenceCost(variant)` yang tahan banting mengekstrak nilai baik dari format skalar maupun format objek, mengembalikan `null` jika tidak valid.

     - Formula margin: $\text{margin\_per\_unit} = \text{unit\_selling\_price} - \text{reference\_cost}$.

     - Jika HPP Acuan belum tersedia: Menampilkan label `"HPP / Modal Acuan belum tersedia"` dan estimasi margin `"Belum dapat dihitung"` (DILARANG menampilkan `-Rp0 / pcs`).

  4. **Penyempurnaan Antarmuka Detail Sales Order (`/admin/orders/{order}`):**

     - **ESTIMASI SEBELUM PRODUKSI (Acuan Variant):** Menampilkan Total Pendapatan, Estimasi HPP / Modal Acuan ($\sum \text{Qty} \times \text{HPP Acuan}$), dan Estimasi Laba Kotor beserta Margin % dengan catatan *"Berdasarkan HPP Acuan Variant saat ini."*.

       - Contoh UAT `SO-202610-0002`: Pendapatan Rp7.600.000, Estimasi HPP Rp3.694.600, Estimasi Laba Kotor Rp3.905.400.

     - **REALISASI PRODUKSI:** Menampilkan HPP Aktual Pesanan (`"Belum Final"` / `Rp...`) dan Laba Kotor Aktual (`"Belum Final"` / `Rp...`) dengan catatan *"Dihitung dari realisasi batch produksi dan hasil QC."*.

     - Ketika produksi selesai final, menyajikan perbandingan eksplisit **Selisih HPP (Aktual vs Acuan)**.

  5. **Pengujian Regresi Komprehensif:**

     - Dibuat feature test baru `tests/Feature/Admin/OrderReferenceVsActualHppTest.php` mencakup 12 skenario pengujian otoritatif.

     - Seluruh suite pengujian aplikasi (1.123 tests, 5.484 assertions) lulus 100% tanpa kegagalan.



### Isu 20: Final UI Copy Cleanup — Sales Order Create / Edit / Detail

- **Tujuan & Prinsip Desain:**

  1. **Prinsip "1 Label = 1 Makna":**

     - Menghilangkan redundansi copywriting, tanda kurung berlebih, label bergaya dokumentasi teknis, dan helper text yang mengulang label.

     - Angka finansial dibuat lebih menonjol dan kontras dibandingkan teks penjelasan.

     - Tone antarmuka: ringkas, profesional, operasional ERP.

  2. **Standardisasi Wording & Terminologi Otoritatif:**

     - `"HPP / Modal Acuan"` $\rightarrow$ `"HPP Acuan"`

     - `"Harga Jual Master"` / `"Harga Satuan Master"` $\rightarrow$ `"Harga Master"`

     - `"Harga Transaksi"` $\rightarrow$ `"Harga Jual"`

     - `"Estimasi Margin"` $\rightarrow$ `"Margin"`

     - `"HPP Aktual Pesanan"` / `"HPP Aktual / Modal Produksi Aktual"` $\rightarrow$ `"HPP Aktual"`

     - `"Estimasi HPP / Modal Acuan"` $\rightarrow$ `"Estimasi HPP"`

     - `"Estimasi Laba Kotor"` $\rightarrow$ `"Estimasi Laba"`

     - `"Laba Kotor Aktual"` $\rightarrow$ `"Laba Aktual"`

  3. **Penyempurnaan Form Create & Edit (`admin/orders/create` & `admin/orders/edit`):**

     - Setiap item menampilkan kolom utama yang lapang: `Varian Produk` (5/12), `Qty (pcs)` (2/12), `Harga Jual (Rp)` (3/12), `Subtotal` (2/12).

     - Menghapus label ganda `Harga Transaksi:` di bawah field input.

     - Financial reference ditempatkan sebagai compact row di bawah item:

       - `HPP Acuan: Rp xx.xxx / pcs` (atau `"Belum tersedia"`)

       - `Harga Master: Rp xx.xxx` (atau `"Belum diset"`)

       - `Margin: Rp xx.xxx / pcs` (bersih tanpa prefix `+`; atau `"Belum tersedia"`)

     - Menghapus teks tag berulang `(HPP / Modal Acuan)` di samping nominal.

     - Penanganan data kosong seragam menampilkan `"Belum tersedia"` (tidak pernah `-Rp0`, `Rp0`, atau `NaN`).

  4. **Penyempurnaan Detail Sales Order (`admin/orders/show`):**

     - Tabel Item: Header kolom menjadi `"Harga Jual"`. Teks sekunder per baris menampilkan ringkas: `HPP Acuan Rp...` &middot; `Margin Rp...`.

     - Card Finansial:

       - Header utama: `"RINGKASAN NILAI PESANAN"`

       - Sub-seksi 1: `Estimasi Sebelum Produksi` $\rightarrow$ `Pendapatan`, `Estimasi HPP`, `Estimasi Laba` (helper: *"Berdasarkan HPP Acuan Variant."*).

       - Sub-seksi 2: `REALISASI PRODUKSI` $\rightarrow$ `HPP Aktual`, `Laba Aktual` (helper: *"Menunggu produksi dan QC selesai."* saat belum final; `"Selisih HPP"` saat final).

  5. **Integritas Logika Bisnis & Pengujian Regresi:**

     - Seluruh formula matematika HPP, validasi server-side selling price floor, dan data historis pesanan tidak mengalami perubahan.

     - Basis data tidak direset.

     - Seluruh 1.123 tests (5.495 assertions) lulus 100% tanpa kegagalan.



### Isu 21: Simplify Sales Order UI — Menghapus Display HPP / Margin / Profitabilitas dari Halaman Sales Order

- **Konteks & Keputusan Final UX:**

  1. **Fokus Operasional Bersih:**

     - Sales Order dikembalikan pada fungsi utamanya sebagai dokumen dan antarmuka transaksi operasional pesanan customer.

     - Sales Order bukan halaman analisis HPP/profitabilitas. Seluruh kartu analisis finansial dan rincian margin dihapus dari tampilan Sales Order.

  2. **Halaman Form Buat & Edit Sales Order (create.blade.php & edit.blade.php):**

     - Setiap item pesanan menampilkan secara bersih 4 kolom: Varian Produk | Qty | Harga Jual | Subtotal.

     - Tampilan visual HPP Acuan, Harga Master, dan Margin sepenuhnya ditiadakan dari baris item.

     - Backend selling price floor validation tetap aktif sepenuhnya (Selling Price > Reference HPP).

     - Pesan kesalahan hanya muncul kondisional ketika dipicu:

       - Harga jual harus lebih tinggi dari HPP Acuan. (jika harga input <= HPP Acuan).

       - HPP Acuan variant belum tersedia. Lengkapi data biaya produk terlebih dahulu. (jika HPP acuan variant belum tersedia).

       - Harga Master varian belum ditetapkan. Varian ini tidak dapat dipesan. (jika master price belum diset).

  3. **Halaman Detail Sales Order (show.blade.php):**

     - Tabel item hanya menampilkan: No | Produk & Varian | SKU | Rute Produksi | Qty | Harga Jual | Subtotal.

     - Teks sekunder di bawah kolom harga jual (HPP Acuan Rp... & Margin Rp...) dihapus.

     - Bagian RINGKASAN NILAI PESANAN beserta seluruh kartu profitabilitas (Pendapatan, Estimasi HPP, Estimasi Laba, REALISASI PRODUKSI, HPP Aktual, Laba Aktual, Selisih HPP, dan badge Acuan Variant) sepenuhnya dihapus.

     - Informasi transaksi utama dipertahankan di footer tabel item dan ringkasan pesanan: **Total Keseluruhan** (kuantitas total pcs dan nominal total transaksi Rp...).

  4. **Modul HPP & Profitabilitas Tetap Tersedia:**

     - Modul Product/Variant (/admin/variants/{id}): Tetap menyediakan HPP Acuan, rincian pembentuk HPP, Harga Master, dan evaluasi margin.

     - Modul Batch Produksi: Tetap menyediakan HPP Aktual per batch.

     - Modul Laporan & Keuangan: Tetap menyediakan laporan laba rugi, revenue, HPP, laba kotor, dan profitabilitas.

  5. **Pengujian & Keamanan Sistem:**

     - Dibuat feature test baru 	ests/Feature/Admin/SalesOrderUiSimplificationTest.php untuk memverifikasi 11 butir spesifikasi secara otomatis.

     - Seluruh 1.134 tests (5.564 assertions) lulus 100% dengan 0 failures.

     - Tidak ada logic bisnis yang dihapus, tidak ada data historis yang berubah, database tidak direset, git commit/push tidak dilakukan.



---



### Isu 22: Eliminasi Duplikasi Action "Kelola Pembayaran" dari Area Batch Produksi pada Detail Sales Order

- **Konteks & Masalah Redundansi UX:**

  - Pada halaman Detail Sales Order (`/admin/orders/{order}`), saat pesanan menunggu DP dan invoice telah diterbitkan, muncul dua tombol aksi identik dalam satu halaman:

    1. Pada seksi **Batch Produksi**: tombol `[ Kelola Pembayaran ]`.

    2. Pada seksi **Tagihan / Invoice**: tombol `[ Kelola Pembayaran ]`.

  - Hal ini memecah fokus Admin dan melanggar prinsip desain *satu aksi untuk satu fungsi* (*Single Source of Action*). Pengelolaan pembayaran customer semestinya dikelola terpusat dari seksi Tagihan / Invoice saja, sedangkan seksi Batch Produksi berfokus pada monitoring dan penjadwalan produksi.

- **Penyederhanaan Antarmuka (UX Refinement):**

  1. **Seksi Batch Produksi (`resources/views/admin/orders/show.blade.php`):**

     - Status operasional tetap dipertahankan: peringatan "Menunggu Pembayaran DP" pada callout banner dan label "Menunggu DP" pada card item pesanan tetap terlihat jelas agar Admin memahami alasan teknis batch belum dapat dibuat.

     - Tombol `[ Buat Batch ]` (header dan per-item) tetap dalam status disabled sebelum terdapat pembayaran DP terkonfirmasi > 0.

     - Tombol/action `[ Kelola Pembayaran ]` dihapus sepenuhnya dari area Batch Produksi.

  2. **Seksi Tagihan / Invoice:**

     - Tombol `[ Kelola Pembayaran ]` dipertahankan sebagai satu-satunya aksi utama pada Detail Sales Order untuk mengakses detail invoice dan pembayaran customer.

  3. **Integritas Logika Bisnis & Production Gate:**

     - Tidak ada perubahan pada aturan backend DP Gate (`BatchService::canCreateForOrder`), konfirmasi pembayaran (`PaymentStatus::CONFIRMED`), maupun syarat penerbitan batch (`confirmed DP > 0`).

     - Setelah terdapat pembayaran terkonfirmasi > 0, warning "Menunggu Pembayaran DP" hilang dan tombol "Buat Batch" otomatis aktif sesuai alur kerja yang ada.

- **Pengujian & Verifikasi Kualitas:**

  - Ditambahkan automated assertion pada `tests/Feature/Admin/OrderDetailHubTest.php` (`test_batch_section_does_not_contain_duplicate_kelola_pembayaran_action_when_awaiting_dp`) untuk memverifikasi bahwa `Kelola Pembayaran` hanya muncul tepat 1 kali di seluruh halaman (pada seksi invoice) dan tidak ada duplikasi pada seksi batch.

  - Verifikasi targeted tests: `ProductionDownPaymentGateTest`, `OrderDetailHubTest`, `InvoiceManagementTest` lulus 100%.

  - Seluruh test suite sistem (1.135 tests, 5.575 assertions) lulus 100% tanpa kegagalan (0 failures).

  - Dilakukan `php artisan view:clear`, `php artisan view:cache`, dan `git diff --check`.

  - Basis data tidak direset, tidak ada git commit/push.



---

### Isu 23: Final Cleanup Area Batch Produksi — Eliminasi Seluruh Teks dan Callout Menunggu DP dari Detail Sales Order
- **Konteks & Keputusan Final UX:**
  - Area Batch Produksi pada Detail Sales Order (`/admin/orders/{order}`) dikembalikan secara murni pada fungsi operasional penjadwalan dan pemantauan produksi per varian item (Dipesan, Terjadwal, Sisa, Batch, Belum Dijadwalkan, dan tombol Buat Batch).
  - Penjelasan dan pengelolaan pembayaran customer diserahkan seutuhnya pada seksi Tagihan / Invoice. Seksi Batch Produksi tidak perlu menampilkan alasan pembayaran.
- **Penyempurnaan Antarmuka (UX Cleanup):**
  1. **Penghapusan Seluruh Teks & Callout Terkait DP:**
     - Menghapus seluruh visualisasi callout kuning (`bg-amber-50/70`) peringatan pembayaran.
     - Menghapus teks "Menunggu DP" dan "Menunggu Pembayaran DP" secara permanen dari seluruh area Batch Produksi maupun tooltip tombol header/item.
     - Menghapus helper text pembayaran dan duplikasi form terbitkan invoice dari seksi batch.
  2. **Status Tombol Buat Batch:**
     - Jika syarat pembayaran belum terpenuhi, tombol `[ Buat Batch ]` (pada header seksi maupun per card item) tetap berstatus disabled (`cursor-not-allowed`) dengan tooltip singkat netral `Belum dapat membuat batch`.
  3. **Integritas Aturan Bisnis & Backend Gate Tetap Aktif:**
     - Walaupun label visual pembayaran ditiadakan dari seksi batch, hard server-side gate tetap wajib aktif: batch hanya dapat dibuat jika order DEAL dan customer payment confirmed > 0.
     - Direct request pembuatan batch tanpa confirmed payment tetap ditolak oleh controller/service.
     - Setelah terdapat confirmed payment > 0, tombol `[ Buat Batch ]` otomatis aktif.
  4. **Pengujian & Verifikasi Kualitas:**
     - Tes `OrderDetailHubTest` dan `ProductionDownPaymentGateTest` diperbarui untuk memvalidasi ketiadaan teks "Menunggu DP", "Menunggu Pembayaran DP", dan callout kuning, sekaligus memastikan backend gate dan tombol disabled tetap berfungsi.

---

### Isu 24: Implementasi & Finalisasi Ringkasan Keuangan Owner Read-Only + Formula Authoritative ROI Sesuai Arahan Pembimbing
- **Konteks & Arahan Bisnis Otoritatif:**
  - Pembimbing menetapkan peran tegas:
    - **ADMIN**: Mengelola operasional keuangan (input/konfirmasi pembayaran customer, input pembayaran supplier, input beban usaha, kelola saldo awal kas).
    - **OWNER**: Menjalankan fungsi monitoring eksekutif secara **READ-ONLY** (tanpa input transaksi keuangan, tanpa pencatatan pembayaran, tanpa membuat/mengedit expense/saldo awal). Menggunakan informasi ringkasan keuangan untuk evaluasi kinerja bisnis dan pengambilan keputusan.
    - **ROLE LAIN**: Gudang, Produksi, dan Customer dilarang mengakses modul ringkasan keuangan (HTTP 403 Forbidden).
  - Pembimbing menetapkan formula **ROI** otoritatif berbasis arus kas nyata:
    $$\text{ROI} = \frac{\text{Pendapatan Diterima}}{\text{Total Pengeluaran}} \times 100\%$$
    dengan:
    - `Pendapatan Diterima` = Seluruh pembayaran customer berstatus `CONFIRMED` dalam periode terpilih (`payment_date` dalam range).
    - `Total Pengeluaran` = Pengeluaran kas riil (`PurchasePayment` + `BusinessExpense`) dalam periode terpilih.
    - HPP **DILARANG** ditambahkan ke penyebut Total Pengeluaran untuk mencegah *double counting* (pembelian material sudah masuk sebagai kas keluar supplier).
    - Aturan Pembagi Nol (*Zero Denominator Rule*): Jika Total Pengeluaran = 0, ROI bernilai `null` dan ditampilkan sebagai **"Belum dapat dihitung"** (helper: *"Belum ada pengeluaran pada periode ini."*), bukan 0%, bukan NaN, bukan Infinity, dan tidak terjadi division by zero.
- **Penyempurnaan Arsitektur & Rekayasa Sistem:**
  1. **Financial Services & Backend Extraction:**
     - `FinancialRecordService`: Mengekstrak metode publik `calculateProcurementSummary(?string $kasTersedia = null): array` untuk menghitung total PO, PO terbayar, sisa komitmen kewajiban supplier, dan status kecukupan kas. Digunakan ulang secara konsisten pada Catatan Keuangan Admin dan Ringkasan Keuangan Owner.
     - `PeriodFinancialService`:
       - Menghitung kas keluar riil periode dari `PurchasePayment` dan `BusinessExpense` menggunakan BCMath scale 4.
       - Menghitung Authoritative ROI supervisor dengan penanganan aman penyebut nol.
       - Menyediakan metrik lengkap: Total Penjualan, Pendapatan Diterima, Piutang Customer, Total Pengeluaran, Kas Tersedia (cut-off opening balance Rp100.000.000), HPP Produksi, Laba Kotor, Laba Bersih, ROI, dan Komitmen PO.
  2. **Routing, Controller, & Navigasi Antarmuka:**
     - Menambahkan alias rute `/owner/financial-summary` di samping `/owner/performance`.
     - Menambahkan dukungan filter periode `all` (Semua waktu) pada controller dan form UI (`PerformanceController` dan `DashboardController`).
     - Memperbarui label menu sidebar Owner menjadi "Ringkasan Keuangan" dengan ikon chart/finance.
     - Merancang antarmuka `/owner/performance` yang menyajikan 10 metrik terstruktur dalam kartu eksekutif: Ringkasan Arus Kas, Profitabilitas & ROI, Komitmen PO & Kewajiban Supplier, Struktur Laba Rugi Waterfall, Rincian Pengeluaran Kas, dan Daftar Pesanan Dalam Periode Read-Only (tanpa tombol aksi mutasi).
     - Memperbarui kartu ROI pada Dashboard Owner (`/owner/dashboard`) agar menggunakan formula otoritatif pembimbing.
  3. **Pemisahan Konsep Finansial (Metric Separation):**
     - Arus Kas (Pendapatan Diterima vs Pengeluaran Kas vs Kas Tersedia) dipisahkan dari Profitabilitas Akrual (Penjualan vs HPP vs Laba Bersih).
  4. **Jaminan Keamanan & Non-Destructive Invariant:**
     - Tidak ada `migrate:fresh`, `db:wipe`, atau reset database.
     - Saldo awal Rp100.000.000 dan data transaksi historis tetap utuh dan valid.
     - Seluruh tes unit dan feature (1.157 tests, 5.680 assertions) lulus 100% tanpa kegagalan.

---

### Isu 25: Pre-UAT Audit Owner Financial Summary — Semantik Piutang Pelanggan Lintas Periode & Label Kas Saat Ini
- **Konteks & Temuan Audit:**
  - Audit mengidentifikasi bahwa implementasi awal Piutang Pelanggan menggunakan formula agregat sederhana: $\text{Total Penjualan Periode} - \text{Pendapatan Diterima Periode}$.
  - Pada skenario lintas periode (contoh: Sales Order dibuat di September, pembayaran diterima di Oktober):
    - Jika filter Oktober dipilih, pembayaran September yang masuk di Oktober dapat mendistorsi komputasi piutang pesanan Oktober (bahkan berpotensi menghasilkan selisih semu negatif yang di-clamp ke Rp 0).
    - Sebaliknya, pesanan September yang sudah lunas terbayar di Oktober tetap terlihat memiliki piutang saat filter September dilihat.
  - Selain itu, istilah "Kas Tersedia" berpotensi disalahpahami sebagai kas periodik, padahal menggunakan semantik cut-off mutlak (*current running cash position*).
- **Penyempurnaan Rekayasa Sistem:**
  1. **Semantik Otoritatif Piutang Pelanggan (`PeriodFinancialService`):**
     - Mengubah komputasi Piutang Pelanggan menjadi berbasis saldo outstanding invoice/order aktual:
       $$\text{Piutang Pelanggan} = \sum_{\text{order} \in \text{periode}} \text{Outstanding Invoice/Order}(\text{order})$$
     - Setiap pesanan yang termasuk dalam rentang tanggal filter (`order_date`) dihitung sisa kewajiban pembayarannya per data mutakhir (`total_amount - confirmed_payments`).
     - Pembayaran berstatus `PENDING`, `CANCELLED`, atau `REJECTED` **TIDAK MENGURANGI** piutang.
     - Pesanan tanpa invoice dihitung utuh sebesar nilai pendapatan pesanannya.
     - Kebal dari distorsi lintas periode (*cross-period safe*):
       - Penjualan September tetap menjadi omzet September.
       - Pembayaran Oktober tetap menjadi penerimaan kas Oktober.
       - Sisa kewajiban pesanan September terefleksi lunas (Rp 0), dan pesanan baru Oktober merefleksikan sisa tagihan aslinya tanpa tertutup pembayaran September.
  2. **Penyempurnaan Label Antarmuka Kas:**
     - Mengubah label visual pada `/owner/performance`: `"Kas Tersedia"` $\to$ **`"Kas Saat Ini"`**.
     - Mengubah helper text menjadi: *"Saldo kas operasional terkini"*.
     - Formula perhitungan kas berbasis cut-off tidak diubah sedikit pun.
  3. **Verifikasi Kasus Lintas Periode & Variasi Pembayaran:**
     - Ditambahkan automated test Case 30 pada `tests/Feature/Owner/OwnerFinancialSummaryFeatureTest.php` mencakup: partial payment, full payment, pending payment, cancelled payment, dan cross-period settlement.
     - Seluruh test suite (1.158 tests, 5.696 assertions) lulus 100% tanpa kegagalan (0 failures).

---

### Issue 26: Konsolidasi Modul Keuangan Admin NAYARA & Pusat Catatan Keuangan
- **Tanggal:** 13 Oktober 2026
- **Status:** Selesai
- **Permasalahan / Kebutuhan:**
  Sesuai arahan pembimbing, menyederhanakan navigasi keuangan admin dan memusatkan pencatatan serta pencarian arus kas operasional pada menu **Catatan Keuangan** (`/admin/financial-records`):
  1. Hapus menu "Pembayaran Supplier" dan "Laporan Penjualan" dari sidebar Admin.
  2. Pertahankan route lama dengan aman (tanpa 404) untuk workflow/test yang masih menggunakannya.
  3. Tambahkan tombol aksi header `[ + Tambah Pemasukan ]` berdampingan dengan `[ + Tambah Beban Usaha ]` bergaya NAYARA burgundy.
  4. Implementasikan model canonical `BusinessIncome` (`business_incomes`) untuk kas masuk manual non-invoice (Pemasukan Lain, Modal Tambahan, Pendapatan Non-Penjualan).
  5. Penyatuan Kategori Supplier: Menggunakan SATU kategori resmi yaitu **"Pembayaran Supplier"** berbasis `PurchasePayment`. Kategori terpisah "Biaya Supplier" dihapus dari form dan filter setelah audit mengonfirmasi 0 data historis.
  6. Pembayaran PO tetap canonical melalui `PurchasePayment` (label "Pembayaran Supplier") tanpa membuat `BusinessExpense` otomatis (anti double-counting).
  7. Filter bertingkat responsif: Arus Kas (`flow`: `all`, `in`, `out`) secara dinamis membatasi Jenis Transaksi (`type`) dengan fitur auto-reset otomatis saat arus kas berubah.
  8. Fitur pencarian multi-sumber (`search` / `q`) yang dapat menjangkau seluruh sumber transaksi (customer, supplier, SO, PO, invoice, kategori, referensi, dan keterangan) dan dapat digabung bersama filter periode, arus kas, dan jenis transaksi.
  9. Mempertahankan formula keuangan canonical:
     - `Current Cash` = Saldo Awal + Customer Payment CONFIRMED (post-cutoff) + BusinessIncome (post-cutoff) - PurchasePayment (post-cutoff) - BusinessExpense (post-cutoff).
     - `Uang Masuk` = Customer Payment CONFIRMED + BusinessIncome dalam periode.
     - `Uang Keluar` = PurchasePayment + BusinessExpense dalam periode.
     - `Arus Bersih` = Uang Masuk - Uang Keluar.
     - `BusinessIncome` TIDAK menambah Total Penjualan dan TIDAK mengubah ROI supervisor.
  10. Mempertahankan action queue PO Menunggu Pembayaran di Catatan Keuangan.
- **Tindakan Penyelesaian:**
  1. Memperbarui `resources/views/components/sidebar.blade.php`: menghapus link menu "Pembayaran Supplier" dan "Laporan Penjualan" dari sidebar Admin tanpa mengganggu fungsionalitas internal.
  2. Memastikan audit database: 0 rekam historis `BusinessExpense` bertipe supplier ditemukan, sehingga opsi "Biaya Supplier" dihapus aman dari enum dan antarmuka.
  3. Membuat migrasi aditif `database/migrations/2026_10_13_000001_create_business_incomes_table.php` dan menjalankan `php artisan migrate`.
  4. Membuat model `app/Models/Finance/BusinessIncome.php`, request `StoreBusinessIncomeRequest`, controller `BusinessIncomeController`, route `admin.incomes.create` / `admin.incomes.store`, dan tampilan view compact `resources/views/admin/incomes/create.blade.php`.
  5. Memperbarui `app/Services/Finance/FinancialRecordService.php`:
     - Normalisasi filter `flow`, `type`, `search`/`q`.
     - Inflow menggabungkan CustomerPayment confirmed dan BusinessIncome.
     - Outflow menggabungkan BusinessExpense dan PurchasePayment (Pembayaran Supplier) tanpa double-counting.
     - Pencarian komprehensif ke semua relasi dan teks.
     - Current Cash memasukkan BusinessIncome post-cutoff dengan BCMath/DECIMAL.
  6. Memperbarui `resources/views/admin/financial-records/index.blade.php`:
     - Header buttons: `[ + Tambah Pemasukan ]` dan `[ + Tambah Beban Usaha ]`.
     - Tabel antrean aksi PO Menunggu Pembayaran dengan CTA Catat Pembayaran.
     - Filter bar desktop medium-density dengan dependent select (Arus Kas -> Jenis Transaksi) dan auto-reset baik di client-side maupun server-side, di mana kategori supplier tampil tepat satu kali sebagai "Pembayaran Supplier".
  7. Memperbarui `app/Services/Finance/PeriodFinancialService.php` dan enum `ExpenseType`.
  8. Menyusun test suite lengkap `tests/Feature/Finance/AdminFinancialRecordConsolidationTest.php` (22 test assertions).

---

### Issue 27: Implementasi Master Supplier ↔ Material & Integrasi Pengadaan (Purchase Order)
- **Tanggal:** 14 Oktober 2026
- **Status:** Selesai
- **Permasalahan / Kebutuhan:**
  Menerapkan relasi Many-to-Many antara Supplier dan Material untuk Role Gudang berdasarkan audit data historis pembelian:
  1. Gudang dapat melihat material apa saja yang disuplai oleh setiap vendor.
  2. Supplier dapat memiliki banyak material; satu material dapat memiliki lebih dari satu supplier alternatif (contoh nyata: Kain Ceruty Premium disuplai oleh PT Sumber Kain dan PT Barang Hijab).
  3. Relasi awal dibackfill secara otomatis dan idempoten hanya dari data Purchase Order non-cancelled yang sah (menghasilkan tepat 14 relasi unik).
  4. Data transaksi pembelian lama, kuantitas stok, dan average cost (MWA) tetap 100% utuh tanpa perubahan.
  5. Pada pembuatan dan pembaruan PO, pilihan material difilter secara ketat hanya pada material yang disediakan oleh supplier terpilih (tanpa toggle bypass). Pergantian supplier otomatis mereset pilihan material yang tidak sesuai dan memberikan notifikasi.
  6. Backend validation menolak setiap material PO yang tidak terdaftar pada supplier bersangkutan dengan pesan ramah operasional.
  7. Form Tambah/Edit Supplier menyediakan multi-select checklist material dari master data Material. Menghapus relasi pada Edit Supplier tidak menghapus master material atau riwayat PO.
  8. Daftar supplier menampilkan ringkasan material maksimal 2 item + badge `+N lainnya`, serta mendukung pencarian berdasarkan nama material.
### Issue 28: Implementasi Customer Prospect Flow (Admin) & Performa Karyawan (Khusus Role Owner)
- **Tanggal:** 14-15 Oktober 2026
- **Status:** Selesai
- **Permasalahan / Kebutuhan:**
  1. Kanal pemesanan utama NAYARA adalah WhatsApp. Admin perlu mencatat setiap customer/prospek yang baru bertanya tanpa harus langsung membuat Sales Order resmi.
  2. Status Customer harus otoritatif dan derived:
     - `BELUM DEAL`: Customer belum mempunyai Sales Order valid dengan CustomerPayment confirmed > 0.
     - `DEAL`: Customer memiliki minimal 1 Sales Order resmi dengan CustomerPayment confirmed > 0.
     - DILARANG mengimplementasikan status "Tidak Jadi", "Lost", "Warm", atau tahapan CRM kompleks lainnya agar rasio konversi Admin dapat diukur secara objektif.
  3. Proteksi duplikasi nomor WhatsApp: Melakukan normalisasi format telepon dan menampilkan peringatan ramah jika nomor sudah terdaftar pada customer lain.
  4. Atribusi PIC Admin: Setiap customer otomatis diatribusikan ke Admin yang mencatat (`handled_by_user_id`), default ke user login. Data historis tanpa PIC dilaporkan transparan tanpa backfill acak.
  5. Pengukuran Performa Karyawan (Admin Performance) EKSKLUSIF pada Role Owner:
     - Admin murni melakukan pekerjaan operasional dan TIDAK melihat kartu KPI, persentase Deal, atau halaman performa dirinya sendiri.
     - Owner memiliki antarmuka read-only "Performa Karyawan" (sub-halaman "Performa Admin", disiapkan arsitektur untuk penambahan "Performa Produksi" kelak).
     - Formula: $\text{Persentase Deal} = \frac{\text{Distinct Deal Customers}}{\text{Total Customers}} \times 100\%$. Repeat order dihitung sebagai 1 Customer Deal (tidak menggelembungkan persentase melebihi 100%).
     - Penanganan Denominator Nol: Jika Admin belum memiliki customer, nilai persentase Deal adalah `null` dan antarmuka menampilkan `"Belum Ada Data"` (bukan 0%, NaN, atau Infinity).
     - Filter kohort periode berbasis Tanggal Masuk Customer (`first_contact_date`).
- **Tindakan Penyelesaian:**
  1. Membuat migrasi aditif `database/migrations/2026_10_15_000001_add_prospect_fields_to_customers_table.php` (`first_contact_date`, `notes`, `handled_by_user_id`).
  2. Mengembangkan `App\Support\PhoneNormalizer` untuk normalisasi dan format nomor WhatsApp Indonesia secara konsisten.
  3. Memperluas model `Customer`: menambahkan relasi `handledBy()`, scope `withDealStatus()`, method `isDeal()`, serta atribut akses status badge dan format telepon.
  4. Menyesuaikan form request `StoreCustomerRequest` dan `UpdateCustomerRequest` dengan validasi duplikasi nomor WhatsApp yang ramah.
  5. Memperbarui `CustomerController` dan antarmuka Admin Customer (`index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`): input prospek cepat, badge status Belum Deal / Deal, tombol CTA Buat Sales Order, serta isolasi total tanpa metrik KPI performa.
  6. Mengembangkan `AdminPerformanceService` dan `EmployeePerformanceController` (Owner) untuk kalkulasi rasio Deal berdasar distinct customer, penanganan denominator nol, filter periode kohort, dan rincian performa per Admin.
  7. Membangun view Owner: `owner/employee-performance/index.blade.php` dan `owner/employee-performance/admin-detail.blade.php` dengan desain terpadu NAYARA.
  8. Memperbarui alur pembuatan Sales Order atomik dengan opsi DP langsung (`dp_amount`, `payment_date`, `payment_method`) di `OrderController::store` dengan backward compatibility 100%.
  9. Menulis test suite komprehensif: `CustomerProspectFlowTest` (8 tests, 36 assertions) dan `OwnerEmployeePerformanceTest` (10 tests, 42 assertions), memastikan seluruh otorisasi dan formula berjalan dengan tepat.

---

### Issue 29: Implementasi Performa Karyawan Produksi (Khusus Role Owner) & Penugasan PIC Batch
- **Tanggal:** 14-15 Oktober 2026
- **Status:** Selesai
- **Permasalahan / Kebutuhan:**
  1. Owner membutuhkan visibilitas terhadap performa operasional tim Produksi di samping performa konversi Admin:
     - **Ketepatan Waktu Produksi**: Membandingkan target selesai batch (`planned_end_date`) dengan tanggal aktual selesai (`ProductionResult.result_date`).
     - **Ketepatan Jumlah Produksi**: Membandingkan total target produksi (`target_qty`) dengan total hasil produksi fisik aktual (`ProductionResult.actual_qty`).
  2. Penugasan PIC Produksi:
     - Ditambahkan kolom `production_employee_id` pada `production_batches` yang merujuk ke pengguna dengan role `PRODUKSI`.
     - Admin dapat memilih PIC Karyawan Produksi saat menjadwalkan pembuatan batch baru. Penugasan bersifat opsional (`NULL` diizinkan).
  3. Integritas Data Historis:
     - Batch historis tanpa PIC Produksi otoritatif tetap bernilai `NULL` (tidak diasumsikan/dihubungkan sembarangan).
     - Batch tanpa PIC dilaporkan secara transparan pada ringkasan KPI Owner (`unassigned_batches_count`).
  4. Formula & Klasifikasi Metrik:
     - **Klasifikasi Waktu**:
       - `result_date < planned_end_date` $\rightarrow$ Lebih Cepat (`LEBIH_CEPAT`, *"X hari lebih cepat"*).
       - `result_date == planned_end_date` $\rightarrow$ Tepat Waktu (`TEPAT_WAKTU`, *"Tepat waktu"*).
       - `result_date > planned_end_date` $\rightarrow$ Terlambat (`TERLAMBAT`, *"Terlambat X hari"*).
       - Keduanya (Lebih Cepat dan Tepat Waktu) dihitung sebagai batch tepat waktu (`on_time_batches`).
       - Persentase Ketepatan Waktu: $\frac{\text{Batch On-Time}}{\text{Batch dengan Target Selesai}} \times 100\%$.
     - **Pencapaian Jumlah**:
       - Formula agregat karyawan: $\frac{\sum \text{actual\_qty}}{\sum \text{target\_qty}} \times 100\%$ (Sum over Sum, bukan rata-rata dari persentase batch).
       - Persentase dapat melebihi 100% jika produksi menghasilkan surplus (tidak dipotong/dicap).
     - **Penanganan Denominator Nol**:
       - Jika pembagi bernilai 0 (karyawan belum memiliki data batch bertarget atau kuantitas), nilai dikembalikan sebagai `null` dan ditampilkan sebagai **`"Belum Ada Data"`**.
  5. Isolasi Role Ketat (Owner-Only):
     - Seluruh metrik, ranking, dan halaman performa hanya dapat diakses oleh Role **OWNER** (Read-Only). Role Produksi, Admin, Gudang, dan Customer ditolak (HTTP 403 Forbidden).
     - Karyawan Produksi tidak melihat ranking atau evaluasi dirinya sendiri.
  6. Filter Periode Kohort:
     - Dihitung berdasarkan **Tanggal Selesai Produksi Aktual** (`ProductionResult.result_date`).
- **Tindakan Penyelesaian:**
  1. Membuat migrasi aditif `database/migrations/2026_10_15_000002_add_production_employee_id_to_production_batches_table.php` dan menjalankannya tanpa database reset.
  2. Memperbarui model `ProductionBatch` (kolom fillable, relasi `productionEmployee()`, helper waktu: `time_difference_days`, `time_performance_status`, `time_performance_label`, `time_performance_badge_class`, dan `production_quantity_achievement_percent`).
  3. Memperbarui model `User` dengan relasi `assignedProductionBatches()`.
  4. Memperbarui validasi `StoreProductionBatchRequest` untuk validasi `production_employee_id` (role PRODUKSI).
  5. Memperbarui `ProductionBatchController::create` dan view `admin/batches/create.blade.php` dengan dropdown pemilihan PIC Produksi opsional.
  6. Memperbarui `BatchService::createBatch` untuk menyimpan `production_employee_id` ke dalam database secara transaksi.
  7. Mengembangkan service `App\Services\Owner\ProductionPerformanceService` untuk perhitungan ketepatan waktu, ketepatan jumlah (sum over sum), penanganan denominator nol, pelaporan unassigned batch, dan breakdown detail per karyawan.
  8. Memperbarui `EmployeePerformanceController` dan `routes/web.php` (menambahkan route detail produksi `/owner/employee-performance/production/{user}`).
  9. Memperbarui antarmuka `resources/views/owner/employee-performance/index.blade.php` dengan tab navigasi dual (Performa Admin & Performa Produksi) serta membuat view detail `resources/views/owner/employee-performance/production-detail.blade.php`.
  10. Menyusun test suite lengkap `tests/Feature/Owner/OwnerProductionPerformanceTest.php` (14 tests, 62 assertions) yang memverifikasi otorisasi role, penugasan batch PIC, klasifikasi waktu, perhitungan sum over sum kuantitas, penanganan denominator nol, filter periode, dan penanganan unassigned batch.

### Sesi: Master Karyawan yang Dikelola Owner, Integrasi PIC Produksi, dan Resolusi Batch Historis
- **Tujuan:** Mengimplementasikan master karyawan internal yang dikelola oleh Owner (`/owner/employees`), integrasi peran PRODUKSI sebagai PIC Production Batch, penegakan guard hasil produksi, serta resolusi atribusi PIC untuk batch historis yang belum memiliki PIC.
- **Kaidah Bisnis & Ketentuan:**
  1. **Master Karyawan**: Menggunakan kembali model `App\Models\System\User` tanpa membuat tabel duplikat.
  2. **Role yang Dikelola**: Hanya `ADMIN`, `GUDANG`, dan `PRODUKSI`. Role `OWNER` dan `CUSTOMER` diproteksi dan dikecualikan dari scope CRUD karyawan.
  3. **Keaktifan (Status)**: Mendukung status `ACTIVE` dan `INACTIVE`. Karyawan nonaktif diblokir dari login dan disembunyikan dari penugasan baru, namun relasi historis tetap dipertahankan (tidak ada hard delete).
  4. **Pemisahan Wewenang Operasional**: Owner mengelola master karyawan; Admin memilih PIC Produksi pada pembuatan batch; Owner memantau performa.
  5. **Guard Hasil Produksi (Production Result Guard)**: Batch wajib memiliki atribut `production_employee_id` sebelum hasil produksi final dapat dicatat (`ProductionResultService::recordFinalResult`). Mencegah munculnya batch tanpa PIC di masa depan.
  6. **Resolusi PIC Historis (Owner Only)**: Menyediakan antarmuka `/owner/employee-performance/unassigned-batches` bagi Owner untuk menetapkan PIC historis secara manual tanpa mengubah target, hasil aktual, tanggal hasil, Sales Order, Invoice, stok, maupun QC.
  7. **Peringatan Dinamis**: Banner kompak pada dashboard performa menampilkan `"{N} batch historis belum memiliki PIC Produksi. [Lengkapi PIC]"`, dan tersembunyi jika seluruh batch telah memiliki PIC.
- **Tindakan Penyelesaian:**
  1. Membuat request validasi: `App\Http\Requests\Owner\StoreEmployeeRequest` dan `App\Http\Requests\Owner\UpdateEmployeeRequest`.
  2. Mengembangkan `App\Http\Controllers\Owner\EmployeeController` untuk operasi CRUD karyawan dan toggle status keaktifan dengan proteksi terhadap role Owner dan Customer.
  3. Memperbarui `App\Http\Controllers\Owner\EmployeePerformanceController` dengan aksi resolusi PIC batch historis (`unassignedBatches`, `assignBatchPic`, `bulkAssignBatchPic`).
  4. Mendaftarkan route karyawan dan resolusi batch historis di `routes/web.php` dalam grup middleware `['auth', 'role:OWNER']`.
  5. Menambahkan menu navigasi "Karyawan" pada sidebar Owner (`resources/views/components/sidebar.blade.php`) di bawah bagian "Manajemen".
  6. Membuat blade views NAYARA yang bersih dan profesional:
     - `resources/views/owner/employees/index.blade.php` (daftar karyawan, filter nama/email, role, status, badge styling, pagination).
     - `resources/views/owner/employees/create.blade.php` (tambah karyawan dengan hashing password).
     - `resources/views/owner/employees/edit.blade.php` (edit karyawan dengan peringatan riwayat batch jika ada perubahan role).
     - `resources/views/owner/employees/show.blade.php` (detail profil dan rangkuman operasional serta tautan ke performa canonical).
     - `resources/views/owner/employee-performance/unassigned-batches.blade.php` (tabel penugasan PIC historis).
  7. Memperbarui `resources/views/owner/employee-performance/index.blade.php` dengan banner peringatan kompak dan tombol CTA `[Lengkapi PIC]`.
  8. Memperbarui `resources/views/produksi/batches/show.blade.php` dan `index.blade.php` untuk menampilkan nama PIC dan badge `"PIC Anda"` untuk staf yang sedang login.
  9. Menegakkan guard PIC Produksi pada `ProductionResultService::recordFinalResult` dan `ProductionResultController::create`.
  10. Menyusun test suite komprehensif `tests/Feature/Owner/OwnerEmployeeManagementAndBatchPicTest.php` yang memverifikasi 35 poin uji: otorisasi role (1-5), pembuatan karyawan (6-10), hashing password dan keaktifan (11-15), dropdown PIC operasional (16-20), penugasan Admin dan Owner (21-25), integritas data historis (26-30), dan integrasi performa (31-35).

### Sesi: Penghapusan Permanen Reset Password Admin dan Sentralisasi Pengelolaan Password Karyawan ke Owner
- **Tujuan:** Menjadikan Owner sebagai satu-satunya role yang mengelola akun karyawan internal, menghapus fitur dan navigasi Reset Password dari role Admin secara permanen, dan memusatkan pengelolaan password karyawan di Owner → Karyawan.
- **Kaidah Bisnis & Ketentuan:**
  1. **Admin Tanpa Reset Password**: Admin tidak lagi memiliki fitur Reset Password, tidak boleh mengganti/mereset password akun karyawan lain, dan tidak memiliki menu "Manajemen User" / "Reset Password".
  2. **Penghapusan Bersih Route & Controller Admin**: Seluruh route mandiri `/admin/password-resets*`, `PasswordResetRequestController`, serta view `admin/password-resets/*` dihapus secara permanen. Percobaan akses oleh Admin menghasilkan HTTP 404 Not Found.
  3. **Pembersihan Dashboard & Sidebar Admin**: Header seksi "MANAJEMEN USER" dan menu "Reset Password" pada sidebar Admin dieliminasi sepenuhnya (tidak meninggalkan seksi kosong). Action queue dan query pending reset password pada Admin Dashboard dihilangkan.
  4. **Sentralisasi ke Owner Karyawan**: Pengelolaan password karyawan berada di detail/edit karyawan (`/owner/employees/{employee}`) via tombol secondary dan modal kompak "Reset Password" bergaya NAYARA. Tidak ada menu standalone di sidebar Owner.
  5. **Cakupan Role Karyawan**: Hanya karyawan internal (`ADMIN`, `GUDANG`, `PRODUKSI`) yang dapat direset. Akun `OWNER` dan `CUSTOMER` diproteksi keras (HTTP 403 Forbidden).
  6. **Keamanan Password**: Menggunakan `Hash::make`, validasi `required|confirmed|min:8`, pembatalan sesi aktif pengguna (`invalidateUserSessions`), penghapusan `remember_token`, dan pencatatan audit log tanpa menyimpan plaintext password.
- **Tindakan Penyelesaian:**
  1. Menghapus menu "Reset Password" dan seksi kosong "MANAJEMEN USER" pada `resources/views/components/sidebar.blade.php`.
  2. Menghapus route `admin.password-resets.*` dari `routes/admin.php`.
  3. Menghapus file usang `app/Http/Controllers/Admin/PasswordResetRequestController.php` dan folder `resources/views/admin/password-resets`.
  4. Menghapus referensi pending password reset dari `DashboardController.php`, `DashboardActionService.php`, dan `dashboard/admin.blade.php`.
  5. Membuat form request `App\Http\Requests\Owner\ResetEmployeePasswordRequest` dengan aturan otorisasi Owner, target internal employee, dan validasi password konfirmasi min 8 karakter.
  6. Mendaftarkan route `POST /owner/employees/{employee}/reset-password` (`owner.employees.reset-password`) dalam grup middleware `['auth', 'role:OWNER']` di `routes/web.php`.
  7. Menambahkan aksi `resetPassword` dan invalidasi sesi pengguna di `App\Http\Controllers\Owner\EmployeeController`.
  8. Memperbarui `resources/views/owner/employees/show.blade.php` dengan tombol aksi `[Reset Password]` dan modal kompak NAYARA.
  9. Memperbarui test suite `tests/Feature/Auth/ForgotPasswordFeatureTest.php` (12 tests, 33 assertions lulus).
  10. Menyusun automated feature test `tests/Feature/Owner/OwnerEmployeePasswordResetTest.php` (15 tests, 47 assertions lulus 100%) yang memverifikasi seluruh 15 skenario: pembersihan sidebar Admin, status 404 route lama, proteksi akses Admin, reset akun Admin, Gudang, Produksi, hashing password, validasi konfirmasi, pengujian login password lama dan baru, isolasi role Owner/Customer, dan proteksi non-Owner 403.

### Sesi: Perbaikan Ukuran Ikon Flash Success Alert dan Label Tombol Tambah Karyawan (Owner → Karyawan)
- **Tujuan:** Memperbaiki bug tampilan ikon centang flash alert yang membesar ratusan piksel hingga merusak layout pada halaman Owner Karyawan (`/owner/employees`), serta memperbaiki teks ganda "+ + Tambah Karyawan" menjadi "+ Tambah Karyawan".
- **Akar Penyebab & Temuan Audit:**
  1. **Kelas Non-Tailwind `w-4.5` dan `h-4.5`**: Komponen alert lokal di `owner/employees/index.blade.php` (dan `show.blade.php`) menggunakan kelas `w-4.5 h-4.5`. Karena Tailwind CSS standar tidak memiliki skala spacing `4.5` secara bawaan, kelas ini tidak menghasilkan aturan CSS ukuran (`width`/`height`).
  2. **Perilaku Rendering SVG Browser**: Elemen `<svg viewBox="0 0 24 24">` tanpa atribut `width`/`height` dan tanpa aturan CSS eksplisit di dalam kontainer flex akan meregang sesuai aspect ratio intrinsik hingga batas kontainer (300px+), menyebabkan ikon centang hijau membesar ekstrem dan merusak tinggi halaman.
  3. **Duplikasi Alert**: Halaman `owner/employees/index.blade.php` memiliki blok `@if(session('success'))` lokal tambahan di bawah feature header, padahal layout utama `layouts/app.blade.php` telah menyematkan shared component `<x-admin.flash-message />` tepat di atas feature header.
  4. **Duplikasi Simbol Plus**: Tombol Tambah Karyawan memiliki icon SVG plus (`M12 4.5v15m7.5-7.5h-15`) dan teks `<span>+ Tambah Karyawan</span>`, menghasilkan tampilan visual ganda `+ + Tambah Karyawan`.
- **Tindakan Penyelesaian:**
  1. **Refaktor Shared Flash Message Component (`components/admin/flash-message.blade.php`)**:
     - Menetapkan ukuran eksplisit `w-5 h-5 shrink-0` serta atribut `width="20" height="20"` pada seluruh ikon alert (`success`, `error`, `warning`, `info`).
     - Menetapkan ukuran eksplisit `w-4 h-4 shrink-0` serta atribut `width="16" height="16"` pada tombol close/dismiss.
     - Mengatur layout bar alert kompak dengan tinggi min `48px` (`min-h-[48px]`), padding `px-4 py-3`, styling `bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl`, dan teks fleksibel `flex-1 min-w-0 leading-snug`.
     - Menambahkan dukungan untuk tipe flash `info` dengan palet warna sky blue (`bg-sky-50 border-sky-200 text-sky-800`).
  2. **Pembersihan Halaman Owner Karyawan (`owner/employees/index.blade.php` & `show.blade.php`)**:
     - Menghapus blok duplikat inline flash message sehingga pesan sukses terpusat rapi di atas feature header melalui layout utama.
     - Mengubah `<span>+ Tambah Karyawan</span>` menjadi `<span>Tambah Karyawan</span>`, menghasilkan tampilan bersih satu plus (`[+] Tambah Karyawan`).
  3. **Audit Kelas SVG di Seluruh Antarmuka**: Memperbaiki sisa kelas `w-4.5 h-4.5` pada `owner/employee-performance/unassigned-batches.blade.php` dan `owner/employee-performance/index.blade.php` menjadi ukuran eksplisit standar.
  4. **Penyusunan Feature Test**: Menambahkan `tests/Feature/Owner/OwnerEmployeeSuccessAlertTest.php` (4 tests, 28 assertions lulus 100%) untuk memastikan ukuran eksplisit ikon alert, ketiadaan kelas `w-4.5`, tidak adanya duplikasi alert, dan perbaikan tombol tambah karyawan.

---

### Sesi: Finalisasi Revisi Alur Purchase Order (PO): Pengajuan Gudang ke Admin, Persetujuan Admin, Pembayaran Wajib Bukti Transfer, Auto-Ordered, dan Penerimaan Barang Fisik
- **Tujuan:** Menuntaskan penyesuaian alur Purchase Order (PO) konveksi NAYARA sesuai arahan pembimbing:
  1. Gudang membuat PO (`DRAFT`) lalu mengajukan ke Admin (`Ajukan ke Admin`, `PENDING_APPROVAL`).
  2. Admin menyetujui, meminta revisi, atau menolak PO (`/admin/purchases/approvals`). Hak persetujuan Owner dihapus permanen.
  3. Admin membayar supplier dengan kewajiban unggah file bukti transfer (`transfer_proof`: PDF, JPG, PNG <= 5MB) yang disimpan di storage disk `local` direktori `transfer-proofs/`.
  4. Pembayaran kumulatif lunas (`outstanding_amount == 0`) secara otomatis memindahkan status PO dari `DRAFT` menjadi `ORDERED` (`Dipesan`), tanpa tombol konfirmasi supplier manual oleh Gudang.
  5. Gudang menerima fisik barang (`ORDERED` -> `RECEIVED`). Penambahan stok fisik (`qty_on_hand`) dan perhitungan Moving Weighted Average (MWA) HANYA terjadi saat penerimaan barang oleh Gudang, BUKAN saat pembayaran supplier. Pembayaran supplier mencatat pengeluaran kas tanpa mutasi stok fisik.
- **Tindakan Penyelesaian:**
  1. Membuat migrasi aditif `database/migrations/2026_10_15_000003_add_transfer_proof_path_to_purchase_payments_table.php` dan menjalankannya tanpa reset database.
  2. Menambahkan atribut `transfer_proof_path`, method `hasTransferProof()`, dan accessor `transfer_proof_url` pada model `PurchasePayment`.
  3. Memperbarui `PurchaseApprovalService` untuk menegakkan peran Admin dalam persetujuan, revisi, dan penolakan PO; mengalihkan notifikasi ke Admin.
  4. Menandai method `confirmPurchase` pada `PurchaseOrderService` sebagai usang (*obsolete*, melempar `DomainException`), serta mengotomatiskan transisi status ke `ORDERED` saat pembayaran lunas.
  5. Memperbarui `DashboardActionService` dan `Admin\DashboardController` sehingga PO Menunggu Persetujuan berada pada Prioritas 1 di Antrean Aksi Cepat Dashboard Admin; mengosongkan antrean Owner.
  6. Membangun modul persetujuan PO Admin lengkap: `PurchaseApprovalController` dan views `resources/views/admin/purchases/approvals/` dengan kartu perencanaan MRP dan kartu finansial.
  7. Memperbarui `PurchasePaymentController`: mewajibkan file `transfer_proof`, menyimpannya pada storage `local` di `transfer-proofs/`, transisi otomatis ke `ORDERED` saat lunas, dan menyediakan endpoint aman `showProof`.
  8. Memperbarui sidebar (`components/sidebar.blade.php`), views form pembayaran (`admin/purchases/payments/create.blade.php`), dan view detail PO Gudang (`gudang/purchases/show.blade.php`).
  9. Menghapus route usang `owner.purchase-approvals.*` dan `gudang.purchases.confirm`.
  10. Memperbarui seluruh test suite terkait sehingga 100% lulus (1.487 tests, 7.580 assertions, 0 failures).

---

*Dokumen ini merupakan catatan riil pengembangan sistem NAYARA Hijab ERP yang disusun berdasarkan artefak dan riwayat resmi repositori.*
