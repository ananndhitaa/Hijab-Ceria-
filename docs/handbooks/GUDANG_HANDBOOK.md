# BUKU PANDUAN GUDANG (LOGISTIK & PENGADAAN BAHAN)
**Sistem:** NAYARA SaaS Platform  
**Target Pengguna:** Staf Logistik & Kepala Gudang Bahan Baku  
**Fase:** 11–Final (Release Candidate)

---

## 1. Peran & Tanggung Jawab
Staf Gudang bertanggung jawab atas ketersediaan fisik bahan kain, benang, renda, dan aksesoris hijab. Gudang memastikan bahan yang dibeli sesuai pesanan dan alokasi bahan ke lantai produksi tercatat akurat tanpa selisih stok fiktif.

---

## 2. Alur Operasional Gudang

### 2.1 Pengajuan Draf Purchase Order (PO)
1. Periksa kebutuhan bahan dari pesanan aktif di menu Material Requirement Planning (MRP).
2. Jika stok fisik tidak mencukupi, buat draf pembelian baru di `/gudang/purchases/create`.
3. Pilih supplier resmi, pilih bahan, masukkan kuantiti yang dibutuhkan dan harga estimasi.
4. Klik **Kirim untuk Persetujuan**. Draf akan masuk ke antrean Admin.
> *Catatan:* Gudang tidak berwenang menyetujui PO. Tunggu status persetujuan dari Admin sebelum memesan ke supplier.

### 2.2 Penerimaan Bahan Fisik dari Supplier
1. Saat pesanan bahan tiba di gudang pabrik, buka PO yang telah disetujui di `/gudang/purchases/{id}`.
2. Hitung dan periksa kondisi fisik rol kain (panjang yard/meter, warna, cacat kain).
3. Catat penerimaan di sistem. Stok fisik di sistem otomatis bertambah (*increment*) secara tepat saat tombol penerimaan dikonfirmasi.

### 2.3 Alokasi Bahan ke Batch Produksi (SPK)
1. Tim Produksi akan menerbitkan Surat Perintah Kerja (SPK) batch produksi.
2. Buka menu alokasi bahan di `/gudang/allocations`.
3. Serahkan rol bahan fisik kepada staf potong/jahit dan konfirmasi alokasi di sistem.
4. Stok gudang akan berkurang (*decrement*) secara otomatis.
5. Bahan yang telah dialokasikan terikat pada nomor batch terkait dan tidak dapat dialokasikan ulang ke batch lain.

### 2.4 Stock Opname (Penyesuaian Fisik Berkala)
1. Lakukan audit fisik stok gudang secara periodik di `/gudang/stock-opnames`.
2. Masukkan hasil hitung fisik riil. Jika terjadi selisih, sistem akan mencatat alasan penyesuaian untuk keperluan transparansi audit.
