@extends('layouts.guest')

@section('title', 'Tentang Kami')

@section('content')
    <div class="bg-white">
        <div class="relative bg-amber-600 py-16 sm:py-24">
            <div class="absolute inset-0 overflow-hidden">
                <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1471&q=80"
                    alt="Team work" class="w-full h-full object-cover opacity-10">
            </div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">Tentang Sabili</h1>
                <p class="mt-6 max-w-2xl mx-auto text-xl text-amber-100">
                    Partner terpercaya UMKM Indonesia dalam legalitas usaha dan sertifikasi halal.
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

            <div class="max-w-3xl mx-auto text-lg text-gray-500 mb-16">
                <h2 class="text-3xl font-extrabold text-gray-900 mb-6 text-center">Siapa Kami?</h2>
                <p class="mb-6 leading-relaxed">
                    <strong>Sabili Community</strong> adalah platform digital dan komunitas yang bergerak di bidang jasa
                    pendampingan usaha. Kami hadir sebagai solusi bagi pelaku Usaha Mikro, Kecil, dan Menengah (UMKM) yang
                    seringkali mengalami kesulitan dalam mengurus administrasi legalitas dan sertifikasi halal.
                </p>
                <p class="leading-relaxed">
                    Dengan dukungan tim Pendamping Proses Produk Halal (PPH) yang tersertifikasi dan berpengalaman, kami
                    menjembatani proses yang rumit menjadi lebih sederhana, transparan, dan terukur. Kami percaya bahwa
                    setiap produk UMKM berhak untuk naik kelas melalui legalitas yang sah.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-20">
                <div class="bg-amber-50 p-8 rounded-2xl border border-amber-100">
                    <div class="w-12 h-12 bg-amber-600 rounded-lg flex items-center justify-center text-white mb-6 text-xl">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Visi Kami</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Menjadi ekosistem pendukung UMKM terbesar dan terpercaya di Indonesia yang mampu melahirkan jutaan
                        produk halal berkualitas global demi kemandirian ekonomi umat.
                    </p>
                </div>

                <div class="bg-gray-50 p-8 rounded-2xl border border-gray-100">
                    <div class="w-12 h-12 bg-gray-800 rounded-lg flex items-center justify-center text-white mb-6 text-xl">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Misi Kami</h3>
                    <ul class="space-y-3 text-gray-600">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-amber-600 mt-1"></i>
                            <span>Memberikan pendampingan sertifikasi halal yang cepat, tepat, dan sesuai syariat.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-amber-600 mt-1"></i>
                            <span>Mengedukasi pelaku usaha tentang pentingnya Sistem Jaminan Produk Halal (SJPH).</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-amber-600 mt-1"></i>
                            <span>Membangun jejaring bisnis yang solid antar anggota komunitas.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-gray-900 mb-12">Nilai Utama</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="flex flex-col items-center">
                        <div
                            class="flex items-center justify-center h-16 w-16 rounded-full bg-amber-100 text-amber-600 mb-4">
                            <i class="fas fa-handshake text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Amanah</h3>
                        <p class="mt-2 text-base text-gray-500 max-w-xs">
                            Kami menjaga kepercayaan data dan proses Anda dengan integritas tinggi.
                        </p>
                    </div>
                    <div class="flex flex-col items-center">
                        <div
                            class="flex items-center justify-center h-16 w-16 rounded-full bg-amber-100 text-amber-600 mb-4">
                            <i class="fas fa-user-tie text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Profesional</h3>
                        <p class="mt-2 text-base text-gray-500 max-w-xs">
                            Didampingi oleh tenaga ahli yang tersertifikasi resmi oleh BPJPH.
                        </p>
                    </div>
                    <div class="flex flex-col items-center">
                        <div
                            class="flex items-center justify-center h-16 w-16 rounded-full bg-amber-100 text-amber-600 mb-4">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Sinergi</h3>
                        <p class="mt-2 text-base text-gray-500 max-w-xs">
                            Tumbuh bersama dalam komunitas yang saling mendukung dan memberdayakan.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-900 rounded-2xl overflow-hidden shadow-xl">
                <div
                    class="px-6 py-12 sm:px-12 lg:py-16 lg:px-16 flex flex-col md:flex-row items-center justify-between gap-8">
                    <div class="text-left">
                        <h2 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                            Ingin berkonsultasi langsung?
                        </h2>
                        <p class="mt-3 text-lg text-gray-300">
                            Tim kami siap membantu Anda di jam kerja (Senin - Jumat, 09.00 - 17.00 WIB).
                        </p>
                        <div class="mt-6 text-gray-400 space-y-2">
                            <p class="flex items-center gap-3">
                                <i class="fas fa-map-marker-alt text-amber-500"></i>
                                Jl. R.H Mulya, Desa Pusakasari, Kec. Leles, Kab. Cianjur, Jawa Barat
                            </p>
                            <p class="flex items-center gap-3">
                                <i class="fas fa-envelope text-amber-500"></i>
                                sabiliapps@gmail.com
                            </p>
                            <p class="flex items-center gap-3">
                                <i class="fas fa-phone text-amber-500"></i>
                                0857-1295-3879
                            </p>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="https://wa.me/6285712953879" target="_blank"
                            class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-gray-900 bg-amber-500 hover:bg-amber-600 transition shadow-lg">
                            <i class="fab fa-whatsapp mr-2 font-bold"></i> Hubungi WhatsApp
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
