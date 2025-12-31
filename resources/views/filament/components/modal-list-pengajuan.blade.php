<div class="overflow-x-auto">
    {{-- Info Header Ringkas --}}
    <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
        Nomor Invoice: <span class="font-bold text-gray-800 dark:text-white">{{ $tagihan->nomor_invoice }}</span>
    </div>

    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">Nama Pelaku Usaha</th>
                <th scope="col" class="px-6 py-3">Merk Dagang</th>
                <th scope="col" class="px-6 py-3 text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $item->user->name }}
                        <br>
                        <span class="text-xs text-gray-400">NIK: {{ $item->user->nik }}</span>
                    </td>
                    <td class="px-6 py-4">
                        {{ $item->user->merk_dagang ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-right font-medium">
                        {{-- Tampilkan Nominal Satuan dari Database --}}
                        Rp {{ number_format($tagihan->total_nominal, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-600">
                <td class="px-6 py-3" colspan="2">TOTAL YANG HARUS DIBAYAR ({{ $count }} Orang)</td>
                <td class="px-6 py-3 text-right text-lg text-primary-600">
                    {{-- Kalkulasi Total: Harga Satuan x Jumlah Orang --}}
                    Rp {{ number_format($tagihan->total_nominal * $count, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
