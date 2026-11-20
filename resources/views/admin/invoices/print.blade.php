<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_no }} - {{ $invoice->order?->outlet?->outlet_name ?? 'NAYARA' }}</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #172B4D;
            background-color: #F8F9FA;
        }

        @media print {
            body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .print-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            @page {
                size: A4 portrait;
                margin: 12mm 15mm 15mm 15mm;
            }

            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="min-h-screen py-4 sm:py-8 antialiased">

    {{-- Top Action Bar (Screen Only) --}}
    <div class="no-print max-w-4xl mx-auto px-4 sm:px-6 mb-5 flex flex-wrap items-center justify-between gap-3">
        <button type="button"
                onclick="window.history.back()"
                class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50 transition cursor-pointer shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            <span>Kembali</span>
        </button>

        <div class="flex items-center gap-2.5">
            <button type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-2 h-10 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#68113F] transition cursor-pointer shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.05-.37-2.14-.37-3.254 0-4.694 3.806-8.5 8.5-8.5s8.5 3.806 8.5 8.5c0 1.114-.13 2.204-.37 3.254M17 17v4.5a.75.75 0 01-.75.75H7.75a.75.75 0 01-.75-.75V17m10 0H7m10 0v-4.5a.75.75 0 00-.75-.75H7.75a.75.75 0 00-.75.75V17m2-8.5h.01"/>
                </svg>
                <span>Cetak / Simpan PDF</span>
            </button>
        </div>
    </div>

    {{-- Printable Sheet Container --}}
    <div class="print-container max-w-4xl mx-auto bg-white rounded-2xl border border-gray-200/90 shadow-md p-7 sm:p-10 text-[13px] leading-relaxed">

        {{-- 1. Header (Brand, Konveksi Info & Document Title) --}}
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6 pb-6 border-b-2 border-[#8E1B5C]">
            {{-- Left: Brand & Outlet Info --}}
            <div class="space-y-1.5 max-w-sm">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-[#8E1B5C] text-white flex items-center justify-center font-serif font-black text-xl italic shadow-xs">
                        N
                    </div>
                    <div>
                        <h1 class="text-lg font-extrabold text-[#172B4D] tracking-tight uppercase leading-tight">
                            {{ $invoice->order?->outlet?->outlet_name ?? 'NAYARA HIJAB & CONVECTION' }}
                        </h1>
                        <span class="text-[11px] font-semibold text-[#8E1B5C] tracking-wide block">
                            PRODUKSI &amp; KONVEKSI HIJAB
                        </span>
                    </div>
                </div>

                <div class="text-xs text-[#64748B] pt-1 space-y-0.5">
                    <p>{{ $invoice->order?->outlet?->address ?? 'Pusat Produksi Busana Muslim & Hijab' }}</p>
                    <p>Telepon/WhatsApp: <span class="text-[#172B4D] font-medium">{{ $invoice->order?->outlet?->phone ?? '0812-3456-7890' }}</span></p>
                    <p>Email: <span class="text-[#172B4D] font-medium">{{ $invoice->order?->outlet?->email ?? 'admin@nayarahijab.com' }}</span></p>
                </div>
            </div>

            {{-- Right: Invoice Identity & Status --}}
            <div class="text-left sm:text-right space-y-1 shrink-0">
                <div class="inline-block">
                    <span class="inline-block text-[11px] font-extrabold uppercase tracking-widest text-[#8E1B5C] bg-[#FFD8D8] px-3 py-1 rounded-full border border-[#FFD8D8]">
                        FAKTUR PENJUALAN
                    </span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold font-mono text-[#172B4D] tracking-tight mt-1">
                    {{ $invoice->invoice_no }}
                </h2>

                <div class="pt-1">
                    @php
                        $statusClass = match($invoice->status->value) {
                            'PAID' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                            'DOWN_PAYMENT' => 'bg-indigo-50 text-indigo-800 border-indigo-300',
                            'PARTIAL' => 'bg-amber-50 text-amber-800 border-amber-300',
                            'CANCELLED' => 'bg-red-50 text-red-800 border-red-300',
                            default => 'bg-rose-50 text-rose-800 border-rose-300',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider border {{ $statusClass }}">
                        {{ $invoice->status->label() }}
                    </span>
                </div>
            </div>
        </div>

        {{-- 2. Metadata Grid (Bill To & Order References) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-b border-gray-200">
            {{-- Bill To (Pelanggan) --}}
            <div class="space-y-1.5">
                <span class="text-[10.5px] font-bold text-[#64748B] uppercase tracking-wider block">
                    Ditagihkan Kepada (Bill To):
                </span>
                <p class="text-base font-bold text-[#172B4D]">
                    {{ $invoice->order?->customer?->customer_name ?? 'Pelanggan Umum' }}
                </p>
                <div class="text-xs text-[#64748B] space-y-0.5">
                    <p>No. Telepon / WA: <span class="text-[#172B4D] font-medium font-mono">{{ $invoice->order?->customer?->phone ?: '-' }}</span></p>
                    <p>Email: <span class="text-[#172B4D] font-medium">{{ $invoice->order?->customer?->email ?: '-' }}</span></p>
                    <p class="leading-relaxed">Alamat: <span class="text-[#172B4D]">{{ $invoice->order?->customer?->address ?: '-' }}</span></p>
                </div>
            </div>

            {{-- Document Details (Nomor Order & Tanggal) --}}
            <div class="sm:text-right space-y-1.5">
                <span class="text-[10.5px] font-bold text-[#64748B] uppercase tracking-wider block">
                    Informasi Transaksi:
                </span>
                <div class="text-xs space-y-1">
                    <p class="flex sm:justify-end gap-2 text-[#64748B]">
                        <span>Nomor Sales Order:</span>
                        <span class="font-mono font-bold text-[#8E1B5C]">{{ $invoice->order?->order_no ?? '-' }}</span>
                    </p>
                    <p class="flex sm:justify-end gap-2 text-[#64748B]">
                        <span>Tanggal Terbit Invoice:</span>
                        <span class="font-medium text-[#172B4D]">
                            {{ $invoice->invoice_date ? $invoice->invoice_date->format('d/m/Y') : '-' }}
                        </span>
                    </p>
                    <p class="flex sm:justify-end gap-2 text-[#64748B]">
                        <span>Batas Waktu Pelunasan:</span>
                        <span class="font-medium text-[#172B4D]">
                            {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : ($invoice->invoice_date ? $invoice->invoice_date->copy()->addDays(7)->format('d/m/Y') : '-') }}
                        </span>
                    </p>
                    <p class="flex sm:justify-end gap-2 text-[#64748B]">
                        <span>Status Pengerjaan:</span>
                        <span class="font-medium text-[#172B4D] uppercase">
                            {{ $invoice->order?->status?->label() ?? '-' }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        {{-- 3. Items Table --}}
        <div class="py-6 border-b border-gray-200 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b-2 border-gray-300 text-[11px] font-bold uppercase tracking-wider text-[#64748B] bg-gray-50/80">
                        <th class="py-3 px-3 text-center w-12">No.</th>
                        <th class="py-3 px-3">Deskripsi Produk &amp; Varian</th>
                        <th class="py-3 px-3 font-mono">SKU</th>
                        <th class="py-3 px-3 text-right">Kuantitas</th>
                        <th class="py-3 px-3 text-right">Harga Satuan</th>
                        <th class="py-3 px-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($invoice->order?->items ?? [] as $index => $item)
                        <tr>
                            <td class="py-3.5 px-3 text-center text-[#64748B] font-mono">
                                {{ $index + 1 }}
                            </td>
                            <td class="py-3.5 px-3">
                                <p class="font-bold text-[#172B4D] text-xs">
                                    {{ $item->variant?->product?->product_name ?? 'Produk Hijab' }}
                                </p>
                                <p class="text-[11px] text-[#8E1B5C] font-medium mt-0.5">
                                    Varian: {{ $item->variant?->variant_name ?? '-' }}
                                </p>
                            </td>
                            <td class="py-3.5 px-3 font-mono text-[11px] text-[#64748B]">
                                {{ $item->variant?->sku ?? '-' }}
                            </td>
                            <td class="py-3.5 px-3 text-right font-semibold text-[#172B4D]">
                                {{ rtrim(rtrim(number_format((float) $item->qty_ordered, 2, ',', '.'), '0'), ',') }} pcs
                            </td>
                            <td class="py-3.5 px-3 text-right font-mono text-[#172B4D]">
                                {{ \App\Support\MoneyFormatter::rupiah($item->unit_selling_price) }}
                            </td>
                            <td class="py-3.5 px-3 text-right font-mono font-bold text-[#172B4D]">
                                {{ \App\Support\MoneyFormatter::rupiah($item->subtotal) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-gray-500 italic">
                                Tidak ada rincian item pesanan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 4. Summary & Payment Breakdown --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 py-6 border-b border-gray-200 items-start">
            {{-- Left: Payment Records --}}
            <div class="space-y-3">
                <span class="text-[10.5px] font-bold text-[#64748B] uppercase tracking-wider block">
                    Riwayat Pembayaran Terverifikasi:
                </span>

                @if($invoice->payments && $invoice->payments->isNotEmpty())
                    <div class="border border-gray-200 rounded-xl overflow-hidden bg-gray-50/50">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-100 text-[10px] font-bold uppercase text-[#64748B]">
                                <tr>
                                    <th class="py-2 px-3">Tanggal</th>
                                    <th class="py-2 px-3">Tipe</th>
                                    <th class="py-2 px-3">Metode</th>
                                    <th class="py-2 px-3 text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 font-mono text-[11px]">
                                @foreach($invoice->payments as $p)
                                    <tr>
                                        <td class="py-2 px-3 text-[#64748B]">
                                            {{ $p->payment_date ? $p->payment_date->format('d/m/Y') : ($p->created_at ? $p->created_at->format('d/m/Y') : '-') }}
                                        </td>
                                        <td class="py-2 px-3 text-[#172B4D] font-sans font-medium">
                                            {{ is_object($p->payment_type) && method_exists($p->payment_type, 'label') ? $p->payment_type->label() : ($p->payment_type ?? 'Pembayaran') }}
                                        </td>
                                        <td class="py-2 px-3 text-[#64748B] font-sans">
                                            {{ $p->payment_method ?? 'Transfer' }}
                                        </td>
                                        <td class="py-2 px-3 text-right font-bold text-emerald-700">
                                            {{ \App\Support\MoneyFormatter::rupiah($p->amount) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-3 bg-gray-50 rounded-xl border border-dashed border-gray-200 text-xs text-[#64748B]">
                        Belum ada pembayaran yang tercatat pada invoice ini.
                    </div>
                @endif

                {{-- Bank Account Instructions --}}
                <div class="p-3.5 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] text-xs space-y-1 mt-4">
                    <span class="text-[10px] font-bold text-[#8E1B5C] uppercase tracking-wider block">
                        Informasi Rekening Pembayaran Resmi:
                    </span>
                    <div class="text-[11px] text-[#172B4D] space-y-0.5">
                        <p>Bank BCA: <strong class="font-mono">8045-2918-20</strong> a/n <strong>PT INOVINDO DIGITAL MEDIA</strong></p>
                        <p>Bank Mandiri: <strong class="font-mono">131-00-9821-3321</strong> a/n <strong>PT INOVINDO DIGITAL MEDIA</strong></p>
                    </div>
                    <p class="text-[10.5px] text-[#64748B] pt-1 leading-normal italic">
                        *Harap sertakan nomor invoice (<strong>{{ $invoice->invoice_no }}</strong>) saat melakukan konfirmasi transfer.
                    </p>
                </div>
            </div>

            {{-- Right: Totals Calculation Box --}}
            <div class="space-y-2 bg-[#FFFCFB] p-5 rounded-xl border border-[#FFD8D8] max-w-sm ml-auto w-full">
                <div class="flex justify-between items-center text-xs text-[#64748B]">
                    <span>Subtotal Tagihan:</span>
                    <span class="font-mono font-semibold text-[#172B4D]">
                        {{ \App\Support\MoneyFormatter::rupiah($invoice->total_amount) }}
                    </span>
                </div>

                <div class="flex justify-between items-center text-xs text-[#64748B]">
                    <span>Total Pajak &amp; Diskon:</span>
                    <span class="font-mono text-[#64748B]">Rp 0</span>
                </div>

                <div class="pt-2 border-t border-[#FFD8D8] flex justify-between items-center">
                    <span class="text-xs font-bold uppercase text-[#172B4D]">Total Faktur:</span>
                    <span class="text-base font-extrabold font-mono text-[#8E1B5C]">
                        {{ \App\Support\MoneyFormatter::rupiah($invoice->total_amount) }}
                    </span>
                </div>

                <div class="flex justify-between items-center text-xs text-emerald-700 pt-1">
                    <span class="font-semibold">Total Terbayar (DP / Lunas):</span>
                    <span class="font-mono font-bold">
                        {{ \App\Support\MoneyFormatter::rupiah($paidAmount) }}
                    </span>
                </div>

                <div class="pt-2 border-t-2 border-dashed border-[#FFD8D8] flex justify-between items-center">
                    <span class="text-xs font-bold uppercase {{ bccomp($outstandingBalance, '0.00', 2) > 0 ? 'text-[#ED3500]' : 'text-[#64748B]' }}">
                        Sisa Tagihan:
                    </span>
                    <span class="text-lg font-extrabold font-mono {{ bccomp($outstandingBalance, '0.00', 2) > 0 ? 'text-[#ED3500]' : 'text-[#172B4D]' }}">
                        {{ \App\Support\MoneyFormatter::rupiah($outstandingBalance) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- 5. Signatures & Official Validation --}}
        <div class="grid grid-cols-2 gap-8 pt-8 pb-4 text-center text-xs">
            {{-- Customer --}}
            <div class="space-y-16">
                <p class="font-semibold text-[#64748B]">Diterima Oleh (Pelanggan),</p>
                <div>
                    <p class="font-bold text-[#172B4D] border-b border-gray-400 pb-1 max-w-[200px] mx-auto">
                        {{ $invoice->order?->customer?->customer_name ?? 'Pelanggan' }}
                    </p>
                    <p class="text-[10px] text-[#64748B] mt-0.5">Tanda Tangan &amp; Nama Terang</p>
                </div>
            </div>

            {{-- Konveksi Official Stamp --}}
            <div class="space-y-16">
                <p class="font-semibold text-[#64748B]">Hormat Kami (Admin Keuangan),</p>
                <div class="relative">
                    @if($invoice->status->value === 'PAID')
                        <div class="absolute -top-12 left-1/2 -translate-x-1/2 rotate-[-12deg] pointer-events-none select-none">
                            <span class="inline-block px-4 py-1.5 border-2 border-emerald-600 rounded-lg text-emerald-600 font-extrabold tracking-widest uppercase text-base opacity-90 shadow-xs bg-emerald-50/50">
                                LUNAS / PAID
                            </span>
                        </div>
                    @elseif($hasConfirmedDp)
                        <div class="absolute -top-12 left-1/2 -translate-x-1/2 rotate-[-12deg] pointer-events-none select-none">
                            <span class="inline-block px-4 py-1.5 border-2 border-indigo-600 rounded-lg text-indigo-600 font-extrabold tracking-widest uppercase text-sm opacity-90 shadow-xs bg-indigo-50/50">
                                DP TERBAYAR
                            </span>
                        </div>
                    @endif
                    <p class="font-bold text-[#172B4D] border-b border-gray-400 pb-1 max-w-[200px] mx-auto">
                        {{ $invoice->order?->outlet?->outlet_name ?? 'NAYARA HIJAB' }}
                    </p>
                    <p class="text-[10px] text-[#64748B] mt-0.5">Cap Resmi &amp; Stempel Konveksi</p>
                </div>
            </div>
        </div>

        {{-- 6. Legal / System Footer --}}
        <div class="pt-6 mt-6 border-t border-gray-200 text-center text-[10.5px] text-[#64748B] leading-relaxed">
            <p>Terima kasih atas kepercayaan Anda memesan produk hijab berkualitas di <strong>{{ $invoice->order?->outlet?->outlet_name ?? 'NAYARA Hijab' }}</strong>.</p>
            <p class="text-[10px] text-gray-400 mt-0.5">Faktur ini diterbitkan secara otomatis dan sah sebagai bukti penagihan yang sah melalui Sistem ERP NAYARA.</p>
        </div>

    </div>

</body>
</html>
