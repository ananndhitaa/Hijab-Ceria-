# NAYARA GROUP — DATABASE SCHEMA SUMMARY
**Dokumen:** Ringkasan Skema Database Multi-Tenant  
**Fase:** 11–Final (Prompt 3 of 3)  
**Tanggal:** 22 Oktober 2026

---

## 1. Arsitektur Multi-Tenant Database
Sistem menggunakan pendekatan **Shared Database, Shared Schema** dengan penegakan partisi logis berbasis kolom diskriminator `outlet_id`.
- **Primary Tenant Key:** `outlet_id` (foreign key ke tabel `outlets`).
- **Database Engine:** MariaDB 10.4.32 / MySQL 8.0 kompatibel (InnoDB dengan foreign key cascade/restrict ketat).
- **Concurrency & Locking:** `lockForUpdate()` diterapkan pada tabel penomoran dokumen, slot kuota, dan reservasi kapasitas iklan.

---

## 2. Struktur Tabel Tambahan Fase 11 (Monetisasi & Showcase Publik)

### 2.1 `products` (Penambahan Kolom Showcase Publik)
- `is_published` (`BOOLEAN`, default `false`): Status publikasi ke etalase publik.
- `public_description` (`TEXT`, nullable): Deskripsi khusus etalase publik (tanpa data teknis konveksi).
- `public_price` (`DECIMAL(18,2)`, nullable): Harga jual publik.
- `featured_image` (`VARCHAR(255)`, nullable): Path gambar utama produk.
- `public_category` (`VARCHAR(100)`, nullable): Kategori etalase publik (misal: Bergo, Pashmina, Segi Empat).
- Indeks: `idx_products_outlet_published (outlet_id, is_published)`, `is_published`, `public_category`.

### 2.2 `outlet_listing_slots` (Kuota Slot Publikasi Tenant)
- `id` (`BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`)
- `outlet_id` (`BIGINT UNSIGNED`, unique foreign key ke `outlets`)
- `purchased_slots` (`UNSIGNED INTEGER`, default `0`): Jumlah slot permanen yang telah dibeli tenant.
- Indeks unik: `outlet_id`.

### 2.3 `platform_invoices` (Penagihan Layanan Platform)
- `id` (`BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`)
- `invoice_no` (`VARCHAR(50)`, unique): Nomor invoice berformat `INV-PLT-YYYYMMDD-XXXX`.
- `outlet_id` (`BIGINT UNSIGNED`, foreign key ke `outlets`)
- `created_by` (`BIGINT UNSIGNED`, nullable, foreign key ke `users`)
- `service_type` (`VARCHAR(50)`): `PREMIUM`, `ANALYTICS_ADDON`, `LISTING_SLOTS`, `UPGRADE_PREMIUM`, `SHOP_AD`, `PRODUCT_AD`, `SETUP_TRAINING`.
- `package_duration_months` (`TINYINT UNSIGNED`, nullable): Durasi paket (1, 3, 6, 12).
- `amount` (`DECIMAL(18,2)`): Total tagihan dalam Rupiah.
- `status` (`VARCHAR(30)`): `UNPAID`, `PAYMENT_SUBMITTED`, `VERIFIED`, `EXPIRED`, `CANCELLED`, `REFUNDED`.
- `payment_method` (`VARCHAR(50)`, nullable)
- `payment_proof_path` (`VARCHAR(255)`, nullable)
- `payment_submitted_at` (`DATETIME`, nullable)
- `expires_at` (`DATETIME`): Batas waktu pembayaran (2 jam untuk iklan, 12 jam untuk upgrade, dsb).
- `verified_at` (`DATETIME`, nullable)
- `verified_by` (`BIGINT UNSIGNED`, nullable, foreign key ke `users`)
- `metadata` (`JSON`, nullable): Rincian teknis pesanan layanan.

### 2.4 `tenant_subscriptions` (Hak Langganan Tenant)
- `id` (`BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`)
- `outlet_id` (`BIGINT UNSIGNED`, foreign key ke `outlets`)
- `type` (`VARCHAR(50)`): `PREMIUM`, `ANALYTICS_ADDON`.
- `starts_at` (`DATETIME`): Waktu mulai aktif.
- `expires_at` (`DATETIME`): Waktu berakhir aktif.
- `status` (`VARCHAR(30)`): `ACTIVE`, `EXPIRED`, `UPGRADED`, `CANCELLED`.
- `platform_invoice_id` (`BIGINT UNSIGNED`, nullable, foreign key ke `platform_invoices`)
- Indeks: `idx_subscriptions_lookup (outlet_id, type, status)`, `expires_at`.

