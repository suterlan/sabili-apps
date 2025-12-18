<?php

use App\Http\Controllers\CetakKartuController;
use App\Http\Controllers\GoogleDriveImageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
