# BUKU PANDUAN SUPER ADMIN (PLATFORM OPERATOR)
**Sistem:** NAYARA SaaS Platform  
**Target Pengguna:** Operator & Administrator Platform NAYARA  
**Fase:** 11–Final (Release Candidate)

---

## 1. Tanggung Jawab Utama
Sebagai Super Admin Platform, Anda memegang otoritas tata kelola platform secara global. Anda terpisah dari transaksi harian masing-masing pabrik konveksi hijab.

Tanggung jawab utama:
1. **Review & Persetujuan Pendaftaran Tenant:** Menilai calon tenant baru yang mendaftar melalui form publik.
2. **Verifikasi Pembayaran Platform:** Memeriksa bukti transfer bank untuk langganan Premium, kuota slot permanen, Iklan Toko, Iklan Produk, dan Add-On Analytics.
3. **Moderasi Konten & Materi Iklan:** Menilai kelayakan banner, tagline, dan judul iklan sebelum diterbitkan.
4. **Penyelesaian Pengembalian Dana (Refund):** Memproses transfer pengembalian dana jika terjadi pembatalan pra-tayang atau gangguan teknis berbayar.
5. **Manajemen Layanan Khusus:** Mengatur ruang lingkup, jadwal, dan estimasi penawaran untuk layanan setup onboarding atau pelatihan staf produksi.
6. **Pemantauan Metrik Global:** Mengawasi kesehatan operasional dan tren bisnis platform.

---

## 2. Prosedur Operasional Standar (SOP)

### SOP 1: Review Pendaftaran Tenant Baru
1. Buka menu **Tenant Registrations** di `/platform/registrations`.
2. Klik nama calon tenant untuk melihat detail perusahaan, kontak WA, email, dan alamat.
3. Lakukan verifikasi kontak melalui WhatsApp atau panggilan telepon.
4. Pilih tindakan:
   - **Setujui (Approve):** Tenant akan dibuatkan record outlet dan akun Owner baru. Email notifikasi otomatis disiapkan.
   - **Minta Revisi (Needs Revision):** Masukkan alasan spesifik (misal: scan izin usaha kurang jelas).
   - **Tolak (Reject):** Masukkan alasan penolakan final.

### SOP 2: Verifikasi Pembayaran Platform
1. Buka menu **Billing Platform** di `/platform/billing`.
2. Filter status `PAYMENT_SUBMITTED` untuk melihat pembayaran yang menunggu konfirmasi.
3. Periksa kecocokan nomor rekening pengirim, nama mutasi, dan nominal transfer dengan invoice.
4. Klik **Verifikasi Dana Diterima**:
   - Sistem secara idempoten akan mengaktifkan paket Premium / kuota slot / kampanye iklan terkait.
   - Hak tenant langsung bertambah saat itu juga tanpa perlu restart server.

### SOP 3: Moderasi Materi Iklan (SLA 12 Jam)
1. Buka menu **Iklan Platform** di `/platform/ads`.
2. Buka antrean materi `PENDING_REVIEW`.
3. Periksa kesesuaian gambar/banner (resolusi, kesopanan norma hijab, tidak mengandung unsur SARA/pelanggaran hak cipta).
4. Ambil keputusan dalam batas SLA 12 jam:
   - **Setujui (Approve):** Tenant dapat melanjutkan ke tahap reservasi jadwal dan penerbitan invoice.
   - **Perlu Revisi (Needs Revision):** Catat revisi yang dibutuhkan (misal: "Ukuran teks terlalu kecil").
   - **Tolak (Reject):** Batalkan pengajuan jika melanggar ketentuan pokok etika platform.

### SOP 4: Penyelesaian Refund
1. Buka menu **Refund Platform** di `/platform/refunds`.
2. Periksa rincian pengajuan refund dan nomor rekening tujuan.
3. Lakukan transfer bank dari rekening operasional platform ke rekening Owner.
4. Unggah bukti transfer settlement dan klik **Selesaikan Refund**.
