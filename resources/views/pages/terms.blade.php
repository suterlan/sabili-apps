@extends('layouts.guest')

@section('title', 'Syarat dan Ketentuan')

@section('content')
    <div class="bg-gray-50 min-h-screen py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Syarat & Ketentuan</h1>
                <p class="mt-4 text-lg text-gray-500">Perjanjian penggunaan layanan antara Pengguna dan Sabili Community.</p>
                <p class="mt-2 text-sm text-gray-400">Terakhir diperbarui: {{ date('d F Y') }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-8 sm:p-10 space-y-8 text-gray-600 leading-relaxed">

                    <section>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 uppercase tracking-wide">1. Struktur Biaya Layanan
                        </h3>
                        <p>Pengguna setuju untuk membayar layanan sesuai dengan tarif yang berlaku pada sistem kami:</p>
                        <ul class="list-disc pl-5 space-y-2 mt-2">
                            <li><strong>Membership:</strong> Rp 50.000/bulan (Akses dashboard & komunitas).</li>
                            <li><strong>Jasa Input Admin:</strong> Rp 20.000/data (Bantuan entri data teknis ke sistem
                                pemerintah).</li>
                            <li><strong>Produk Fisik:</strong> Rp 10.000 (Paket cetak 5 pcs stiker halal).</li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 uppercase tracking-wide">2. Pembayaran Melalui
                            Duitku</h3>
                        <p>Seluruh transaksi pada platform Sabili diproses secara otomatis melalui payment gateway Duitku.
                            Pengguna wajib memastikan nominal yang dibayarkan sesuai dengan kode bayar atau tautan yang
                            diberikan. Layanan akan otomatis aktif setelah status pembayaran dinyatakan "Berhasil" oleh
                            sistem Duitku.</p>
                    </section>

                    <section>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 uppercase tracking-wide">3. Batas Tanggung Jawab
                        </h3>
                        <p>Sabili tidak bertanggung jawab atas kegagalan pengajuan sertifikasi jika disebabkan oleh
                            ketidakjujuran pengguna mengenai bahan baku atau kelalaian dalam melengkapi dokumen setelah jasa
                            input (Rp 20.000) dilakukan.</p>
                    </section>

                    <div class="mt-8 pt-8 border-t border-gray-200 text-center">
                        <p class="text-sm text-gray-500">Kontak bantuan: <a href="mailto:sabiliapps@gmail.com"
                                class="text-amber-600 hover:underline">sabiliapps@gmail.com</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
