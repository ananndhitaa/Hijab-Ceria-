<?php

namespace App\Http\Controllers\Platform;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\System\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformRoleController extends Controller
{
    /**
     * Display Role & Access Control governance matrix and platform administrator accounts.
     * Hardened: Strictly isolates tenant data. Super Admin cannot view tenant users/personnel.
     */
    public function index(Request $request): View
    {
        $selectedRole = $request->query('role');
        $validRoles = array_column(UserRole::cases(), 'value');

        // Only accept recognized UserRole values for matrix filtering
        if ($selectedRole && ! in_array($selectedRole, $validRoles, true)) {
            $selectedRole = null;
        }

        // Strictly query ONLY Platform Administrators (SUPER_ADMIN without tenant outlet binding)
        // Tenant personnel (OWNER, ADMIN, GUDANG, PRODUKSI, CUSTOMER) are NEVER loaded or exposed.
        $platformAdmins = User::where('role', UserRole::SUPER_ADMIN->value)
            ->whereNull('outlet_id')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $platformAdminCount = User::where('role', UserRole::SUPER_ADMIN->value)
            ->whereNull('outlet_id')
            ->count();

        // Official 6 NAYARA SaaS Roles Matrix (Identik Gambar 1)
        $rolesMatrix = [
            [
                'role'        => UserRole::SUPER_ADMIN->value,
                'label'       => 'Super Admin Platform',
                'badge'       => 'PLATFORM AUTHORITY',
                'tag'         => 'Platform',
                'level'       => 'Level: Platform',
                'color'       => '#8E1B5C',
                'scope'       => 'Seluruh Platform NAYARA (Global)',
                'outlet_req'  => 'Strictly NULL (Tidak terikat outlet)',
                'short_desc'  => 'Pengelola ekosistem NAYARA secara global.',
                'description' => 'Mengelola ekosistem platform: onboarding tenant, direktori, billing, verifikasi iklan, refund, audit, dan kebijakan. Dilarang memutasi transaksi privat operasional tenant.',
                'permissions' => [
                    'Manajemen tenant (toko/konveksi)',
                    'Pengelolaan paket langganan & billing',
                    'Moderasi iklan & promosi platform',
                    'Pencairan dana (refund) & audit platform',
                    'Monitoring performa & analitik global',
                ],
                'restrictions' => [
                    'Tidak dapat mengakses akun karyawan tenant.',
                    'Tidak dapat melihat data operasional tenant (produksi, stok, pesanan, keuangan privat).',
                    'Tidak dapat mengubah role karyawan tenant.',
                ],
            ],
            [
                'role'        => UserRole::OWNER->value,
                'label'       => 'Pemilik Konveksi (Owner)',
                'badge'       => 'TENANT EXECUTIVE',
                'tag'         => 'Tenant',
                'level'       => 'Level: Tenant',
                'color'       => '#4F46E5',
                'scope'       => 'Konveksi Sendiri (Tenant-Scoped)',
                'outlet_req'  => 'Wajib terikat ke 1 Outlet sah',
                'short_desc'  => 'Pemilik konveksi dan pengambil keputusan.',
                'description' => 'Pemilik bisnis konveksi. Memiliki hak menetapkan saldo awal kas sekali seumur hidup, memantau laporan keuangan real-time, memesan paket & iklan. Dilarang menyetujui Purchase Order.',
                'permissions' => [
                    'Setup saldo awal kas operasional (one-time)',
                    'Dashboard keuangan & rekonsiliasi real-time',
                    'Pemesanan paket langganan & slot etalase',
                    'Pengajuan iklan & pengembalian dana',
                    'Manajemen pengguna & staf konveksi sendiri',
                ],
                'restrictions' => [
                    'Dilarang menyetujui Purchase Order (eksklusif wewenang Admin).',
                    'Tidak dapat mengubah saldo awal kas setelah dikunci.',
                    'Tidak dapat mengakses data transaksi konveksi tenant lain.',
                ],
            ],
            [
                'role'        => UserRole::ADMIN->value,
                'label'       => 'Administrator Operasional',
                'badge'       => 'TENANT OPERATIONS',
                'tag'         => 'Tenant',
                'level'       => 'Level: Tenant',
                'color'       => '#0284C7',
                'scope'       => 'Konveksi Sendiri (Tenant-Scoped)',
                'outlet_req'  => 'Wajib terikat ke 1 Outlet sah',
                'short_desc'  => 'Pengelola operasional dan transaksi.',
                'description' => 'Satu-satunya role yang berwenang menyetujui Purchase Order (PO). Mengelola pesanan penjualan, master data harga, BOM, dan pencatatan kas/beban operasional.',
                'permissions' => [
                    'Approval / penolakan Purchase Order (eksklusif)',
                    'Input & kelola pesanan penjualan (SO)',
                    'Manajemen master data produk, varian, harga & BOM',
                    'Pencatatan kas operasional & beban usaha harian',
                ],
                'restrictions' => [
                    'Tidak dapat mengubah saldo awal kas toko.',
                    'Tidak dapat mengelola langganan & tagihan platform.',
                    'Tidak dapat mengakses data konveksi tenant lain.',
                ],
            ],
            [
                'role'        => UserRole::GUDANG->value,
                'label'       => 'Staf Gudang & Logistik',
                'badge'       => 'TENANT WAREHOUSE',
                'tag'         => 'Tenant',
                'level'       => 'Level: Tenant',
                'color'       => '#D97706',
                'scope'       => 'Konveksi Sendiri (Tenant-Scoped)',
                'outlet_req'  => 'Wajib terikat ke 1 Outlet sah',
                'short_desc'  => 'Pengelola persediaan dan bahan baku.',
                'description' => 'Mengajukan Purchase Order ke supplier, mencatat penerimaan barang (Goods Receiving) yang menambah stok fisik gudang dan memperbarui MWA/HPP, serta stock opname.',
                'permissions' => [
                    'Pengajuan draft Purchase Order bahan baku ke Admin',
                    'Penerimaan fisik barang (update stok fisik & MWA/HPP)',
                    'Pencatatan stock opname & penyesuaian fisik inventori',
                    'Monitoring safety stock & buffer bahan baku',
                ],
                'restrictions' => [
                    'Dilarang menyetujui Purchase Order (wajib diverifikasi Admin).',
                    'Tidak dapat melihat laporan keuangan & laba-rugi toko.',
                    'Tidak dapat mengakses transaksi penjualan produk.',
                ],
            ],
            [
                'role'        => UserRole::PRODUKSI->value,
                'label'       => 'Staf Produksi & Finishing',
                'badge'       => 'TENANT PRODUCTION',
                'tag'         => 'Tenant',
                'level'       => 'Level: Tenant',
                'color'       => '#059669',
                'scope'       => 'Konveksi Sendiri (Tenant-Scoped)',
                'outlet_req'  => 'Wajib terikat ke 1 Outlet sah',
                'short_desc'  => 'Pelaksana dan pencatat proses produksi.',
                'description' => 'Menjalankan batch produksi dari pesanan yang telah lunas/DP confirmed. Mencatat alokasi bahan, hasil potong, jahit, finishing, QC pass/reject, dan serah terima ke gudang barang jadi.',
                'permissions' => [
                    'Eksekusi batch produksi berdasarkan pesanan (SO valid)',
                    'Pencatatan alokasi bahan baku & snapshot HPP produksi',
                    'Pencatatan hasil potong, jahit, finishing & Quality Control (QC)',
                    'Serah terima produk jadi ke etalase/gudang toko',
                ],
                'restrictions' => [
                    'Tidak dapat mengubah master data harga atau BOM bahan baku.',
                    'Tidak dapat menyetujui PO atau mengeluarkan kas operasional.',
                    'Tidak dapat melihat laporan keuangan privat toko.',
                ],
            ],
            [
                'role'        => UserRole::CUSTOMER->value,
                'label'       => 'Pelanggan / Pembeli',
                'badge'       => 'PUBLIC / CLIENT',
                'tag'         => 'Publik',
                'level'       => 'Level: Publik',
                'color'       => '#64748B',
                'scope'       => 'Pesanan Sendiri / Etalase Publik',
                'outlet_req'  => 'Opsional / Publik',
                'short_desc'  => 'Pengguna katalog dan layanan publik.',
                'description' => 'Akses etalase publik, katalog hijab, pemesanan kustom, pelacakan status pesanan, dan bukti transfer pembayaran.',
                'permissions' => [
                    'Eksplorasi etalase publik & profil konveksi mitra',
                    'Pemesanan hijab kustom & pelacakan status pesanan real-time',
                    'Upload bukti transfer pembayaran pesanan',
                ],
                'restrictions' => [
                    'Tidak memiliki akses ke modul ERP operasional internal konveksi.',
                    'Tidak dapat melihat kalkulasi HPP atau biaya produksi konveksi.',
                    'Tidak dapat memanipulasi status verifikasi pesanan.',
                ],
            ],
        ];

        // Filter the matrix view if a specific role is selected
        $filteredMatrix = $rolesMatrix;
        if ($selectedRole) {
            $filteredMatrix = array_values(array_filter(
                $rolesMatrix,
                fn (array $item) => $item['role'] === $selectedRole
            ));
        }

        return view('platform.roles.index', [
            'rolesMatrix'        => $rolesMatrix,
            'filteredMatrix'     => $filteredMatrix,
            'selectedRole'       => $selectedRole,
            'platformAdmins'     => $platformAdmins,
            'platformAdminCount' => $platformAdminCount,
        ]);
    }
}
