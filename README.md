# Sabili - Sistem Manajemen Sertifikasi Halal

Sabili adalah aplikasi web berbasis **Laravel** dan **Filament v3** yang dirancang untuk mengelola alur proses sertifikasi halal mandiri. Aplikasi ini memfasilitasi kolaborasi antara Pelaku Usaha, Pendamping, Koordinator, dan Admin Verifikator dalam satu platform terpusat, mulai dari pengajuan data hingga penerbitan invoice.

## Fitur Utama

-   **Manajemen Pengguna Berbasis Peran**: Kontrol akses dinamis untuk Super Admin, Admin (Verifikator), Koordinator, dan Pendamping.
-   **Dashboard Monitoring**: Statistik dan ringkasan data yang disesuaikan untuk setiap peran, memberikan gambaran cepat tentang progres, antrian, dan tugas.
-   **Alur Kerja Pengajuan**: Proses pengajuan sertifikasi dari Pelaku Usaha, verifikasi oleh Admin, hingga status sertifikat terbit.
-   **Manajemen Binaan**: Pendamping dapat memantau dan mengelola data serta progres pengajuan dari Pelaku Usaha yang menjadi binaannya.
-   **Sistem Penagihan (Invoice)**: Pembuatan dan pelacakan tagihan (invoice) untuk pengajuan yang telah selesai, lengkap dengan link pembayaran dan rekapitulasi.
-   **Integrasi Google Drive**: Penyimpanan dokumen dan berkas (KTP, Ijazah, Foto, dll.) secara aman dan terstruktur di Google Drive.
-   **Ekspor & Impor Data**: Kemampuan untuk mengekspor laporan data ke format Excel dan mengimpor data untuk pembuatan invoice massal.
-   **Filter & Pencarian Lanjutan**: Fitur pencarian dan filter yang kuat di seluruh tabel data untuk memudahkan pengelolaan.

## Tumpukan Teknologi (Tech Stack)

-   **Backend**: PHP 8.3, Laravel 12
-   **Admin Panel**: Filament 3, Livewire 3
-   **Database**: MySQL / MariaDB
-   **Penyimpanan File**: Google Drive API

## Prasyarat

-   PHP 8.2+
-   Composer
-   MySQL / MariaDB
-   Node.js & NPM
-   Akun Google Cloud Platform untuk kredensial Google Drive API

## Instalasi Lokal

1.  **Clone repositori:**
    ```bash
    git clone <https://github.com/suterlan/sabili-apps> sabili
    ```
2.  **Masuk ke direktori proyek:**
    ```bash
    cd sabili
    ```
3.  **Install dependensi PHP:**
    ```bash
    composer install
    ```
4.  **Install dependensi JavaScript:**
    ```bash
    npm install
    ```
5.  **Salin file environment:**
    ```bash
    cp .env.example .env
    ```
6.  **Generate kunci aplikasi:**
    ```bash
    php artisan key:generate
    ```
7.  **Konfigurasi file `.env`:**
    -   Atur koneksi database (`DB_*`).
    -   Isi kredensial Google Drive (`GOOGLE_DRIVE_*`).
    -   Atur konfigurasi email untuk notifikasi (`MAIL_*`).
8.  **Jalankan migrasi dan seeder database:**
    ```bash
    php artisan migrate --seed
    ```
9.  **Buat symbolic link untuk storage:**
    ```bash
    php artisan storage:link
    ```
10. **Compile aset frontend:**
    ```bash
    npm run build
    ```

## Konfigurasi Penting (.env)

```dotenv
# Konfigurasi Aplikasi
APP_NAME=Sabili
APP_URL=http://sabili.test

# Koneksi Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sabili_db
DB_USERNAME=root
DB_PASSWORD=

# Konfigurasi Google Drive untuk Penyimpanan File
FILESYSTEM_DISK=google
GOOGLE_DRIVE_CLIENT_ID=isi_dengan_client_ID_anda
GOOGLE_DRIVE_CLIENT_SECRET=isi_dengan_client_secret_anda
GOOGLE_DRIVE_REFRESH_TOKEN=isi_dengan_refresh_token_anda
GOOGLE_DRIVE_FOLDER_ID=isi_dengan_ID_folder_di_gdrive
```

## Menjalankan Aplikasi

-   **Untuk development (dengan hot-reload):**
    ```bash
    # Di terminal 1
    npm run dev
    # Di terminal 2
    php artisan serve
    ```
-   Akses aplikasi di browser sesuai `APP_URL` Anda (misal: `http://localhost:8000`).

## Peran Pengguna (User Roles)

-   **Super Admin**: Memiliki akses penuh ke seluruh sistem, termasuk manajemen semua pengguna dan konfigurasi.
-   **Admin (Verifikator)**: Bertugas memverifikasi pengajuan yang masuk, mengubah status, dan menerbitkan invoice.
-   **Koordinator**: Memonitor kinerja para Pendamping di wilayah (kecamatan) yang menjadi tanggung jawabnya.
-   **Pendamping**: Mengelola data Pelaku Usaha (binaan), membantu proses pengajuan, dan memantau progres sertifikasi.
-   **Pelaku Usaha (User)**: Pengguna akhir yang melakukan pengajuan sertifikasi dan mengunggah dokumen yang diperlukan.

## Kontribusi

Silakan buka *issue* untuk diskusi fitur/bug atau kirim *pull request* dengan penjelasan yang jelas.

## Lisensi

Proyek ini dilisensikan di bawah Lisensi MIT.
