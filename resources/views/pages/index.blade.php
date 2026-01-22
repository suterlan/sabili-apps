@extends('layouts.guest')

@section('title', 'Pendaftaran Anggota')

@section('content')
    <div class="relative bg-white overflow-hidden" id="tentang">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                <svg class="hidden lg:block absolute right-0 inset-y-0 h-full w-48 text-white transform translate-x-1/2"
                    fill="currentColor" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                    <polygon points="50,0 100,0 50,100 0,100" />
                </svg>

                <div class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                    <div class="sm:text-center lg:text-left">
                        <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                            <span class="block xl:inline">Membangun Umat</span>
                            <span class="block text-amber-600 xl:inline">Melalui Komunitas</span>
                        </h1>
                        <p
                            class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                            Platform resmi pendaftaran anggota Sabili. Dapatkan akses eksklusif sertifikasi halal,
                            pendampingan usaha, dan jaringan bisnis terpercaya.
                        </p>

                        <div class="mt-5 flex items-center gap-2 text-sm text-gray-500 sm:justify-center lg:justify-start">
                            <i class="fas fa-check-circle text-green-500"></i> Pembayaran Aman
                            <i class="fas fa-check-circle text-green-500 ml-3"></i> Data Terenkripsi
                            <i class="fas fa-check-circle text-green-500 ml-3"></i> Layanan Resmi
                        </div>

                        <div class="mt-8 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                            <div class="rounded-md shadow">
                                <a href="/admin/register"
                                    class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 md:py-4 md:text-lg transition">Gabung
                                    Member</a>
                            </div>
                            <div class="mt-3 sm:mt-0 sm:ml-3">
                                <a href="#biaya"
                                    class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-amber-700 bg-amber-50 hover:bg-amber-100 md:py-4 md:text-lg transition">Lihat
                                    Paket</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
            <img class="h-56 w-full object-cover sm:h-72 md:h-96 lg:w-full lg:h-full"
                src="https://images.unsplash.com/photo-1556761175-5973dc0f32e7?ixlib=rb-1.2.1&auto=format&fit=crop&w=2850&q=80"
                alt="Team">
        </div>
    </div>

    <div class="bg-gray-50 border-y border-gray-200">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm font-semibold uppercase text-gray-500 tracking-wide">Mendukung Pembayaran Melalui
            </p>
            <div
                class="mt-6 grid grid-cols-2 gap-8 md:grid-cols-5 opacity-60 grayscale hover:grayscale-0 transition-all duration-500 text-center items-center">
                <span class="font-bold text-xl text-gray-700">BCA</span>
                <span class="font-bold text-xl text-gray-700">MANDIRI</span>
                <span class="font-bold text-xl text-gray-700">BRI</span>
                <span class="font-bold text-xl text-gray-700">QRIS</span>
                <span class="font-bold text-xl text-gray-700">E-WALLET</span>
            </div>
        </div>
    </div>

    <div class="py-16 bg-white" id="keuntungan">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-base text-amber-600 font-semibold tracking-wide uppercase">Value Proposition</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">Solusi Usaha Anda
                </p>
            </div>
            <div class="mt-14">
                <dl class="space-y-10 md:space-y-0 md:grid md:grid-cols-3 md:gap-x-8 md:gap-y-10">
                    <div class="relative p-6 bg-gray-50 rounded-xl hover:shadow-lg transition duration-300">
                        <dt>
                            <div
                                class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-amber-500 text-white">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <p class="ml-16 text-lg leading-6 font-bold text-gray-900">Jejaring Bisnis</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">Akses ke ribuan pelaku usaha untuk kolaborasi,
                            berbagi info supplier, dan ekspansi pasar.</dd>
                    </div>
                    <div class="relative p-6 bg-gray-50 rounded-xl hover:shadow-lg transition duration-300">
                        <dt>
                            <div
                                class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-amber-500 text-white">
                                <i class="fas fa-certificate text-xl"></i>
                            </div>
                            <p class="ml-16 text-lg leading-6 font-bold text-gray-900">Pendampingan Halal</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">Bantuan teknis pengurusan sertifikasi halal (Self
                            Declare & Reguler) hingga terbit sertifikat.</dd>
                    </div>
                    <div class="relative p-6 bg-gray-50 rounded-xl hover:shadow-lg transition duration-300">
                        <dt>
                            <div
                                class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-amber-500 text-white">
                                <i class="fas fa-shield-alt text-xl"></i>
                            </div>
                            <p class="ml-16 text-lg leading-6 font-bold text-gray-900">Legalitas Terjamin</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">Sistem terintegrasi dengan pemerintah untuk
                            memastikan validitas NIB dan data usaha Anda.</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <div class="bg-gray-50 py-16" id="biaya">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Biaya Keanggotaan</h2>
                {{-- <p class="mt-4 text-lg text-gray-500">Investasi transparan tanpa biaya tersembunyi.</p> --}}
            </div>

            <div class="mt-10 max-w-lg mx-auto bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
                <div class="px-6 py-8 sm:p-10 sm:pb-6 text-center">
                    <span
                        class="inline-flex px-4 py-1 rounded-full text-sm font-semibold tracking-wide uppercase bg-amber-100 text-amber-600">Membership</span>
                    <div class="mt-4 flex justify-center items-baseline text-6xl font-extrabold text-gray-900">
                        Rp 50.000 <span class="ml-1 text-2xl font-medium text-gray-500">/bulan</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 italic">
                        *Tagihan dikirim setelah data usaha diverifikasi Admin
                    </p>
                </div>
                <div class="px-6 pt-6 pb-8 bg-gray-50 sm:p-10 sm:pt-6">
                    <ul class="space-y-4 mb-6">
                        <li class="flex items-start"><i class="fas fa-check text-green-500 mt-1 mr-3"></i> <span
                                class="text-gray-700">Akses Penuh Dashboard</span></li>
                        <li class="flex items-start"><i class="fas fa-check text-green-500 mt-1 mr-3"></i> <span
                                class="text-gray-700">Pendampingan Sertifikasi Halal</span></li>
                        <li class="flex items-start"><i class="fas fa-check text-green-500 mt-1 mr-3"></i> <span
                                class="text-gray-700">Prioritas Event Komunitas</span></li>
                    </ul>

                    <a href="/admin/register"
                        class="block w-full text-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-gray-900 hover:bg-gray-800 transition">
                        Daftar & Ajukan Verifikasi
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
