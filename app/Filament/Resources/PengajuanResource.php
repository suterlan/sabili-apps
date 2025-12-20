<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengajuanResource\Pages;
use App\Filament\Resources\PengajuanResource\RelationManagers;
use App\Models\Pengajuan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\FontWeight; // Untuk styling teks
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class PengajuanResource extends Resource
{
    protected static ?string $model = Pengajuan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Pengajuan';
    protected static ?string $modelLabel = 'Progres Pengajuan';
    protected static ?string $pluralModelLabel = 'Progres Pengajuan';
    protected static ?string $slug = 'pengajuan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Auto refresh setiap 5 detik agar jika ada antrian baru masuk/diklaim orang lain, tabel update
            ->poll('5s')

            // Agar admin tahu ini pengajuan punya siapa
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelaku Usaha')
                    ->searchable(),

                Tables\Columns\TextColumn::make('pendamping.name')
                    ->label('Pendamping')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        Pengajuan::STATUS_MENUNGGU => 'gray',
                        Pengajuan::STATUS_NIK_TERDAFTAR, Pengajuan::STATUS_NIK_INVALID => 'danger',
                        Pengajuan::STATUS_UPLOAD_NIB, Pengajuan::STATUS_UPLOAD_KK => 'warning',
                        Pengajuan::STATUS_DIPROSES, Pengajuan::STATUS_INVOICE => 'info',
                        Pengajuan::STATUS_SERTIFIKAT, Pengajuan::STATUS_SELESAI => 'success',
                        default => 'primary',
                    })
                    ->sortable(),

                // Kolom Verifikator (Siapa yang sedang mengerjakan)
                Tables\Columns\TextColumn::make('verificator.name')
                    ->label('Verifikator')
                    ->placeholder('Belum Diklaim')
                    ->icon('heroicon-m-user'),
            ])
            ->actions([
                // LOGIC KLAIM TUGAS (Sama seperti sebelumnya, tapi sekarang update model Pengajuan)
                Tables\Actions\Action::make('claim_task')
                    ->label('Proses')
                    ->icon('heroicon-m-hand-raised')
                    ->color('primary')
                    ->visible(function (Pengajuan $record, $livewire) {
                        // Syarat 1: Belum ada verifikator
                        $belumDiklaim = is_null($record->verificator_id);

                        // Syarat 2: BUKAN di tab 'semua'
                        // Kita cek apakah $livewire punya properti activeTab, lalu cek isinya
                        $bukanTabHistory = isset($livewire->activeTab) && $livewire->activeTab !== 'semua';

                        return $belumDiklaim && $bukanTabHistory;
                    })

                    ->action(function (Pengajuan $record) {
                        // 1. Update Data
                        $record->update([
                            'verificator_id' => auth()->id(),
                            'status_verifikasi' => Pengajuan::STATUS_DIPROSES,
                        ]);

                        // 2. Kirim Notifikasi
                        Notification::make()
                            ->success() // Warna Hijau
                            ->title('Tugas Berhasil Diklaim')
                            ->body('Pengajuan ini telah masuk ke daftar "Tugas Saya".')
                            ->send();
                    }),

                // LOGIC UPDATE STATUS
                Tables\Actions\Action::make('update_status')
                    ->label('Verifikasi')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->color('warning')
                    ->modalWidth('5xl')
                    ->visible(function (Pengajuan $record, $livewire) {
                        // Syarat 1: User login adalah verifikatornya
                        $isMyTask = auth()->id() === $record->verificator_id;

                        // Syarat 2: BUKAN di tab 'semua'
                        $bukanTabHistory = isset($livewire->activeTab) && $livewire->activeTab !== 'semua';

                        return $isMyTask && $bukanTabHistory;
                    })

                    ->form([
                        // --- BAGIAN 1: TAMPILAN DATA USER (READ ONLY) ---
                        Section::make('Identitas Pelaku Usaha')
                            ->columns(3)
                            ->schema([
                                Placeholder::make('nama')
                                    ->label('Nama Lengkap')
                                    ->content(fn($record) => $record->user->name)
                                    ->extraAttributes(['class' => 'font-bold']), // CSS Bold

                                Placeholder::make('nik')
                                    ->label('NIK')
                                    ->content(fn($record) => $record->user->nik),

                                Placeholder::make('tgl_lahir')
                                    ->label('Tanggal Lahir')
                                    ->content(fn($record) => $record->user->tanggal_lahir), // Bisa tambah format date jika mau

                                Placeholder::make('hp')
                                    ->label('No. Telepon/WA')
                                    ->content(fn($record) => $record->user->phone),

                                Placeholder::make('district.name')
                                    ->label('Kecamatan')
                                    ->content(fn($record) => $record->user->district->name),

                                Placeholder::make('merk')
                                    ->label('Merk Dagang')
                                    ->content(fn($record) => $record->user->merk_dagang)
                                    ->extraAttributes(['class' => 'text-primary-600 font-bold']),

                                Placeholder::make('alamat')
                                    ->label('Alamat Lengkap')
                                    ->content(fn($record) => $record->user->address . ', Kec. ' . $record->user->district->name)
                                    ->columnSpanFull(),
                            ]),

                        // --- BAGIAN 2: GAMBAR (MENGGUNAKAN HTML STRING) ---
                        Section::make('Dokumentasi Foto')
                            ->description('Preview foto dokumentasi usaha.')
                            ->schema([
                                Grid::make(3)->schema([
                                    // FOTO 1: PRODUK
                                    Placeholder::make('img_produk')
                                        ->label('1. Foto Produk')
                                        ->content(fn($record) => new HtmlString('
                                            <div class="border rounded-lg p-2 bg-gray-50 h-72 flex items-center justify-center">
                                                <img src="' . self::getBase64Image($record->user->file_foto_produk) . '" 
                                                class="max-w-full max-h-full object-contain rounded" 
                                                alt="Foto Produk">
                                            </div>
                                        ')),

                                    // FOTO 2: BERSAMA
                                    Placeholder::make('img_bersama')
                                        ->label('2. Foto Bersama Pendamping')
                                        ->content(fn($record) => new HtmlString('
                                            <div class="border rounded-lg p-2 bg-gray-50 h-72 flex items-center justify-center">
                                                <img src="' . self::getBase64Image($record->user->file_foto_bersama) . '" 
                                                class="max-w-full max-h-full object-contain rounded" 
                                                alt="Foto Bersama">
                                            </div>
                                        ')),

                                    // FOTO 3: TEMPAT USAHA
                                    Placeholder::make('img_usaha')
                                        ->label('3. Foto Tempat Usaha')
                                        ->content(fn($record) => new HtmlString('
                                            <div class="border rounded-lg p-2 bg-gray-50 h-72 flex items-center justify-center">
                                                <img src="' . self::getBase64Image($record->user->file_foto_usaha) . '" 
                                                class="max-w-full max-h-full object-contain rounded" 
                                                alt="Foto Usaha">
                                            </div>
                                        ')),
                                ]),
                            ]),

                        // --- BAGIAN 3: FORM INPUT VERIFIKASI ---
                        Section::make('Keputusan Verifikasi')
                            ->schema([
                                Select::make('status_verifikasi')
                                    ->label('Status Baru')
                                    ->options(Pengajuan::getStatusVerifikasiOptions())
                                    ->required()
                                    ->native(false),

                                Textarea::make('catatan_revisi')
                                    ->label('Catatan / Alasan Penolakan')
                                    ->placeholder('Contoh: Foto NIB buram, mohon upload ulang.')
                                    ->rows(3)
                                    ->required(fn($get) => in_array($get('status_verifikasi'), [
                                        Pengajuan::STATUS_NIK_INVALID,
                                        Pengajuan::STATUS_UPLOAD_NIB
                                    ])), // Wajib isi catatan jika statusnya Revisi/Tolak
                            ])
                    ])

                    // --- 3. EKSEKUSI DATA ---
                    ->action(function (Pengajuan $record, array $data) {
                        // 1. Update Data
                        $record->update($data);

                        // 2. Kirim Notifikasi
                        Notification::make()
                            ->success()
                            ->title('Status Diperbarui')
                            ->body("Status berhasil diubah menjadi: {$data['status_verifikasi']}")
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    // --- MANAJEMEN HAK AKSES ---
    public static function canViewAny(): bool
    {
        // Menu ini bisa dilihat oleh: Superadmin, Admin
        return Auth::user()->isSuperAdmin()
            || Auth::user()->isAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengajuans::route('/'),
            'edit' => Pages\EditPengajuan::route('/{record}/edit'),
        ];
    }

    // Helper untuk convert gambar ke Base64
    protected static function getBase64Image($path)
    {
        // 1. Validasi Input
        if (! $path) return null;
        if (is_array($path)) $path = array_shift($path);
        if (! is_string($path)) return null;

        try {
            $disk = \Illuminate\Support\Facades\Storage::disk('google');

            if ($disk->exists($path)) {
                // Ambil konten raw file
                $content = $disk->get($path);

                // Ambil ekstensi
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                // --- PERBAIKAN UTAMA DISINI (FIX PNG & JPG) ---
                // Jangan percaya 100% pada $disk->mimeType(), sering meleset di GDrive.
                // Kita tentukan manual berdasarkan ekstensi agar browser tidak bingung.
                $mime = match ($extension) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'webp' => 'image/webp',
                    'gif' => 'image/gif',
                    default => $disk->mimeType($path) // Fallback ke deteksi driver
                };

                // --- LOGIKA KHUSUS HEIC (Tetap pertahankan) ---
                if ($extension === 'heic' || $mime === 'image/heic' || $mime === 'image/heif') {
                    if (extension_loaded('imagick')) {
                        try {
                            $imagick = new \Imagick();
                            $imagick->readImageBlob($content);
                            $imagick->setImageFormat('jpeg');
                            $content = $imagick->getImageBlob();
                            $mime = 'image/jpeg';
                            $imagick->clear();
                            $imagick->destroy();
                        } catch (\Exception $e) {
                            // Jika convert gagal, biarkan apa adanya (atau return null)
                        }
                    }
                }

                // Return string Base64 yang valid
                return 'data:' . $mime . ';base64,' . base64_encode($content);
            }
        } catch (\Exception $e) {
            // Log error jika perlu: Log::error($e->getMessage());
            return null;
        }

        return null;
    }
}
