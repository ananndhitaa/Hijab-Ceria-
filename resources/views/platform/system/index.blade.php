@extends('platform.layouts.app', [
    'title' => 'Monitoring Sistem',
    'headerTitle' => 'Monitoring Sistem Platform NAYARA',
    'breadcrumb' => 'Platform / Monitoring Sistem'
])

@section('content')
<div class="space-y-6">

    {{-- System Status Banner --}}
    <x-feature-header
        title="Status Platform Aktif"
        subtitle="Informasi konfigurasi infrastruktur dan runtime sistem yang aman untuk diinspeksi oleh Super Admin."
        icon="security"
        badge="MONITORING SISTEM &rsaquo; PLATFORM NAYARA"
    >
        <x-slot:actions>
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Infrastruktur Sehat
            </span>
        </x-slot:actions>
    </x-feature-header>

    {{-- Grid Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        {{-- Runtime Environment --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-sm text-slate-800">Runtime & Framework</h3>
            </div>
            <dl class="divide-y divide-slate-100 text-xs mt-3">
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500">Sistem Operasi</dt>
                    <dd class="font-semibold text-slate-800">{{ $systemData['os'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500">Versi PHP</dt>
                    <dd class="font-semibold text-slate-800">{{ $systemData['php_version'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500">Versi Laravel</dt>
                    <dd class="font-semibold text-slate-800">{{ $systemData['laravel_version'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500">Web Server</dt>
                    <dd class="font-semibold text-slate-800 truncate max-w-[180px] text-right">{{ $systemData['server_software'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500">Timezone</dt>
                    <dd class="font-semibold text-slate-800">{{ $systemData['app_timezone'] }}</dd>
                </div>
            </dl>
        </div>

        {{-- Database Engine --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7m-16 0c0-2 1.5-3 3.5-3h9c2 0 3.5 1 3.5 3m-16 0v5c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7"/>
                    </svg>
                </div>
                <h3 class="font-bold text-sm text-slate-800">Database & Schema</h3>
            </div>
            <dl class="divide-y divide-slate-100 text-xs mt-3">
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500">Koneksi Driver</dt>
                    <dd class="font-semibold text-slate-800 uppercase">{{ $systemData['database_connection'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500">Versi Database</dt>
                    <dd class="font-semibold text-slate-800 truncate max-w-[180px] text-right">{{ $systemData['database_version'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500">Nama Database</dt>
                    <dd class="font-mono font-semibold text-indigo-600">{{ $systemData['database_name'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500">Total Tabel</dt>
                    <dd class="font-semibold text-slate-800">{{ $systemData['total_tables'] }} tabel</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500">Total Migrasi Diterapkan</dt>
                    <dd class="font-semibold text-slate-800">{{ $systemData['migrations_count'] }} migrasi</dd>
                </div>
            </dl>
        </div>

        {{-- Multi-Tenant & Security Policy --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-sm text-slate-800">Isolasi & Kebijakan Keamanan</h3>
            </div>
            <dl class="divide-y divide-slate-100 text-xs mt-3">
                <div class="py-2.5 flex justify-between items-center">
                    <dt class="text-slate-500">Tenant Isolation Mode</dt>
                    <dd>
                        @if($systemData['tenant_isolation'])
                            <span class="badge badge-success">ACTIVE (TRUE)</span>
                        @else
                            <span class="badge badge-secondary">LEGACY COMPAT (FALSE)</span>
                        @endif
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500">Lingkungan Aplikasi (Env)</dt>
                    <dd class="font-semibold text-slate-800 uppercase">{{ $systemData['app_env'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between items-center">
                    <dt class="text-slate-500">Debug Mode</dt>
                    <dd>
                        @if($systemData['app_debug'])
                            <span class="badge badge-warning">ENABLED</span>
                        @else
                            <span class="badge badge-secondary">DISABLED</span>
                        @endif
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500">Cache Driver</dt>
                    <dd class="font-semibold text-slate-800">{{ $systemData['cache_driver'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500">Session Driver</dt>
                    <dd class="font-semibold text-slate-800">{{ $systemData['session_driver'] }}</dd>
                </div>
            </dl>
        </div>

    </div>

</div>
@endsection
