# PANDUAN LENGKAP & CHECKLIST MANUAL USER ACCEPTANCE TESTING (UAT)
**Sistem:** NAYARA Multi-Tenant SaaS ERP  
**Target Penguji:** Tim QA, Penguji Non-Developer, Manajemen & Stakeholder  
**Bahasa:** Bahasa Indonesia Alami  
**Fase:** 11–Final (Release Candidate)

---

## 1. Panduan Menjalankan Lingkungan Pengujian

### 1.1 Prasyarat & Perangkat Lunak
- Sistem Operasi: Windows 10/11 atau Linux/macOS.
- PHP 8.2+ / 8.4 terpasang.
- Composer 2.x terpasang.
- Docker Desktop (opsional untuk MariaDB terisolasi).

### 1.2 Langkah Menjalankan Aplikasi
Buka terminal (PowerShell atau Bash) pada direktori proyek:
```bash
# 1. Jalankan server Laravel lokal
php artisan serve --port=8000

# 2. Buka terminal kedua untuk asset frontend Vite (jika melakukan build dinamis)
npm run build

# 3. Buka terminal ketiga untuk menjalankan antrean scheduler (opsional untuk testing otomatis)
php artisan schedule:run
```
Akses aplikasi melalui peramban: **`http://localhost:8000`**

---

## 2. Akun Demonstrasi Sintetis untuk Pengujian

Gunakan akun demonstrasi berikut untuk menguji masing-masing peran:

| Peran | Email Login | Password Default | Konteks Outlet / Toko |
|---|---|---|---|
| **SUPER_ADMIN** | `superadmin@nayara.test` | `Password123!` | Platform Global (Tanpa Outlet) |
| **OWNER A** | `owner.bandung@nayara.test` | `Password123!` | Outlet 1 (Nayara Hijab Bandung) |
| **OWNER B** | `owner.jakarta@nayara.test` | `Password123!` | Outlet 2 (Nayara Hijab Jakarta) |
| **ADMIN A** | `admin.bandung@nayara.test` | `Password123!` | Outlet 1 (Nayara Hijab Bandung) |
| **ADMIN B** | `admin.jakarta@nayara.test` | `Password123!` | Outlet 2 (Nayara Hijab Jakarta) |
| **GUDANG A** | `gudang.bandung@nayara.test` | `Password123!` | Outlet 1 (Nayara Hijab Bandung) |
| **PRODUKSI A**| `produksi.bandung@nayara.test`| `Password123!` | Outlet 1 (Nayara Hijab Bandung) |
| **PENGUNJUNG**| *Tanpa Login* | *Tanpa Password* | Akses `/storefront` |

---

## 3. Checklist Skenario UAT Manual

Kategori Hasil UAT:
- `PASS`: Berhasil diuji langsung oleh manusia dan hasil sesuai ekspektasi.
- `FAIL`: Ditemukan ketidaksesuaian fungsional atau error.
- `BLOCKED`: Terhalang oleh langkah sebelumnya yang gagal.
- `NOT TESTED`: Belum dieksekusi oleh penguji manusia.

> [!NOTE]
> Seluruh baris di bawah berstatus awal **`NOT TESTED`** hingga pengguna manual manusia benar-benar mengujinya di peramban web.

### Skenario 1: Showcase Publik & Tanpa Login
- **Langkah:** Buka URL `http://localhost:8000/storefront` tanpa melakukan login.
- **Ekspektasi:** Halaman landing page NAYARA terbuka. Terlihat banner toko (Center Highlight), etalase kartu produk bersponsor, dan katalog produk. Tidak ada informasi biaya produksi atau data supplier yang bocor.
- **Status:** `NOT TESTED` (Menunggu eksekusi penguji)

### Skenario 2: Pendaftaran Tenant Baru & Review Super Admin
- **Langkah:** Buka `/tenant/register`, isi formulir konveksi baru, lalu login sebagai Super Admin di `/platform/registrations` untuk menyetujui.
- **Ekspektasi:** Data pendaftaran tersimpan dengan status `PENDING`. Super Admin dapat menyetujui dan tenant baru siap diaktifkan.
- **Status:** `NOT TESTED` (Menunggu eksekusi penguji)

### Skenario 3: Verifikasi Isolasi Antar Tenant (Owner A vs Owner B)
- **Langkah:** Login sebagai Owner A di tab biasa, dan Owner B di tab incognito. Periksa pesanan, stok, dan laporan keuangan masing-masing.
- **Ekspektasi:** Data milik Toko Bandung tidak pernah terlihat oleh Toko Jakarta, begitu pula sebaliknya.
- **Status:** `NOT TESTED` (Menunggu eksekusi penguji)

### Skenario 4: Pembelian Langganan Premium & Ekspor Laporan
- **Langkah:** Login sebagai Owner A, buka `/owner/subscriptions`, pilih paket 1 Bulan (Rp59.000), laporkan bukti transfer fiktif. Login sebagai Super Admin di `/platform/billing`, klik "Verifikasi Pembayaran". Kembali ke Owner A dan unduh PDF laporan keuangan di `/owner/analytics`.
- **Ekspektasi:** Status paket Premium berubah menjadi AKTIF, kuota slot bertambah +10, dan laporan PDF terunduh sempurna.
- **Status:** `NOT TESTED` (Menunggu eksekusi penguji)

### Skenario 5: Pengajuan & Penayangan Iklan Toko (Center Highlight)
- **Langkah:** Login sebagai Owner A, ajukan Iklan Toko di `/owner/ads/create`. Super Admin menyetujui materi. Owner memilih durasi dan melaporkan pembayaran. Super Admin memverifikasi dana. Buka halaman depan `/storefront`.
- **Ekspektasi:** Banner toko muncul di Center Highlight dan berganti dengan mulus setiap 7 detik.
- **Status:** `NOT TESTED` (Menunggu eksekusi penguji)

### Skenario 6: Alur Inti ERP (Order -> DP -> Batch -> PO -> QC -> Selesai)
- **Langkah:** Admin A membuat pesanan dan mencatat pembayaran DP. Produksi A membuat batch SPK. Gudang A membuat draf PO bahan, Admin A menyetujui PO. Gudang A menerima bahan. Produksi A mencatat bahan terpakai dan hasil QC.
- **Ekspektasi:** DP mengunci izin produksi; hanya Admin yang dapat menyetujui PO; hasil $\text{Good} + \text{Reject} = \text{Total Output}$; status akhir pesanan menjadi selesai.
- **Status:** `NOT TESTED` (Menunggu eksekusi penguji)
