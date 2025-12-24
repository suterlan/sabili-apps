<?php

use App\Http\Controllers\GoogleDriveImageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

// Route untuk menampilkan gambar dari Google Drive (atau disk lain)
Route::get('/drive-image/{path}', [GoogleDriveImageController::class, 'show'])
    ->where('path', '.*') // Regex agar path yang mengandung '/' tetap terbaca
    ->name('drive.image')
    ->middleware('auth'); // Sesuaikan middleware keamanan Anda
