<?php

namespace App\Providers;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;
use Filament\Tables\Table;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Konfigurasi Global untuk Semua Tabel
        Table::configureUsing(function (Table $table): void {
            $table
                ->striped() // Membuat baris belang-belang (wajib agar data mudah dibaca)
                ->defaultPaginationPageOption(25); // Set default 25 baris
            // HAPUS baris density() karena tidak valid
        });

        try {
            Storage::extend('google', function ($app, $config) {
                $options = [];

                if (!empty($config['teamDriveId'] ?? null)) {
                    $options['teamDriveId'] = $config['teamDriveId'];
                }

                $client = new Client();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);

                $service = new Drive($client);

                $rootFolderId = $config['folder'] ?? '/';
                $adapter = new GoogleDriveAdapter($service, $rootFolderId, $options);
                $driver = new Filesystem($adapter);

                return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
            });
        } catch (\Exception $e) {
            // Biarkan kosong agar tidak error saat migrasi awal
        }
    }
}
