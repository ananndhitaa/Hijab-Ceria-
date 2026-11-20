# PANDUAN SHOWCASE & ETALASE PUBLIK NAYARA
**Sistem:** NAYARA SaaS Platform  
**Target Pengguna:** Pengunjung Publik, Pembeli Grosir, dan Tenant  
**Fase:** 11–Final (Release Candidate)

---

## 1. Konsep Showcase Publik NAYARA
Showcase Publik NAYARA (`/storefront`) **BUKANLAH** marketplace transaksi langsung (seperti e-commerce checkout keranjang), melainkan **katalog etalase digital multi-tenant** yang memamerkan profil perusahaan konveksi hijab dan karya produk unggulannya.

---

## 2. Fitur Pengunjung Publik

### 2.1 Menjelajahi Toko Konveksi (`/storefront/shops`)
- Publik dapat mencari pabrik konveksi hijab berdasarkan nama perusahaan atau kota domisili.
- Setiap kartu toko menampilkan nama konveksi, kota, nomor kontak WhatsApp terverifikasi, jumlah produk terbit, dan tautan ke profil toko.

### 2.2 Menjelajahi Etalase Produk (`/storefront`)
- Bagian **Center Highlight**: Menampilkan banner sponsor toko aktif dengan rotasi halus 7 detik per giliran.
- Bagian **Etalase Unggulan**: Menampilkan kartu produk bersponsor dengan label `SPONSOR` transparan dan elegan.
- Bagian **Semua Produk Etalase**: Menampilkan seluruh produk aktif terbit dari semua toko independen.
- Pengunjung dapat memfilter produk berdasarkan kategori hijab (misal: Bergo, Pashmina, Segi Empat) atau mencari berdasarkan nama model.

### 2.3 Rincian Produk Publik (`/storefront/products/{id}`)
- Pengunjung dapat melihat foto produk utama, kisaran harga publik, kategori, dan deskripsi etalase yang menarik.
- **Tombol WhatsApp Resmi:** Klik tombol "Pesan via WhatsApp" untuk langsung membuka WhatsApp Web / Aplikasi WA menuju kontak resmi pemilik toko dengan teks otomatis:
  ```
  Halo [Nama Toko], saya tertarik memesan produk [Nama Produk] yang saya lihat di NAYARA Showcase.
  ```

---

## 3. Batasan Privasi & Keamanan Informasi
Untuk melindungi hak cipta dan rahasia dagang masing-masing konveksi:
1. **Harga Pokok (HPP) Terlindungi:** Publik tidak dapat melihat modal kain, biaya jahit, atau margin keuntungan toko.
2. **Resep Bahan (BOM) Tersembunyi:** Komposisi bahan baku spesifik tidak ditampilkan di halaman publik.
3. **Data Supplier Rahasia:** Nama dan kontak supplier bahan baku terisolasi di modul internal Gudang.
4. **Tidak Ada Open Redirect:** Tautan WhatsApp divalidasi dan di-encode secara ketat ke domain resmi `wa.me` tanpa kerentanan redirect terbuka.
