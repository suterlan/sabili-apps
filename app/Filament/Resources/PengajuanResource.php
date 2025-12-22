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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

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

                // Action Detail 
                Tables\Actions\Action::make('view_detail')
                    ->label('Detail & Foto')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->modalWidth('2xl')
                    ->slideOver()
                    ->modalSubmitAction(false) // Hilangkan tombol Submit karena hanya view
                    ->modalCancelActionLabel('Tutup')
                    ->form([
                        // --- BAGIAN 1: TAMPILAN DATA USER (READ ONLY) ---
                        Section::make('Identitas Pelaku Usaha')
                            ->columns(2)
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
                                    ->content(fn($record) => $record->user->tanggal_lahir
                                        ? $record->user->tanggal_lahir->format('d-m-Y')
                                        : '-'),

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
                                // Gunakan Grid 1 atau 2 untuk slideover
                                Grid::make(1)->schema([
                                    // 1. FOTO PRODUK
                                    Placeholder::make('img_produk')
                                        ->label('1. Foto Produk')
                                        ->content(fn($record) => self::generatePhotoCard(
                                            self::getBase64Image($record->user->file_foto_produk), // Pastikan helper getBase64Image Anda mengembalikan full string lengkap dengan header
                                            'Foto Produk',
                                            'Produk-' . Str::slug($record->user->name)
                                        )),

                                    // 2. FOTO BERSAMA
                                    Placeholder::make('img_bersama')
                                        ->label('2. Foto Bersama')
                                        ->content(fn($record) => self::generatePhotoCard(
                                            self::getBase64Image($record->user->file_foto_bersama),
                                            'Foto Bersama',
                                            'Bersama-' . Str::slug($record->user->name)
                                        )),

                                    // 3. FOTO TEMPAT USAHA
                                    Placeholder::make('img_usaha')
                                        ->label('3. Foto Tempat Usaha')
                                        ->content(fn($record) => self::generatePhotoCard(
                                            self::getBase64Image($record->user->file_foto_usaha),
                                            'Foto Usaha',
                                            'Usaha-' . Str::slug($record->user->name)
                                        )),

                                    // 4. FOTO KTP
                                    Placeholder::make('img_ktp')
                                        ->label('4. Foto KTP')
                                        ->content(fn($record) => self::generatePhotoCard(
                                            self::getBase64Image($record->user->file_ktp),
                                            'Foto KTP',
                                            'KTP-' . Str::slug($record->user->name)
                                        )),
                                ]),
                            ]),
                    ]),

                // LOGIC UPDATE STATUS
                Tables\Actions\Action::make('update_status')
                    ->label('Verifikasi')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->color('warning')
                    ->slideOver() // 1. Ubah jadi SlideOver agar tidak menumpuk/overflow
                    ->stickyModalHeader() // Header slideover selalu terlihat
                    ->stickyModalFooter() // Footer slideover selalu terlihat
                    ->modalWidth('2xl') // Sesuaikan lebar slideover

                    ->visible(function (Pengajuan $record, $livewire) {
                        // Syarat 1: User login adalah verifikatornya
                        $isMyTask = auth()->id() === $record->verificator_id;

                        // Syarat 2: BUKAN di tab 'semua'
                        $bukanTabHistory = isset($livewire->activeTab) && $livewire->activeTab !== 'semua';

                        return $isMyTask && $bukanTabHistory;
                    })

                    ->form([
                        // --- BAGIAN 3: FORM INPUT VERIFIKASI ---
                        Section::make('Keputusan Verifikasi')
                            ->schema([
                                // Tampilkan nama user sekilas agar tidak salah orang
                                Placeholder::make('info_user')
                                    ->label('Pelaku Usaha')
                                    ->content(fn($record) => $record->user->name . ' (' . $record->user->merk_dagang . ')'),

                                Select::make('status_verifikasi')
                                    ->label('Status Baru')
                                    ->options(Pengajuan::getStatusVerifikasiOptions())
                                    ->required()
                                    ->native(false)
                                    ->live(),

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

    private static function generatePhotoCard(string $base64Data, string $label, string $filenamePrefix): HtmlString
    {
        // 1. Deteksi MIME Type dari string Base64
        // Format standar: "data:image/png;base64,....."
        $mimeType = 'image/jpeg'; // Default fallback
        $extension = 'jpg';      // Default fallback

        if (preg_match('/^data:(\w+\/[\w-]+);base64,/', $base64Data, $matches)) {
            $mimeType = $matches[1]; // contoh: image/png

            // Mapping ekstensi manual agar akurat
            $extensions = [
                'image/jpeg' => 'jpg',
                'image/jpg'  => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
                'image/heic' => 'heic', // Browser tidak bisa render ini, tapi download aman
                'application/pdf' => 'pdf',
            ];

            $extension = $extensions[$mimeType] ?? 'jpg';
        }

        // 2. Logic Tampilan: Jika HEIC, tampilkan icon placeholder (karena browser gagal render)
        // Jika format biasa (jpg/png), tampilkan gambarnya.
        $isPreviewable = in_array($extension, ['jpg', 'png', 'webp', 'gif']);

        $imageHtml = $isPreviewable
            ? '<img src="' . $base64Data . '" class="max-w-full max-h-full object-contain shadow-sm rounded" alt="' . $label . '">'
            : '<div class="text-gray-400 flex flex-col items-center text-center p-4">
             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mb-2">
               <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
             </svg>
             <span class="text-sm">Preview tidak tersedia untuk format .' . $extension . '</span>
           </div>';

        // 3. Return HTML String yang Rapi
        return new HtmlString('
        <div class="border rounded-lg bg-gray-50 overflow-hidden flex flex-col w-full relative">[
            <div class="h-64 flex items-center justify-center p-2 bg-gray-100 border-b relative">
                ' . $imageHtml . '
            </div>
            
            <div class="p-2 flex justify-center bg-white mt-auto">
                <a href="' . $base64Data . '" 
                   download="' . $filenamePrefix . '-' . date('YmdHis') . '.' . $extension . '"
                   class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-500 transition w-full justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Unduh (.' . strtoupper($extension) . ')
                </a>
            </div>
        </div>
    ');
    }
}
