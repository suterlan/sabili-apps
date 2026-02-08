@extends('layouts.guest')

@section('title', 'Kebijakan Privasi')

@section('content')
    <div class="bg-gray-50 min-h-screen py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Kebijakan Privasi</h1>
                <p class="mt-4 text-lg text-gray-500">Bagaimana Sabili mengumpulkan, menggunakan, dan melindungi data Anda.
                </p>
                <p class="mt-2 text-sm text-gray-400">Terakhir diperbarui: {{ date('d F Y') }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-8 sm:p-10 space-y-8 text-gray-600 leading-relaxed">
                    <p>Sabili ("Kami") berkomitmen melindungi data pribadi Anda. Kebijakan ini menjelaskan pengelolaan
                        informasi Anda saat menggunakan layanan pendampingan sertifikasi halal dan fitur pembayaran kami.
                    </p>

                    <section>
                        <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="bg-amber-100 text-amber-600 w-8 h-8 rounded-full flex items-center justify-center text-sm">1</span>
                            Informasi yang Kami Kumpulkan
                        </h3>
                        <ul class="list-disc pl-12 space-y-2">
                            <li><strong>Data Identitas:</strong> Nama, NIK, Alamat KTP, dan foto identitas.</li>
                            <li><strong>Data Usaha & Produksi:</strong> NIB, NPWP, Komposisi Bahan Baku, dan Foto Produk.
                            </li>
                            <li><strong>Data Transaksi:</strong> Alamat email, Nomor WhatsApp, dan histori pembayaran
                                layanan.</li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="bg-amber-100 text-amber-600 w-8 h-8 rounded-full flex items-center justify-center text-sm">2</span>
                            Pembagian Data kepada Pihak Ketiga
                        </h3>
                        <p class="mb-3">Kami meneruskan data Anda kepada instansi berwenang (BPJPH/MUI) untuk sertifikasi.
                            Terkait transaksi keuangan:</p>
                        <ul class="list-disc pl-12 space-y-2 text-amber-800">
                            <li><strong>Payment Gateway (Duitku):</strong> Kami membagikan data kontak dan nilai transaksi
                                kepada mitra resmi kami, <strong>Duitku</strong>, untuk memproses pembayaran secara aman.
                            </li>
                            <li><strong>Data Kartu/Rekening:</strong> Semua data finansial diproses di server
                                aman Duitku menggunakan enkripsi SSL.</li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="bg-amber-100 text-amber-600 w-8 h-8 rounded-full flex items-center justify-center text-sm">3</span>
                            Kontak Privasi
                        </h3>
                        <div class="mt-4 bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <p><strong>Email:</strong> sabiliapps@gmail.com</p>
                            <p><strong>Alamat:</strong> Jl. R.H Mulya, Desa Pusakasari, Kec. Leles, Kab. Cianjur, Jawa Barat
                            </p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
