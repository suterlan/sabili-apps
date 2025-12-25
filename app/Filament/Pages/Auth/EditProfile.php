<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\Wizard;
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
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;
use Filament\Support\Enums\ActionSize;
use Filament\Actions\Action;

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

	// 2. SEMBUNYIKAN TOMBOL DEFAULT DI FOOTER
	// Agar user tidak menyimpan sebelum sampai step terakhir
	protected function getFormActions(): array
	{
		return [

			// Tombol Kembali (Tambahan Kita)
			Action::make('back')
				->label('Kembali ke Dashboard')
				->url(filament()->getUrl()) // Mengarah ke Dashboard
				->color('gray')
				->icon('heroicon-m-arrow-left')
				->size(ActionSize::Large),
		];
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Wizard::make([

					// ==========================================
					// STEP 1: INFORMASI PRIBADI
					// ==========================================
					Wizard\Step::make('Data Diri')
						->icon('heroicon-o-user')
						->schema([

							Section::make('Informasi Dasar')
								->schema([
									$this->getNameFormComponent(),
									$this->getEmailFormComponent(),
									TextInput::make('phone')
										->label('No HP / WhatsApp')
										->tel()
										->maxLength(13)
										->extraAttributes(['oninput' => "this.value = this.value.replace(/[^0-9]/g, '')"])
										->required(),
								])->columns(2),
						]),

					// ==========================================
					// STEP 2: WILAYAH & DOMISILI
					// ==========================================
					Wizard\Step::make('Domisili')
						->icon('heroicon-o-map')
						->schema([
							Section::make('Alamat Lengkap')
								->schema([
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
											return $prov ? City::where('province_code', $prov)->pluck('name', 'code') : Collection::empty();
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
											return $kab ? District::where('city_code', $kab)->pluck('name', 'code') : Collection::empty();
										})
										->searchable()
										->live()
										->afterStateUpdated(fn(Set $set) => $set('desa', null))
										->required(),

									Select::make('desa')
										->label('Desa / Kelurahan')
										->options(function (Get $get) {
											$kec = $get('kecamatan');
											return $kec ? Village::where('district_code', $kec)->pluck('name', 'code') : Collection::empty();
										})
										->searchable()
										->required(),

									Textarea::make('address')
										->label('Alamat KTP')
										->rows(2)
										->required(),
									Textarea::make('alamat_domisili')
										->label('Alamat Domisili')
										->rows(2)
										->required(),
								])->columns(2),
						]),

					// ==========================================
					// STEP 3: PENDIDIKAN & BANK
					// ==========================================
					Wizard\Step::make('Pekerjaan & Bank')
						->icon('heroicon-o-academic-cap')
						->schema([
							Section::make('Pendidikan & Pekerjaan')
								->schema([
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
										->required(),
									TextInput::make('nama_instansi')
										->label('Nama Sekolah / Instansi')
										->required(),
								])->columns(2),

							Section::make('Rekening Bank')
								->schema([
									TextInput::make('nama_bank')
										->label('Nama Bank')
										->placeholder('Contoh: BCA')
										->required(),
									TextInput::make('nomor_rekening')
										->label('Nomor Rekening')
										->mask('99999999999999999999') // Mask angka panjang
										->required(),
								])->columns(2),
						]),

					// ==========================================
					// STEP 4: AKUN & KEAMANAN
					// ==========================================
					Wizard\Step::make('Akun')
						->icon('heroicon-o-key')
						->schema([
							Section::make('Akun SiHalal')
								->description('Kredensial akun eksternal.')
								->schema([
									TextInput::make('akun_halal')
										->label('Email Akun Halal')
										->required(),
									TextInput::make('pass_akun_halal')
										->label('Password Akun Halal')
										->password()
										->revealable()
										->required(),
								])->columns(2),

							Section::make('Ganti Password Login')
								->description('Kosongkan jika tidak ingin mengganti password.')
								->schema([
									$this->getPasswordFormComponent(),
									$this->getPasswordConfirmationFormComponent(),
								])->columns(2),
						]),


					// ...
					// STEP 5: DOKUMEN (Sekarang memanggil dari Model)
					// ==========================================
					Wizard\Step::make('Dokumen')
						->icon('heroicon-o-folder-open')
						// Logika visible: Hanya muncul di wizard jika role pendamping
						->visible(fn() => auth()->user()->isPendamping())
						->schema(
							// Panggil static function dari User Model
							// Return-nya sudah berupa array [ Section::make(...) ]
							User::getDokumenPendampingFormSchema()
						),
				])
					->columnSpanFull()
					->skippable(false) // User harus urut
					->persistStepInQueryString() // Agar kalau refresh tetap di step yang sama
					// TOMBOL SUBMIT CUSTOM DI STEP TERAKHIR
					->submitAction(
						Action::make('save')
							->label('Simpan Perubahan')
							->submit('save') // Memanggil method save() bawaan EditProfile
							->keyBindings(['mod+s'])
							->color('primary')
							->icon('heroicon-m-check-circle')
							->extraAttributes([
								'class' => 'w-full',
								'wire:loading.attr' => 'disabled',
								'wire:loading.class' => 'opacity-50 cursor-wait',
								'wire:target' => 'save',
							])
					),
			]);
	}
}
