<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

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
									]),

								// 1. DATA WILAYAH
								Section::make('Wilayah Kerja / Domisili')
									->icon('heroicon-o-map')
									->schema([
										Grid::make(2)->schema([
											Textarea::make('address')
												->label('Alamat KTP')
												->rows(3)
												->required(),
											Textarea::make('alamat_domisili')
												->label('Alamat Domisili')
												->rows(3)
												->required(),
										]),

										Select::make('provinsi')
											->label('Provinsi')
											->options(Province::pluck('name', 'code'))
											->searchable()
											->live()
											->afterStateUpdated(function (Set $set) {
												$set('kabupaten', null);
												$set('kecamatan', null);
												$set('desa', null);
											})
											->required(),

										Select::make('kabupaten')
											->label('Kabupaten / Kota')
											->options(function (Get $get) {
												$prov = $get('provinsi');
												if (!$prov) return Collection::empty();
												return City::where('province_code', $prov)->pluck('name', 'code');
											})
											->searchable()
											->live()
											->afterStateUpdated(function (Set $set) {
												$set('kecamatan', null);
												$set('desa', null);
											})
											->required(),

										Select::make('kecamatan')
											->label('Kecamatan')
											->options(function (Get $get) {
												$kab = $get('kabupaten');
												if (!$kab) return Collection::empty();
												return District::where('city_code', $kab)->pluck('name', 'code');
											})
											->searchable()
											->live()
											->afterStateUpdated(fn(Set $set) => $set('desa', null))
											->required(),

										Select::make('desa')
											->label('Desa / Kelurahan')
											->options(function (Get $get) {
												$kec = $get('kecamatan');
												if (!$kec) return Collection::empty();
												return Village::where('district_code', $kec)->pluck('name', 'code');
											})
											->searchable()
											->required(),
									])->columns(2),

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
												->label('Pendidikan Terakhir')
												->required(),
											TextInput::make('nama_instansi')
												->label('Nama Sekolah / Instansi')
												->required(),
										]),
									]),

								Section::make('Data Rekening Bank')
									->icon('heroicon-o-credit-card')
									->columns(2)
									->schema([
										TextInput::make('nama_bank')
											->label('Nama Bank')
											->placeholder('Contoh: BCA / Mandiri')
											->required(),
										TextInput::make('nomor_rekening')
											->label('Nomor Rekening')
											->numeric()
											->required(),
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
											->helperText('Password email utama Anda.')
											->required(),

										TextInput::make('akun_halal')
											->label('User Akun Halal')
											->required(),

										TextInput::make('pass_akun_halal')
											->label('Pass Akun Halal')
											->password()
											->revealable()
											->required(),
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

				// --- BAGIAN BAWAH (DOKUMEN FULL LEBAR) ---
				Group::make(User::getDokumenPendampingFormSchema())
					// Opsional: Pastikan hanya pendamping yang melihat
					->visible(fn() => auth()->user()->role === 'pendamping')
					->columnSpanFull(), // Paksa lebar penuh

			]);
	}
}
