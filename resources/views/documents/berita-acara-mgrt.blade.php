<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Berita Acara MGRT</title>
    <style>
        @page {
            margin: 10mm 12mm;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #000;
            background-color: white;
            position: relative;
            min-height: 100vh;
        }

        .header-logo {
            text-align: right;
            margin-bottom: 15px;
            margin-top: -8px;
            padding-right: 0;
        }

        .header-logo img {
            width: 180px;
            height: auto;
            display: block;
            margin-left: auto;
            margin-right: 0;
        }

        .title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            color: #0099cc;
            margin: 8px 0 15px 0;
            letter-spacing: 0.5px;
        }

        .subtitle {
            text-align: center;
            font-size: 10pt;
            color: #333;
            margin-bottom: 15px;
        }

        .date-info {
            background-color: #0099cc;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            text-align: left;
            margin-bottom: 12px;
            font-size: 9pt;
            line-height: 1.5;
        }

        .info-section-wrapper {
            border: 2px solid #5a8fb5;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .section-header {
            background-color: #5a8fb5;
            color: white;
            padding: 6px 12px;
            font-weight: bold;
            font-size: 10pt;
            margin: 0;
            border: none;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            background-color: white;
            border: none;
        }

        .info-table td {
            padding: 8px 10px;
            vertical-align: top;
            font-size: 9pt;
        }

        .info-label {
            color: #000;
            font-size: 9pt;
            font-weight: normal;
            white-space: nowrap;
            padding-right: 10px;
            width: 140px;
        }

        .info-value {
            font-weight: normal;
            border-bottom: 1px solid #333;
            font-size: 9pt;
            text-align: left;
        }

        .mgrt-section {
            margin: 15px 0;
        }

        .mgrt-title {
            font-weight: bold;
            color: #0099cc;
            margin-bottom: 8px;
            font-size: 10pt;
        }

        .mgrt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        .mgrt-table td {
            padding: 6px 10px;
            vertical-align: top;
        }

        .mgrt-table .label {
            width: 140px;
            font-weight: normal;
        }

        .mgrt-table .value {
            border-bottom: 1px solid #333;
        }

        .checklist-section {
            margin: 12px 0;
        }

        .checklist-title {
            font-weight: bold;
            color: #0099cc;
            margin-bottom: 8px;
            font-size: 10pt;
        }

        .checklist-item {
            display: table;
            width: 100%;
            margin-bottom: 4px;
            padding: 4px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .checklist-item .bullet {
            display: table-cell;
            width: 15px;
            vertical-align: middle;
        }

        .checklist-item .bullet::before {
            content: "";
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #0099cc;
            border-radius: 50%;
        }

        .checklist-item .text {
            display: table-cell;
            padding-left: 8px;
            vertical-align: middle;
            font-size: 9pt;
            width: 50%;
        }

        .checklist-item .status {
            display: table-cell;
            text-align: left;
            width: 50%;
            vertical-align: middle;
            font-size: 9pt;
            font-weight: bold;
            color: #16a34a;
        }

        .responsibility-text {
            margin: 15px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-left: 3px solid #0099cc;
            font-size: 9pt;
            line-height: 1.4;
            text-align: justify;
        }

        .signature-wrapper {
            margin-top: 25px;
            width: 70%;
        }

        .signature-table {
            width: 100%;
            border: 2px solid #0099cc;
            border-collapse: collapse;
        }

        .signature-table td {
            border: 2px solid #0099cc;
            padding: 12px;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 60px;
            display: block;
            font-size: 9pt;
        }

        .signature-name {
            padding-top: 5px;
            display: inline-block;
            min-width: 150px;
            font-size: 9pt;
        }

        .footer-wrapper {
            position: relative;
            margin-top: 25px;
        }

        .footer-text {
            text-align: left;
            font-size: 8pt;
            margin-bottom: 10px;
        }

        .footer-company {
            font-weight: bold;
            font-style: normal;
        }
    </style>
</head>

<body>
    <div class="header-logo">
        @if(file_exists($logo_path))
            <img src="{{ $logo_path }}" alt="PGN Logo">
        @endif
    </div>

    <div class="title">BERITA ACARA<br>METER GAS RUMAH TANGGA</div>

    <div class="date-info">
        Pada hari ini, <strong>{{ $tanggal_indonesia['day_name'] }}</strong> tanggal
        <strong>{{ $tanggal_indonesia['day'] }} {{ $tanggal_indonesia['month'] }}
            {{ $tanggal_indonesia['year'] }}</strong>,
        telah dilakukan pemasangan Meter Gas Rumah Tangga (MGRT) kepada:
    </div>

    <div class="info-section-wrapper">
        <div class="section-header">Informasi Pelanggan</div>
        <div style="padding: 10px;">
            <table class="info-table">
                <tr>
                    <td class="info-label">No. ID Pelanggan</td>
                    <td class="info-value">{{ $reff_id_formatted }}</td>
                </tr>
                <tr>
                    <td class="info-label">Nama Lengkap</td>
                    <td class="info-value">{{ $customer->nama_pelanggan ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Alamat</td>
                    <td class="info-value">{{ $customer->alamat ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">RT / RW</td>
                    <td class="info-value">{{ $customer->rt ?? '-' }} / {{ $customer->rw ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Kelurahan</td>
                    <td class="info-value">{{ $customer->kelurahan ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Kecamatan</td>
                    <td class="info-value">{{ $customer->kecamatan ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Kota / Kabupaten</td>
                    <td class="info-value">{{ $customer->kota ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="info-section-wrapper">
        <div class="section-header">Data Meter Gas Rumah Tangga (MGRT)</div>
        <div style="padding: 10px;">
            <table class="info-table">
                <tr>
                    <td class="info-label">No. Seri MGRT</td>
                    <td class="info-value"><strong>{{ $sr->no_seri_mgrt ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td class="info-label">Merk / Brand</td>
                    <td class="info-value">{{ $sr->merk_brand_mgrt ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Tanggal Pemasangan</td>
                    <td class="info-value">{{ $tanggal_indonesia['day'] }} {{ $tanggal_indonesia['month'] }}
                        {{ $tanggal_indonesia['year'] }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="checklist-section">
        <div class="checklist-title">Hal-hal yang telah dilakukan:</div>

        <div class="checklist-item">
            <div class="bullet"></div>
            <div class="text">Pemasangan Meter Gas Rumah Tangga</div>
            <div class="status">✓ Terlaksana</div>
        </div>

        <div class="checklist-item">
            <div class="bullet"></div>
            <div class="text">Pengecekan dan pengujian instalasi</div>
            <div class="status">✓ Terlaksana</div>
        </div>

        <div class="checklist-item">
            <div class="bullet"></div>
            <div class="text">Sosialisasi pengoperasian kepada pelanggan</div>
            <div class="status">✓ Terlaksana</div>
        </div>

        <div class="checklist-item">
            <div class="bullet"></div>
            <div class="text">Serah terima meter kepada pelanggan</div>
            <div class="status">✓ Terlaksana</div>
        </div>
    </div>

    <div class="responsibility-text">
        Dengan ditandatanganinya Berita Acara ini, maka Pelanggan menyatakan telah menerima dan menyetujui
        pemasangan Meter Gas Rumah Tangga (MGRT) serta bertanggung jawab atas pemeliharaan dan penggunaan
        meter gas sesuai dengan ketentuan yang berlaku.
    </div>

    <div class="signature-wrapper">
        <table class="signature-table">
            <tr>
                <td>
                    <span class="signature-label">Pelanggan</span>
                    <div class="signature-name">
                        ( {{ $customer->nama_pelanggan ?? '.............................' }} )
                    </div>
                </td>
                <td>
                    <span class="signature-label">Petugas</span>
                    <div class="signature-name">
                        ( ............................. )
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-wrapper">
        <div class="footer-text">
            Terima kasih atas kepercayaan Saudara kepada kami,<br>
            <span class="footer-company">PT Perusahaan Gas Negara (Persero) Tbk.</span>
        </div>
    </div>
</body>

</html>