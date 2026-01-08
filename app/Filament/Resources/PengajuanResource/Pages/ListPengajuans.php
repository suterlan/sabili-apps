<?php

namespace App\Filament\Resources\PengajuanResource\Pages;

use App\Exports\TemplateInvoiceExport;
use App\Filament\Resources\PengajuanResource;
use App\Imports\ImportTagihan;
use App\Models\Pengajuan;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListPengajuans extends ListRecords
{
    protected static string $resource = PengajuanResource::class;

    public function getTabs(): array
    {
        $user = auth()->user();
        $tabs = []; // Inisialisasi array kosong

        // Helper: Cek apakah user adalah Super Admin
        // (Sesuaikan method ini dengan logic role di aplikasi Anda)
        $hasGlobalAccess = $user->isSuperAdmin() || $user->isManajemen();

        // -------------------------------------------------------
        // LOGIC QUERY WILAYAH
        // -------------------------------------------------------
        // Fungsi helper untuk filter wilayah berdasarkan User Pelaku Usaha
        // Pengajuan -> User (Pelaku Usaha) -> district_code
        $applyRegionFilter = function (Builder $query) use ($user, $hasGlobalAccess) {
            if (!$hasGlobalAccess && $user->hasAssignedDistricts()) {
                $query->whereHas('user', function (Builder $q) use ($user) {
                    $q->whereIn('kecamatan', $user->assigned_districts);
                });
            }

            return $query;
        };

        // -------------------------------------------------------
        // HELPER FILTER VERIFIKATOR
        // (Agar kode tidak berulang-ulang di setiap tab)
        // -------------------------------------------------------
        $filterByVerificator = function (Builder $query) use ($user, $hasGlobalAccess) {
            // 1. SYARAT UTAMA: Data harus SUDAH DI-KLAIM (Punya Verifikator)
            // Ini mencegah data "Antrian" bocor ke tab Proses/Revisi/Lainnya
            $query->whereNotNull('verificator_id');

            // 2. FILTER KEPEMILIKAN
            // Jika Super Admin: Bebas lihat punya siapa saja (asal sudah diklaim)
            // Jika Verifikator: Hanya lihat punya sendiri
            if (! $hasGlobalAccess) {
                $query->where('verificator_id', $user->id);
            }
            return $query;
        };

        // -------------------------------------------------------
        // TAB 1: ANTRIAN (Semua User Bisa Lihat Antrian Masuk)
        // -------------------------------------------------------
        // Hitung badge antrian (termasuk filter wilayah)
        $badgeAntrian = Pengajuan::whereNull('verificator_id');
        // Terapkan filter wilayah untuk badge jika bukan superadmin
        $applyRegionFilter($badgeAntrian);

        $tabs['antrian'] = Tab::make('Antrian')
            ->icon('heroicon-m-inbox-stack')
            ->badge($badgeAntrian->count()) // Badge tetap menghitung TOTAL antrian (misal: 50)
            ->modifyQueryUsing(function (Builder $query) use ($applyRegionFilter, $hasGlobalAccess) {
                // 1. Tentukan Limit Berdasarkan Role
                // Jika Super Admin = 5, Jika Verifikator biasa = 1
                $limitData = $hasGlobalAccess ? 5 : 1;

                // 1. Buat Query Terpisah untuk mencari ID (Clone logic filter)
                // Kita tidak bisa pakai $query langsung karena akan konflik dengan query utama tabel
                $idsQuery = Pengajuan::query()->whereNull('verificator_id');

                // 2. Terapkan Filter Wilayah ke Query pencari ID
                $applyRegionFilter($idsQuery);

                // 3. Ambil 5 ID teratas (FIFO - Terlama duluan)
                $top5Ids = $idsQuery->orderBy('created_at', 'asc')
                    ->limit($limitData)
                    ->pluck('id') // Ambil Array ID-nya saja
                    ->toArray();

                // 4. Terapkan ke Query Utama Filament
                if (! empty($top5Ids)) {
                    // Paksa tabel hanya merender data dengan ID tersebut
                    return $query->whereIn('id', $top5Ids)
                        ->orderBy('created_at', 'asc');
                }

                // Fallback jika data kosong (mencegah error)
                // Tetap filter null dan wilayah agar konsisten
                $query->whereNull('verificator_id');
                return $applyRegionFilter($query);
            });

        // Tab Perlu Revisi (Merah)
        $tabs['revisi'] = Tab::make('Perlu Revisi')
            ->icon('heroicon-m-exclamation-triangle')
            ->badgeColor('danger')
            ->badge(function () use ($filterByVerificator) {
                return $filterByVerificator(Pengajuan::whereIn('status_verifikasi', Pengajuan::getStatRevisi()))->count();
            })
            ->modifyQueryUsing(function (Builder $query) use ($filterByVerificator) {
                $query->whereIn('status_verifikasi', Pengajuan::getStatRevisi());
                return $filterByVerificator($query);
            });

        // Tab Sedang Diproses (Kuning/Biru)
        $tabs['proses'] = Tab::make('Diproses')
            ->icon('heroicon-m-arrow-path')
            ->badgeColor('warning')
            ->badge(function () use ($filterByVerificator) {
                return $filterByVerificator(Pengajuan::whereIn('status_verifikasi', Pengajuan::getStatProses()))->count();
            })
            ->modifyQueryUsing(function (Builder $query) use ($filterByVerificator) {
                $query->whereIn('status_verifikasi', Pengajuan::getStatProses());
                return $filterByVerificator($query);
            });

        // Tab Pengajuan Dikirim
        $tabs['dikirim'] = Tab::make('Dikirim')
            ->icon('heroicon-m-paper-airplane')
            ->badgeColor('primary')
            ->badge(function () use ($filterByVerificator) {
                // Hitung badge dengan helper filter
                return $filterByVerificator(Pengajuan::whereIn('status_verifikasi', Pengajuan::getStatDikirim()))->count();
            })
            ->modifyQueryUsing(function (Builder $query) use ($filterByVerificator) {
                $query->whereIn('status_verifikasi', Pengajuan::getStatDikirim());
                return $filterByVerificator($query); // Apply filter verifikator jika perlu
            });

        // Tab Siap Invoice = status sertifikat terbit (Hijau) -> Trigger Tombol Import
        $tabs['siap_invoice'] = Tab::make('Siap Invoice')
            ->icon('heroicon-m-banknotes')
            ->badgeColor('info')
            ->badge(function () use ($filterByVerificator) {
                return $filterByVerificator(Pengajuan::whereIn('status_verifikasi', Pengajuan::getStatSiapInvoice()))->count();
            })
            ->modifyQueryUsing(function (Builder $query) use ($filterByVerificator) {
                $query->whereIn('status_verifikasi', Pengajuan::getStatSiapInvoice());
                return $filterByVerificator($query);
            });

        // TAB SELESAI = status invoice diterbitkan dan status selesai
        $tabs['invoice'] = Tab::make('Invoice')
            ->icon('heroicon-m-document-currency-dollar')
            ->badgeColor('success')
            ->badge(function () use ($filterByVerificator) {
                return $filterByVerificator(Pengajuan::whereIn('status_verifikasi', Pengajuan::getStatInvoice()))->count();
            })
            ->modifyQueryUsing(function (Builder $query) use ($filterByVerificator) {
                $query->whereIn('status_verifikasi', Pengajuan::getStatInvoice())->latest();
                return $filterByVerificator($query);
            });

        // TAB SELESAI = status invoice diterbitkan dan status selesai
        $tabs['selesai'] = Tab::make('Selesai')
            ->icon('heroicon-m-check-badge')
            ->badgeColor('success')
            ->badge(function () use ($filterByVerificator) {
                return $filterByVerificator(Pengajuan::whereIn('status_verifikasi', Pengajuan::getStatSelesai()))->count();
            })
            ->modifyQueryUsing(function (Builder $query) use ($filterByVerificator) {
                $query->whereIn('status_verifikasi', Pengajuan::getStatSelesai())->latest();
                return $filterByVerificator($query);
            });

        // TAB RIWAYAT (History Kerja)
        $tabs['semua'] = Tab::make('Riwayat')
            ->icon('heroicon-m-clock') // Tambah ikon jam/history
            ->badgeColor('gray')
            ->badge(function () use ($filterByVerificator) {
                // Badge hanya menghitung total data yg relevan
                return $filterByVerificator(Pengajuan::query())->count();
            })
            ->modifyQueryUsing(function (Builder $query) use ($filterByVerificator) {
                // Apply filter: Superadmin lihat semua, Verif lihat punya sendiri
                return $filterByVerificator($query)->latest();
            });

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                // 1. Download Template
                Action::make('download_siap_invoice')
                    ->label('Download Template Invoice')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(fn() => Excel::download(new TemplateInvoiceExport, 'Data_Siap_Invoice_' . date('Y-m-d') . '.xlsx')),

                // 2. Import Data Balik
                Action::make('import_invoice_balik')
                    ->label('Upload Invoice')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('attachment')
                            ->label('File Excel')
                            ->disk('local')
                            ->directory('temp-invoice')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $path = Storage::disk('local')->path($data['attachment']);

                        // Validasi keberadaan file
                        if (! file_exists($path)) {
                            Notification::make()->title('File tidak ditemukan')->danger()->send();

                            return;
                        }

                        try {
                            $import = new ImportTagihan;
                            Excel::import($import, $path);
                            $stats = $import->getStats();

                            // Bersihkan file temp agar server tidak penuh
                            @unlink($path);

                            Notification::make()
                                ->title('Import Berhasil')
                                ->body("Invoice Baru: <b>{$stats['invoice_baru']}</b><br>Update Pengajuan: <b>{$stats['sukses']}</b> <br>Gagal: <b>{$stats['gagal']}</b>")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Import')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
                ->label('Menu Import')
                ->icon('heroicon-m-currency-dollar')
                ->color('warning')
                ->button()
                // VISIBILITY CHECK YANG LEBIH AMAN
                ->visible(function () {
                    // Mengembalikan true HANYA jika tab yang aktif adalah 'siap_invoice' dan hanya superadmin
                    $isInoviceTab = $this->activeTab === 'siap_invoice';
                    $superAdmin = auth()->user()->isSuperAdmin();

                    return $isInoviceTab && $superAdmin;
                }),

            \Filament\Actions\CreateAction::make(),
        ];
    }
}
