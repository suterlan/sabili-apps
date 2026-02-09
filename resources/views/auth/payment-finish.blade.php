@extends('layouts.guest')

@section('title', 'Pembayaran Berhasil')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full text-center bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                <i class="fas fa-check text-4xl text-green-600"></i>
            </div>

            <h1 class="text-3xl font-extrabold text-gray-900">Pembayaran Berhasil!</h1>

            <div class="mt-4 text-gray-600 leading-relaxed">
                <p>Terima kasih atas pembayaran Anda. Transaksi telah berhasil diverifikasi oleh sistem kami.</p>
                <p class="mt-2 text-sm text-gray-500 italic">
                    Layanan Anda akan segera diperbarui secara otomatis dalam beberapa saat.
                </p>
            </div>

            <div class="mt-8 space-y-3">
                <a href="/admin"
                    class="block w-full bg-amber-600 text-white font-bold px-6 py-3 rounded-xl hover:bg-amber-700 transition shadow-lg shadow-amber-200">
                    Masuk ke Dashboard
                </a>

                <p class="text-xs text-gray-400">
                    Ada kendala? Hubungi kami di <span class="font-medium text-gray-600">sabiliapps@gmail.com</span>
                </p>
            </div>
        </div>
    </div>
@endsection
