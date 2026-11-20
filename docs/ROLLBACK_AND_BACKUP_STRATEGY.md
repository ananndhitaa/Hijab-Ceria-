# STRATEGI CADANGAN & PEMULIHAN (ROLLBACK & BACKUP STRATEGY)
**Sistem:** NAYARA Multi-Tenant SaaS ERP  
**Fase:** 11–Final (Release Candidate)  
**Tanggal:** 22 Oktober 2026

---

## 1. Kebijakan Basis Data Terlindungi (Protected Database Policy)
- Database operasional utama: `hijab_erp` dan `hijab_erp_nayara_rehearsal`.
- **Aturan Absolut:** Dilarang keras menjalankan `migrate:fresh`, `db:wipe`, atau `migrate:rollback` pada database operasional tanpa persetujuan tertulis pimpinan dan cadangan fisik yang tervalidasi.

---

## 2. Prosedur Pencadangan (Backup Procedure)

### 2.1 Cadangan Skema & Data Lengkap
Sebelum melakukan cutover migrasi apa pun:
```bash
# Dump fisik database dengan konsistensi InnoDB
mysqldump -u root -p --single-transaction --routines --triggers hijab_erp > backup_hijab_erp_pre_fase11.sql
```

### 2.2 Verifikasi Integritas Cadangan
Periksa ukuran file SQL hasil dump dan pastikan tidak ada baris yang terpotong sebelum melanjutkan operasi rilis.

---

## 3. Strategi Pemulihan (Rollback Procedure)

### 3.1 Rollback Kode Aplikasi (Git)
Jika terjadi anomali kritis pasca-deploy rilis:
```bash
# Kembalikan codebase ke commit checkpoint rilis stabil sebelumnya
git checkout [LAST_APPROVED_TAG_OR_COMMIT]
```

### 3.2 Rollback Migrasi Skema
Setiap migrasi Fase 11 telah dilengkapi dengan blok `down()` yang aman dan idempoten:
- Menghapus indeks `idx_products_outlet_published` dan kolom publikasi di `products`.
- Menghapus tabel: `platform_notifications`, `platform_service_requests`, `platform_refunds`, `ad_campaign_revisions`, `ad_campaigns`, `tenant_subscriptions`, `platform_invoices`, `outlet_listing_slots`.
```bash
# Rollback terbatas hanya untuk batch migrasi Fase 11
php artisan migrate:rollback --step=6
```
