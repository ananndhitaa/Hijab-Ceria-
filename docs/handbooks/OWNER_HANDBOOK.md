# BUKU PANDUAN OWNER (PEMILIK PERUSAHAAN KONVEKSI)
**Sistem:** NAYARA SaaS Platform  
**Target Pengguna:** Pemilik Perusahaan Konveksi Hijab (Tenant)  
**Fase:** 11–Final (Release Candidate)

---

## 1. Peran & Batasan Kritis
Sebagai Pemilik (Owner), Anda memegang kepemilikan penuh atas perusahaan konveksi Anda di NAYARA. Anda mengontrol karyawan, memantau laba-rugi, mengelola etalase publik, dan membeli paket layanan platform.

> [!IMPORTANT]
> **ATURAN BISNIS KRITIS (PERSETUJUAN PO):**
> Sesuai tata kelola NAYARA yang telah disetujui, **Persetujuan Purchase Order (PO) HANYA dapat dilakukan oleh peran ADMIN**. Owner **TIDAK DAPAT** menyetujui Purchase Order. Dokumen perencanaan lama yang menyatakan Owner menyetujui PO telah digantikan secara permanen oleh aturan ini.

---

## 2. Fitur Pokok Owner

### 2.1 Saldo Awal Kas Operasional (One-Time Setup)
- Menu: `/owner/opening-cash`
- Digunakan untuk memasukkan modal kas operasional awal perusahaan Anda saat pertama kali bergabung.
- Bersifat permanen dan terkunci setelah disimpan untuk menjamin integritas pembukuan kas.

### 2.2 Manajemen Karyawan
- Menu: `/owner/employees`
- Daftarkan staf Anda: Admin, Gudang, dan Produksi.
- Setiap staf memiliki username dan password masing-masing dengan batasan hak akses sesuai tugasnya.

### 2.3 Manajemen Etalase Publik & Kuota Slot
- Menu: `/owner/publications`
- Setiap perusahaan mendapatkan **5 slot produk gratis** untuk dipajang di Showcase Publik NAYARA.
- Status produk internal ERP tidak terbatas. Hanya produk yang diterbitkan ke publik yang memotong kuota slot.
- Anda dapat menambah kapasitas slot permanen (+5 slot Rp49.000 atau +10 slot Rp89.000) atau berlangganan paket Premium (+10 slot sementara).

### 2.4 Berlangganan Premium & Add-On Analitik
- Menu: `/owner/subscriptions`
- **Paket Premium:** Rp59.000 (1 bln), Rp169.000 (3 bln), Rp319.000 (6 bln), Rp569.000 (12 bln). Memberikan +10 slot produk publik, analitik grafik lengkap, dan ekspor laporan ke PDF/Excel.
- **Financial Analytics Add-On:** Rp45.000 (1 bln), Rp129.000 (3 bln), Rp249.000 (6 bln), Rp489.000 (12 bln). Memberikan analitik mendalam dan ekspor laporan tanpa bonus kuota etalase.
- **Upgrade Add-On ke Premium:** Anda dapat meng-upgrade Add-On aktif ke Premium dengan membayar selisih harga prorata selama sisa masa aktif minimal 24 jam.

### 2.5 Periklanan (Iklan Toko & Iklan Produk)
- Menu: `/owner/ads`
- **Iklan Toko (Center Highlight):** Menampilkan banner toko Anda secara bergilir di bagian tengah landing page (rotasi 7 detik). Maksimal 1 kampanye bersamaan per perusahaan.
- **Iklan Produk (Etalase Unggulan):** Menampilkan kartu produk pilihan Anda di etalase terdepan dengan badge `SPONSOR`.
- Alur: Ajukan materi -> Tunggu review Super Admin -> Pilih jadwal & durasi -> Bayar invoice (2 jam) -> Tayang.

### 2.6 Analisis Keuangan Bisnis
- Menu: `/owner/analytics`
- Menyajikan ringkasan omzet penjualan riil, pembayaran DP & lunas yang sudah diterima, HPP produksi, laba kotor, beban operasional, laba bersih, dan perhitungan ROI resmi.
- Ekspor PDF dan Excel dapat diunduh jika Anda memiliki hak akses Premium atau Analytics Add-On aktif.
