# NAYARA GROUP — SIX-ROLE PERMISSION MATRIX
**Dokumen:** Matriks Hak Akses & Kewenangan Enam Peran  
**Fase:** 11–Final (Prompt 3 of 3)  
**Tanggal:** 22 Oktober 2026

---

## 1. Ikhtisar Kewenangan & Struktur Peran
NAYARA mengimplementasikan pemisahan tugas (*separation of duties*) yang ketat untuk menjamin integritas operasional dan finansial.
- **SUPER_ADMIN:** Otoritas tingkat platform (SaaS Provider). Terpisah dari data internal perusahaan konveksi.
- **OWNER:** Pemilik perusahaan konveksi tenant. Mengelola tim, menetapkan saldo awal kas satu kali, mengawasi kinerja, membeli layanan platform. **DILARANG menyetujui Purchase Order (PO)**.
- **ADMIN:** Administrator operasional tenant. Mengelola Sales Order, pembayaran pelanggan, **menyetujui Purchase Order (eksklusif Admin)**, dan mencatat pengeluaran operasional.
- **GUDANG:** Penanggung jawab logistik tenant. Mengelola stok bahan baku, menyusun draf PO, penerimaan barang fisik, dan alokasi bahan.
- **PRODUKSI:** Penanggung jawab lantai produksi tenant. Menjalankan batch yang diterbitkan Admin, mencatat bahan riil terpakai, QC good/reject, pengemasan, dan pengiriman.
- **CUSTOMER:** Pelanggan tenant. Memantau progres pesanan secara read-only melalui token pelacakan publik.
- **PENGUNJUNG PUBLIK:** Tanpa login, dapat menelusuri etalase publik dan menghubungi via WhatsApp.

---

## 2. Matriks Hak Akses Antar Peran

| Domain / Aksi Sistem | SUPER_ADMIN | OWNER | ADMIN | GUDANG | PRODUKSI | CUSTOMER | PUBLIK |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Platform Management** |
| Registrasi Tenant Baru (Review/Approve) | **YA** | Tidak | Tidak | Tidak | Tidak | Tidak | Form Daftar |
| Moderasi & Review Materi Iklan | **YA** | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak |
| Verifikasi Pembayaran Platform | **YA** | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak |
| Eksekusi & Bukti Transfer Refund | **YA** | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak |
| Pengelolaan Layanan Setup/Training | **YA** | Request | Tidak | Tidak | Tidak | Tidak | Tidak |
| Dasbor Analitik Global Platform | **YA** | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak |
| **Monetisasi & Etalase Publik** |
| Menjelajahi Toko & Produk Publik | YA | YA | YA | YA | YA | YA | **YA** |
| Hubungi WhatsApp Toko Resmi | YA | YA | YA | YA | YA | YA | **YA** |
| Kelola Publikasi Produk ke Etalase | Tidak | **YA** | Tidak | Tidak | Tidak | Tidak | Tidak |
| Pembelian Premium & Slot Kuota | Tidak | **YA** | Tidak | Tidak | Tidak | Tidak | Tidak |
| Pengajuan Kampanye Iklan (Toko/Produk) | Tidak | **YA** | Tidak | Tidak | Tidak | Tidak | Tidak |
| Reschedule & Revisi Iklan | Tidak | **YA** | Tidak | Tidak | Tidak | Tidak | Tidak |
| Ekspor Laporan Keuangan (PDF/CSV) | Tidak | **YA (Berbayar)** | Tidak | Tidak | Tidak | Tidak | Tidak |
| **Operasional ERP Tenant** |
| Saldo Awal Kas Operasional (One-Time) | Tidak | **YA** | Tidak | Tidak | Tidak | Tidak | Tidak |
| Pengawasan Finansial & HPP (Read-Only) | Tidak | **YA** | Read/Write | Tidak | Tidak | Tidak | Tidak |
| Kelola Akun Karyawan & Reset Password | Tidak | **YA** | Tidak | Tidak | Tidak | Tidak | Tidak |
| Input Sales Order & Negosiasi Deal | Tidak | Tidak | **YA** | Tidak | Tidak | Tidak | Tidak |
| Konfirmasi Down Payment (DP) | Tidak | Tidak | **YA** | Tidak | Tidak | Tidak | Tidak |
| **Persetujuan Purchase Order (PO)** | Tidak | **DILARANG** | **HANYA ADMIN** | Draf Saja | Tidak | Tidak | Tidak |
| Pembayaran Invoice Supplier | Tidak | Tidak | **YA** | Tidak | Tidak | Tidak | Tidak |
| Pencatatan Beban Operasional Bisnis | Tidak | Audit Saja | **YA** | Tidak | Tidak | Tidak | Tidak |
| Manajemen Stok & Draf PO Pembelian | Tidak | Tidak | Tidak | **YA** | Tidak | Tidak | Tidak |
| Penerimaan Fisik Bahan dari Supplier | Tidak | Tidak | Tidak | **YA** | Tidak | Tidak | Tidak |
| Alokasi Bahan ke Batch Produksi | Tidak | Tidak | Tidak | **YA** | Tidak | Tidak | Tidak |
| Buat Batch Produksi (SPK) | Tidak | Tidak | **YA** | Tidak | Tidak | Tidak | Tidak |
| Catat Penggunaan Bahan Riil | Tidak | Tidak | Tidak | Tidak | **YA** | Tidak | Tidak |
| Catat Hasil QC (Good & Reject) | Tidak | Tidak | Tidak | Tidak | **YA** | Tidak | Tidak |
| Penyelesaian Packing & Siap Kirim | Tidak | Tidak | Tidak | Tidak | **YA** | Tidak | Tidak |
| Pelacakan Status Pesanan Sendiri | Tidak | Tidak | Tidak | Tidak | Tidak | **YA** | Tidak |

---

## 3. Penegakan Aturan Kritis
1. **Persetujuan PO (Purchase Order):**
   Sesuai mandat final Section A, persetujuan PO menjadi wewenang **eksklusif ADMIN**. Peran OWNER diblokir di tingkat middleware, policy, dan controller dari tindakan persetujuan PO.
2. **Pemisahan Finansial Super Admin:**
   `SUPER_ADMIN` dilarang keras membuat atau mengedit transaksi ERP internal tenant (Sales Order, Expense, PO, pembayaran tenant, dan mutasi stok). Aksesnya dibatasi pada data platform serta metadata/direktori tenant yang diperlukan (`outlets`, `tenant_registrations`, `tenant_registration_histories`, `tenant_subscriptions`, `platform_invoices`, `ad_campaigns`, `platform_refunds`, `platform_service_requests`, dan `platform_notifications`), tanpa membuka detail transaksi operasional tenant.
3. **Pembuatan Batch Produksi:** Hanya ADMIN yang dapat menerbitkan batch dari Sales Order yang telah memenuhi syarat DP. GUDANG dan PRODUKSI hanya menjalankan tugas sesuai tahapnya; PRODUKSI tidak membuat batch baru.
4. **Pemberian Hak Akses Berbayar:**
   Fitur ekspor laporan keuangan dan penambahan kuota etalase diperiksa langsung di level backend (`TenantEntitlementService`), bukan sekadar menyembunyikan tombol UI.
