<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; // Saya ganti icon biar lebih pas
    protected static ?string $navigationGroup = 'Manajemen Sistem'; // Opsional: Biar rapi

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nama Lengkap'),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required(),

                // Password
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create'),

                // LOGIKA HIERARKI ROLE
                Forms\Components\Select::make('role')
                    ->label('Role Akun')
                    ->options(function () {
                        // Pastikan user login terdeteksi
                        $user = Auth::user();

                        if ($user && $user->isSuperAdmin()) {
                            return [
                                'admin' => 'Admin',
                                'pendamping' => 'Pendamping',
                            ];
                        }

                        // Default untuk Admin biasa
                        return [
                            'pendamping' => 'Pendamping',
                        ];
                    })
                    ->required()
                    ->default('pendamping'),

                Forms\Components\TextInput::make('phone')
                    ->label('No HP'),

                Forms\Components\Section::make('Berkas Dokumen Pendamping')
                    ->icon('heroicon-o-folder-open')
                    ->collapsible() // Bisa ditutup biar tidak memenuhi layar
                    ->collapsed() // Default tertutup
                    // Section ini HANYA MUNCUL jika user yang dilihat adalah Pendamping
                    ->visible(fn($get, $record) => $get('role') === 'pendamping' || ($record && $record->role === 'pendamping'))
                    ->schema([

                        Forms\Components\Group::make([
                            // Pas Foto
                            Forms\Components\FileUpload::make('file_pas_foto')
                                ->label('Pas Foto')
                                ->disk('google') // Wajib disk google
                                ->image()
                                ->avatar()
                                ->downloadable() // Admin bisa download
                                ->openable() // Admin bisa klik untuk preview di tab baru
                                ->disabled(), // Admin TIDAK BOLEH ubah/hapus (Hanya lihat)

                            // Buku Rekening
                            Forms\Components\FileUpload::make('file_buku_rekening')
                                ->label('Buku Rekening')
                                ->disk('google')
                                ->downloadable()
                                ->openable()
                                ->disabled(),
                        ])->columns(2),

                        Forms\Components\Group::make([
                            // KTP
                            Forms\Components\FileUpload::make('file_ktp')
                                ->label('Scan KTP')
                                ->disk('google')
                                ->downloadable()
                                ->openable()
                                ->disabled(),

                            // Ijazah
                            Forms\Components\FileUpload::make('file_ijazah')
                                ->label('Scan Ijazah')
                                ->disk('google')
                                ->downloadable()
                                ->openable()
                                ->disabled(), // hapus jika admin boleh ubah
                        ])->columns(2),

                        // Data Tambahan (Teks)
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('nama_bank')->disabled(),
                            Forms\Components\TextInput::make('nomor_rekening')->disabled(),
                            Forms\Components\TextInput::make('pendidikan_terakhir')->disabled(),
                            Forms\Components\TextInput::make('nama_instansi')->label('Sekolah/Kampus')->disabled(),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'superadmin' => 'gray', // Tambahan warna buat superadmin
                        'admin' => 'danger',
                        'pendamping' => 'warning', // Pendamping warna kuning
                        'member' => 'success',
                        default => 'primary',
                    }),
                // --- KOLOM BARU: STATUS ---
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'verified' => 'success', // Hijau
                        'rejected' => 'danger',  // Merah
                        'pending' => 'warning',  // Kuning
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)) // Huruf besar awal
                    ->icon(fn(string $state): string => match ($state) {
                        'verified' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        'pending' => 'heroicon-o-clock',
                    }),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                // --- TOMBOL AKSI VERIFIKASI ---
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('verify')
                        ->label('Verifikasi Akun')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation() // Minta konfirmasi biar gak salah klik
                        ->action(fn(User $record) => $record->update(['status' => 'verified']))
                        ->visible(fn(User $record) => $record->status !== 'verified'), // Sembunyi jika sudah verify

                    Tables\Actions\Action::make('reject')
                        ->label('Tolak Akun')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn(User $record) => $record->update(['status' => 'rejected']))
                        ->visible(fn(User $record) => $record->status !== 'rejected'),
                ])
                    ->label('Ubah Status')
                    ->icon('heroicon-m-ellipsis-vertical')
                    // Aksi ini hanya boleh dilihat Superadmin/Admin
                    ->visible(fn() => Auth::user()->isSuperAdmin() || Auth::user()->isAdmin()),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Cek Auth agar aman
        $user = Auth::user();

        // Jika BUKAN Superadmin
        if ($user && ! $user->isSuperAdmin()) {
            // Admin hanya bisa lihat pendamping
            $query->where('role', 'pendamping');
        }

        // Jangan tampilkan member di sini
        $query->where('role', '!=', 'member');

        // Jangan tampilkan Superadmin di list (biar aman)
        $query->where('role', '!=', 'superadmin');

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::check() && (Auth::user()->isSuperAdmin() || Auth::user()->isAdmin());
    }
}
