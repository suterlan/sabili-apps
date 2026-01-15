<x-filament-widgets::widget>
    <x-filament::section>
        {{-- BAGIAN FORM FILTER --}}
        <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
            {{ $this->form }}
        </div>

        {{-- BAGIAN TABEL DATA --}}
        <div class="overflow-x-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                    Rekapitulasi Kinerja Verifikator
                </h2>
                <span
                    class="text-xs font-semibold text-primary-600 bg-primary-50 px-3 py-1 rounded-full border border-primary-200">
                    Periode: {{ $filter_label }}
                </span>
            </div>

            <table
                class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border-collapse border border-gray-200 dark:border-gray-700">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-200">
                    <tr>
                        <th scope="col" class="px-4 py-3 border border-gray-200 dark:border-gray-600">
                            Nama Verifikator
                        </th>
                        @foreach ($statuses as $status)
                            <th scope="col"
                                class="px-2 py-3 border border-gray-200 dark:border-gray-600 text-center min-w-[80px]">
                                {{-- Mengganti underscore dengan spasi agar rapi --}}
                                {{ ucwords(str_replace('_', ' ', $status)) }}
                            </th>
                        @endforeach
                        <th scope="col"
                            class="px-4 py-3 border border-gray-200 dark:border-gray-600 text-center font-bold bg-gray-200 dark:bg-gray-800">
                            TOTAL
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {{-- LOOPING ADMIN / VERIFIKATOR --}}
                    @foreach ($verificators as $user)
                        <tr
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                            <td
                                class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white border-r dark:border-gray-600">
                                {{ $user->name }}
                            </td>

                            @php $grandTotalUser = 0; @endphp

                            @foreach ($statuses as $status)
                                @php
                                    $count = $matrix[$user->id][$status] ?? 0;
                                    $grandTotalUser += $count;
                                @endphp
                                {{-- Cell Angka --}}
                                <td
                                    class="px-2 py-2 text-center border-r dark:border-gray-600 {{ $count > 0 ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-gray-300 dark:text-gray-600' }}">
                                    {{ $count }}
                                </td>
                            @endforeach

                            {{-- Total Per User --}}
                            <td
                                class="px-4 py-2 text-center font-bold bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white border-l dark:border-gray-600">
                                {{ $grandTotalUser }}
                            </td>
                        </tr>
                    @endforeach

                    {{-- LOOPING YANG BELUM DIKLAIM (JIKA ADA) --}}
                    @if (array_sum($unclaimed) > 0)
                        <tr class="bg-red-50 dark:bg-red-900/20 border-b border-red-100 dark:border-red-900">
                            <td
                                class="px-4 py-2 font-medium text-red-600 dark:text-red-400 border-r dark:border-gray-600 italic">
                                Belum Ada Verifikator
                            </td>
                            @php $grandTotalUnclaimed = 0; @endphp
                            @foreach ($statuses as $status)
                                @php
                                    $count = $unclaimed[$status] ?? 0;
                                    $grandTotalUnclaimed += $count;
                                @endphp
                                <td
                                    class="px-2 py-2 text-center border-r dark:border-gray-600 {{ $count > 0 ? 'font-bold text-red-600' : 'text-gray-300' }}">
                                    {{ $count }}
                                </td>
                            @endforeach
                            <td class="px-4 py-2 text-center font-bold text-red-700 dark:text-red-400">
                                {{ $grandTotalUnclaimed }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

            @if (count($verificators) == 0 && array_sum($unclaimed) == 0)
                <div class="p-4 text-center text-gray-500 italic">
                    Tidak ada data verifikasi pada rentang tanggal ini.
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
