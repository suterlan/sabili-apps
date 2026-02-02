<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Member Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center py-10 px-4">

    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Daftar Member</h2>
            <p class="text-gray-500 mt-2">Isi data diri & lakukan pembayaran untuk akses penuh.</p>
        </div>

        {{-- [UPDATE] Alert Error yang Lebih Cantik --}}
        @if (session('error'))
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Gagal Memproses</h3>
                        <p class="text-sm text-red-700 mt-1">
                            {{ session('error') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Alert Validation Errors Tetap Sama --}}
        @if ($errors->any())
            <div class="bg-yellow-50 text-yellow-700 p-4 rounded-lg mb-6 text-sm border border-yellow-200">
                <p class="font-bold mb-1">Mohon periksa inputan Anda:</p>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('member.register.store') }}" method="POST">
            @csrf

            <div class="mb-5">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                    class="w-full px-4 py-2 border rounded-lg outline-none transition
                    @error('name') border-red-500 focus:ring-red-500 focus:border-red-500 text-red-900 
                    @else border-gray-300 focus:ring-amber-500 focus:border-amber-500 @enderror"
                    placeholder="Contoh: Budi Santoso">

                {{-- Pesan Error Spesifik --}}
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                    class="w-full px-4 py-2 border rounded-lg outline-none transition
                    @error('email') border-red-500 focus:ring-red-500 focus:border-red-500 text-red-900
                    @else border-gray-300 focus:ring-amber-500 focus:border-amber-500 @enderror"
                    placeholder="nama@email.com">

                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp / HP</label>
                <input type="number" name="phone" id="phone" value="{{ old('phone') }}"
                    class="w-full px-4 py-2 border rounded-lg outline-none transition
                    @error('phone') border-red-500 focus:ring-red-500 focus:border-red-500 text-red-900
                    @else border-gray-300 focus:ring-amber-500 focus:border-amber-500 @enderror"
                    placeholder="08123456789">

                @error('phone')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror

            </div>

            <div class="mb-5">
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap</label>
                <textarea name="address" id="address" rows="3"
                    class="w-full px-4 py-2 border rounded-lg outline-none transition
                    @error('address') border-red-500 focus:ring-red-500 focus:border-red-500 text-red-900
                    @else border-gray-300 focus:ring-amber-500 focus:border-amber-500 @enderror"
                    placeholder="Contoh: Jl. Merdeka No. 123, Jakarta">{{ old('address') }}</textarea>

                @error('address')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" id="password"
                    class="w-full px-4 py-2 border rounded-lg outline-none transition
                    @error('password') border-red-500 focus:ring-red-500 focus:border-red-500
                    @else border-gray-300 focus:ring-amber-500 focus:border-amber-500 @enderror"
                    placeholder="Minimal 8 karakter">

                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-8">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi
                    Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                    class="w-full px-4 py-2 border rounded-lg outline-none transition
                    @error('password') border-red-500 focus:ring-red-500 focus:border-red-500
                    @else border-gray-300 focus:ring-amber-500 focus:border-amber-500 @enderror"
                    placeholder="Ulangi password">
            </div>

            <button type="submit"
                class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-3 rounded-lg shadow-md transition duration-300 transform hover:scale-[1.02]">
                Daftar Sekarang
            </button>

            <div class="text-center mt-6">
                <p class="text-sm text-gray-600">Sudah punya akun?
                    <a href="/admin/login" class="text-amber-600 hover:underline font-semibold">Login disini</a>
                </p>
            </div>
        </form>
    </div>

</body>

</html>
