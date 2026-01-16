<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Carbon\Carbon;

class StatsKinerjaVerifikator extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.stats-kinerja-verifikator';

    // Agar widget melebar penuh
    protected int | string | array $columnSpan = 'full';

    // Variabel untuk menampung data filter form
    public ?array $data = [];

    // Set default tanggal saat widget pertama kali dimuat
    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date'   => now()->endOfMonth()->toDateString(),
        ]);
    }

    // Definisi Form Filter
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(4) // Grid 4 kolom agar input tanggal tidak terlalu panjang
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Dari Tanggal (Verifikasi)')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->default(now()->startOfMonth())
                            ->live() // Update real-time saat dipilih
                            ->afterStateUpdated(fn() => $this->dispatch('refresh-widget')),

                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal (Verifikasi)')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->default(now()->endOfMonth())
                            ->live() // Update real-time saat dipilih
                            ->afterStateUpdated(fn() => $this->dispatch('refresh-widget')),
                    ]),
            ])
            ->statePath('data');
    }

    // Logic Hak Akses & Lokasi Tampil
    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public function getViewData(): array
    {
        // Ambil range tanggal dari form
        $startDate = $this->data['start_date'] ?? null;
        $endDate   = $this->data['end_date'] ?? null;

        // Siapkan Query
        $query = Pengajuan::query()
            ->select('verificator_id', 'status_verifikasi', DB::raw('count(*) as total'));

        // Terapkan Filter Tanggal pada kolom 'verified_at'
        if ($startDate) {
            $query->whereDate('verified_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('verified_at', '<=', $endDate);
        }

        // Eksekusi Query Grouping
        $rawStats = $query
            ->groupBy('verificator_id', 'status_verifikasi')
            ->get();

        // Ambil list semua status yang mungkin ada (untuk header tabel)
        $statuses = array_keys(Pengajuan::getStatusVerifikasiOptions());

        // Ambil list User yang pernah memverifikasi (pastikan relasi pengajuansVerified ada di Model User)
        $verificators = User::whereHas('pengajuansVerified')->get();

        // Mapping data agar mudah dipanggil di View: $matrix[user_id][status]
        $matrix = [];
        foreach ($rawStats as $stat) {
            $verifId = $stat->verificator_id ?? 0; // 0 = belum diklaim
            $matrix[$verifId][$stat->status_verifikasi] = $stat->total;
        }

        return [
            'statuses'     => $statuses,
            'verificators' => $verificators,
            'matrix'       => $matrix,
            'unclaimed'    => $matrix[0] ?? [], // Data yang verifikatornya NULL
            'filter_label' => ($startDate && $endDate)
                ? Carbon::parse($startDate)->format('d M Y') . ' s/d ' . Carbon::parse($endDate)->format('d M Y')
                : 'Semua Waktu',
        ];
    }
}
