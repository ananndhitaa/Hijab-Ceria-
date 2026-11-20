# NAYARA GROUP — FINAL FEATURE INVENTORY
**Dokumen:** Final Feature Inventory  
**Fase:** 11–Final (Prompt 3 of 3)  
**Tanggal:** 22 Oktober 2026  
**Status:** Release Candidate Ready (Evaluasi Manual UAT)

---

## 1. Ikhtisar Platform
NAYARA adalah Multi-Tenant SaaS ERP untuk perusahaan konveksi hijab independen di mana **1 Outlet = 1 Perusahaan Independen**. Platform memisahkan wewenang platform (`SUPER_ADMIN`) dengan wewenang operasional tenant (`OWNER`, `ADMIN`, `GUDANG`, `PRODUKSI`, `CUSTOMER`, serta Pengunjung Publik).

---

## 2. Inventaris Modul & Fitur

### Modul A: Keamanan & Multi-Tenant Isolation
| ID Fitur | Nama Fitur | Status | Penjelasan Implementasi |
|---|---|---|---|
| SEC-01 | Tenant Activation Gate | SELESAI | Saat `tenant_isolation_enabled = false`, pengguna tenant baru diblokir dari akses ERP tak terbatas. Sistem monolith lama tetap berfungsi normal. |
| SEC-02 | Multi-Tenant Database Scoping | SELESAI | TenantContext otomatis menyuntikkan `outlet_id` pada seluruh entitas bisnis saat mode isolasi aktif. |
| SEC-03 | Anti-Cross Tenant Leakage | SELESAI | Tidak ada data pesanan, keuangan, stok, atau bahan yang bocor antar outlet. Diverifikasi via unit/feature tests. |
| SEC-04 | Super Admin Non-ERP Scope | SELESAI | `SUPER_ADMIN` terisolasi dari konteks outlet tenant dan tidak memiliki izin mutasi transaksi finansial ERP tenant. |

---

### Modul B: Public Storefront & Etalase Publik
| ID Fitur | Nama Fitur | Status | Penjelasan Implementasi |
|---|---|---|---|
| PUB-01 | Direktori Toko Publik | SELESAI | Menampilkan seluruh toko aktif terverifikasi tanpa login. Pencarian berdasarkan nama & kota. |
| PUB-02 | Profil Toko Publik | SELESAI | Menampilkan kontak WhatsApp terverifikasi, alamat resmi, deskripsi, dan katalog produk terbit. |
| PUB-03 | Katalog Produk Publik | SELESAI | Menampilkan gambar produk, harga publik, deskripsi etalase. Bebas dari kebocoran HPP, BOM, dan data supplier. |
| PUB-04 | Kontak WhatsApp Terverifikasi | SELESAI | Tombol chat langsung terhubung ke nomor WhatsApp resmi toko dengan template pesan otomatis aman. |

---

### Modul C: Kuota Etalase & Publikasi Produk
| ID Fitur | Nama Fitur | Status | Penjelasan Implementasi |
|---|---|---|---|
| QTA-01 | Kuota Dasar Gratis (5 Slot) | SELESAI | Setiap tenant mendapatkan kuota 5 produk publik aktif gratis. Produk internal ERP tidak dibatasi. |
| QTA-02 | Slot Permanen Berbayar | SELESAI | Pembelian paket +5 slot (Rp49.000) dan +10 slot (Rp89.000) berlaku permanen sepanjang umur tenant. |
| QTA-03 | Bonus Slot Premium (+10 Slot) | SELESAI | Langganan Premium aktif memberikan kuota sementara +10 slot. |
| QTA-04 | Transaksional Publishing Gate | SELESAI | Penerbitan produk memvalidasi kuota secara transaksional dengan row-locking untuk mencegah bypass persaingan. |
| QTA-05 | Penanganan Over-Quota saat Premium Berakhir | DECISION REQUIRED | Sesuai Section L, sistem mendeteksi kelebihan kuota secara aman, memberi peringatan Owner, dan memblokir penerbitan baru tanpa menghapus data produk secara sepihak. |

---

