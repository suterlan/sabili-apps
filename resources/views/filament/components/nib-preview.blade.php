@php
    $record = $getRecord();
    $path = $record->user->file_foto_nib ?? null;
@endphp

@if ($path)
    @php
        $url = route('drive.image', ['path' => $path]);
        $isPdf = \Illuminate\Support\Str::endsWith(strtolower($path), '.pdf');
    @endphp

    <div class="w-full border rounded-lg overflow-hidden bg-gray-50 mt-2" style="min-height: 600px;">

        {{-- KONDISI JIKA PDF --}}
        @if ($isPdf)
            <object data="{{ $url }}" type="application/pdf" width="100%" height="600px" class="w-full h-[600px]"
                style="min-height: 600px;">
                <iframe src="{{ $url }}" width="100%" height="600px"
                    style="border: none; min-height: 600px;"></iframe>
            </object>

            <div class="p-3 border-t bg-white flex items-center gap-4">
                <div class="bg-red-100 text-red-600 p-2 rounded">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
                <div class="text-sm text-gray-500">
                    <p class="font-bold text-gray-700">Dokumen PDF</p>
                    <a href="{{ $url }}" target="_blank"
                        class="text-primary-600 underline font-bold hover:text-primary-500">
                        Buka PDF
                    </a>
                </div>
            </div>

            {{-- KONDISI JIKA GAMBAR --}}
        @else
            <div class="p-2 text-center">
                <img src="{{ $url }}"
                    style="max-height: 500px; max-width: 100%; margin: 0 auto; border-radius: 8px;" class="shadow-sm"
                    alt="Preview NIB">
                <div class="mt-2 text-xs text-gray-500">
                    <a href="{{ $url }}" target="_blank" class="text-primary-600 underline">Lihat Gambar
                        Penuh</a>
                </div>
            </div>
        @endif
    </div>
@endif
