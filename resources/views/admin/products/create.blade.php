<x-app-layout pageTitle="Tambah Produk Baru" breadcrumb="Produk > Tambah">
    <div class="max-w-2xl mx-auto space-y-4 sm:space-y-5">
        
        {{-- Header Card --}}
        <x-feature-header
            title="Tambah Produk Baru"
            subtitle="Masukkan nama produk hijab, kode produk, dan deskripsinya."
            icon="product"
        >
            <x-slot:actions>
                <a href="{{ route('admin.products.index') }}"
                   class="inline-flex items-center justify-center gap-2 h-11 sm:h-12 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Form Errors Summary --}}
        <x-admin.form-errors />

        {{-- Form Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-6 shadow-card">
            <form method="POST"
                  action="{{ route('admin.products.store') }}"
                  class="space-y-4"
                  x-data="{
                      productName: '{{ old('product_name') }}',
                      code: '{{ old('code') }}',
                      userModifiedCode: {{ old('code') ? 'true' : 'false' }},
                      suggestCode(name) {
                          if (this.userModifiedCode) return;
                          let clean = (name || '').trim().replace(/^hijab\s+/i, '').replace(/[^a-zA-Z0-9\s]/g, '');
                          let words = clean.split(/\s+/).filter(Boolean);
                          if (words.length === 0) {
                              this.code = '';
                              return;
                          }
                          if (words.length >= 2) {
                              let first = words[0][0].toUpperCase();
                              let descriptors = ['polos', 'basic', 'standar', 'standard'];
                              let qualifier = words[words.length - 1];
                              for (let i = words.length - 1; i >= 1; i--) {
                                  if (!descriptors.includes(words[i].toLowerCase())) {
                                      qualifier = words[i];
                                      break;
                                  }
                              }
                              this.code = first + qualifier[0].toUpperCase();
                          } else {
                              let word = words[0].toUpperCase();
                              if (word.length <= 2) {
                                  this.code = word;
                              } else {
                                  let first = word[0];
                                  let consonants = word.slice(1).match(/[BCDFGHJKLMNPQRSTVWXYZ]/);
                                  this.code = first + (consonants ? consonants[0] : word[1]);
                              }
                          }
                      }
                  }">
                @csrf

                {{-- Product Name --}}
                <div>
                    <label for="product_name" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                        Nama Produk <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="product_name"
                           id="product_name"
                           x-model="productName"
                           @input="suggestCode(productName)"
                           value="{{ old('product_name') }}"
                           placeholder="Contoh: Hijab Voal Polos Premium"
                           required
                           class="w-full h-10 px-3 text-xs border @error('product_name') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition-all">
                    @error('product_name')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Product Code --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="code" class="block text-xs font-semibold text-[#172B4D]">
                            Kode Produk <span class="text-red-500">*</span>
                        </label>
                        <span class="text-[11px] text-[#64748B]">Dapat disesuaikan sebelum disimpan</span>
                    </div>
                    <input type="text"
                           name="code"
                           id="code"
                           x-model="code"
                           @input="userModifiedCode = true; code = code.toUpperCase().replace(/[^A-Z0-9]/g, '')"
                           maxlength="5"
                           placeholder="Contoh: VP"
                           class="w-full h-10 px-3 text-xs font-mono font-bold uppercase border @error('code') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#8E1B5C] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition-all">
                    <p class="text-[11px] text-[#64748B] mt-1">Kode singkat untuk identitas SKU. Dibuat otomatis dari nama produk dan dapat disesuaikan sebelum produk disimpan.</p>
                    @error('code')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                        Deskripsi Produk
                    </label>
                    <textarea name="description" 
                              id="description" 
                              rows="3" 
                              placeholder="Deskripsi singkat produk, spesifikasi bahan umum, atau catatan..." 
                              class="w-full min-h-[90px] p-3 text-xs border @error('description') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition-all">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit actions --}}
                <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-[#FFD8D8]">
                    <a href="{{ route('admin.products.index') }}"
                       class="inline-flex items-center justify-center h-9 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all cursor-pointer shadow-xs">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all cursor-pointer shadow-xs">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Simpan Produk</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
