<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name')) - Sabili Community</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 antialiased flex flex-col min-h-screen">

    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex-shrink-0 flex items-center gap-3 cursor-pointer" onclick="window.location.href='/'">
                    <div
                        class="w-10 h-10 bg-amber-600 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-lg">
                        S</div>
                    <div>
                        <span class="block font-bold text-xl tracking-tight text-gray-900 leading-none">Sabili</span>
                        <span class="text-xs text-gray-500 font-medium">Community Platform</span>
                    </div>
                </div>

                <div class="hidden md:flex items-center gap-8">
                    @if (Request::is('/'))
                        <a href="#tentang"
                            class="text-sm font-medium text-gray-600 hover:text-amber-600 transition">Tentang Kami</a>
                        <a href="#keuntungan"
                            class="text-sm font-medium text-gray-600 hover:text-amber-600 transition">Keuntungan</a>
                        <a href="#biaya"
                            class="text-sm font-medium text-gray-600 hover:text-amber-600 transition">Biaya</a>
                    @else
                        <a href="/"
                            class="text-sm font-medium text-gray-600 hover:text-amber-600 transition flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                        </a>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="/admin"
                            class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-full font-medium transition shadow-md text-sm">Dashboard</a>
                    @else
                        <a href="/admin/login"
                            class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-full font-medium transition shadow-lg shadow-amber-600/20 text-sm">Masuk</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow">
        @yield('content')
    </main>

    <footer class="bg-white border-t border-gray-200 pt-12 pb-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div class="col-span-1 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-amber-600 rounded text-white flex items-center justify-center font-bold">
                            S</div>
                        <span class="font-bold text-xl text-gray-900">Sabili</span>
                    </div>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Platform komunitas dan pendampingan sertifikasi halal terpercaya di Indonesia.
                    </p>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase mb-4">Kantor Pusat</h3>
                    <p class="text-gray-700 text-sm mb-2"><i class="fas fa-map-marker-alt w-5 text-amber-600"></i> Jl.
                        R.H Mulya, Desa Pusakasari, Kec. Leles, Kab. Cianjur, Jawa Barat</p>
                    <p class="text-gray-700 text-sm mb-2"><i class="fas fa-envelope w-5 text-amber-600"></i>
                        sabiliapps@gmail.com</p>
                    <p class="text-gray-700 text-sm"><i class="fas fa-phone w-5 text-amber-600"></i> 0857-1295-3879</p>
                </div>

                <div class="col-span-1">
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase mb-4">Kebijakan</h3>
                    <ul class="space-y-3">
                        <li><a href="{{ route('page.terms') }}"
                                class="text-base text-gray-500 hover:text-amber-600">Syarat & Ketentuan</a></li>
                        <li><a href="{{ route('page.privacy') }}"
                                class="text-base text-gray-500 hover:text-amber-600">Kebijakan Privasi</a></li>
                        <li><a href="{{ route('page.refund') }}"
                                class="text-base text-gray-500 hover:text-amber-600">Refund Policy</a></li>
                        <li><a href="{{ route('page.about') }}"
                                class="text-base text-gray-500 hover:text-amber-600">Tentang Kami</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-200 pt-8 text-center text-gray-400 text-sm">
                &copy; {{ date('Y') }} Sabili Community. All rights reserved.
            </div>
        </div>
    </footer>
</body>

</html>
