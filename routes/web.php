<?php

use App\Http\Controllers\CetakKartuController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route Cetak Kartu (Wajib Login)
Route::middleware('auth')->group(function () {
    Route::get('/cetak-kartu/{id}', [CetakKartuController::class, 'download'])->name('cetak.kartu');
});
