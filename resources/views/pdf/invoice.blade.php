<!DOCTYPE html>
<html>

<head>
    <title>Invoice #{{ $tagihan->nomor_invoice }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
            color: #333;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }

        .invoice-details {
            float: right;
            text-align: right;
        }

        .bill-to {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f3f4f6;
        }

        .total-row td {
            font-weight: bold;
            background-color: #f9fafb;
        }

        .footer {
            margin-top: 40px;
            font-size: 12px;
            text-align: center;
            color: #777;
        }

        .status-stamp {
            border: 2px solid;
            padding: 5px 10px;
            display: inline-block;
            transform: rotate(-10deg);
            font-weight: bold;
            letter-spacing: 2px;
            position: absolute;
            right: 50px;
            top: 150px;
        }

        .paid {
            color: green;
            border-color: green;
        }

        .unpaid {
            color: red;
            border-color: red;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">{{ config('app.name') }}</div>
        {{-- Ganti teks di atas dengan <img src="..." height="50"> jika punya logo --}}

        <div class="invoice-details">
            <strong>INVOICE</strong><br>
            Nomor: {{ $tagihan->nomor_invoice }}<br>
            Tanggal: {{ \Carbon\Carbon::parse($tagihan->tanggal_terbit)->translatedFormat('d F Y') }}
        </div>
        <div style="clear: both;"></div>
    </div>

    {{-- Stampel Lunas/Belum --}}
    @if ($tagihan->status_pembayaran === 'DIBAYAR')
        <div class="status-stamp paid">LUNAS</div>
    @else
        <div class="status-stamp unpaid">BELUM DIBAYAR</div>
    @endif

    <div class="bill-to">
        <strong>Ditujukan Kepada:</strong><br>
        {{-- Mengambil nama dari perwakilan pengajuan pertama --}}
        {{ $tagihan->pengajuan->user->name ?? 'Pelaku Usaha' }}<br>
        @if ($tagihan->pengajuan->user->merk_dagang)
            {{ $tagihan->pengajuan->user->merk_dagang }}<br>
        @endif
        {{-- Tambahkan alamat jika ada di database user --}}
        {{ $tagihan->pengajuan->user->alamat ?? '' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Deskripsi / Nama Pelaku Usaha</th>
                <th>NIK</th>
                <th style="text-align: right;">Biaya</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tagihan->pengajuans as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->user->name }}</strong><br>
                        <small>{{ $item->user->merk_dagang ?? '-' }}</small>
                    </td>
                    <td>{{ $item->user->nik }}</td>
                    <td style="text-align: right;">Rp {{ number_format($tagihan->total_nominal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">TOTAL TAGIHAN</td>
                <td style="text-align: right;">Rp
                    {{ number_format($tagihan->total_nominal * $tagihan->pengajuans->count(), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 30px;">
        <strong>Metode Pembayaran:</strong><br>
        Silahkan transfer ke rekening berikut:<br>
        Bank {{ config('pengaturan.bank') }}: {{ config('pengaturan.nomor_rek') }} (a.n.
        {{ config('pengaturan.nama_rek') }})<br>
        Atau melalui link: {{ $tagihan->link_pembayaran ?? '-' }}
    </div>

    <div class="footer">
        Dokumen ini diterbitkan secara otomatis oleh sistem.<br>
        Terima kasih atas kerjasamanya.
    </div>
</body>

</html>
