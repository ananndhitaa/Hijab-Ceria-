<x-app-layout pageTitle="Manajemen Karyawan" breadcrumb="Manajemen > Karyawan">
    <div class="max-w-7xl mx-auto space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Karyawan"
            subtitle="Kelola akun karyawan internal NAYARA."
            icon="users"
            badge="Manajemen Akun Internal"
        >
            <x-slot:actions>
                <a href="{{ route('owner.employees.create') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-xl bg-[#8E1B5C] hover:bg-[#8E1B5C] text-white text-xs font-semibold transition shadow-xs cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Tambah Karyawan</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Filter & Search Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card">
            <form method="GET" action="{{ route('owner.employees.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-1 max-w-md">
                    <div class="relative w-full">
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Cari nama atau email karyawan..."
                               class="w-full h-9 pl-9 pr-3 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                        <svg class="w-4 h-4 text-[#64748B] absolute left-3 top-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <select name="role"
                            class="h-9 px-3 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                        <option value="">Semua Role</option>
                        @foreach($roles as $roleItem)
                            <option value="{{ $roleItem->value }}" @selected($roleFilter === $roleItem->value)>
                                {{ $roleItem->label() }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status"
                            class="h-9 px-3 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $statusItem)
                            <option value="{{ $statusItem->value }}" @selected($statusFilter === $statusItem->value)>
                                {{ $statusItem->label() }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                            class="inline-flex items-center justify-center h-9 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition cursor-pointer shadow-xs">
                        Filter
                    </button>

                    @if($search || $roleFilter || $statusFilter)
                        <a href="{{ route('owner.employees.index') }}"
                           class="inline-flex items-center justify-center h-9 px-3 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs font-semibold text-[#64748B] hover:bg-[#FFD8D8] transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Employees Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11px] font-bold text-[#64748B] uppercase tracking-wider">
                            <th class="px-5 py-3">Nama Karyawan</th>
                            <th class="px-4 py-3">Email / Username</th>
                            <th class="px-4 py-3 text-center">Role</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($employees as $employee)
                            <tr class="hover:bg-[#FFD8D8]/20 transition">
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-[#172B4D] text-sm">
                                        {{ $employee->name }}
                                    </div>
                                    <div class="text-[10.5px] text-[#64748B]">
                                        Terdaftar sejak {{ \App\Support\DateTimeFormatter::formatDate($employee->created_at, 'd M Y') }}
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 font-mono text-[#64748B]">
                                    {{ $employee->email }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    @if($employee->isAdmin())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#8E1B5C]/10 text-[#8E1B5C] border border-[#8E1B5C]/20">
                                            Admin
                                        </span>
                                    @elseif($employee->isGudang())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                                            Gudang
                                        </span>
                                    @elseif($employee->isProduksi())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            Produksi
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    @if($employee->isActive())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-600 border border-neutral-300">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('owner.employees.show', $employee) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                                            Detail
                                        </a>
                                        <a href="{{ route('owner.employees.edit', $employee) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-neutral-50 transition shadow-2xs">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('owner.employees.toggle-status', $employee) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    onclick="return confirm('Apakah Anda yakin ingin {{ $employee->isActive() ? 'menonaktifkan' : 'mengaktifkan' }} akun karyawan {{ $employee->name }}?')"
                                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer {{ $employee->isActive() ? 'text-amber-700 hover:bg-amber-50 border border-amber-200' : 'text-emerald-700 hover:bg-emerald-50 border border-emerald-200' }}">
                                                {{ $employee->isActive() ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-[#64748B]">
                                    Tidak ada data karyawan internal yang cocok dengan kriteria filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($employees->hasPages())
                <div class="p-4 border-t border-[#FFD8D8] bg-[#FFFCFB]">
                    {{ $employees->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
