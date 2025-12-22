<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

// Route untuk menampilkan gambar dari Google Drive (atau disk lain)
Route::get('/drive-image/{path}', function ($path) {
    // Pastikan path didecode agar karakter aneh terbaca
    $path = urldecode($path);

    // Ganti 'google' dengan nama disk Anda di config/filesystems.php
    $disk = Storage::disk('google');

    if (! $disk->exists($path)) {
        abort(404);
    }

    // Ini kuncinya: Laravel akan men-streaming file dari GDrive ke Browser
    // tanpa membebani DOM halaman utama.
    $response = $disk->response($path);

    // [OPTIMASI] Tambahkan Header Cache (misal: simpan 1 hari / 86400 detik)
    $response->headers->set('Cache-Control', 'public, max-age=86400');
    $response->headers->set('Pragma', 'public');
    $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

    return $response;
})->where('path', '.*')->name('drive.image')->middleware('auth');
// 'where' di atas penting jika path mengandung folder (slash /)
