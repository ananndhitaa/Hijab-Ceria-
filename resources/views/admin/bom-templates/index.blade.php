<x-app-layout pageTitle="Template Bill of Materials">
    <div class="space-y-5">

        {{-- Section A: Header Card --}}
        <x-feature-header
            title="Template BOM"
            subtitle="Kelola master resep Bill of Materials yang dapat disalin ke berbagai varian produk."
            icon="mrp"
            badge="MASTER DATA PRODUKSI"
        >
            <x-slot:actions>
                <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Katalog Produk</span>
                </a>
                <a href="{{ route('admin.bom-templates.create') }}" class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Template</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Section B: Filter Toolbar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
            <form method="GET" action="{{ route('admin.bom-templates.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full min-w-0">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#64748B]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari nama template atau deskripsi..."
                           class="w-full h-10 pl-10 pr-3.5 text-xs bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                </div>

                <div class="w-full sm:w-60 shrink-0">
                    <select name="product_id"
                            class="w-full h-10 px-3 text-xs bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                        <option value="">-- Semua Produk --</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" {{ (string) $productId === (string) $prod->id ? 'selected' : '' }}>
                                {{ $prod->product_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer flex-1 sm:flex-initial">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Filter</span>
                    </button>
                    @if($search || $productId)
                        <a href="{{ route('admin.bom-templates.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer flex-1 sm:flex-initial">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Section C: Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFD8D8]/50 border-b border-[#FFD8D8] text-[#172B4D] font-bold uppercase tracking-wider text-[11px]">
                            <th class="px-4 py-3 text-center whitespace-nowrap w-12">#</th>
                            <th class="px-4 py-3 whitespace-nowrap w-64">Nama Template</th>
                            <th class="px-4 py-3 whitespace-nowrap w-56">Produk Terkait</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap w-32">Jumlah Material</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap w-28">Status</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap w-40">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @forelse($templates as $index => $template)
                            <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/40">
                                <td class="px-4 py-3.5 text-center font-mono text-[#64748B] whitespace-nowrap">
                                    {{ $templates->firstItem() + $index }}
                                </td>
                                <td class="px-4 py-3.5 font-semibold text-xs text-[#172B4D]">
                                    <a href="{{ route('admin.bom-templates.show', $template) }}" class="hover:underline hover:text-[#8E1B5C] transition-colors">
                                        {{ $template->name }}
                                    </a>
                                    @if($template->description)
                                        <p class="text-[11px] text-[#64748B] font-normal line-clamp-1 mt-0.5">{{ $template->description }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-xs text-[#172B4D] whitespace-nowrap">
                                    <span class="font-medium">{{ $template->product->product_name ?? '-' }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $template->items_count > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ $template->items_count }} Material
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    @if($template->is_active)
                                        <span class="badge-success text-xs">Aktif</span>
                                    @else
                                        <span class="badge-secondary text-xs">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('admin.bom-templates.show', $template) }}"
                                           class="inline-flex items-center justify-center h-8 px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] transition-all shadow-xs cursor-pointer">
                                            Resep Material
                                        </a>
                                        <form method="POST" action="{{ route('admin.bom-templates.toggle-active', $template) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center h-8 px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#64748B] hover:bg-gray-50 transition-all shadow-xs cursor-pointer"
                                                    title="{{ $template->is_active ? 'Nonaktifkan template' : 'Aktifkan template' }}">
                                                {{ $template->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-[#64748B] bg-[#FFFCFB] cursor-default">
                                    <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3 shadow-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <p class="font-bold text-[#172B4D] text-sm">Belum Ada Template BOM</p>
                                    <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">Buat template BOM resep bahan baku untuk diterapkan ke varian produk secara otomatis.</p>
                                    <div class="mt-4">
                                        <a href="{{ route('admin.bom-templates.create') }}" class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all shadow-xs cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span>Tambah Template Pertama</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($templates->hasPages())
                <div class="p-4 border-t border-[#FFD8D8]">
                    {{ $templates->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
