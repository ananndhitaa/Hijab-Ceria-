<x-app-layout pageTitle="Produk & BOM" breadcrumb="Produk & BOM > Katalog Produk">
    <div class="space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Produk & BOM"
            subtitle="Master produk hijab, varian, dan BOM."
            icon="product"
        >
            <span class="sr-only">Katalog Produk</span>
            <x-slot:actions>
                <a href="{{ route('admin.bom-templates.index') }}" class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>BOM Template</span>
                </a>
                <a href="{{ route('admin.products.create') }}" class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Produk</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Section B: Search Toolbar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
            <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full min-w-0">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-text-sec">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari nama produk atau deskripsi..."
                           class="w-full h-10 pl-10 pr-3.5 text-xs bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg text-text-main placeholder:text-text-sec/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer flex-1 sm:flex-initial">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Cari</span>
                    </button>
                    @if($search)
                        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer flex-1 sm:flex-initial">
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
                        <tr class="bg-[#FFD8D8]/50 border-b border-[#FFD8D8] text-text-main font-bold uppercase tracking-wider text-[11px]">
                            <th class="px-3 py-3 text-center whitespace-nowrap w-12">#</th>
                            <th class="px-4 py-3 whitespace-nowrap w-56 sm:w-64">Nama Produk</th>
                            <th class="px-4 py-3">Deskripsi</th>
                            <th class="px-3 py-3 text-center whitespace-nowrap w-36">Jumlah Varian</th>
                            <th class="px-3 py-3 text-center whitespace-nowrap w-28">Status</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($products as $index => $product)
                            <tr class="hover:bg-[#FFFCFB] transition-colors {{ !$product->active ? 'bg-gray-50/50' : '' }}">
                                <td class="px-3 py-3 text-center font-mono text-xs text-text-sec whitespace-nowrap">
                                    {{ $products->firstItem() + $index }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-xs text-text-main whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.products.show', $product) }}" class="hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer">
                                            {{ $product->product_name }}
                                        </a>
                                        <span class="font-mono text-[10px] text-[#8E1B5C] font-semibold bg-[#FFD8D8] px-1.5 py-0.5 rounded border border-[#FFD8D8]">
                                            {{ $product->code }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-text-sec">
                                    <p class="truncate max-w-xs md:max-w-sm lg:max-w-md" title="{{ $product->description ?? '-' }}">
                                        {{ $product->description ?? '-' }}
                                    </p>
                                </td>
                                <td class="px-3 py-3 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 bg-[#FFD8D8] text-[#8E1B5C] font-bold rounded-full text-xs">
                                        {{ $product->variants_count }} Varian
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center whitespace-nowrap">
                                    @if($product->active)
                                        <span class="badge-success text-xs">Aktif</span>
                                    @else
                                        <span class="badge-secondary text-xs">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5 min-w-[110px]">
                                        <a href="{{ route('admin.products.show', $product) }}"
                                           class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                           title="Detail & Kelola Varian"
                                           aria-label="Detail & Kelola Varian">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('admin.products.edit', $product) }}"
                                           class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                           title="Edit Produk"
                                           aria-label="Edit Produk">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('admin.products.toggle-active', $product) }}" class="inline-block shrink-0">
                                            @csrf
                                            <button type="submit"
                                                    class="w-8 h-8 rounded-lg {{ $product->active ? 'bg-gray-100 text-gray-600 hover:bg-gray-200' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' }} inline-flex items-center justify-center transition-colors cursor-pointer shadow-xs"
                                                    title="{{ $product->active ? 'Nonaktifkan Produk' : 'Aktifkan Produk' }}"
                                                    aria-label="{{ $product->active ? 'Nonaktifkan Produk' : 'Aktifkan Produk' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-text-sec cursor-default">
                                    <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3 shadow-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <p class="font-bold text-text-main text-sm">Belum ada Produk</p>
                                    <p class="text-xs text-text-sec mt-1 max-w-sm mx-auto">Tambahkan produk agar varian dan BOM dapat dikelola.</p>
                                    <div class="mt-4">
                                        <a href="{{ route('admin.products.create') }}" class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all shadow-xs cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span>Tambah Produk</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="px-5 py-3.5 border-t border-[#FFD8D8] bg-[#FFFCFB]">
                    {{ $products->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
