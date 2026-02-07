<?php

namespace App\Services;

use App\Models\Tagihan;
use Duitku\Config as DuitkuConfig;
use Duitku\Pop as DuitkuPop;
use Illuminate\Support\Facades\Log;

class DuitkuInvoiceService
{
  public static function generatePaymentUrl(Tagihan $record)
  {
    try {
      // Hitung Grand Total (Nominal Satuan * Jumlah Pengajuan)
      $grandTotal = $record->total_nominal * $record->pengajuans()->count();
      $user = $record->pendamping; // Berdasarkan skema baru Anda

      $duitkuConfig = new DuitkuConfig(
        env('DUITKU_API_KEY'),
        env('DUITKU_MERCHANT_CODE')
      );
      $duitkuConfig->setSandboxMode(env('DUITKU_SANDBOX_MODE'));
      $duitkuConfig->setDuitkuLogs(false);

      $params = [
        'paymentAmount'   => (int) $grandTotal,
        'merchantOrderId' => $record->nomor_invoice . '-' . time(),
        'productDetails'  => 'Tagihan Invoice ' . $record->nomor_invoice,
        'customerVaName'  => $user->name,
        'email'           => $user->email,
        'phoneNumber'     => $user->phone ?? '08123456789',
        'callbackUrl'     => env('DUITKU_CALLBACK_URL'),
        'returnUrl'       => env('DUITKU_RETURN_URL'),
        'expiryPeriod'    => 60
      ];

      $response = DuitkuPop::createInvoice($params, $duitkuConfig);
      $result = json_decode($response, true);

      if (isset($result['paymentUrl'])) {
        // Simpan link ke database agar permanen
        $record->update(['link_pembayaran' => $result['paymentUrl']]);
        return $result['paymentUrl'];
      }

      throw new \Exception($result['statusMessage'] ?? 'Gagal mendapatkan respon dari Duitku');
    } catch (\Exception $e) {
      Log::error('Duitku Service Error: ' . $e->getMessage());
      throw $e;
    }
  }
}
