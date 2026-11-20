<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan Eksekutif — NAYARA SaaS</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.5; padding: 20px; }
        .header { border-bottom: 2px solid #8E1B5C; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; color: #8E1B5C; margin: 0; }
        .subtitle { font-size: 11px; color: #666; margin: 3px 0 0 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; }
        th { background-color: #FFD8D8; color: #8E1B5C; font-size: 11px; text-transform: uppercase; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .section-title { font-size: 13px; font-weight: bold; color: #8E1B5C; margin-top: 25px; margin-bottom: 5px; }
        .footer { margin-top: 30px; font-size: 10px; color: #888; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background-color: #8E1B5C; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
            🖨️ Cetak / Simpan sebagai PDF
        </button>
    </div>

    <div class="header">
        <h1 class="title">LAPORAN KEUANGAN & KINERJA BISNIS</h1>
        <p class="subtitle">Platform NAYARA SaaS Multi-Tenant — ID Outlet: #{{ $outletId }}</p>
        <p class="subtitle">Periode Laporan: {{ $startDate }} s/d {{ $endDate }} | Diunduh: {{ now()->format('d M Y H:i:s') }} WIB</p>
    </div>

    @php $m = $analytics['metrics']; @endphp

    <div class="section-title">1. Ringkasan Eksekutif Keuangan</div>
    <table>
        <thead>
            <tr>
                <th>Komponen Keuangan</th>
                <th class="text-right">Nilai (Rupiah)</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">Pendapatan Bersih (Revenue)</td>
                <td class="text-right font-bold">Rp {{ number_format((float)$m['revenue'], 0, ',', '.') }}</td>
                <td>Total nilai pesanan non-batal dalam periode</td>
            </tr>
            <tr>
                <td>Pembayaran Terkonfirmasi</td>
                <td class="text-right">Rp {{ number_format((float)$m['confirmed_payments'], 0, ',', '.') }}</td>
                <td>Dana masuk dari customer berstatus confirmed</td>
            </tr>
            <tr>
                <td>Harga Pokok Penjualan (HPP)</td>
                <td class="text-right">
                    @if($m['hpp'] !== null)
                    Rp {{ number_format((float)$m['hpp'], 0, ',', '.') }}
                    @else
                    Belum Final
                    @endif
                </td>
                <td>{{ $m['is_hpp_final'] ? 'Biaya produksi terhitung final' : 'Terdapat batch belum selesai QC' }}</td>
            </tr>
            <tr style="background-color: #fafafa;">
                <td class="font-bold">Laba Kotor (Gross Profit)</td>
                <td class="text-right font-bold">
                    @if($m['gross_profit'] !== null)
                    Rp {{ number_format((float)$m['gross_profit'], 0, ',', '.') }}
                    @else
                    -
                    @endif
                </td>
                <td>Pendapatan dikurangi HPP</td>
            </tr>
            <tr>
                <td>Beban Operasional Bisnis</td>
                <td class="text-right">Rp {{ number_format((float)$m['expenses'], 0, ',', '.') }}</td>
                <td>Total biaya operasional di luar HPP</td>
            </tr>
            <tr style="background-color: #FFD8D8; font-size: 13px;">
                <td class="font-bold">Laba Bersih (Net Profit)</td>
                <td class="text-right font-bold">
                    @if($m['net_profit'] !== null)
                    Rp {{ number_format((float)$m['net_profit'], 0, ',', '.') }}
                    @else
                    -
                    @endif
                </td>
                <td>Laba kotor dikurangi beban operasional</td>
            </tr>
            <tr>
                <td>Arus Kas Masuk</td>
                <td class="text-right">Rp {{ number_format((float)$m['cash_in'], 0, ',', '.') }}</td>
                <td>Total penerimaan kas operasional</td>
            </tr>
            <tr>
                <td>Arus Kas Keluar</td>
                <td class="text-right">Rp {{ number_format((float)$m['cash_out'], 0, ',', '.') }}</td>
                <td>Total pengeluaran kas operasional</td>
            </tr>
        </tbody>
    </table>

    @if(!empty($analytics['charts']['expense_composition']['categories']))
    <div class="section-title">2. Rincian Beban Operasional</div>
    <table>
        <thead>
            <tr>
                <th>Kategori Beban</th>
                <th class="text-right">Jumlah (Rupiah)</th>
                <th class="text-right">Porsi (%)</th>
            </tr>
        </thead>
        <tbody>
            @php $totalExp = max(1, (float)($analytics['charts']['expense_composition']['total'] ?? 1)); @endphp
            @foreach($analytics['charts']['expense_composition']['categories'] as $cat)
            <tr>
                <td>{{ $cat['label'] }}</td>
                <td class="text-right">Rp {{ number_format((float)$cat['amount'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format(((float)$cat['amount'] / $totalExp) * 100, 1) }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        Dokumen ini diterbitkan secara otomatis oleh NAYARA Multi-Tenant SaaS ERP. Seluruh angka bersumber dari pencatatan pembukuan internal toko terotorisasi.
    </div>

</body>
</html>
