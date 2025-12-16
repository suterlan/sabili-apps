# Sabili

Sabili adalah aplikasi web berbasis Laravel yang dikembangkan untuk kebutuhan manajemen data anggota, pendamping, dan koordinator. README ini berisi panduan singkat untuk menyiapkan, menjalankan, dan berkontribusi pada proyek.

## Fitur utama

-   Autentikasi dan otorisasi (login, register, roles)
-   Manajemen pengguna dan data master
-   Upload berkas (dokumen / gambar)
-   Pencarian dan filter data
-   Export laporan (CSV/PDF)

## Prasyarat

-   PHP 8.x
-   Composer
-   SQLite / MySQL / MariaDB (atau DB lain yang didukung Laravel)
-   Node.js & npm (opsional untuk asset)
-   Laragon / XAMPP / Valet (opsional)

## Instalasi (lokal)

1. Clone repositori:
   git clone <https://github.com/suterlan/sabili-apps> sabili
2. Masuk ke direktori:
   cd sabili
3. Install dependensi PHP:
   composer install
4. Salin file environment dan konfigurasi:
   cp .env.example .env
    - Atur koneksi database dan pengaturan lain pada `.env`
5. Generate app key:
   php artisan key:generate
6. Jalankan migrasi:
   php artisan migrate

Jika menggunakan Laragon, pastikan virtual host dan folder proyek sudah diatur.

## Konfigurasi penting (.env)

-   APP_URL=http://localhost
-   DB_CONNECTION=mysql
-   DB_HOST=127.0.0.1
-   DB_PORT=3306
-   DB_DATABASE=sabili_db
-   DB_USERNAME=root
-   DB_PASSWORD=

## Konfig tambahan untuk menggunakan penyimpanan google drive, tambahkan ke .env

-   GOOGLE_DRIVE_CLIENT_ID=isi_dengan_client_ID
-   GOOGLE_DRIVE_CLIENT_SECRET=isi_dengan_client_secret
-   GOOGLE_DRIVE_REFRESH_TOKEN=isi_dengan_client_refresh_token
-   GOOGLE_DRIVE_FOLDER_NAME="isi_dengan_nama_folder_di_gdrive"

Tambahkan konfigurasi mail, storage, dan layanan pihak ketiga sesuai kebutuhan.

## Menjalankan aplikasi

-   Local server Laravel:
    php artisan serve
-   Akses di browser: http://localhost:8000 (atau sesuai APP_URL)

## Testing

-   Jalankan test:
    php artisan test

## Migrasi & Seeders

-   Migrasi:
    php artisan migrate
-   Rollback:
    php artisan migrate:rollback
-   Jalankan seeder spesifik:
    php artisan db:seed --class=NamaSeeder

## Deploy singkat

-   Pastikan env terkonfigurasi untuk production
-   Jalankan composer install --no-dev --optimize-autoloader
-   Jalankan php artisan migrate --force
-   Jalankan php artisan config:cache dan php artisan route:cache
-   Siapkan storage link:
    php artisan storage:link

## Kontribusi

-   Buka issue untuk diskusi fitur/bug
-   Buat branch feature/bugfix dari main
-   Kirim pull request yang jelas (deskripsi & langkah reproduksi)
-   Ikuti standar coding project dan tes sebelum PR

## Lisensi

MIT — lihat file LICENSE untuk detail.
