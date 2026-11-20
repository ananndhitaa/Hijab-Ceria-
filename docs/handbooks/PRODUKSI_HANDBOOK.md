# BUKU PANDUAN PRODUKSI (LANTAI PRODUKSI & QC)
**Sistem:** NAYARA SaaS Platform  
**Target Pengguna:** Manajer Pabrik, Supervisor Cutting/Sewing, dan Staf QC  
**Fase:** 11–Final (Release Candidate)

---

## 1. Peran & Tanggung Jawab
Tim Produksi bertanggung jawab atas eksekusi Surat Perintah Kerja (SPK) batch produksi, pemotongan kain, penjahitan, penyablonan/printing, pencatatan bahan riil terpakai, serta pengawasan mutu (Quality Control) hingga tahap pengemasan.

---

## 2. Alur Kerja Lantai Produksi

### 2.1 Pembuatan & Penerimaan Batch Produksi (SPK)
1. Pesanan yang telah dikonfirmasi DP-nya oleh Admin akan muncul di daftar antrean produksi `/produksi/batches`.
2. Klik **Buat Batch SPK** untuk memecah pesanan ke dalam batch kerja sesuai kapasitas mesin jahit.
3. Tentukan PIC operator dan target penyelesaian.

### 2.2 Pencatatan Pemakaian Bahan Baku Riil
1. Setelah bahan baku dialokasikan oleh tim Gudang, tim potong mencatat pemakaian riil di `/produksi/batches/{id}/materials`.
2. Masukkan kuantiti meter/yard kain yang benar-benar terpakai.
3. Jika ada sisa kain yang layak pakai (*surplus*), catat pengembalian ke gudang melalui fitur retur sisa bahan agar tidak terjadi pemborosan HPP.

### 2.3 Pelaksanaan Quality Control (QC)
1. Setelah proses jahit dan finishing selesai, staf QC memeriksa setiap helai hijab di `/produksi/batches/{id}/qc`.
2. Catat jumlah barang lolos kontrol kualitas (**Good**) dan jumlah barang cacat (**Reject**).
> [!IMPORTANT]
> **Konsistensi Data Mutlak:**
> Total output riil wajib memenuhi rumus: $\text{Good} + \text{Reject} = \text{Total Output Hasil Kerja}$.
> Hasil output riil tidak boleh menimpa atau mengubah target rencana awal pesanan.

### 2.4 Pengemasan (Packing) & Serah Terima Pengiriman
1. Barang lolos QC (`Good`) diteruskan ke meja pengemasan di `/produksi/packing`.
2. Tempelkan label kode barcode / SKU dan kemas sesuai standar packaging pesanan.
3. Konfirmasi status **Selesai Packing (Siap Kirim)** agar Admin dapat memproses pengiriman bersama ekspedisi kurir.
