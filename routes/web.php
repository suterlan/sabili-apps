<?php

use App\Http\Controllers\GoogleDriveImageController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'index'])->name('page.index');
Route::get('/tentang-kami', [PageController::class, 'about'])->name('page.about');
Route::get('/syarat-ketentuan', [PageController::class, 'terms'])->name('page.terms');
Route::get('/kebijakan-privasi', [PageController::class, 'privacy'])->name('page.privacy');
Route::get('/kebijakan-refund', [PageController::class, 'refund'])->name('page.refund');

// Route untuk menampilkan gambar dari Google Drive (atau disk lain)
Route::get('/drive-image/{path}', [GoogleDriveImageController::class, 'show'])
    ->where('path', '.*') // Regex agar path yang mengandung '/' tetap terbaca
    ->name('drive.image')
    ->middleware('auth'); // Sesuaikan middleware keamanan Anda

// Route khusus untuk Demo Reviewer Duitku
Route::get('/demo/invoice-simulation', function () {
    return view('pages.invoice-demo');
})->name('demo.invoice');
