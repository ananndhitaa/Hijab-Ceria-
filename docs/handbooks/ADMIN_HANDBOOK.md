# BUKU PANDUAN ADMIN (OPERASIONAL & KEUANGAN TENANT)
**Sistem:** NAYARA SaaS Platform  
**Target Pengguna:** Staf Administrator Perusahaan Konveksi  
**Fase:** 11–Final (Release Candidate)

---

## 1. Peran & Tanggung Jawab
Admin adalah penggerak operasional utama perusahaan konveksi. Admin mengelola siklus pesanan pelanggan dari komunikasi awal hingga pengiriman, serta bertanggung jawab atas otorisasi pengeluaran dan persetujuan pengadaan bahan baku.

---

## 2. Alur Kerja Harian Admin

### 2.1 Manajemen Sales Order (Pesanan Pelanggan)
1. **Penerimaan Pesanan:**
   - Calon pelanggan menghubungi melalui WhatsApp resmi toko.
   - Buat pesanan baru di `/admin/orders/create` dengan status awal `KOMUNIKASI`.
2. **Negosiasi & Status Deal:**
   - Setelah rincian produk, varian, kuantiti, dan harga disepakati, pesanan berpindah ke tahap penagihan.
3. **Pencatatan Down Payment (DP):**
   - Masukkan pembayaran DP pelanggan di menu pembayaran `/admin/payments/create`.
   - **Aturan Kritis:** Produksi hanya dapat dibuka jika telah ada pembayaran DP dengan status `CONFIRMED` dan nominal $> 0$. Pesanan dengan DP berstatus `PENDING` atau `CANCELLED` tidak dapat masuk antrean potong/jahit.

### 2.2 Persetujuan Purchase Order (Eksklusif Admin)
> [!IMPORTANT]
> **Kewenangan Tunggal:** Persetujuan PO adalah hak prerogatif Admin. Tim Gudang hanya membuat draf PO, dan Owner tidak memiliki wewenang approval ini.
1. Buka menu **Persetujuan Pembelian** di `/admin/purchases/approvals`.
2. Tinjau draf PO yang diajukan oleh staf Gudang (kuantiti bahan, harga satuan supplier, total komitmen kas).
3. Bandingkan dengan kebutuhan bahan dari pesanan aktif (MRP).
4. Klik **Setujui PO (Approve)** atau **Tolak PO (Reject)** dengan alasan yang jelas.

### 2.3 Pembayaran Supplier & Pelunasan
1. Setelah PO disetujui dan barang diterima oleh Gudang, buka menu pembayaran pembelian.
2. Catat bukti dan tanggal transfer pembayaran kepada supplier.
3. Kas keluar tercatat secara otomatis dan mengurangi saldo kas operasional.

### 2.4 Pencatatan Beban Operasional Bisnis (Expenses)
1. Buka menu `/admin/expenses`.
2. Catat biaya operasional (listrik, sewa, gaji, bensin, promosi, dsb).
3. Jika ada koreksi pada data yang sudah disimpan, masukkan alasan perubahan (*amendment reason*). Sistem mencatat riwayat audit secara permanen dan tidak dapat dimanipulasi.