### 2.5 `ad_campaigns` (Kampanye Iklan Platform)
- `id` (`BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`)
- `outlet_id` (`BIGINT UNSIGNED`, foreign key ke `outlets`)
- `created_by` (`BIGINT UNSIGNED`, nullable, foreign key ke `users`)
- `ad_type` (`VARCHAR(30)`): `SHOP_AD`, `PRODUCT_AD`.
- `product_id` (`BIGINT UNSIGNED`, nullable, foreign key ke `products`)
- `title` (`VARCHAR(150)`): Judul iklan / nama kampanye.
- `tagline` (`VARCHAR(255)`, nullable)
- `banner_path` (`VARCHAR(255)`, nullable)
- `total_budget` (`DECIMAL(18,2)`, default `0`)
- `duration_days` (`UNSIGNED INTEGER`, default `0`)
- `effective_bid_per_day` (`DECIMAL(18,2)`, default `0`)
- `starts_at` (`DATETIME`, nullable)
- `ends_at` (`DATETIME`, nullable)
- `status` (`VARCHAR(30)`): `DRAFT`, `PENDING_REVIEW`, `NEEDS_REVISION`, `APPROVED`, `PAYMENT_PENDING`, `SCHEDULED`, `ACTIVE`, `COMPLETED`, `CANCELLED`, `REJECTED`, `SUSPENDED`.
- `schedule_change_count` (`UNSIGNED SMALLINT`, default `0`)
- `creative_revision_count` (`UNSIGNED SMALLINT`, default `0`)
- `platform_invoice_id` (`BIGINT UNSIGNED`, nullable, foreign key ke `platform_invoices`)
- `verified_payment_at` (`DATETIME`, nullable)
- `review_notes` (`TEXT`, nullable)
- `reviewed_by` (`BIGINT UNSIGNED`, nullable, foreign key ke `users`)
- `reviewed_at` (`DATETIME`, nullable)
- Indeks: `idx_ads_active_schedule (ad_type, status, starts_at, ends_at)`, `idx_ads_outlet_lookup (outlet_id, ad_type, status)`, `effective_bid_per_day`.

### 2.6 `ad_campaign_revisions` (Riwayat Revisi Materi Iklan)
- `id` (`BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`)
- `ad_campaign_id` (`BIGINT UNSIGNED`, foreign key ke `ad_campaigns` cascade on delete)
- `title` (`VARCHAR(150)`)
- `tagline` (`VARCHAR(255)`, nullable)
- `banner_path` (`VARCHAR(255)`, nullable)
- `revision_type` (`VARCHAR(30)`): `CREATIVE`, `FACTUAL`.
- `status` (`VARCHAR(30)`): `PENDING`, `APPROVED`, `REJECTED`.
- `review_notes` (`TEXT`, nullable)
- `reviewed_by` (`BIGINT UNSIGNED`, nullable, foreign key ke `users`)
- `reviewed_at` (`DATETIME`, nullable)

### 2.7 `platform_refunds` (Pengembalian Dana Layanan Platform)
- `id` (`BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`)
- `outlet_id` (`BIGINT UNSIGNED`, foreign key ke `outlets`)
- `platform_invoice_id` (`BIGINT UNSIGNED`, nullable, foreign key ke `platform_invoices`)
- `ad_campaign_id` (`BIGINT UNSIGNED`, nullable, foreign key ke `ad_campaigns`)
- `refund_type` (`VARCHAR(30)`): `FULL_100_PERCENT`, `PROPORTIONAL`.
- `amount` (`DECIMAL(18,2)`)
- `reason` (`TEXT`)
- `status` (`VARCHAR(30)`): `REQUESTED`, `APPROVED`, `COMPLETED`, `REJECTED`.
- `destination_bank_name` (`VARCHAR(100)`)
- `destination_account_number` (`VARCHAR(50)`)
- `destination_account_name` (`VARCHAR(150)`)
- `transfer_proof_path` (`VARCHAR(255)`, nullable)
- `approved_by` (`BIGINT UNSIGNED`, nullable, foreign key ke `users`)
- `approved_at` (`DATETIME`, nullable)
- `completed_by` (`BIGINT UNSIGNED`, nullable, foreign key ke `users`)
- `completed_at` (`DATETIME`, nullable)

### 2.8 `platform_service_requests` (Permintaan Setup & Pelatihan)
- `id` (`BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`)
- `outlet_id` (`BIGINT UNSIGNED`, foreign key ke `outlets`)
- `created_by` (`BIGINT UNSIGNED`, nullable, foreign key ke `users`)
- `service_type` (`VARCHAR(50)`): `SETUP_ONBOARDING`, `TRAINING_STAFF`, `CUSTOM_CONSULTATION`.
- `scope_details` (`TEXT`)
- `preferred_date` (`DATE`, nullable)
- `status` (`VARCHAR(30)`): `SUBMITTED`, `IN_REVIEW`, `SCOPED`, `SCHEDULED`, `IN_PROGRESS`, `COMPLETED`, `CANCELLED`.
- `custom_quote_amount` (`DECIMAL(18,2)`, nullable)
- `super_admin_notes` (`TEXT`, nullable)

### 2.9 `platform_notifications` (Notifikasi Platform & Tenant)
- `id` (`BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`)
- `outlet_id` (`BIGINT UNSIGNED`, nullable, foreign key ke `outlets` cascade on delete)
- `user_id` (`BIGINT UNSIGNED`, nullable, foreign key ke `users` cascade on delete)
- `type` (`VARCHAR(50)`): `AD_REVIEW`, `AD_REMINDER`, `INVOICE_EXPIRY`, `SUBSCRIPTION_EXPIRY`, `REFUND_UPDATE`, `SERVICE_REQUEST`.
- `title` (`VARCHAR(150)`)
- `message` (`TEXT`)
- `action_url` (`VARCHAR(255)`, nullable)
- `is_read` (`BOOLEAN`, default `false`)
