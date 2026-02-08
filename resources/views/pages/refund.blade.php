@extends('layouts.guest')

@section('title', 'Kebijakan Pengembalian Dana')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Kebijakan Pengembalian Dana</h1>
            <p class="mt-2 text-lg text-gray-500">Transparansi layanan untuk kepercayaan Anda.</p>
        </div>

        <div
            class="prose prose-amber max-w-none text-gray-600 space-y-6 bg-white p-8 rounded-xl shadow-sm border border-gray-100">
            <section>
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-500"></i> 1. Kondisi Refund (Diterima)
                </h3>
                <div class="ml-7">
                    <ul class="list-disc pl-5 space-y-2">
                        <li><strong>Pembayaran Ganda:</strong> Jika terjadi pendebetan ganda oleh sistem Duitku untuk
                            invoice yang sama.</li>
                        <li><strong>Produk Rusak:</strong> Khusus untuk pembelian <strong>Stiker Halal (Rp 10.000)</strong>,
                            dana akan dikembalikan atau dikirim ulang jika stiker diterima dalam keadaan cacat/salah cetak
                            dengan bukti video unboxing.</li>
                        <li><strong>Kegagalan Layanan:</strong> Layanan admin belum diproses dalam 7 hari kerja karena
                            kendala internal kami.</li>
                    </ul>
                </div>
            </section>

            <section>
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-times-circle text-red-500"></i> 2. Kondisi Non-Refundable (Ditolak)
                </h3>
                <div class="ml-7 bg-red-50 p-4 rounded-md text-red-800 text-sm border-l-4 border-red-500 mb-4">
                    <strong>PENTING:</strong> Dana tidak dapat dikembalikan jika proses pekerjaan telah dilakukan.
                </div>
                <div class="ml-7">
                    <ul class="list-disc pl-5 space-y-2">
                        <li><strong>Jasa Input Data (Rp 20.000):</strong> Dana tidak dapat ditarik kembali jika data sudah
                            berhasil diinput ke sistem pemerintah, terlepas dari hasil akhir pengajuan (Diterima/Ditolak).
                        </li>
                        <li><strong>Membership (Rp 50.000):</strong> Biaya langganan yang sudah berjalan tidak dapat
                            dikembalikan secara prorata.</li>
                        <li><strong>Kesalahan Pengguna:</strong> Salah memberikan bahan baku atau membatalkan pengajuan saat
                            audit sedang berlangsung.</li>
                    </ul>
                </div>
            </section>

            <section class="mt-8">
                <h3 class="text-lg font-bold text-gray-900">3. Jangka Waktu Refund</h3>
                <p>Proses pengembalian dana melalui payment gateway membutuhkan waktu <strong>7 hingga 14 hari
                        kerja</strong> sesuai dengan kebijakan bank penarik atau penyedia e-wallet terkait.</p>
            </section>
        </div>
    </div>
@endsection
