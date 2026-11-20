# Audit UI/UX dan Keselarasan Alur NAYARA
**Tanggal:** 3 November 2026  
**Basis audit:** Arsip project `hijab (2).zip` yang diberikan pada percakapan ini.

## 1. Hasil Audit Singkat

Project ini memang sudah memiliki modul **Super Admin**. Implementasinya mencakup role `SUPER_ADMIN`, route platform, controller, layout/sidebar khusus, dashboard, dan halaman modul platform. Tidak perlu membuat ulang Super Admin dari awal.

Temuan utama bukan ketiadaan halaman, melainkan inkonsistensi tampilan dan beberapa metrik dashboard yang tidak sesuai dengan batas kewenangan Super Admin.

## 2. Temuan Sebelum Perbaikan

1. Dashboard Super Admin mencampurkan data platform dengan data operasional tenant, termasuk query Sales Order dan Payment.
2. Grafik pesanan/transaksi menggunakan angka contoh, termasuk nilai 7 hari, 30 hari, 3 bulan, persentase SLA, kepuasan, dan nilai transaksi yang tidak seluruhnya berasal dari database.
3. Bagian aktivitas terbaru berisi narasi statis, bukan riwayat aktivitas aktual.
4. Daftar "Top Stores" disusun berdasarkan tenant terbaru, bukan peringkat performa yang benar.
5. KPI mencampurkan metrik platform dengan metrik tenant dan placeholder pajak bernilai nol.
6. Skala font, jenis font, tinggi komponen, warna, serta ukuran ikon belum konsisten di seluruh role.
7. Shell ERP tenant mengunci tinggi viewport dan membuat area konten memiliki scrollbar vertikal sendiri.
8. Sejumlah tombol dan ikon memakai utility ukuran Tailwind yang tidak tersedia, seperti `w-4.5`, `h-4.5`, dan `h-8.5`.
9. Dokumentasi root masih memuat aturan lama yang menyatakan Multi-Tenant tidak termasuk ruang lingkup. Matriks izin juga tidak konsisten dengan alur pembuatan batch yang diterapkan pada route dan audit alur bisnis.

## 3. Perbaikan UI/UX

### Design system

- Menyatukan font antarmuka menjadi **Inter**.
- Menggunakan palet final NAYARA: biru #093FB4, putih #FFFCFB, pink #FFD8D8, dan oranye #ED3500 sesuai referensi desain serta ketentuan tombol pada dokumen aturan bisnis.
- Menetapkan skala tipografi yang lebih rapat: judul halaman sekitar 20–21 px, judul bagian 14–16 px, teks isi 12–13.5 px, label 10–11 px, dan nilai KPI sekitar 21–22 px.
- Menyamakan radius, border, jarak, tinggi tombol, input, tabel, dan kartu.
- Mengubah ikon KPI menjadi ikon berukuran terukur di dalam wadah kontras; menghilangkan watermark ikon besar yang mengganggu keterbacaan.
- Menyamakan komponen Super Admin pada halaman dashboard, direktori tenant, pendaftaran, tagihan, iklan, refund, analitik, layanan, tata kelola, dan monitoring.
- Menambahkan ikon peran Super Admin berbentuk shield-check pada kartu profil sidebar.

### Scroll dan responsivitas

- Mengubah shell ERP tenant agar menggunakan scroll dokumen/halaman, bukan scrollbar vertikal di dalam area konten utama.
- Sidebar tetap berada di sisi layar dan memiliki area scroll menu sendiri jika item navigasi melebihi tinggi viewport.
- Konten utama tidak lagi dikunci pada tinggi viewport.
- Menyamakan tinggi kartu dalam satu baris grid dan merapikan perilaku grid desktop, tablet, dan mobile.
- Mempertahankan scroll horizontal hanya pada tabel yang memang membutuhkan lebar minimum.

### Dashboard Super Admin

Dashboard disederhanakan menjadi:
1. Header dashboard dan profil Super Admin.
2. Banner ringkas pusat kendali platform.
3. Delapan KPI platform.
4. Pendaftaran terbaru dan antrean yang benar-benar memerlukan tindakan.
5. Tren pendaftaran tujuh hari terakhir dan aktivitas pendaftaran aktual.
6. Footer status isolasi tenant.

KPI yang digunakan:
- Total Tenant
- Tenant Aktif
- Menunggu Review
- Perlu Revisi
- Nilai Invoice Terverifikasi
- Tagihan Menunggu
- Premium Aktif
- Iklan Aktif

Sumber data dashboard sekarang berasal dari entitas platform seperti `Outlet`, `TenantRegistration`, `PlatformInvoice`, `TenantSubscription`, `AdCampaign`, `PlatformRefund`, dan `TenantRegistrationHistory`.

Dashboard tidak lagi mengambil detail atau agregat dari tabel `Sales\Order`, `Sales\Payment`, dan `Master\Product`. Grafik dibuat dari jumlah pendaftaran tenant aktual selama tujuh hari terakhir. Aktivitas diambil dari riwayat pendaftaran yang tersimpan. Angka contoh, SLA/CSAT buatan, nilai transaksi tenant, pajak placeholder, dan status sistem 100% statis dihapus.

