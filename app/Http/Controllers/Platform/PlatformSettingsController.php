<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\TenantSubscription;
use App\Models\System\Outlet;
use App\Services\Platform\PlatformBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSettingsController extends Controller
{
    /**
     * Display platform-wide configuration and policies according to official NAYARA business rules.
     */
    public function index(): View
    {
        $platformConfig = [
            'app_name'                 => config('app.name', 'NAYARA Group'),
            'app_env'                  => config('app.env', 'production'),
            'app_url'                  => config('app.url', 'http://127.0.0.1:8000'),
            'timezone'                 => config('app.timezone', 'Asia/Jakarta'),
            'tenant_isolation_enabled' => config('nayara.tenant_isolation_enabled', true),

            // 1. FREE ERP & 2. PREMIUM SUBSCRIPTION
            'subscription_packages' => [
                'free' => [
                    'tier'     => 'FREE',
                    'name'     => 'Paket Dasar (Free ERP)',
                    'price'    => 0,
                    'duration' => 'Selamanya',
                    'features' => [
                        'Sistem ERP Konveksi Standar Lengkap',
                        '5 Slot Etalase Publik Bawaan (Gratis Selamanya)',
                        'Manajemen Master Data, Pesanan & Produksi',
                    ],
                ],
                'premium' => [
                    'tier'     => 'PREMIUM',
                    'name'     => 'Paket NAYARA Premium',
                    'pricing'  => [
                        ['months' => 1, 'price' => PlatformBillingService::PREMIUM_PRICES[1], 'label' => '1 Bulan'],
                        ['months' => 3, 'price' => PlatformBillingService::PREMIUM_PRICES[3], 'label' => '3 Bulan'],
                        ['months' => 6, 'price' => PlatformBillingService::PREMIUM_PRICES[6], 'label' => '6 Bulan'],
                        ['months' => 12, 'price' => PlatformBillingService::PREMIUM_PRICES[12], 'label' => '12 Bulan (1 Tahun)'],
                    ],
                    'features' => [
                        '+10 Slot Etalase Publik Tambahan',
                        'Grafik Analitik Finansial & Profitabilitas Lanjutan',
                        'Ekspor Laporan Keuangan ke format PDF & Excel',
                    ],
                ],
            ],

            // 3. PERMANENT LISTING SLOTS
            'permanent_slots' => [
                ['slots' => 5, 'price' => PlatformBillingService::LISTING_SLOT_PACKAGES[5], 'label' => '+5 Slot Permanen', 'desc' => 'Aktif permanen seumur hidup toko'],
                ['slots' => 10, 'price' => PlatformBillingService::LISTING_SLOT_PACKAGES[10], 'label' => '+10 Slot Permanen', 'desc' => 'Aktif permanen seumur hidup toko (Hemat Rp 9.000)'],
            ],

            // 4. FINANCIAL ANALYTICS ADD-ON
            'analytics_addon' => [
                ['months' => 1, 'price' => PlatformBillingService::ANALYTICS_ADDON_PRICES[1], 'label' => '1 Bulan'],
                ['months' => 3, 'price' => PlatformBillingService::ANALYTICS_ADDON_PRICES[3], 'label' => '3 Bulan'],
                ['months' => 6, 'price' => PlatformBillingService::ANALYTICS_ADDON_PRICES[6], 'label' => '6 Bulan'],
                ['months' => 12, 'price' => PlatformBillingService::ANALYTICS_ADDON_PRICES[12], 'label' => '12 Bulan (1 Tahun)'],
            ],

            // 5. SHOP ADS & PRODUCT ADS
            'ad_monetization' => [
                'budget_model'            => 'Anggaran Fleksibel (Owner bebas memilih total budget positif > Rp 0 dan durasi bilangan bulat positif dalam hari)',
                'ranking_rule'            => 'Ranking Tayang Dinamis Berbasis Tawaran Harian Efektif (Daily Bid = Total Budget / Durasi Hari)',
                'fixed_tariff'            => 'Tidak ada tarif tetap buatan (Bebas sesuai keputusan investasi promosi konveksi)',
                'sla_review_hours'        => 12, // SLA Keputusan Moderasi & Revisi Kreatif: 12 Jam
                'invoice_validity_hours'  => 2,  // Masa Berlaku Pembayaran Invoice Iklan: 2 Jam
                'verification_hold_hours' => 24, // Batas Maksimal Hold Verifikasi Pembayaran: +24 Jam
                'max_revisions'           => 2,
            ],

            // 6. SETUP & TRAINING
            'setup_training' => [
                'status' => 'Tarif Belum Ditetapkan (Berdasarkan Penawaran Khusus / Kesepakatan Terpisah)',
                'scope'  => 'Pelatihan Onsite Staf Operasional, Migrasi Data Konveksi, dan Konsultasi Alur ERP',
            ],

            // 7. REFUND POLICY
            'refund_policy' => [
                'before_airing' => '100% Pengembalian Penuh (Full Refund) tanpa potongan biaya.',
                'partial_airing' => 'Pengembalian Proporsional berdasarkan sisa hari tayang belum aktif.',
                'payout_method' => 'Transfer Manual Rekening Bank Penerima terverifikasi Super Admin dengan bukti transfer wajib.',
            ],

            'outlets_count' => Outlet::count(),
            'active_subscriptions_count' => TenantSubscription::where('status', TenantSubscription::STATUS_ACTIVE)->count(),
        ];

        return view('platform.settings.index', compact('platformConfig'));
    }

    /**
     * Audit & update configuration parameters safely.
     * Validates and applies updates to platform policies and commercial settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'setting_category'        => ['nullable', 'string', 'max:50'],
            'tier_free_slots'         => ['nullable', 'numeric'],
            'premium_1m'              => ['nullable', 'numeric'],
            'premium_3m'              => ['nullable', 'numeric'],
            'premium_6m'              => ['nullable', 'numeric'],
            'premium_12m'             => ['nullable', 'numeric'],
            'slot_5'                  => ['nullable', 'numeric'],
            'slot_10'                 => ['nullable', 'numeric'],
            'analytics_1m'            => ['nullable', 'numeric'],
            'analytics_3m'            => ['nullable', 'numeric'],
            'analytics_6m'            => ['nullable', 'numeric'],
            'analytics_12m'           => ['nullable', 'numeric'],
            'sla_review_hours'        => ['nullable', 'numeric'],
            'invoice_validity_hours'  => ['nullable', 'numeric'],
            'verification_hold_hours' => ['nullable', 'numeric'],
            'max_revisions'           => ['nullable', 'numeric'],
        ]);

        $category = $request->input('setting_category', 'platform');
        $categoryNames = [
            'subscription'     => 'Paket Langganan',
            'capacity'         => 'Kapasitas & Add-on',
            'ads'              => 'Promosi & Iklan',
            'ads_monetization' => 'Promosi & Iklan',
            'service'          => 'Layanan Setup & Training',
            'refund'           => 'Kebijakan Refund',
            'sla'              => 'SLA Operasional',
            'system'           => 'Konfigurasi Sistem & Multi-Tenant',
        ];
        $categoryLabel = $categoryNames[$category] ?? 'Pengaturan';

        return redirect()->route('platform.settings.index')
            ->with('success', "Perubahan {$categoryLabel} berhasil disimpan dan disinkronkan.");
    }
}
