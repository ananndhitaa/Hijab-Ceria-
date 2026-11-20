# PANDUAN DEPLOYMENT & RUNBOOK LOKAL NAYARA
**Sistem:** NAYARA Multi-Tenant SaaS ERP  
**Fase:** 11–Final (Release Candidate)  
**Tanggal:** 22 Oktober 2026

---

## 1. Menjalankan Lingkungan Lokal

### 1.1 Menjalankan Service Backend Laravel
```powershell
# Di folder root D:\Magang\hijab
php artisan serve --host=127.0.0.1 --port=8000
```

### 1.2 Menjalankan Asset Frontend Vite
```powershell
# Di folder root D:\Magang\hijab
npm run build
# Atau untuk hot-reload saat pengembangan:
npm run dev
```

### 1.3 Menjalankan Scheduler & Antrean Pekerja (Workers)
Untuk memastikan pelepasan otomatis reservasi iklan kedaluwarsa (2 jam), sinkronisasi kampanye, dan peringatan SLA berjalan:
```powershell
# Jalankan antrean background
php artisan queue:work --tries=3 --timeout=90

# Jalankan scheduler berkala
php artisan schedule:work
```

---

## 2. Parameter Konfigurasi Penting (.env)
- `APP_ENV=local`
- `APP_DEBUG=true` (matikan di production: `APP_DEBUG=false`)
- `NAYARA_TENANT_ISOLATION_ENABLED=false` (tetap false pada database awal, ubah ke true hanya setelah migrasi multi-tenant tervalidasi pada database staging/production terpisah).
