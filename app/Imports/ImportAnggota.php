<?php

namespace App\Imports;

use App\Models\Pengajuan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
// Panggil Model Laravolt
use Laravolt\Indonesia\Models\Village;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportAnggota implements ToModel, WithHeadingRow
{
    // 1. Tambahkan Properti Counter
    private $usersCount = 0;

    private $pengajuanCount = 0;

    /**
     * Tentukan baris mana yang berisi Header Kolom.
     * Kita set ke 4 karena baris 1-3 berisi Instruksi/Panduan.
     */
    public function headingRow(): int
    {
        return 4;
    }

    public function model(array $row)
    {
        // ---------------------------------------------------------
        // 1. MAPPING HEADER KE KEY (SLUG)
        // Laravel Excel mengubah "Provinsi (Teks: Jawa Barat)"
        // menjadi "provinsi_teks_jawa_barat" (lowercase + underscore)
        // ---------------------------------------------------------

        $email = $row['email'] ?? null;
        $idPendamping = $row['id_pendamping_lihat_sheet_2'] ?? null;

        // Validasi Dasar: Skip jika data krusial kosong
        if (empty($email) || empty($idPendamping)) {
            return null;
        }

        // Cek Duplikasi Email (Agar tidak error SQL)
        if (User::where('email', $email)->exists()) {
            return null;
        }

        // ---------------------------------------------------------
        // 2. PARSING TANGGAL LAHIR (EXCEL DATE vs STRING)
        // Header: "Tanggal Lahir (YYYY-MM-DD)" -> slug: "tanggal_lahir_yyyy_mm_dd"
        // ---------------------------------------------------------
        $tglLahirRaw = $row['tanggal_lahir_yyyy_mm_dd'] ?? null;
        $tglLahir = null;

        if (! empty($tglLahirRaw)) {
            try {
                if (is_numeric($tglLahirRaw)) {
                    // Jika format serial number Excel (contoh: 44560)
                    $tglLahir = Date::excelToDateTimeObject($tglLahirRaw);
                } else {
                    // Jika format text (contoh: "1990-01-01")
                    $tglLahir = Carbon::parse($tglLahirRaw);
                }
            } catch (\Exception $e) {
                $tglLahir = null; // Jika format kacau, biarkan null
            }
        }

        // ---------------------------------------------------------
        // 3. LOGIKA PENCARIAN KODE WILAYAH BERDASARKAN NAMA
        // Hirarki: Provinsi -> Kabupaten -> Kecamatan -> Desa
        // ---------------------------------------------------------

        $provCode = null;
        $cityCode = null;
        $distCode = null;
        $villCode = null;

        // Ambil input teks dari Excel & bersihkan spasi
        $inputProv = trim($row['provinsi'] ?? '');
        $inputKab = trim($row['kabupaten'] ?? '');
        $inputKec = trim($row['kecamatan'] ?? '');
        $inputDesa = trim($row['desa'] ?? '');

        // A. Cari Provinsi
        if (! empty($inputProv)) {
            $prov = Province::where('name', 'LIKE', '%'.$inputProv.'%')->first();
            if ($prov) {
                $provCode = $prov->code;

                // B. Cari Kabupaten (Hanya cari di dalam provinsi yg ditemukan)
                if (! empty($inputKab)) {
                    $city = City::where('province_code', $provCode)
                        ->where('name', 'LIKE', '%'.$inputKab.'%')
                        ->first();

                    if ($city) {
                        $cityCode = $city->code;

                        // C. Cari Kecamatan (Hanya cari di dalam kabupaten yg ditemukan)
                        if (! empty($inputKec)) {
                            $dist = District::where('city_code', $cityCode)
                                ->where('name', 'LIKE', '%'.$inputKec.'%')
                                ->first();

                            if ($dist) {
                                $distCode = $dist->code;

                                // D. Cari Desa (Hanya cari di dalam kecamatan yg ditemukan)
                                if (! empty($inputDesa)) {
                                    $vill = Village::where('district_code', $distCode)
                                        ->where('name', 'LIKE', '%'.$inputDesa.'%')
                                        ->first();

                                    if ($vill) {
                                        $villCode = $vill->code;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // ---------------------------------------------------------
        // 4. SIMPAN USER BARU
        // ---------------------------------------------------------
        $user = new User([
            // Identitas
            'name' => $row['nama_lengkap'],
            'email' => $email,
            'password' => Hash::make($row['password_optional'] ?? '12345678'),
            'phone' => $row['no_hp'],
            'nik' => $row['nik'],
            'tanggal_lahir' => $tglLahir,

            // Data Usaha
            'nomor_nib' => $row['nib'],
            'merk_dagang' => $row['merk_dagang'],
            'address' => $row['alamat_lengkap'],

            // Relasi Pendamping
            'pendamping_id' => $idPendamping,

            // Wilayah (Hasil Konversi Nama ke Kode)
            'provinsi' => $provCode,
            'kabupaten' => $cityCode,
            'kecamatan' => $distCode,
            'desa' => $villCode,

            // Data Halal
            'akun_halal' => $row['akun_sihalal'] ?? null,
            'pass_akun_halal' => $row['pass_sihalal'] ?? null,
            'mitra_halal' => $row['mitra_halal'] ?? 'SABILI',

            // System Defaults
            'role' => 'member',
            'status' => 'verified',
        ]);

        $user->save(); // Save agar User ID ter-generate
        $this->usersCount++; // <--- 2. Increment Counter User

        // -----------------------------------------------------
        // 5. CEK APAKAH PERLU MEMBUAT PENGAJUAN?
        // Header Excel: "Ajukan? (1=Ya, 0=Tidak)" -> slug: ajukan_1ya_0tidak
        // -----------------------------------------------------

        $isAjukan = isset($row['ajukan_1ya_0tidak']) && $row['ajukan_1ya_0tidak'] == 1;

        if ($isAjukan) {
            // Ambil ID Verifikator dari Excel
            // Header: "ID Admin Verifikator (Lihat Sheet 3)" -> slug: id_admin_verifikator_lihat_sheet_3
            $verifikatorId = $row['id_admin_verifikator_lihat_sheet_3'] ?? null;

            // Buat Pengajuan
            Pengajuan::create([
                'user_id' => $user->id,        // ID User yang baru dibuat
                'pendamping_id' => $idPendamping,    // Pendamping dari User
                'verificator_id' => $verifikatorId,   // Admin yang ditugaskan
                'status_verifikasi' => Pengajuan::STATUS_DIPROSES, // Default Status

                // Field tanggal opsional (biar tercatat kapan dibuat)
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->pengajuanCount++; // <--- 3. Increment Counter Pengajuan
        }

        // Return null karena kita sudah melakukan save manual di atas ($user->save()).
        // Kalau kita return $user, Excel akan mencoba save lagi (double insert/error).
        return null;
    }

    // 4. Tambahkan Getter untuk mengambil nilai counter
    public function getUsersCount(): int
    {
        return $this->usersCount;
    }

    public function getPengajuanCount(): int
    {
        return $this->pengajuanCount;
    }
}