### Modul D: Langganan Premium & Billing Platform
| ID Fitur | Nama Fitur | Status | Penjelasan Implementasi |
|---|---|---|---|
| BIL-01 | Paket Langganan Premium | SELESAI | Harga final: 1 Bln (Rp59.000), 3 Bln (Rp169.000), 6 Bln (Rp319.000), 12 Bln (Rp569.000). |
| BIL-02 | Financial Analytics Add-On | SELESAI | Harga final: 1 Bln (Rp45.000), 3 Bln (Rp129.000), 6 Bln (Rp249.000), 12 Bln (Rp489.000). |
| BIL-03 | Upgrade Add-On ke Premium | SELESAI | Upgrade prorata selisih harga durasi paket jika sisa masa aktif >= 24 jam. Invoice kedaluwarsa 12 jam. |
| BIL-04 | Verifikasi Pembayaran Idempoten | SELESAI | Invoice platform terpisah dari kas operasional tenant. Verifikasi Super Admin bersifat idempoten tanpa duplikasi hak. |

---

### Modul E: Periklanan Toko (Iklan Toko — Center Highlight)
| ID Fitur | Nama Fitur | Status | Penjelasan Implementasi |
|---|---|---|---|
| ADS-01 | Banner Center Highlight | SELESAI | Menampilkan 1 banner profil toko secara rotasi 7 detik per giliran. |
| ADS-02 | Kapasitas Global (5 Slot) | SELESAI | Maksimal 5 kampanye Iklan Toko berjalan bersamaan secara global. Maksimal 1 kampanye per tenant. |
| ADS-03 | Siklus 20 Putaran & Algoritma Remainder | SELESAI | Siklus 140 detik (20 giliran). Setiap kampanye mendapat minimal 1 giliran. Sisa giliran dibagi proporsional dengan metode sisa terbesar (Hamilton-Hare) dan interleaving merata. |
| ADS-04 | Effective Bid per Day Ranking | SELESAI | Perankingan murni berdasarkan Total Budget / Durasi Hari. Tie-break: bukti transfer sah lebih awal. |

---

### Modul F: Periklanan Produk (Iklan Produk — Etalase Unggulan)
| ID Fitur | Nama Fitur | Status | Penjelasan Implementasi |
|---|---|---|---|
| ADP-01 | Kartu Etalase Unggulan | SELESAI | Menampilkan hingga 6 kartu produk bersponsor dengan badge transparan SPONSOR. |
| ADP-02 | Validasi Kelayakan Produk | SELESAI | Iklan produk wajib merujuk ke produk publik yang aktif dan terbit. |
| ADP-03 | Perankingan Efektif Harian | SELESAI | Urutan peringkat kartu berdasarkan bid efektif harian tertinggi tanpa memanipulasi statistik penjualan organik. |

---

### Modul G: Reservasi, Review & Siklus Hidup Iklan
| ID Fitur | Nama Fitur | Status | Penjelasan Implementasi |
|---|---|---|---|
| REV-01 | Moderasi Materi Iklan | SELESAI | Super Admin mereview materi (APPROVED / NEEDS_REVISION / REJECTED) dengan catatan spesifik. |
| REV-02 | Reservasi Kapasitas & Kedaluwarsa Invoice | SELESAI | Invoice iklan berlaku 2 jam. Bukti pembayaran tepat waktu menahan reservasi hingga +24 jam saat verifikasi. |
| REV-03 | Batas Reschedule & Revisi Materi | SELESAI | Maksimal 2x reschedule jadwal sebelum tayang. Maksimal 2x revisi kreatif yang disetujui. |
| REV-04 | Kebijakan Pengembalian Dana (Refund) | SELESAI | Pembatalan sebelum tayang: Refund 100%. Setelah tayang: 0%. Gangguan teknis NAYARA: Kompensasi proporsional durasi aktual hilang. |

---

### Modul H: Analisis Keuangan & Notifikasi
| ID Fitur | Nama Fitur | Status | Penjelasan Implementasi |
|---|---|---|---|
| ANL-01 | Dasbor Analisis Keuangan Owner | SELESAI | Metrik pendapatan, pembayaran diterima, HPP, laba kotor/bersih, beban, dan ROI terisolasi per tenant. |
| ANL-02 | Ekspor PDF & CSV Berbayar | SELESAI | Ekspor fail-closed di sisi server tanpa langganan Premium atau Analytics Add-On aktif. |
| ANL-03 | Dasbor Analisis Global Super Admin | SELESAI | Agregat total toko, pendapatan platform, utilisasi iklan, refund, dan Top Toko/Produk organik. |
| ANL-04 | Layanan Setup & Pelatihan | POLICY PENDING | Alur pengajuan dan kustomisasi penawaran berjalan operasional, harga paket menunggu keputusan bisnis final. |
| NOT-01 | Sistem Notifikasi & Pengingat SLA | SELESAI | Notifikasi in-app untuk review iklan, pengingat revisi (12h/24h sebelum selesai), dan status pembayaran. |
