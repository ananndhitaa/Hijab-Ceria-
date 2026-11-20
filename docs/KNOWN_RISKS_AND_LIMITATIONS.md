# DAFTAR RISIKO, BATASAN & KEPUTUSAN TERBUKA (KNOWN RISKS & LIMITATIONS)
**Sistem:** NAYARA Multi-Tenant SaaS ERP  
**Fase:** 11–Final (Release Candidate)  
**Tanggal:** 22 Oktober 2026

---

## 1. Keputusan Kebijakan Bisnis Terbuka (Open Business Decisions)

### 1.1 Penanganan Over-Quota saat Paket Premium Kedaluwarsa (Status: DECISION REQUIRED)
- **Kondisi:** Saat paket Premium berakhir, kuota etalase publik tenant berkurang kembali ke 5 slot dasar (+ slot permanen). Jika tenant sebelumnya telah menerbitkan 15 produk, terjadi kondisi over-quota.
- **Implementasi Aman yang Diterapkan:**
  - Sistem **TIDAK MENGHAPUS** produk apa pun secara otomatis (tidak ada penghapusan produk tertua atau terbaru sepihak).
  - Seluruh data produk internal ERP tetap utuh 100%.
  - Sistem menampilkan kartu peringatan khusus (*Over-Quota Banner*) di dasbor Owner.
  - Penerbitan produk publik baru **diblokir** sampai Owner secara manual memilih produk mana yang akan disembunyikan (*unpublish*) atau membeli paket baru.
- **Tindakan Lanjutan Sebelum Produksi:** Manajemen bisnis NAYARA harus menandatangani kebijakan resmi apakah produk ke-6 s/d ke-15 otomatis diubah menjadi draf atau tetap tayang dengan masa tenggang tertentu (*grace period*).

### 1.2 Penetapan Harga Layanan Setup & Pelatihan (Status: POLICY PENDING)
- **Kondisi:** Modul permintaan layanan konsultasi, setup pabrik, dan pelatihan staf produksi telah selesai secara alur kerja. Namun harga paket resmi belum diputuskan oleh manajemen.
- **Implementasi Aman:**
  - Owner dapat mengajukan permohonan layanan, rincian kebutuhan, dan tanggal yang diinginkan.
  - Super Admin dapat meninjau dan mencatat penawaran kustom (*custom quote amount*) kasus per kasus.
  - Tidak ada harga paket buatan yang dipaksakan.

---

## 2. Batasan Teknis & Mitigasi Risiko

### 2.1 Mode Isolasi Multi-Tenant Operasional
- **Status Flag:** `nayara.tenant_isolation_enabled = false` secara default pada lingkungan produksi sampai cutover migrasi live disetujui.
- **Mitigasi:** `EnsureUserIsActive` dan `LoginController` mengunci akses tenant baru dari rute ERP jika isolasi masih bernilai `false`, guna mencegah kebocoran data antar-perusahaan akibat kueri tak bertuan.

### 2.2 Integrasi WhatsApp Publik
- Tautan WhatsApp menggunakan format standar `https://wa.me/{nomor}?text={pesan}` yang di-encode secara aman. Kelancaran pembukaan aplikasi bergantung pada ketersediaan WhatsApp pada perangkat pengguna.
