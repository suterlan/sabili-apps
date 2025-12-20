<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
	// Kita override method getTitle() untuk logika dinamis
	public function getTitle(): string | Htmlable
	{
		$user = auth()->user();

		// 1. Logika untuk Koordinator
		if ($user && $user->isKoordinator()) {
			// Ambil nama kecamatan dari relasi district
			// Pastikan relasi 'district' ada di model User, atau gunakan kolom lain
			$namaWilayah = $user->district->name ?? $user->kecamatan;

			// Format Title: "Dashboard Kec. Sukamaju"
			return "Dashboard Koordinator Kec. " . ucfirst(strtolower($namaWilayah));
		}

		// 2. Logika untuk Pendamping (Opsional)
		if ($user && $user->isPendamping()) {
			return 'Dashboard Pendamping';
		}

		// 3. Default (Admin/Superadmin)
		return 'Dashboard Utama';
	}
}
