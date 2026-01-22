@extends('layouts.guest')

@section('title', 'Syarat dan Ketentuan')

@section('content')
    <div class="bg-gray-50 min-h-screen py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12">
                <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Syarat & Ketentuan</h1>
                <p class="mt-4 text-lg text-gray-500">
                    Perjanjian penggunaan layanan antara Pengguna dan Sabili Community.
                </p>
                <p class="mt-2 text-sm text-gray-400">Terakhir diperbarui: {{ date('d F Y') }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-8 sm:p-10 space-y-8 text-gray-600 leading-relaxed">

                    <p>
                        Selamat datang di Sabili. Dengan mendaftar, mengakses, atau menggunakan layanan kami, Anda
                        menyatakan setuju untuk terikat oleh Syarat dan Ketentuan ini. Jika Anda tidak setuju, mohon untuk
                        tidak melanjutkan penggunaan layanan.
                    </p>

                    <section>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 uppercase tracking-wide">1. Definisi Layanan</h3>
                        <p>
                            Sabili menyediakan jasa intermediasi, pendampingan, dan edukasi bagi Pelaku Usaha (UMKM) untuk
                            mengurus legalitas usaha (NIB) dan Sertifikasi Halal.
                        </p>
                        <div class="mt-3 bg-yellow-50 p-4 rounded border-l-4 border-yellow-400 text-yellow-800 text-sm">
                            <strong>PENAFIAN PENTING:</strong> Sabili bertindak sebagai Fasilisator dan Pendamping.
                            Kewenangan penerbitan Sertifikat Halal sepenuhnya berada di tangan Badan Penyelenggara Jaminan
                            Produk Halal (BPJPH) dan Komite Fatwa MUI. Kami tidak menjamin kelulusan jika produk/bahan Anda
                            tidak memenuhi syariat.
                        </div>
                    </section>

                    <hr class="border-gray-100">

                    <section>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 uppercase tracking-wide">2. Akun dan Keanggotaan
                        </h3>
                        <ul class="list-disc pl-5 space-y-2">
                            <li>Anda wajib memberikan informasi yang akurat, lengkap, dan terbaru saat mendaftar.</li>
                            <li>Anda bertanggung jawab menjaga kerahasiaan kata sandi akun Anda.</li>
                            <li>Sabili berhak menangguhkan akun jika ditemukan indikasi pemalsuan data atau penipuan.</li>
                        </ul>
                    </section>

                    <hr class="border-gray-100">

                    <section>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 uppercase tracking-wide">3. Pembayaran dan Biaya
                        </h3>
                        <ul class="list-disc pl-5 space-y-2">
                            <li>Biaya keanggotaan atau jasa pendampingan tertera pada halaman penawaran dan dapat berubah
                                sewaktu-waktu.</li>
                            <li>Pembayaran wajib dilunasi sebelum proses pendampingan dimulai.</li>
                            <li>Kebijakan mengenai pembatalan dan pengembalian dana diatur secara terpisah dalam halaman
                                <a href="{{ route('page.refund') }}"
                                    class="text-amber-600 hover:underline font-medium">Kebijakan Refund</a>.
                            </li>
                        </ul>
                    </section>

                    <hr class="border-gray-100">

                    <section>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 uppercase tracking-wide">4. Kewajiban Pengguna
                            (Pelaku Usaha)</h3>
                        <p class="mb-2">Dalam proses sertifikasi halal, Anda wajib:</p>
                        <ul class="list-decimal pl-5 space-y-2">
                            <li>Jujur mengenai seluruh bahan baku yang digunakan.</li>
                            <li>Tidak mengubah bahan baku tanpa sepengetahuan Pendamping selama proses berjalan.</li>
                            <li>Menjamin proses produksi bebas dari kontaminasi bahan haram/najis.</li>
                            <li>Bersedia dilakukan audit/verifikasi lapangan oleh Pendamping atau Auditor Halal.</li>
                        </ul>
                    </section>

                    <hr class="border-gray-100">

                    <section>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 uppercase tracking-wide">5. Pembatasan Tanggung
                            Jawab</h3>
                        <p>
                            Sabili tidak bertanggung jawab atas kerugian langsung maupun tidak langsung yang timbul akibat:
                        </p>
                        <ul class="list-disc pl-5 mt-2 space-y-1">
                            <li>Kesalahan input data yang dilakukan oleh Pengguna.</li>
                            <li>Penolakan pengajuan sertifikasi oleh BPJPH/MUI karena ketidaksesuaian produk.</li>
                            <li>Keterlambatan sistem pemerintahan (SiHalal/OSS) yang berada di luar kendali kami.</li>
                        </ul>
                    </section>

                    <hr class="border-gray-100">

                    <section>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 uppercase tracking-wide">6. Hukum yang Berlaku</h3>
                        <p>
                            Syarat dan ketentuan ini diatur berdasarkan hukum Republik Indonesia. Segala perselisihan yang
                            timbul akan diselesaikan melalui musyawarah untuk mufakat terlebih dahulu.
                        </p>
                    </section>

                    <div class="mt-8 pt-8 border-t border-gray-200 text-center">
                        <p class="text-sm text-gray-500">
                            Jika ada pertanyaan mengenai syarat ini, hubungi kami di <a href="mailto:sabiliapps@gmail.com"
                                class="text-amber-600 hover:underline">sabiliapps@gmail.com</a>
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