Tidak ada panel "Tindakan Cepat" kedua. Aksi ditampilkan pada konteksnya: tautan KPI, tombol tinjau pada baris pendaftaran, serta tautan antrean yang memang membutuhkan tindakan.

## 4. Keselarasan Alur dan Batas Kewenangan

Alur bisnis tenant yang dipertahankan:

1. Customer menghubungi toko melalui WhatsApp.
2. Admin mencatat Customer dan membuat Sales Order berstatus `KOMUNIKASI`.
3. Admin mengonfirmasi `DEAL`, menerbitkan invoice, dan mencatat DP yang sudah terkonfirmasi.
4. Setelah DP terkonfirmasi lebih dari nol, **Admin** menerbitkan Batch Produksi dan menentukan target serta PIC.
5. Gudang menjalankan MRP, mengelola shortage melalui Purchase Order, menerima bahan, dan mengalokasikan bahan ke batch.
6. Produksi menjalankan printing/finishing, mencatat hasil aktual dan pemakaian bahan, melakukan QC Good/Reject, lalu menyelesaikan packing.
7. Admin memproses pelunasan; Produksi menjalankan pengiriman sesuai prasyarat pembayaran.
8. Customer memantau progres dan pengiriman; Owner memantau HPP serta kinerja finansial tenant.

Batas kewenangan:
- `SUPER_ADMIN`: mengelola platform dan tenant pada tingkat platform serta melihat metrik agregat yang diizinkan; tidak mengakses detail SO, pembayaran tenant, HPP, stok, atau mutasi operasional tenant.
- `OWNER`: memantau kinerja dan mengelola kewenangan Owner yang telah disepakati; bukan pembuat batch dan bukan pemberi persetujuan PO.
- `ADMIN`: mengelola penjualan dan administrasi, termasuk pembuatan batch setelah DP valid serta persetujuan PO.
- `GUDANG`: mengelola material, MRP, PO, penerimaan, dan alokasi; tidak membuat batch.
- `PRODUKSI`: mengeksekusi batch yang diterbitkan Admin, melakukan pencatatan hasil, QC, packing, dan shipment; tidak membuat batch.
- `CUSTOMER`: hanya melihat tracking dan riwayat pesanan miliknya.

Ruang lingkup yang tidak ditambahkan: public checkout/keranjang belanja, marketplace commission, biaya pendaftaran tenant, payroll, general ledger, rework, dan multi-warehouse kompleks.

Dokumentasi `PROJECT_SPEC.md`, `README.md`, dan `docs/PERMISSION_MATRIX.md` diselaraskan dengan implementasi serta alur Admin-only batch yang tercatat pada `docs/PROJECT_ALIGNMENT_AUDIT.md` dan `docs/business-rules.md`.

## 5. Cakupan Audit dan Pemeriksaan Statis

| Pemeriksaan | Hasil |
|---|---:|
| Blade view yang ditemukan | 142 |
| Route Laravel terdaftar | 243 |
| Referensi route di Blade | 472 |
| Referensi route yang tidak terdaftar | 0 |
| Referensi view dari controller | 131 |
| View controller yang tidak ditemukan | 0 |
| Link anchor kosong / `href="#"` | 0 |
| Tombol `type="button"` tanpa aksi (tidak termasuk tombol disabled bersyarat) | 0 |
| Utility ukuran Tailwind tidak valid yang ditemukan setelah perbaikan | 0 |
| Error parsing CSS pada dua stylesheet utama | 0 |
| Error kompilasi Blade | 0 dari 142 view |

Pemeriksaan tambahan:
- `php artisan route:list --json` berhasil dan menampilkan 243 route.
- Kompilasi langsung seluruh 142 Blade view berhasil.
- `php -l` pada `PlatformDashboardController.php` berhasil.
- Pemeriksaan CSS menggunakan parser menghasilkan nol error.

## 6. Batasan Verifikasi Runtime

Automated PHPUnit test suite belum dapat dijalankan pada lingkungan pemeriksaan ini karena ekstensi PHP `dom`, `mbstring`, dan `xmlwriter` tidak tersedia. Runtime PHP juga tidak menyediakan driver database SQLite/MySQL. Karena itu, hasil audit ini memastikan konsistensi statis route, view, Blade, CSS, dan struktur alur; pengujian klik langsung, query database pada browser, serta screenshot render akhir tetap perlu dijalankan pada lingkungan lokal project yang memiliki ekstensi PHP dan database yang sesuai.

**Catatan:** file `.env` lokal tidak disertakan dalam arsip hasil perbaikan. Gunakan `.env` milik lingkungan lokal dan jangan membagikan kredensial database atau password operasional.


## Koreksi palet visual 3 November 2026

Paket koreksi ini menyelaraskan warna brand lama burgundy/rose ke palet final NAYARA: biru utama `#093FB4`, biru gelap `#082A73`, putih dasar `#FFFCFB`, pink lembut `#FFD8D8`, dan aksen oranye `#ED3500`. Perubahan mencakup halaman login, layout guest, layout ERP, layout Super Admin, stylesheet bersama, stylesheet Super Admin, serta warna brand yang tertanam pada Blade views dan service UI. Warna status semantik seperti sukses, peringatan, dan bahaya tetap dibedakan. URL stylesheet diberi versi baru agar browser tidak memakai cache CSS lama.
