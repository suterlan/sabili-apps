<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EditProfile extends BaseEditProfile
{
	// 1. PERLEBAR HALAMAN
	// Ubah ukuran container menjadi 7xl (sangat lebar)
	public function getMaxWidth(): MaxWidth | string | null
	{
		return MaxWidth::SevenExtraLarge;
	}

	// (Opsional) Sembunyikan Logo biar lebih bersih karena formnya panjang
	public function hasLogo(): bool
	{
		return false;
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				// KITA GUNAKAN GRID UTAMA 3 KOLOM
				Grid::make(['default' => 1, 'lg' => 3])
					->schema([

						// KOLOM KIRI (Info Dasar) - Memakan 2 Kolom
						Grid::make(1)
							->columnSpan(['default' => 1, 'lg' => 2])
							->schema([
								Section::make('Informasi Pribadi')
									->icon('heroicon-o-user')
									->schema([
										Grid::make(2)->schema([
											$this->getNameFormComponent(),
											TextInput::make('phone')
												->label('No HP / WhatsApp')
												->tel()
												->required(),
										]),
										$this->getEmailFormComponent(),
										Grid::make(2)->schema([
											Textarea::make('address')
												->label('Alamat KTP')
												->rows(3),
											Textarea::make('alamat_domisili')
												->label('Alamat Domisili')
												->rows(3),
										]),
									]),

								Section::make('Data Pendidikan & Pekerjaan')
									->icon('heroicon-o-academic-cap')
									->schema([
										Grid::make(2)->schema([
											Select::make('pendidikan_terakhir')
												->options([
													'SD' => 'SD/Sederajat',
													'SMP' => 'SMP/Sederajat',
													'SMA' => 'SMA/Sederajat',
													'D3' => 'D3',
													'S1' => 'S1',
													'S2' => 'S2',
													'S3' => 'S3',
												])
												->searchable()
												->label('Pendidikan Terakhir'),
											TextInput::make('nama_instansi')
												->label('Nama Sekolah / Instansi'),
										]),
									]),

								Section::make('Data Rekening Bank')
									->icon('heroicon-o-credit-card')
									->columns(2)
									->schema([
										TextInput::make('nama_bank')
											->label('Nama Bank')
											->placeholder('Contoh: BCA / Mandiri'),
										TextInput::make('nomor_rekening')
											->label('Nomor Rekening')
											->numeric(),
									]),
							]),

						// KOLOM KANAN (Akun & Upload) - Memakan 1 Kolom
						Grid::make(1)
							->columnSpan(['default' => 1, 'lg' => 1])
							->schema([
								Section::make('Akun External')
									->icon('heroicon-o-key')
									->schema([
										TextInput::make('pass_email_pendamping')
											->label('Password Email')
											->password()
											->revealable()
											->helperText('Password email utama Anda.'),

										TextInput::make('akun_halal')
											->label('User Akun Halal'),

										TextInput::make('pass_akun_halal')
											->label('Pass Akun Halal')
											->password()
											->revealable(),
									]),

								Section::make('Berkas Dokumen')
									->icon('heroicon-o-document-arrow-up')
									->schema([
										FileUpload::make('file_pas_foto')
											->label('Pas Foto')
											->disk('google') // <--- Wajib: Arahkan ke Google Drive
											->directory(fn() => 'dokumen_' . Str::slug(Auth::user()->name) . '_' . Auth::id() . '/foto')
											->image() // Validasi: Hanya boleh file gambar
											->avatar() // Tampilan di form jadi bulat (cocok untuk profil)
											->imageEditor() // Mengaktifkan fitur edit/crop sebelum upload
											->circleCropper() // Mengaktifkan crop lingkaran
											->maxSize(2048) // Maksimal 2MB agar upload tidak terlalu lama
											->downloadable(), // Agar admin/user bisa download file aslinya

										FileUpload::make('file_ktp')
											->label('Upload KTP')
											->disk('google') // <--- INI KUNCINYA
											->directory(fn() => 'dokumen_' . Str::slug(Auth::user()->name) . '_' . Auth::id() . '/ktp')
											->visibility('private')
											->acceptedFileTypes(['image/*', 'application/pdf'])
											->maxSize(2048)
											->downloadable(),

										FileUpload::make('file_ijazah')
											->label('Upload Ijazah')
											->disk('google') // <--- INI KUNCINYA
											->directory(fn() => 'dokumen_' . Str::slug(Auth::user()->name) . '_' . Auth::id() . '/ijazah')
											->visibility('private')
											->acceptedFileTypes(['image/*', 'application/pdf'])
											->maxSize(2048)
											->downloadable(),

										FileUpload::make('file_buku_rekening')
											->label('Buku Rekening')
											->disk('google') // <--- INI KUNCINYA
											->directory(fn() => 'dokumen_' . Str::slug(Auth::user()->name) . '_' . Auth::id() . '/rekening')
											->visibility('private')
											->acceptedFileTypes(['image/*', 'application/pdf'])
											->maxSize(2048)
											->downloadable(),
									]),

								Section::make('Ganti Password Login')
									->schema([
										$this->getPasswordFormComponent(),
										$this->getPasswordConfirmationFormComponent(),
									])
									->collapsible()
									->collapsed(), // Default tertutup biar rapi
							]),
					]),
			]);
	}
}
