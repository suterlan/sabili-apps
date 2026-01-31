<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentCallbackController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Route ini yang akan ditembak oleh Duitku
Route::post('/payment/callback', [PaymentCallbackController::class, 'handle']);
