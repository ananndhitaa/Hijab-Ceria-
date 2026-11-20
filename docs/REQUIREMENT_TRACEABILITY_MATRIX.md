# NAYARA GROUP — REQUIREMENT TRACEABILITY MATRIX
**Dokumen:** Matriks Ketertelusuran Kebutuhan (RTM)  
**Fase:** 11–Final (Prompt 3 of 3)  
**Tanggal:** 22 Oktober 2026

---

## Tabel Ketertelusuran Komprehensif

| Bagian Spesifikasi | Komponen / Source Path | Rute / Endpoint | File Pengujian Automated | Status Hasil |
|---|---|---|---|---|
| **C. Git & Runtime Audit** | `composer.json`, `composer.lock` | CLI | `php -v`, `git diff --check` | PASS (PHP 8.4.14, PHPUnit 12.5.33, 62 migration baseline) |
| **D. Critical Tenant Activation Gate** | `app/Http/Middleware/EnsureUserIsActive.php`<br>`app/Http/Controllers/Auth/LoginController.php` | `/login`<br>`/admin/*`, `/owner/*` | `tests/Feature/Platform/TenantActivationSafetyGateTest.php` | PASS (7/7 tests) |
| **E. Protected Database Policy** | Config verification | Internal CLI | Rehearsal scripts strictly targeting port 33069 (`hijab_erp_disposable_test`) | PASS (Protected DBs `hijab_erp` 100% aman) |
| **G. Public Storefront** | `app/Http/Controllers/Storefront/PublicStorefrontController.php`<br>`resources/views/storefront/*` | `storefront.index`<br>`storefront.shops`<br>`storefront.shop`<br>`storefront.product` | `tests/Feature/Platform/StorefrontAndListingSlotsTest.php` | PASS (11/11 tests) |
| **H. Free Listing Capacity & Slots** | `app/Services/Platform/TenantEntitlementService.php`<br>`app/Services/Platform/ProductPublicationService.php` | `owner.publications.publish`<br>`owner.publications.unpublish`<br>`owner.publications.update` | `tests/Feature/Platform/StorefrontAndListingSlotsTest.php` | PASS (Row locking, kuota 5 dasar + purchased + 10 premium) |
| **I. Premium Subscriptions** | `app/Services/Platform/PlatformBillingService.php`<br>`app/Http/Controllers/Owner/OwnerSubscriptionController.php` | `owner.subscriptions.subscribe`<br>`owner.subscriptions.invoice` | `tests/Feature/Platform/PlatformBillingAndSubscriptionTest.php` | PASS (12/12 tests, paket 59k/169k/319k/569k) |
| **J. Financial Analytics Add-On** | `app/Services/Platform/TenantEntitlementService.php`<br>`app/Services/Platform/FinancialAnalyticsService.php` | `owner.subscriptions.addon`<br>`owner.analytics.export-csv`<br>`owner.analytics.export-pdf` | `tests/Feature/Platform/PlatformBillingAndSubscriptionTest.php`<br>`tests/Feature/Platform/OwnerAndPlatformAnalyticsTest.php` | PASS (Paket 45k/129k/249k/489k, export fail-closed) |
| **K. Add-On to Premium Upgrade** | `PlatformBillingService::createUpgradeInvoice` | `owner.subscriptions.upgrade` | `tests/Feature/Platform/PlatformBillingAndSubscriptionTest.php` | PASS (Prorata selisih harga, expired 12 jam, idempotent) |
| **L. Premium Expiry Policy Gap** | `TenantEntitlementService::isOverListingQuota` | `owner.publications.index` | `tests/Feature/Platform/StorefrontAndListingSlotsTest.php` | DECISION REQUIRED (Peringatan aman, blokir publish baru) |
| **M. Platform Payment System** | `app/Http/Controllers/Platform/PlatformBillingReviewController.php` | `platform.billing.index`<br>`platform.billing.verify` | `tests/Feature/Platform/PlatformBillingAndSubscriptionTest.php` | PASS (Verifikasi idempoten, kas terpisah dari tenant) |
| **N. Shop Advertising (Iklan Toko)** | `app/Models/Platform/AdCampaign.php`<br>`app/Services/Platform/AdCapacityAndScheduleService.php` | `owner.ads.store`<br>`storefront.index` | `tests/Feature/Platform/AdCampaignLifecycleAndRankingTest.php` | PASS (5 kampanye global, 1 per tenant) |
| **O & P. Center Highlight Algorithm** | `app/Services/Platform/AdRankingAndDistributionService.php` | `storefront.index` | `tests/Feature/Platform/AdCampaignLifecycleAndRankingTest.php` | PASS (20 putaran, 7s rotasi, 140s siklus, Hamilton-Hare) |
| **Q. Featured Product Ads** | `AdRankingAndDistributionService::getActiveFeaturedProductAds` | `storefront.index` | `tests/Feature/Platform/AdCampaignLifecycleAndRankingTest.php` | PASS (Maks 6 kartu, ranking bid efektif harian, badge SPONSOR) |
| **R. Ad Review & Capacity Integrity** | `app/Http/Controllers/Platform/PlatformAdReviewController.php` | `platform.ads.review`<br>`owner.ads.reserve` | `tests/Feature/Platform/AdCampaignLifecycleAndRankingTest.php` | PASS (Invoice 2 jam, +24h hold bukti bayar, half-open interval) |
| **T. Ad Modifications** | `AdCapacityAndScheduleService::rescheduleCampaign`<br>`AdCapacityAndScheduleService::submitRevision` | `owner.ads.reschedule`<br>`owner.ads.revision` | `tests/Feature/Platform/AdRefundAndModificationTest.php` | PASS (Maks 2x reschedule sebelum tayang, maks 2x revisi kreatif) |
| **U. Ad Cancellation & Refunds** | `app/Services/Platform/AdRefundService.php`<br>`app/Http/Controllers/Platform/PlatformRefundReviewController.php` | `owner.ads.cancel`<br>`platform.refunds.complete` | `tests/Feature/Platform/AdRefundAndModificationTest.php` | PASS (Refund 100% pre-airing, 0% post-airing, kompensasi gangguan) |
| **V. Review SLA & Notifications** | `app/Services/Platform/PlatformNotificationService.php` | `owner.notifications.index` | `tests/Feature/Platform/AdRefundAndModificationTest.php` | PASS (Pengingat SLA 12h & 24h, in-app notification) |
| **W. Platform Investigation & Audit** | `app/Http/Controllers/Platform/*` | `/platform/*` | `tests/Feature/Platform/SuperAdminPlatformTest.php` | PASS (Audit log lengkap, Super Admin tanpa write ERP) |
| **X. Owner Financial Analytics** | `app/Services/Platform/FinancialAnalyticsService.php` | `owner.analytics.index` | `tests/Feature/Platform/OwnerAndPlatformAnalyticsTest.php` | PASS (Data terisolasi tenant, metrik laba/rugi, HPP, ROI) |
| **Y. Super Admin Platform Analytics** | `app/Http/Controllers/Platform/PlatformAnalyticsController.php` | `platform.analytics.index` | `tests/Feature/Platform/OwnerAndPlatformAnalyticsTest.php` | PASS (Agregat platform, Top Toko/Produk organik murni pesanan) |
| **Z. Setup & Training Services** | `app/Http/Controllers/Owner/OwnerServiceRequestController.php`<br>`app/Http/Controllers/Platform/PlatformServiceManagementController.php` | `owner.services.store`<br>`platform.services.update` | `tests/Feature/Platform/OwnerAndPlatformAnalyticsTest.php` | POLICY PENDING (Alur request operasional, harga kustom) |
| **AA. UI & Navigation Polish** | Blade templates, Tailwind, Sidebar, Navigation | All frontends | Browser inspection & automated smoke tests | PASS (Label bahasa Indonesia, badge status, empty states) |
| **AB. Full ERP Workflows** | Existing ERP models & controllers | `/admin/*`, `/gudang/*`, `/produksi/*` | Legacy & ERP System Regression Suites | PASS (Komunikasi -> Deal -> DP -> Batch -> Purchase -> QC -> Complete) |
