@extends('layouts.guest')

@section('title', 'Tagihan #INV-2024001')

@section('content')
    <div class="bg-gray-50 min-h-screen py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded shadow-sm" role="alert">
                <p class="font-bold">Mode Simulasi</p>
                <p class="text-sm">Halaman ini adalah contoh tampilan tagihan yang akan diterima member setelah verifikasi
                    data selesai.</p>
            </div>

            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">

                <div
                    class="bg-gray-900 px-8 py-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-white font-bold text-2xl tracking-wider">INVOICE</h1>
                        <p class="text-gray-400 text-sm mt-1">#INV-2024001</p>
                    </div>
                    <div class="text-right">
                        <div
                            class="inline-block px-4 py-1.5 bg-amber-100 text-amber-700 text-xs font-bold rounded-full uppercase tracking-widest border border-amber-200">
                            Menunggu Pembayaran
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10 border-b border-gray-100 pb-10">
                        <div>
                            <h2 class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-2">Ditagihkan Kepada
                            </h2>
                            <p class="font-bold text-gray-900 text-lg">Bapak Reviewer Duitku</p>
                            <p class="text-gray-600">UMKM Contoh Sejahtera</p>
                            <p class="text-gray-500 text-sm mt-1">reviewer@duitku.com</p>
                            <p class="text-gray-500 text-sm">Jl. Kebon Jeruk, Jakarta</p>
                        </div>
                        <div class="md:text-right">
                            <div class="mb-4">
                                <h2 class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Tanggal Tagihan
                                </h2>
                                <p class="font-medium text-gray-900">{{ date('d F Y') }}</p>
                            </div>
                            <div>
                                <h2 class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Jatuh Tempo</h2>
                                <p class="font-bold text-red-600">{{ date('d F Y', strtotime('+3 days')) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full mb-8">
                            <thead>
                                <tr class="text-left border-b-2 border-gray-100">
                                    <th class="pb-4 text-gray-500 font-bold text-xs uppercase tracking-wider w-2/3">
                                        Deskripsi Layanan</th>
                                    <th class="pb-4 text-right text-gray-500 font-bold text-xs uppercase tracking-wider">
                                        Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="py-6 border-b border-gray-50 align-top">
                                        <p class="font-bold text-gray-900 text-lg">Paket Keanggotaan & Pendampingan</p>
                                        <ul class="mt-2 text-sm text-gray-500 list-disc pl-4 space-y-1">
                                            <li>Registrasi Anggota Sabili Community</li>
                                            <li>Verifikasi Data NIB & KTP</li>
                                            <li>Pendampingan Sertifikasi Halal (Self Declare)</li>
                                            <li>Akses Dashboard Full Features</li>
                                        </ul>
                                    </td>
                                    <td
                                        class="py-6 border-b border-gray-50 text-right font-bold text-gray-900 text-lg align-top">
                                        Rp 50.000</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="pt-6 text-gray-600 font-medium text-right pr-8">Total Tagihan</td>
                                    <td class="pt-6 text-right text-3xl font-extrabold text-amber-600">Rp 50.000</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div
                        class="bg-gray-50 rounded-xl p-6 border border-gray-200 mt-4 flex flex-col items-center justify-center text-center">
                        <p class="text-gray-600 mb-6 max-w-lg">
                            Silakan selesaikan pembayaran untuk mengaktifkan akun Anda. Transaksi ini diproses secara aman.
                        </p>

                        <button onclick="openPaymentModal()"
                            class="group relative w-full sm:w-64 bg-amber-600 hover:bg-amber-700 text-white font-bold py-4 px-6 rounded-lg shadow-lg transition-all duration-300 transform hover:-translate-y-1 focus:outline-none focus:ring-4 focus:ring-amber-300">
                            <span class="flex items-center justify-center gap-2">
                                <i class="fas fa-lock text-sm"></i> Bayar Sekarang
                            </span>
                        </button>

                        <div class="mt-6 flex justify-center gap-4 opacity-50 grayscale transition hover:grayscale-0">
                            <i class="fab fa-cc-visa text-2xl"></i>
                            <i class="fab fa-cc-mastercard text-2xl"></i>
                            <i class="fas fa-university text-2xl"></i>
                            <i class="fas fa-qrcode text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-8">
                <a href="/" class="text-gray-400 hover:text-gray-600 text-sm font-medium">
                    &larr; Kembali ke Beranda
                </a>
            </div>
        </div>

        <div id="paymentModal" class="fixed inset-0 z-[999] hidden" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm"
                onclick="closePaymentModal()"></div>

            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">

                    <div
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border-t-4 border-amber-600">

                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-bold leading-6 text-gray-900 flex justify-between items-center"
                                        id="modal-title">
                                        Pilih Metode Pembayaran
                                        <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-500">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 mb-4">Total Pembayaran: <strong
                                                class="text-amber-600">Rp 50.000</strong></p>

                                        <div class="space-y-3">
                                            <button onclick="simulateSuccess('Virtual Account')"
                                                class="w-full flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-amber-500 transition group">
                                                <div class="flex items-center gap-3">
                                                    <div class="bg-blue-100 p-2 rounded text-blue-600"><i
                                                            class="fas fa-university"></i></div>
                                                    <span
                                                        class="font-medium text-gray-700 group-hover:text-amber-700">Virtual
                                                        Account Bank</span>
                                                </div>
                                                <i class="fas fa-chevron-right text-gray-300"></i>
                                            </button>

                                            <button onclick="simulateSuccess('QRIS')"
                                                class="w-full flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-amber-500 transition group">
                                                <div class="flex items-center gap-3">
                                                    <div class="bg-gray-100 p-2 rounded text-gray-800"><i
                                                            class="fas fa-qrcode"></i></div>
                                                    <span class="font-medium text-gray-700 group-hover:text-amber-700">QRIS
                                                        / E-Wallet</span>
                                                </div>
                                                <i class="fas fa-chevron-right text-gray-300"></i>
                                            </button>

                                            <button onclick="simulateSuccess('Retail')"
                                                class="w-full flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-amber-500 transition group">
                                                <div class="flex items-center gap-3">
                                                    <div class="bg-red-100 p-2 rounded text-red-600"><i
                                                            class="fas fa-store"></i></div>
                                                    <span
                                                        class="font-medium text-gray-700 group-hover:text-amber-700">Alfamart
                                                        / Indomaret</span>
                                                </div>
                                                <i class="fas fa-chevron-right text-gray-300"></i>
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <p class="text-xs text-center text-gray-400 w-full">Powered by Duitku Payment Gateway</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function openPaymentModal() {
            const modal = document.getElementById('paymentModal');
            modal.classList.remove('hidden');
            // Animasi kecil
            const content = modal.querySelector('.relative');
            content.classList.add('scale-100', 'opacity-100');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }

        function simulateSuccess(method) {
            closePaymentModal();

            // Simulasi Loading
            Swal.fire({
                title: 'Menghubungkan ke ' + method + '...',
                text: 'Mohon tunggu sebentar',
                timer: 1500,
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading()
                }
            }).then((result) => {
                // Simulasi Berhasil Buka Window Pembayaran
                Swal.fire({
                    icon: 'success',
                    title: 'Simulasi Berhasil',
                    text: 'Di sistem asli, jendela pembayaran Duitku (Snap/Redirect) akan muncul di tahap ini.',
                    confirmButtonColor: '#d97706', // Warna Amber
                    confirmButtonText: 'Mengerti (Kembali ke Demo)'
                });
            });
        }
    </script>
@endsection
