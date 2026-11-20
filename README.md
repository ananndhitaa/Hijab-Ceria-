# NAYARA — Multi-Tenant SaaS ERP Konveksi Hijab

> **Rancang Bangun Sistem Perencanaan Kebutuhan Bahan dan Perhitungan Hasil Produksi Hijab Berbasis Web**

NAYARA adalah platform SaaS ERP untuk perusahaan konveksi hijab dengan model produksi berdasarkan pesanan (*Make-to-Order*). Satu outlet merupakan satu tenant independen. Kewenangan Super Admin platform dipisahkan dari aktivitas operasional setiap tenant.

## Ruang Lingkup Utama

- **ERP manufaktur tenant:** Sales Order, Master Produk/Varian, BOM, MRP, stok bahan, Purchase Order, produksi, QC, pengiriman, HPP, dan analitik Owner.
- **Platform Super Admin:** pendaftaran dan direktori tenant, tagihan platform, langganan, kuota etalase, iklan, refund, analitik agregat, broadcast, audit trail, monitoring, dan pengaturan kebijakan.
- **Public storefront:** direktori toko dan katalog produk publik serta kontak WhatsApp resmi.
- **Customer portal:** tracking dan riwayat pesanan untuk pelanggan yang memiliki akun; pelacakan token tersedia tanpa login.

NAYARA bukan marketplace checkout. Pesanan utama masuk melalui komunikasi WhatsApp dan dibukukan oleh Admin. Sistem tidak membuat Sales Order otomatis dari klik WhatsApp.

## Teknologi

- PHP `>= 8.3`
- Laravel `^13.17`
- MySQL / MariaDB 10.4+
- Blade, Tailwind CSS, Alpine.js
- Composer 2.x

## Instalasi Lokal

1. Pasang dependency:

   ```bash
   composer install
   ```

2. Siapkan file environment:

   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

3. Buat database kosong dan sesuaikan koneksi pada `.env`:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=hijab_erp
   DB_USERNAME=root
   DB_PASSWORD=

   APP_TIMEZONE=Asia/Jakarta
   APP_LOCALE=id
   ```

4. Jalankan migrasi dan seeder pada database pengembangan yang baru:

   ```bash
   php artisan migrate --seed
   ```

   **Penting:** jangan menjalankan `migrate:fresh` atau `db:wipe` pada database yang sudah berisi data. Ikuti kebijakan backup dan migrasi korektif pada `PROJECT_SPEC.md` dan `docs/`.

5. Jalankan aplikasi:

   ```bash
   php artisan serve
   ```

   Buka `http://127.0.0.1:8000`.

## Role dan Batas Kewenangan

| Role | Lingkup |
|---|---|
| **SUPER_ADMIN** | Mengelola platform dan tenant, bukan transaksi internal tenant. |
| **OWNER** | Mengawasi kinerja usaha, HPP, profitabilitas, karyawan, publikasi, langganan, dan layanan platform. |
| **ADMIN** | Mengelola pelanggan, Sales Order, master produk, BOM, harga jual, penerbitan batch, invoice, pembayaran, persetujuan PO, dan beban usaha. |
| **GUDANG** | Mengelola material, stok, MRP, pemasok, draf PO, penerimaan barang, dan alokasi bahan. |
| **PRODUKSI** | Menjalankan batch yang diterbitkan Admin, mencatat hasil aktual, QC Good/Reject, packing, dan shipment. |
| **CUSTOMER** | Melihat tracking, histori pesanan, invoice, dan informasi pengiriman miliknya sendiri. |

### Akun Pengembangan Tenant

Password akun pengembangan yang dibuat oleh `UserSeeder` adalah `password`.

| Role | Email |
|---|---|
| ADMIN | `admin@example.com` |
| GUDANG | `gudang@example.com` |
| PRODUKSI | `produksi@example.com` |
| OWNER | `owner@example.com` |
| CUSTOMER | `customer@example.com` |

Akun Super Admin **tidak dibuat dengan password default oleh seeder**. Buat akun secara eksplisit melalui perintah provisioning yang disediakan:

```bash
php artisan nayara:super-admin:provision-operational
```

Perintah akan meminta email, nama, dan password secara interaktif. Gunakan hanya pada database target yang benar dan tinjau konfirmasi sebelum mengubah akun yang sudah ada. Jangan memasukkan password operasional ke dalam file atau riwayat shell.

## Alur Bisnis Inti

1. Customer menghubungi Admin melalui WhatsApp; Admin mencatat Customer dan membuat Sales Order berstatus `KOMUNIKASI`.
2. Admin mengonfirmasi kesepakatan (`DEAL`), menerbitkan invoice, lalu mencatat pembayaran DP yang telah terkonfirmasi.
3. Setelah DP terkonfirmasi lebih dari nol, Admin menerbitkan Batch Produksi dan menetapkan target serta PIC Produksi.
4. Gudang menjalankan MRP, menangani shortage melalui PO, menerima bahan, dan mengalokasikan material ke batch.
5. Produksi menjalankan printing/finishing, mencatat hasil aktual dan pemakaian bahan, melakukan QC Good/Reject, lalu menyelesaikan packing. Tidak ada alur rework.
6. Admin memproses pelunasan; Produksi menangani shipment sesuai status pembayaran. Customer dapat melacak pesanan.
7. Owner memantau HPP dan kinerja finansial tenant. Super Admin hanya memantau metrik agregat dan layanan pada lingkup platform.

## Pengujian

Jalankan automated test suite:

```bash
php artisan test
```

Test menggunakan SQLite in-memory sesuai konfigurasi `phpunit.xml`; test tidak diarahkan ke database operasional.

## Dokumentasi Proyek

- `PROJECT_SPEC.md` — spesifikasi sistem dan batasan.
- `docs/FINAL_FEATURE_INVENTORY.md` — inventaris fitur final.
- `docs/PERMISSION_MATRIX.md` — matriks hak akses.
- `docs/PROJECT_ALIGNMENT_AUDIT.md` — alur bisnis ERP tenant dan batas kewenangan operasional.
- `docs/REQUIREMENT_TRACEABILITY_MATRIX.md` — ketertelusuran kebutuhan dan pengujian.
- `docs/open-decisions.md` — keputusan yang sudah dikunci dan keputusan bisnis yang masih menunggu kebijakan.

