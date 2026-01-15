<x-filament-widgets::widget>
    <x-filament::section>
        <div class="overflow-x-auto">
            <h2 class="text-lg font-bold mb-4">Rekapitulasi Kinerja Verifikator</h2>

            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border-collapse">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 border border-gray-200 dark:border-gray-600">
                            Nama Verifikator
                        </th>
                        {{-- Loop Header Status --}}
                        @foreach ($statuses as $status)
                            <th scope="col" class="px-4 py-3 border border-gray-200 dark:border-gray-600 text-center">
                                {{ str_replace('_', ' ', $status) }}
                            </th>
                        @endforeach
                        <th scope="col"
                            class="px-4 py-3 border border-gray-200 dark:border-gray-600 text-center font-bold">
                            TOTAL
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {{-- 1. Baris untuk Verifikator --}}
                    @foreach ($verificators as $user)
                        <tr
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white border-r">
                                {{ $user->name }}
                            </td>

                            @php $grandTotalUser = 0; @endphp

                            @foreach ($statuses as $status)
                                @php
                                    $count = $matrix[$user->id][$status] ?? 0;
                                    $grandTotalUser += $count;
                                @endphp
                                <td
                                    class="px-4 py-2 text-center border-r {{ $count > 0 ? 'font-bold text-primary-600' : 'text-gray-300' }}">
                                    {{ $count }}
                                </td>
                            @endforeach

                            <td class="px-4 py-2 text-center font-bold bg-gray-100 dark:bg-gray-900">
                                {{ $grandTotalUser }}
                            </td>
                        </tr>
                    @endforeach

                    {{-- 2. Baris untuk Belum Diklaim (Optional) --}}
                    @if (array_sum($unclaimed) > 0)
                        <tr class="bg-red-50 dark:bg-red-900/20 border-b">
                            <td class="px-4 py-2 font-medium text-red-600 dark:text-red-400 border-r">
                                Belum Ada Verifikator
                            </td>
                            @php $grandTotalUnclaimed = 0; @endphp
                            @foreach ($statuses as $status)
                                @php
                                    $count = $unclaimed[$status] ?? 0;
                                    $grandTotalUnclaimed += $count;
                                @endphp
                                <td
                                    class="px-4 py-2 text-center border-r {{ $count > 0 ? 'font-bold text-red-600' : 'text-gray-300' }}">
                                    {{ $count }}
                                </td>
                            @endforeach
                            <td class="px-4 py-2 text-center font-bold">
                                {{ $grandTotalUnclaimed }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
