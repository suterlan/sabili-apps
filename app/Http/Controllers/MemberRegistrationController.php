<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

// 1. Import Library Duitku
use Duitku\Config as DuitkuConfig;
use Duitku\Pop as DuitkuPop;

class MemberRegistrationController extends Controller
{
    // Tampilkan Form
    public function showForm()
    {
        return view('auth.member-register');
    }

    // Proses Register & Request Payment
    public function registerAndPay(Request $request)
    {
        // A. Validasi
        // A. Validasi dengan Pesan Bahasa Indonesia
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'required|numeric|min_digits:10', // Contoh validasi digit
            'address'  => 'required|string|max:500',
            'password' => 'required|min:8|confirmed',
        ], [
            // Custom Messages (Terjemahan Error)
            'required'   => ':attribute wajib diisi.',
            'string'     => ':attribute harus berupa teks.',
            'email'      => 'Format :attribute tidak valid.',
            'unique'     => ':attribute sudah terdaftar, gunakan yang lain.',
            'numeric'    => ':attribute harus berupa angka.',
            'min'        => ':attribute minimal berisi :min karakter.',
            'min_digits' => ':attribute minimal :min digit angka.',
            'confirmed'  => 'Konfirmasi :attribute tidak cocok.',
            'max'        => ':attribute maksimal :max karakter.',
        ], [
            // Custom Attributes (Nama Kolom agar tidak "name" tapi "Nama Lengkap")
            'name'     => 'Nama Lengkap',
            'email'    => 'Alamat Email',
            'phone'    => 'Nomor WhatsApp',
            'address'  => 'Alamat',
            'password' => 'Kata Sandi',
        ]);

        // MULAI TRANSAKSI DATABASE
        // Jika ada error di bawah, semua perubahan database akan dibatalkan
        \Illuminate\Support\Facades\DB::beginTransaction();

        try {

            // B. Simpan User (Status Pending)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'password' => Hash::make($request->password),
                'role' => 'pendamping',
                'status' => 'pending', // Default pending
            ]);

            // C. Konfigurasi Duitku Library
            $duitkuConfig = new DuitkuConfig(
                env('DUITKU_API_KEY'),
                env('DUITKU_MERCHANT_CODE')
            );
            $duitkuConfig->setSandboxMode(env('DUITKU_SANDBOX_MODE')); // True/False
            $duitkuConfig->setSanitizedMode(false);
            // TAMBAHKAN INI: Matikan log internal library agar tidak perlu mkdir()
            $duitkuConfig->setDuitkuLogs(false);

            // D. Siapkan Parameter Pembayaran
            $paymentAmount = 50000; // Harga Member
            // Gunakan time() + random string agar order ID benar-benar unik saat retry
            $merchantOrderId = 'REG-' . time() . '-' . $user->id;

            $params = [
                'paymentAmount' => $paymentAmount,
                'merchantOrderId' => $merchantOrderId,
                'productDetails' => 'Pendaftaran Keanggotaan',
                'additionalParam' => '',
                'merchantUserInfo' => '',
                'customerVaName' => $user->name,
                'email' => $user->email,
                'phoneNumber' => $user->phone,
                // Detail Item (Opsional tapi bagus untuk invoice)
                'itemDetails' => [
                    [
                        'name' => 'Membership Fee',
                        'price' => $paymentAmount,
                        'quantity' => 1
                    ]
                ],
                'customerDetail' => [
                    'firstName' => $user->name,
                    'email' => $user->email,
                    'phoneNumber' => $user->phone,
                    'address' => $user->address,
                ],
                'callbackUrl' => env('DUITKU_CALLBACK_URL'),
                'returnUrl' => env('DUITKU_RETURN_URL'),
                'expiryPeriod' => 60 // Expire dalam 60 menit
            ];

            // E. Request ke Duitku
            // Library otomatis handle Signature & HTTP Request
            $response = DuitkuPop::createInvoice($params, $duitkuConfig);
            $result = json_decode($response, true);

            // F. Redirect User
            if (isset($result['paymentUrl'])) {
                // SUKSES: Commit database (Simpan user permanen)
                \Illuminate\Support\Facades\DB::commit();

                return redirect($result['paymentUrl']);
            } else {
                // GAGAL DAPAT URL: Rollback database (Batalkan pembuatan user)
                \Illuminate\Support\Facades\DB::rollBack();

                // Jika gagal dapat URL
                Log::error('Duitku Error: ' . json_encode($result));

                // [UPDATE] Ambil pesan error spesifik dari Duitku
                $pesanError = 'Gagal membuat pembayaran.';
                if (isset($result['statusMessage'])) {
                    $pesanError .= ' Pihak pembayaran merespon: "' . $result['statusMessage'] . '"';
                } elseif (isset($result['message'])) {
                    $pesanError .= ' Keterangan: ' . $result['message'];
                }

                return back()->withInput()->with('error', $pesanError);
            }
        } catch (\Exception $e) {
            // EXCEPTION/CRASH (SYSTEM ERROR)
            \Illuminate\Support\Facades\DB::rollBack();

            Log::error('Duitku Exception: ' . $e->getMessage());

            // [UPDATE] Pesan user-friendly, jangan tampilkan raw system error ke user
            return back()->withInput()->with('error', 'Terjadi gangguan koneksi ke sistem pembayaran. Silakan coba sesaat lagi.');
        }
    }
}
