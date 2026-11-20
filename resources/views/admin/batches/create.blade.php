<x-app-layout pageTitle="Buat Batch Produksi" breadcrumb="Batch Produksi > Buat">
    @php
        // Prepare items data for Alpine.js
        $itemsData = $order->items->map(function ($item) use ($batchService) {
            $rem = $batchService->calculateRemainingQty($item);
            $committed = $batchService->calculateCommittedQty($item);
            return [
                'id'            => $item->id,
                'product_name'  => $item->variant->product->product_name ?? 'Produk',
                'variant_name'  => $item->variant->variant_name ?? '-',
                'sku'           => $item->variant->sku ?? '-',
                'qty_ordered'   => (float) $item->qty_ordered,
                'committed_qty' => (float) $committed,
                'remaining_qty' => (float) $rem,
                'is_available'  => bccomp($rem, '0.0000', 4) > 0,
            ];
        })->values();

        $requestedItemId = request()->query('order_item_id');
        $matchedItem = $requestedItemId ? $itemsData->firstWhere('id', (int) $requestedItemId) : null;
        $firstAvailable = ($matchedItem && $matchedItem['is_available']) ? $matchedItem : $itemsData->firstWhere('is_available', true);
        $initialItemId = old('order_item_id', $firstAvailable['id'] ?? null);
        $initialTarget = old('target_qty');
        if (! $initialTarget && $firstAvailable) {
            $initialTarget = $firstAvailable['remaining_qty'];
        }
    @endphp

    <div class="max-w-4xl mx-auto space-y-4 sm:space-y-5"
         x-data="{
             items: {{ Js::from($itemsData) }},
             selectedItemId: {{ Js::from($initialItemId) }},
             targetQty: '{{ $initialTarget }}',
             selectedPicId: '{{ old('production_employee_id', '') }}',
             workloads: {{ Js::from($employeeWorkloads ?? []) }},
             get selectedItem() {
                 return this.items.find(i => i.id == this.selectedItemId) || null;
             },
             get selectedPic() {
                 return this.workloads.find(w => w.id == this.selectedPicId) || null;
             },
             selectItem(item) {
                 if (!item.is_available) return;
                 this.selectedItemId = item.id;
                 this.targetQty = item.remaining_qty;
             },
             selectPic(picId) {
                 this.selectedPicId = picId ? String(picId) : '';
             }
         }">

        {{-- ── 1. Header & Navigation ────────────────────────────────────── --}}
        <x-feature-header
            title="Buat Batch Produksi"
            icon="production"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        WORK ORDER
                    </span>
                    <span class="badge {{ $order->status->badgeClass() }} text-xs px-2.5 py-0.5">
                        {{ $order->status->label() }}
                    </span>
                </div>
            </x-slot:badge>
            <p class="text-xs text-[#64748B] flex items-center gap-1.5 font-medium mt-1">
                <span>Pesanan:</span>
                <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-bold text-[#8E1B5C] hover:underline">
                    {{ $order->order_no }}
                </a>
                <span class="text-[#FFD8D8]">&middot;</span>
                <span class="text-[#172B4D] font-semibold">{{ $order->customer->customer_name ?? '-' }}</span>
            </p>
            <x-slot:actions>
                <a href="{{ route('admin.orders.show', $order) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                   title="Kembali ke detail pesanan">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Form Errors Summary --}}
        <x-admin.form-errors />

        {{-- ── 2. Step 1: Pilih Item Pesanan ───────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="bg-[#FFFCFB] px-4 sm:px-5 py-3 border-b border-[#FFD8D8]">
                <h2 class="text-sm font-bold text-[#172B4D]">1. Pilih Item Pesanan</h2>
                <p class="text-[11px] text-[#64748B]">Pilih item yang kuantitasnya akan dijadwalkan ke batch produksi.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8]">
                            <th class="px-4 py-2.5 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center w-12">Pilih</th>
                            <th class="px-4 py-2.5 text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Produk &amp; Varian</th>
                            <th class="px-4 py-2.5 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">SKU</th>
                            <th class="px-4 py-2.5 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-right whitespace-nowrap">Dipesan</th>
                            <th class="px-4 py-2.5 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-right whitespace-nowrap">Terjadwal</th>
                            <th class="px-4 py-2.5 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-right whitespace-nowrap">Sisa</th>
                            <th class="px-4 py-2.5 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @foreach($itemsData as $item)
                            <tr class="cursor-pointer transition-colors"
                                :class="{
                                    'bg-[#FFD8D8]/60 font-medium': selectedItemId == {{ $item['id'] }},
                                    'opacity-50 bg-gray-50 cursor-not-allowed': ! {{ $item['is_available'] ? 'true' : 'false' }},
                                    'hover:bg-[#FFFCFB]': selectedItemId != {{ $item['id'] }} && {{ $item['is_available'] ? 'true' : 'false' }}
                                }"
                                @click="selectItem({{ Js::from($item) }})">
                                <td class="px-4 py-3 text-center">
                                    <input type="radio"
                                           name="item_radio"
                                           :value="{{ $item['id'] }}"
                                           :checked="selectedItemId == {{ $item['id'] }}"
                                           {{ ! $item['is_available'] ? 'disabled' : '' }}
                                           @click.stop="selectItem({{ Js::from($item) }})"
                                           class="text-[#8E1B5C] focus:ring-[#8E1B5C] h-4 w-4 accent-[#8E1B5C]">
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-[#172B4D] block text-xs">{{ $item['product_name'] }}</span>
                                    <span class="text-[11px] text-[#64748B]">Varian: {{ $item['variant_name'] }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-[#64748B] whitespace-nowrap">{{ $item['sku'] }}</td>
                                <td class="px-4 py-3 text-right font-medium text-xs whitespace-nowrap">{{ number_format($item['qty_ordered'], 0, ',', '.') }} pcs</td>
                                <td class="px-4 py-3 text-right text-xs text-[#64748B] whitespace-nowrap">{{ number_format($item['committed_qty'], 0, ',', '.') }} pcs</td>
                                <td class="px-4 py-3 text-right font-bold text-xs whitespace-nowrap {{ $item['is_available'] ? 'text-[#8E1B5C]' : 'text-gray-400' }}">
                                    {{ number_format($item['remaining_qty'], 0, ',', '.') }} pcs
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($item['is_available'])
                                        <span class="badge badge-success text-[10px]">Tersedia</span>
                                    @else
                                        <span class="badge badge-secondary text-[10px]">Lengkap (100%)</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── 3. Step 2: Parameter Batch ─────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-6 shadow-card space-y-4">
            <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8] pb-2">
                2. Parameter Batch
            </h2>

            <form method="POST" action="{{ route('admin.orders.batches.store', $order) }}" class="space-y-4 sm:space-y-5">
                @csrf

                {{-- Hidden Selected Item ID --}}
                <input type="hidden" name="order_item_id" :value="selectedItemId">

                {{-- Selected Item Summary Banner --}}
                <div x-show="selectedItem" class="p-3.5 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-1">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-[#172B4D]">
                            Item: <span class="text-[#8E1B5C]" x-text="selectedItem ? selectedItem.product_name + ' — ' + selectedItem.variant_name : '-'"></span>
                        </span>
                        <span class="font-mono text-[11px] text-[#64748B]" x-text="selectedItem ? selectedItem.sku : ''"></span>
                    </div>
                    <p class="text-xs text-[#64748B]">
                        Sisa kuantitas tersedia:
                        <strong class="text-[#8E1B5C]" x-text="selectedItem ? selectedItem.remaining_qty + ' pcs' : '0 pcs'"></strong>
                    </p>
                </div>

                {{-- Target Qty Input --}}
                <div>
                    <label for="target_qty" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                        Target Kuantitas Batch (pcs) <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           id="target_qty"
                           name="target_qty"
                           x-model="targetQty"
                           step="1"
                           min="1"
                           :max="selectedItem ? selectedItem.remaining_qty : null"
                           placeholder="Contoh: 50"
                           class="w-full h-10 px-3 text-xs font-mono font-semibold border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('target_qty') border-red-500 @enderror"
                           required>
                    <p class="text-[11px] text-[#64748B] mt-1" x-show="selectedItem">
                        Maksimal <strong x-text="selectedItem ? selectedItem.remaining_qty + ' pcs' : '0 pcs'"></strong>. Item dapat dibagi menjadi beberapa batch (split batch).
                    </p>
                    @error('target_qty')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Planned Dates (Optional Planning Section) --}}
                <div class="p-3.5 sm:p-4 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-3">
                    <div class="space-y-0.5 border-b border-[#FFD8D8] pb-2">
                        <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">
                            Jadwal Rencana (Opsional)
                        </span>
                        <p class="text-[11px] text-[#64748B]">
                            Jadwal ini hanya rencana kerja. Proses aktual dijalankan oleh tim Produksi.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <div>
                            <label for="planned_start_date" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                                Rencana Mulai Produksi <span class="text-[#64748B] text-[11px] font-normal">(Opsional)</span>
                            </label>
                            <input type="date"
                                   id="planned_start_date"
                                   name="planned_start_date"
                                   value="{{ old('planned_start_date') }}"
                                   class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('planned_start_date') border-red-500 @enderror">
                            @error('planned_start_date')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="planned_end_date" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                                Target Selesai Produksi <span class="text-rose-600">*</span>
                            </label>
                            <input type="date"
                                   id="planned_end_date"
                                   name="planned_end_date"
                                   required
                                   value="{{ old('planned_end_date') }}"
                                   class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('planned_end_date') border-red-500 @enderror">
                            @error('planned_end_date')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- PIC Karyawan Produksi & Workload Overview --}}
                <div class="p-4 sm:p-5 bg-white rounded-xl border border-[#FFD8D8] space-y-3.5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-[#FFD8D8] pb-3">
                        <div>
                            <label for="production_employee_id" class="block text-xs font-bold text-[#172B4D]">
                                PIC Karyawan Produksi <span class="text-[#64748B] text-[11px] font-normal">(Opsional)</span>
                            </label>
                            <p class="text-[11px] text-[#64748B] mt-0.5">
                                Pantau beban kerja aktual dan deadline terdekat sebelum menentukan penugasan. Keputusan akhir tetap di tangan Admin.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Rekomendasi Pintar
                            </span>
                        </div>
                    </div>

                    {{-- Dropdown Selection --}}
                    <div>
                        <select id="production_employee_id"
                                name="production_employee_id"
                                x-model="selectedPicId"
                                class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('production_employee_id') border-red-500 @enderror">
                            <option value="">-- Simpan Tanpa PIC (Draft / Belum Ditugaskan) --</option>
                            @foreach($employeeWorkloads ?? [] as $w)
                                <option value="{{ $w['id'] }}" @selected(old('production_employee_id') == $w['id'])>
                                    [{{ $w['workload_status'] }}] {{ $w['name'] }} — {{ $w['active_batches_count'] }} Batch Aktif (Sisa: {{ number_format($w['active_remaining_qty']) }} pcs, DL: {{ $w['closest_deadline_formatted'] }})
                                </option>
                            @endforeach
                        </select>
                        @error('production_employee_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Workload Grid Cards --}}
                    <div class="space-y-2 pt-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#64748B] block">
                            Daftar Beban Kerja &amp; Rekomendasi Karyawan:
                        </span>

                        @if(empty($employeeWorkloads) || count($employeeWorkloads) === 0)
                            <div class="p-3 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8] text-center text-xs text-[#64748B]">
                                Belum ada karyawan Produksi terdaftar pada konveksi ini.
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                                @foreach($employeeWorkloads as $w)
                                    <div class="p-3 rounded-lg border transition-all duration-150 cursor-pointer text-xs"
                                         :class="selectedPicId == '{{ $w['id'] }}' ? 'bg-[#F7E6EE]/50 border-[#8E1B5C] ring-1 ring-[#8E1B5C]' : 'bg-[#FFFCFB] border-[#FFD8D8] hover:border-[#8E1B5C]/60'"
                                         @click="selectPic('{{ $w['id'] }}')">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <div class="font-bold text-[#172B4D] flex items-center gap-1.5">
                                                    <span>{{ $w['name'] }}</span>
                                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-bold border {{ $w['workload_badge_class'] }}">
                                                        {{ $w['workload_status'] }}
                                                    </span>
                                                </div>
                                                <p class="text-[10.5px] text-[#64748B]">{{ $w['email'] }}</p>
                                            </div>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $w['recommendation_badge_class'] }}">
                                                {{ $w['recommendation_label'] }}
                                            </span>
                                        </div>

                                        {{-- Metric Badges --}}
                                        <div class="grid grid-cols-4 gap-1 mt-2.5 text-center text-[10.5px] bg-white rounded p-1.5 border border-[#FFD8D8]">
                                            <div>
                                                <span class="block text-[#64748B] text-[9.5px]">Batch</span>
                                                <span class="font-bold text-[#172B4D]">{{ $w['active_batches_count'] }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-[#64748B] text-[9.5px]">Target</span>
                                                <span class="font-bold text-[#172B4D]">{{ number_format($w['active_target_qty']) }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-[#64748B] text-[9.5px]">Sisa</span>
                                                <span class="font-bold text-[#8E1B5C]">{{ number_format($w['active_remaining_qty']) }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-[#64748B] text-[9.5px]">Deadline</span>
                                                <span class="font-bold {{ $w['has_overdue'] ? 'text-rose-600' : 'text-[#172B4D]' }}">{{ $w['closest_deadline_formatted'] }}</span>
                                            </div>
                                        </div>

                                        {{-- Recommendation Note --}}
                                        <div class="mt-2 text-[10.5px] flex items-center justify-between text-[#64748B]">
                                            <span class="italic truncate max-w-[220px]" title="{{ $w['recommendation_reason'] }}">
                                                {{ $w['recommendation_reason'] }}
                                            </span>
                                            <button type="button"
                                                    class="text-[10px] font-semibold text-[#8E1B5C] hover:underline shrink-0"
                                                    @click.stop="selectPic('{{ $w['id'] }}')">
                                                <span x-text="selectedPicId == '{{ $w['id'] }}' ? '✓ Terpilih' : 'Pilih PIC'"></span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Notes (Optional) --}}
                <div>
                    <label for="notes" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                        Catatan Khusus Batch <span class="text-[#64748B] text-[11px] font-normal">(Opsional)</span>
                    </label>
                    <textarea id="notes"
                              name="notes"
                              rows="3"
                              placeholder="Instruksi produksi, prioritas, atau catatan tambahan"
                              class="w-full min-h-[100px] p-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#FFD8D8]">
                    <a href="{{ route('admin.orders.show', $order) }}"
                       class="inline-flex items-center justify-center h-9 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                        Batal
                    </a>
                    <button type="submit"
                            :disabled="!selectedItemId || !targetQty || targetQty <= 0 || (selectedItem && targetQty > selectedItem.remaining_qty)"
                            class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Simpan Batch</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
