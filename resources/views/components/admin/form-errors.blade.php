{{-- Unified Admin Form Errors Summary Component --}}
@if($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 text-red-900 rounded-xl text-xs font-medium space-y-1.5 shadow-2xs animate-fade-in">
        <div class="flex items-center gap-2 font-bold text-red-900">
            <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>Periksa kembali data formulir yang ditandai:</span>
        </div>
        <ul class="list-disc list-inside pl-5 space-y-0.5 text-red-800">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
