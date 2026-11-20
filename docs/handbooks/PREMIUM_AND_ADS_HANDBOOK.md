# PANDUAN MONETISASI, PREMIUM & PERIKLANAN NAYARA
**Sistem:** NAYARA SaaS Platform  
**Target Pengguna:** Owner Perusahaan Konveksi & Tim Komersial Platform  
**Fase:** 11–Final (Release Candidate)

---

## 1. Paket Langganan & Add-On

### 1.1 Paket Langganan Premium
Harga final yang telah disetujui:
- **1 Bulan:** Rp59.000
- **3 Bulan:** Rp169.000
- **6 Bulan:** Rp319.000
- **12 Bulan:** Rp569.000

**Manfaat Premium:**
1. Tambahan **+10 slot produk publik** selama masa langganan aktif.
2. Analitik keuangan visual lengkap (tren omzet, breakdown beban operasional).
3. Hak ekspor laporan keuangan ke format **PDF** dan **Excel/CSV**.
> *Catatan:* Seluruh fitur inti ERP (Sales Order, Pembelian, Gudang, Produksi, Kas) tetap **100% GRATIS** tanpa kewajiban langganan Premium.

### 1.2 Financial Analytics Add-On
Bagi tenant yang hanya membutuhkan fitur ekspor laporan keuangan tanpa memerlukan kuota slot tambahan:
- **1 Bulan:** Rp45.000
- **3 Bulan:** Rp129.000
- **6 Bulan:** Rp249.000
- **12 Bulan:** Rp489.000

### 1.3 Upgrade Add-On ke Premium
- Jika tenant memiliki Add-On aktif dengan sisa waktu $\ge 24$ jam, tenant berhak upgrade ke Premium.
- Biaya upgrade dihitung secara proporsional dari selisih harga durasi paket:
  - 1 Bulan: Selisih Rp14.000
  - 3 Bulan: Selisih Rp40.000
  - 6 Bulan: Selisih Rp70.000
  - 12 Bulan: Selisih Rp80.000
- Tanggal jatuh tempo akhir paket lama tetap dipertahankan.
- Invoice upgrade memiliki masa kedaluwarsa 12 jam.

### 1.4 Pembelian Kuota Slot Permanen
Tenant dapat menambah slot produk etalase secara permanen sepanjang usia aktif tenant:
- **Paket +5 Slot:** Rp49.000 (dapat dibeli berulang kali)
- **Paket +10 Slot:** Rp89.000 (dapat dibeli berulang kali)
- Rumus Kapasitas Total Etalase: $5\text{ (dasar gratis)} + \text{Slot Permanen} + 10\text{ (jika Premium aktif)}$.

---

## 2. Platform Periklanan (Advertising)

### 2.1 Iklan Toko (Center Highlight)
- **Objek:** Profil perusahaan konveksi.
- **Kapasitas Global:** Maksimal 5 kampanye bersamaan secara global.
- **Batasan Tenant:** Maksimal 1 kampanye Iklan Toko berjalan per tenant.
- **Tampilan:** Banner tengah landing page dengan rotasi 7 detik per giliran.
- **Algoritma Putaran 20 Giliran (Siklus 140 Detik):**
  - Setiap sponsor aktif dijamin mendapat minimal 1 giliran.
  - Sisa giliran ($20 - N$) didistribusikan secara proporsional berdasarkan *Effective Bid per Day* menggunakan metode Hamilton-Hare (sisa terbesar).
  - Distribusi urutan putaran diacak merata (*fair interleaving*) agar banner sponsor yang sama tidak muncul berturut-turut.

### 2.2 Iklan Produk (Etalase Unggulan)
- **Objek:** Produk publik yang sedang aktif terbit.
- **Kapasitas Global:** Hingga 6 kartu produk bersponsor.
- **Tampilan:** Kartu produk di baris terdepan etalase publik dengan badge transparan `SPONSOR`.
- **Perankingan:** Berdasarkan nilai bid efektif harian tertinggi ($\text{Total Budget} / \text{Jumlah Hari}$).
- Tie-break: Bukti transfer sah diterima lebih awal. Jika masih sama, urutan ID kampanye terkecil.

### 2.3 Siklus Hidup & Aturan Reservasi Iklan
1. **Pengajuan Materi:** Owner mengunggah judul, tagline, dan gambar banner.
2. **Review Super Admin:** SLA 12 jam untuk memeriksa kelayakan konten.
3. **Pemesanan Jadwal & Invoice:** Owner memilih tanggal mulai dan durasi (hari utuh 24 jam). Sistem mengunci kapasitas jadwal secara transaksional dengan window setengah-terbuka $[\text{mulai}, \text{selesai})$.
4. **Batas Waktu Invoice:** Invoice berlaku **tepat 2 jam**. Jika belum lapor bayar, reservasi lepas otomatis.
5. **Hold Verifikasi (+24 Jam):** Jika Owner telah mengunggah bukti bayar tepat waktu, kuota ditahan hingga +24 jam saat Super Admin melakukan verifikasi bank.

### 2.4 Kebijakan Pembatalan & Pengembalian Dana (Refund)
- **Dibatalkan Owner SEBELUM jam tayang pertama:** Refund 100%.
- **Dibatalkan Owner SETELAH penayangan dimulai:** Tidak ada refund (0%).
- **Materi Ditolak Final setelah pembayaran:** Refund 100%.
- **Gangguan Teknis Terverifikasi dari Sisi NAYARA:** Kompensasi proporsional dengan rumus:
  $$\text{Nominal Refund} = \text{Total Bayar Terverifikasi} \times \frac{\text{Durasi Hilang Riil}}{\text{Total Durasi yang Dibeli}}$$
- Pembayaran refund dicatat dengan bukti transfer nyata bank dalam batas operasional SLA 7 hari kerja.
