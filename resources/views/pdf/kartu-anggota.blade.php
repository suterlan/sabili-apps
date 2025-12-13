<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Kartu Anggota</title>
    <style>
        /* 1. RESET MARGIN HALAMAN (PENTING BUAT ID CARD) */
        @page {
            margin: 0px;
        }

        body {
            margin: 0px;
            padding: 0px;
            font-family: sans-serif;
            /* Font standar agar aman */
        }

        /* Container Utama */
        .card {
            width: 100%;
            height: 100%;
            /* Gunakan background warna solid dulu untuk tes */
            background: #f59e0b;
            position: relative;
            overflow: hidden;
        }

        /* Hiasan Background (CSS Native) */
        .decoration-circle {
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            z-index: 1;
        }

        /* Header */
        .header {
            position: absolute;
            top: 15px;
            left: 20px;
            width: 100%;
            z-index: 2;
        }

        .header-text {
            color: white;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 2px solid white;
            display: inline-block;
            padding-bottom: 5px;
        }

        /* Foto Profil */
        .photo-wrapper {
            position: absolute;
            top: 50px;
            left: 20px;
            width: 80px;
            height: 100px;
            background-color: #fff;
            padding: 3px;
            border-radius: 4px;
            z-index: 2;
        }

        .photo-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* Crop rapi */
        }

        /* Data Diri (Gunakan Tabel agar rapi tanpa Flexbox) */
        .info-wrapper {
            position: absolute;
            top: 50px;
            left: 115px;
            /* Jarak dari foto */
            z-index: 2;
            color: white;
        }

        table {
            border-collapse: collapse;
        }

        td {
            padding-bottom: 8px;
            vertical-align: top;
        }

        .label {
            font-size: 9px;
            opacity: 0.9;
            display: block;
        }

        .value {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* QR Code */
        .qr-wrapper {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background-color: white;
            padding: 4px;
            border-radius: 4px;
            z-index: 2;
            width: 50px;
            /* Ukuran fix */
            height: 50px;
        }

        .qr-wrapper svg {
            width: 100%;
            height: 100%;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="decoration-circle"></div>

        <div class="header">
            <div class="header-text">KARTU ANGGOTA</div>
        </div>

        <div class="photo-wrapper">
            @if (!empty($foto))
                <img src="{{ $foto }}">
            @else
                <div style="text-align:center; padding-top:40px; font-size:10px; color:#aaa;">No Foto</div>
            @endif
        </div>

        <div class="info-wrapper">
            <table>
                <tr>
                    <td>
                        <span class="label">NAMA LENGKAP</span>
                        <span class="value">{{ $user->name }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="label">NOMOR HP</span>
                        <span class="value">{{ $user->phone ?? '-' }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="label">TANGGAL DAFTAR</span>
                        <span class="value">{{ $user->created_at->format('d M Y') }}</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="qr-wrapper">
            <img src="data:image/png;base64, {{ $qrcode }}" style="width: 100%; height: 100%;">
        </div>
    </div>
</body>

</html>
