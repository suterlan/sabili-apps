<?php

namespace App\Imports;

use App\Models\Pengajuan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportTagihan implements ToModel, WithHeadingRow
{
    private $sukses = 0;

    private $gagal = 0;

    private $invoiceBaru = 0;

    private $invoiceLama = 0;

    public function headingRow(): int
    {
        return 4; // Karena header ada di baris ke-4 (lihat template export di atas)
    }

    public function model(array $row)
    {

        // 1. AMBIL & BERSIHKAN DATA
        $nik = isset($row['nik_jangan_ubah']) ? trim($row['nik_jangan_ubah']) : null;
        $noInvoice = isset($row['nomor_invoice_auto']) ? trim($row['nomor_invoice_auto']) : null;
        $linkBayar = isset($row['link_pembayaran_opsional']) ? trim($row['link_pembayaran_opsional']) : null;

        // Bersihkan Nominal (Hapus Rp, Titik, Koma jika ada, agar jadi integer murni)
        $rawNominal = isset($row['total_nominal_wajib_isi']) ? $row['total_nominal_wajib_isi'] : 0;
        $nominal = (float) preg_replace('/[^0-9]/', '', (string) $rawNominal);

        // Validasi Wajib
        if (empty($nik) || empty($noInvoice)) {
            $this->gagal++;

            return null; // Skip baris kosong/rusak
        }

        // 2. Cari USER berdasarkan NIK
        $user = User::where('nik', $nik)->first();
        if (! $user) {
            $this->gagal++; // User tidak ditemukan

            return null;
        }

        // 3. CARI PENGAJUAN YANG VALID
        // Syarat: Milik User ini AND Statusnya 'Sertifikat Diterbitkan'
        // Kita kunci statusnya biar tidak salah update pengajuan yang masih baru/sudah lunas
        $pengajuan = Pengajuan::where('user_id', $user->id)
            ->where('status_verifikasi', Pengajuan::STATUS_SERTIFIKAT)
            ->latest()
            ->first();

        if (! $pengajuan) {
            // Coba cari yang statusnya sudah Invoice (mungkin admin upload ulang untuk update link bayar)
            $pengajuan = Pengajuan::where('user_id', $user->id)
                ->where('status_verifikasi', Pengajuan::STATUS_INVOICE)
                ->latest()
                ->first();
        }

        if (! $pengajuan) {
            $this->gagal++; // Tidak ada pengajuan yang siap ditagih

            return null;
        }

        // ==================================================================
        // SECURITY CHECK (PENTING!)
        // Pastikan Pengajuan ini milik Verifikator yang sedang login kecuali superadmin yang melakukan
        // ==================================================================
        $isSuperAdmin = auth()->user()->isSuperadmin(); // Cek apakah dia bos besar
        $isMyData = $pengajuan->verificator_id === auth()->id(); // Cek apakah data milik sendiri

        // Jika BUKAN Superadmin DAN BUKAN Data Sendiri, maka tolak.
        if (! $isSuperAdmin && ! $isMyData) {
            $this->gagal++;
            return null; // Skip, Admin A tidak boleh sentuh data Admin B
        }

        // ------------------------------------------------------------------
        // 2. PROSES DATABASE (Bungkus dengan Transaction)
        // ------------------------------------------------------------------
        try {
            DB::transaction(function () use ($pengajuan, $noInvoice, $nominal, $linkBayar) {

                // 4. LOGIKA GROUPING INVOICE (CORE)
                // firstOrCreate akan mengecek: Apakah 'nomor_invoice' ini sudah ada di DB?
                // Jika ADA (Grouping): Dia tidak buat baru, tapi pakai ID yang sudah ada.
                // Jika TIDAK ADA: Dia buat Tagihan baru.

                // Cek dulu apakah invoice ini sudah ada (Untuk logic Counter)
                $existingTagihan = Tagihan::where('nomor_invoice', $noInvoice)->first();

                $tagihan = Tagihan::firstOrCreate(
                    ['nomor_invoice' => $noInvoice], // Kunci Pencarian (Unik)
                    [
                        // Data yang diisi HANYA jika Invoice Baru dibuat
                        'pendamping_id' => $pengajuan->pendamping_id, // Ambil pendamping dari pengajuan pertama
                        'total_nominal' => $nominal,
                        'link_pembayaran' => $linkBayar,
                        'tanggal_terbit' => now(),
                        'status_pembayaran' => 'BELUM DIBAYAR',
                    ]
                );

                // Update Counter & Update Data Lama (Jika perlu)
                if ($tagihan->wasRecentlyCreated) {
                    $this->invoiceBaru++;
                } else {
                    $this->invoiceLama++;

                    // Logic Update: Jika invoice sudah ada, apakah mau update Link/Nominal?
                    // Disini saya set update jika ada data baru di excel
                    $updateData = [];
                    if (! empty($linkBayar)) {
                        $updateData['link_pembayaran'] = $linkBayar;
                    }
                    if ($nominal > 0) {
                        $updateData['total_nominal'] = $nominal;
                    }

                    if (! empty($updateData)) {
                        $tagihan->update($updateData);
                    }
                }

                // B. LOGIKA PENGAJUAN (Update Relasi)
                // -----------------------------------
                // Pastikan tabel pengajuans punya kolom 'tagihan_id'
                // Jika tidak punya, hapus baris 'tagihan_id' dibawah ini.
                $pengajuan->update([
                    'tagihan_id' => $tagihan->id,
                    'status_verifikasi' => Pengajuan::STATUS_INVOICE,
                ]);
            }); // End Transaction

            // Jika sampai sini berarti sukses commit
            $this->sukses++;
        } catch (\Exception $e) {
            // Jika ada error sql/logic di dalam transaction, dia akan lari kesini
            // Dan database otomatis ROLLBACK (batal simpan)
            $this->gagal++;

            // Opsional: Log error untuk debugging developer
            // \Illuminate\Support\Facades\Log::error("Import Error Row: " . $e->getMessage());

            return null;
        }

        return null;
    }

    // Untuk menampilkan hasil di Notifikasi Filament
    public function getStats()
    {
        return [
            'sukses' => $this->sukses,
            'gagal' => $this->gagal,
            'invoice_baru' => $this->invoiceBaru,
            'invoice_grup' => $this->invoiceLama, // Menandakan ada data yang masuk ke grup invoice yg sama
        ];
    }
}
