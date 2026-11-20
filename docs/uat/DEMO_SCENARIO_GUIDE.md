# PANDUAN SKENARIO DEMO NAYARA SAAS ERP
**Dokumen:** Demo Walkthrough & Script Pengujian Langsung  
**Fase:** 11–Final (Release Candidate)  
**Bahasa:** Bahasa Indonesia Alami

---

## 1. Skenario Demo 1: Menjelajahi Etalase Publik & Hubungi Toko
1. Buka peramban di `http://localhost:8000/storefront`.
2. Tunjukkan **Center Highlight**: perhatikan banner toko berputar otomatis setiap 7 detik.
3. Arahkan mouse ke **Etalase Unggulan**: perhatikan kartu produk dengan badge `SPONSOR`.
4. Klik salah satu produk untuk membuka detail produk publik.
5. Klik tombol **Pesan via WhatsApp**: tunjukkan tautan terhubung langsung ke `wa.me` toko dengan format pesan rapi tanpa mengekspos biaya modal konveksi.

---

## 2. Skenario Demo 2: Siklus Hidup Pendaftaran & Persetujuan Tenant
1. Buka `http://localhost:8000/tenant/register`.
2. Isi formulir:
   - Nama Perusahaan: `Konveksi Hijab Melati`
   - Nama Pemilik: `Ibu Melati`
   - Email: `melati@konveksi.test`
   - Nomor WhatsApp: `081234567890`
   - Password: `Password123!`
3. Klik **Daftarkan Perusahaan**. Tunjukkan pesan sukses.
4. Buka tab baru, login sebagai **Super Admin** (`superadmin@nayara.test` / `Password123!`).
5. Buka `/platform/registrations`, buka pendaftaran Ibu Melati, klik tombol hijau **Setujui Pendaftaran**.
6. Tunjukkan bahwa toko telah disetujui dan tenant baru siap beroperasi.

---

## 3. Skenario Demo 3: Monetisasi (Premium, Kuota Slot & Ekspor Analitik)
1. Login sebagai **Owner** (`owner.bandung@nayara.test` / `Password123!`).
2. Buka menu **Etalase Publik** di `/owner/publications`.
3. Tunjukkan indikator kuota slot: `5 / 5 Slot Gratis Terpakai`.
4. Buka menu **Langganan & Layanan** di `/owner/subscriptions`.
5. Pilih **Paket Premium 1 Bulan (Rp59.000)** dan klik **Buat Invoice**.
6. Simulasikan unggah bukti transfer.
7. Login sebagai Super Admin di `/platform/billing`, klik **Verifikasi Pembayaran**.
8. Kembali ke layar Owner:
   - Kuota etalase bertambah menjadi 15 slot (+10 slot Premium).
   - Menu **Analisis Keuangan** `/owner/analytics` kini membuka tombol **Ekspor PDF** dan **Ekspor Excel**.
   - Klik **Ekspor PDF** untuk menunjukkan laporan keuangan berformat cetak resmi.

---

## 4. Skenario Demo 4: Kampanye Iklan Toko & Algoritma Putaran
1. Sebagai Owner, buka menu **Iklan Platform** di `/owner/ads`.
2. Klik **Buat Kampanye Iklan Toko**.
3. Masukkan judul "Hijab Tren 2026", tagline "Pabrik Hijab Kualitas Ekspor", dan unggah banner.
4. Super Admin membuka `/platform/ads` dan klik **Setujui Materi**.
5. Owner memilih jadwal (misal: 3 hari) dan anggaran Rp300.000.
6. Lakukan pembayaran dan verifikasi oleh Super Admin.
7. Buka halaman depan `/storefront` untuk menyaksikan banner toko tayang di Center Highlight.

---

## 5. Skenario Demo 5: Alur ERP Terintegrasi & Pembatasan Wewenang PO
1. Login sebagai **Admin** (`admin.bandung@nayara.test` / `Password123!`).
2. Buat pesanan baru untuk pelanggan.
3. Masukkan pembayaran Down Payment (DP) Rp500.000 (status CONFIRMED).
4. Login sebagai **Gudang**: buat draf PO bahan baku ke supplier.
5. Login sebagai **Owner**: tunjukkan bahwa menu/tombol persetujuan PO **TIDAK ADA** pada akun Owner.
6. Login kembali sebagai **Admin**: buka `/admin/purchases/approvals` dan klik **Setujui PO**.
7. Login sebagai **Gudang**: klik **Konfirmasi Penerimaan Barang**. Tunjukkan stok kain bertambah.
8. Login sebagai **Produksi**: buka batch SPK, catat pemakaian kain riil, masukkan hasil QC (misal: 95 Good, 5 Reject = 100 Total).
9. Selesaikan packing. Pesanan siap dikirimkan.
