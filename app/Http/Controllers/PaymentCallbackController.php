<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function handle(Request $request)
    {
        // Ambil data dari Duitku
        $merchantCode = $request->input('merchantCode');
        $amount = $request->input('amount');
        $merchantOrderId = $request->input('merchantOrderId');
        $signature = $request->input('signature');
        $resultCode = $request->input('resultCode'); // 00 = Sukses

        // 1. Validasi Signature Manual (Wajib demi keamanan)
        // Rumus: MD5(merchantCode + amount + merchantOrderId + apiKey)
        $apiKey = env('DUITKU_API_KEY');
        $calcSignature = md5($merchantCode . $amount . $merchantOrderId . $apiKey);

        if ($signature !== $calcSignature) {
            return response()->json(['status' => 'Bad Signature'], 400);
        }

        // 2. Cari User Berdasarkan Order ID
        // Format Order ID kita tadi: REG-{TIME}-{USER_ID}
        $parts = explode('-', $merchantOrderId);
        $userId = end($parts); // Ambil angka terakhir

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['status' => 'User Not Found'], 404);
        }

        // 3. Cek Status Pembayaran
        if ($resultCode == '00') {
            // SUKSES BAYAR -> Update Status jadi 'verified'
            $user->update(['status' => 'verified']);
            Log::info("User ID {$userId} telah melakukan pembayaran. Akun Aktif.");
        } else {
            Log::warning("Pembayaran gagal/pending untuk User ID {$userId}");
        }

        return response()->json(['status' => 'OK']);
    }

    // Halaman Terima Kasih (Return URL)
    public function finish()
    {
        return view('auth.payment-finish');
    }
}
