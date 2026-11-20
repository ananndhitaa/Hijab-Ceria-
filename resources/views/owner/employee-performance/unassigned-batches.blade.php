<x-app-layout pageTitle="Lengkapi PIC Batch Historis" breadcrumb="Performa Karyawan > Lengkapi PIC Historis">
    <div class="max-w-7xl mx-auto space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Lengkapi PIC Batch Historis"
            subtitle="Tetapkan karyawan penanggung jawab produksi untuk data batch historis agar masuk ke kalkulasi performa."
            icon="batch"
            badge="Resolusi Data Historis"
        >
            <x-slot:actions>
                <a href="{{ route('owner.employee-performance.index', ['tab' => 'production']) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali ke Performa</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 text-xs text-rose-800 space-y-1 shadow-2xs">
                <div class="font-bold flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Terdapat kendala pada input:</span>
                </div>
                <ul class="list-disc list-inside pl-1 text-[11.5px]">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Guide Banner --}}
        <div class="bg-[#FFFCFB] border border-[#FFD8D8] rounded-2xl p-4 text-xs text-[#64748B] flex items-start gap-3 shadow-card">
            <div class="w-8 h-8 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
            </div>
            <div class="space-y-1">
                <p class="font-bold text-[#172B4D]">Aturan Resolusi PIC Historis</p>
                <p class="text-[11.5px] leading-relaxed">
                    Sistem tidak melakukan penugasan otomatis untuk menjaga integritas data. Penetapan PIC di halaman ini hanya memperbarui atribusi karyawan pada batch terkait tanpa mengubah target kuantitas, hasil aktual, tanggal hasil, status pesanan, faktur, maupun kartu stok.
                </p>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-[#FFD8D8] bg-[#FFFCFB] flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-bold text-sm text-[#172B4D]">Daftar Batch Tanpa PIC Produksi</h3>
                    <p class="text-xs text-[#64748B] mt-0.5">Tersisa <strong class="text-[#172B4D] font-semibold">{{ $unassignedBatches->count() }}</strong> batch yang perlu dilengkapi penanggung jawabnya.</p>
                </div>

                @if($unassignedBatches->isNotEmpty())
                    <div class="flex items-center gap-3">
                        <span id="unsaved-count-badge" class="text-xs text-[#64748B] hidden">
                            <span id="unsaved-count-num" class="font-bold text-[#8E1B5C]">0</span> pilihan belum disimpan
                        </span>

                        <form id="bulk-assign-form" method="POST" action="{{ route('owner.employee-performance.bulk-assign-pic') }}">
                            @csrf
                            <div id="bulk-inputs-container"></div>
                            <button type="submit"
                                    id="btn-save-all"
                                    disabled
                                    class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-xl bg-[#8E1B5C] hover:bg-[#8E1B5C] text-white text-xs font-semibold transition shadow-xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span>Simpan Semua Pilihan</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11px] font-bold text-[#64748B] uppercase tracking-wider">
                            <th class="px-5 py-3">No. Batch</th>
                            <th class="px-4 py-3">Produk / Varian</th>
                            <th class="px-4 py-3 text-right">Target</th>
                            <th class="px-4 py-3 text-right">Hasil Aktual</th>
                            <th class="px-4 py-3 text-center">Tanggal Hasil</th>
                            <th class="px-4 py-3 min-w-[200px]">PIC Produksi</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($unassignedBatches as $batch)
                            <tr class="hover:bg-[#FFD8D8]/20 transition" data-row-batch-id="{{ $batch->id }}">
                                <td class="px-5 py-3.5 font-mono font-bold text-[#8E1B5C] whitespace-nowrap">
                                    {{ $batch->batch_no }}
                                    <div class="text-[10.5px] font-normal text-[#64748B] font-sans">
                                        {{ $batch->orderItem?->order?->order_no ?? '-' }} &middot; {{ $batch->orderItem?->order?->customer?->customer_name ?? 'Customer Umum' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-semibold text-[#172B4D]">
                                        {{ $batch->orderItem?->variant?->product?->product_name ?? '-' }}
                                    </div>
                                    <div class="text-[11px] text-[#64748B]">
                                        {{ $batch->orderItem?->variant?->variant_name ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-right font-medium text-[#172B4D] tabular-nums whitespace-nowrap">
                                    {{ number_format($batch->target_qty, 0, ',', '.') }} pcs
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold text-[#8E1B5C] tabular-nums whitespace-nowrap">
                                    {{ number_format($batch->productionResult?->actual_qty ?? 0, 0, ',', '.') }} pcs
                                </td>
                                <td class="px-4 py-3.5 text-center text-[#172B4D] whitespace-nowrap">
                                    {{ \App\Support\DateTimeFormatter::formatDate($batch->productionResult?->result_date, 'd M Y', '-') }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <form id="form-batch-{{ $batch->id }}"
                                          method="POST"
                                          action="{{ route('owner.employee-performance.assign-batch-pic', $batch) }}"
                                          class="flex items-center gap-2">
                                        @csrf
                                        <select name="production_employee_id"
                                                id="pic-select-{{ $batch->id }}"
                                                data-batch-id="{{ $batch->id }}"
                                                required
                                                class="batch-pic-select w-full h-8 px-2.5 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C] transition">
                                            <option value="">-- Pilih PIC Produksi --</option>
                                            @foreach($productionEmployees as $emp)
                                                <option value="{{ $emp->id }}">
                                                    {{ $emp->name }} {{ $emp->isActive() ? '' : '(Nonaktif)' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <button type="submit"
                                            form="form-batch-{{ $batch->id }}"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#8E1B5C] hover:bg-[#8E1B5C] text-white text-xs font-semibold transition shadow-xs cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        <span>Simpan</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        </div>
                                        <span class="font-bold text-sm text-[#172B4D]">Semua Batch Historis Sudah Memiliki PIC Produksi</span>
                                        <p class="text-xs text-[#64748B]">Tidak ada batch produksi yang belum memiliki atribut PIC Karyawan Produksi.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Client-side draft persistence via sessionStorage --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const STORAGE_KEY = 'nayara:historical-pic-draft';
            const selects = document.querySelectorAll('.batch-pic-select');
            const bulkForm = document.getElementById('bulk-assign-form');
            const btnSaveAll = document.getElementById('btn-save-all');
            const unsavedBadge = document.getElementById('unsaved-count-badge');
            const unsavedNum = document.getElementById('unsaved-count-num');
            const bulkInputsContainer = document.getElementById('bulk-inputs-container');

            // 1. Read existing draft from sessionStorage
            let draft = {};
            try {
                const raw = sessionStorage.getItem(STORAGE_KEY);
                if (raw) {
                    draft = JSON.parse(raw) || {};
                }
            } catch (e) {
                draft = {};
            }

            // 2. Clear only successfully saved batch IDs reported by backend session flash
            const savedBatchIds = @json(session('saved_batch_ids') ?? []);
            if (Array.isArray(savedBatchIds) && savedBatchIds.length > 0) {
                let changed = false;
                savedBatchIds.forEach(function (id) {
                    const key = String(id);
                    if (key in draft) {
                        delete draft[key];
                        changed = true;
                    }
                });
                if (changed) {
                    try {
                        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(draft));
                    } catch (e) {}
                }
            }

            // 3. Collect active batch IDs on current page
            const pageBatchIds = new Set();
            selects.forEach(function (select) {
                pageBatchIds.add(String(select.dataset.batchId));
            });

            // Clean up draft entries for batches no longer on this page
            let cleanedDraft = false;
            Object.keys(draft).forEach(function (batchId) {
                if (!pageBatchIds.has(batchId)) {
                    delete draft[batchId];
                    cleanedDraft = true;
                }
            });

            // 4. Restore selections on current page
            selects.forEach(function (select) {
                const batchId = String(select.dataset.batchId);
                if (batchId in draft) {
                    const savedUserId = String(draft[batchId]);
                    const opt = select.querySelector(`option[value="${savedUserId}"]`);
                    if (opt && savedUserId !== '') {
                        select.value = savedUserId;
                        applySelectedStyle(select, true);
                    } else {
                        delete draft[batchId];
                        cleanedDraft = true;
                        applySelectedStyle(select, false);
                    }
                } else {
                    applySelectedStyle(select, false);
                }
            });

            if (cleanedDraft) {
                try {
                    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(draft));
                } catch (e) {}
            }

            function applySelectedStyle(select, isSelected) {
                if (isSelected) {
                    select.classList.remove('border-[#FFD8D8]', 'bg-[#FFFCFB]');
                    select.classList.add('border-[#8E1B5C]', 'bg-[#FFD8D8]', 'text-[#8E1B5C]', 'font-medium');
                } else {
                    select.classList.remove('border-[#8E1B5C]', 'bg-[#FFD8D8]', 'text-[#8E1B5C]', 'font-medium');
                    select.classList.add('border-[#FFD8D8]', 'bg-[#FFFCFB]');
                }
            }

            function updateUiCounters() {
                let selectedCount = 0;
                selects.forEach(function (select) {
                    if (select.value) {
                        selectedCount++;
                    }
                });

                if (btnSaveAll) {
                    btnSaveAll.disabled = (selectedCount === 0);
                }

                if (unsavedBadge && unsavedNum) {
                    if (selectedCount > 0) {
                        unsavedNum.textContent = selectedCount;
                        unsavedBadge.classList.remove('hidden');
                    } else {
                        unsavedBadge.classList.add('hidden');
                    }
                }
            }

            // 5. Save draft on select change
            selects.forEach(function (select) {
                select.addEventListener('change', function () {
                    const batchId = String(select.dataset.batchId);
                    const val = select.value;
                    if (val) {
                        draft[batchId] = val;
                        applySelectedStyle(select, true);
                    } else {
                        delete draft[batchId];
                        applySelectedStyle(select, false);
                    }

                    try {
                        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(draft));
                    } catch (e) {}

                    updateUiCounters();
                });
            });

            // 6. Handle bulk form submit
            if (bulkForm) {
                bulkForm.addEventListener('submit', function (e) {
                    if (bulkInputsContainer) {
                        bulkInputsContainer.innerHTML = '';
                    }
                    let count = 0;
                    selects.forEach(function (select) {
                        if (select.value) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = `assignments[${select.dataset.batchId}]`;
                            input.value = select.value;
                            bulkInputsContainer.appendChild(input);
                            count++;
                        }
                    });

                    if (count === 0) {
                        e.preventDefault();
                    }
                });
            }

            updateUiCounters();
        });
    </script>
</x-app-layout>
