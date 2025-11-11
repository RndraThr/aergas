<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Gas In</title>
    <style>
        @page {
            margin: 10mm 12mm;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 8.5pt;
            line-height: 1.2;
            color: #000;
        }
        .header-logo {
            text-align: right;
            margin-bottom: -15px;
            margin-top: -8px;
            padding-right: 0;
        }
        .header-logo img {
            width: 400px;
            height: auto;
            display: block;
            margin-left: auto;
            margin-right: 0;
        }
        .title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            color: #0099cc;
            margin: 8px 0 5px 0;
            letter-spacing: 0.5px;
        }
        .ba-number {
            text-align: left;
            font-size: 9pt;
            margin-bottom: 5px;
            font-style: normal;
            font-weight: bold;
            color: #dc2626;
        }
        .date-info {
            background-color: #0099cc;
            color: white;
            padding: 6px 12px;
            border-radius: 3px;
            text-align: left;
            margin-bottom: 10px;
            font-size: 8.5pt;
            line-height: 1.4;
        }
        .date-space {
            display: inline-block;
            width: 250px;
            border-bottom: 1px solid white;
            margin: 0 5px;
        }
        .info-section-wrapper {
            border: 2px solid #5a8fb5;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .section-header {
            background-color: #5a8fb5;
            color: white;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 9pt;
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
            vertical-align: bottom;
            font-size: 8.5pt;
        }
        .info-label {
            color: #000;
            font-size: 8.5pt;
            font-weight: normal;
            white-space: nowrap;
            padding-right: 10px;
        }
        .info-value {
            font-weight: normal;
            border-bottom: 1px solid #333;
            font-size: 8.5pt;
            min-height: 20px;
        }
        .info-row-full {
            display: block;
            width: 100%;
        }
        .checklist-section {
            margin: 8px 0;
        }
        .checklist-title {
            font-weight: bold;
            color: #0099cc;
            margin-bottom: 5px;
            font-size: 8.5pt;
        }
        .checklist-item {
            display: table;
            width: 100%;
            margin-bottom: 3px;
            padding: 3px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .checklist-item .bullet {
            display: table-cell;
            width: 15px;
            color: #0099cc;
            font-weight: bold;
            vertical-align: middle;
            font-size: 7pt;
        }
        .checklist-item .text {
            display: table-cell;
            padding-left: 6px;
            vertical-align: middle;
            font-size: 7.5pt;
        }
        .checklist-item .status {
            display: table-cell;
            text-align: right;
            width: 120px;
            vertical-align: middle;
            font-size: 7.5pt;
        }
        .meter-section {
            margin: 8px 0;
        }
        .meter-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .meter-row {
            display: table-row;
        }
        .meter-cell {
            display: table-cell;
            width: 50%;
            padding: 3px 6px;
            vertical-align: top;
        }
        .meter-label {
            font-size: 7pt;
            color: #666;
            margin-bottom: 2px;
        }
        .meter-value {
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            padding-bottom: 2px;
            font-size: 7.5pt;
        }
        .responsibility-text {
            margin: 10px 0;
            padding: 8px;
            background-color: #f8f9fa;
            border-left: 3px solid #0099cc;
            font-size: 7.5pt;
            line-height: 1.3;
            text-align: justify;
        }
        .signature-section {
            margin-top: 15px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 10px 5px;
            vertical-align: bottom;
        }
        .signature-label {
            font-weight: bold;
            margin-bottom: 40px;
            display: block;
            font-size: 8pt;
        }
        .signature-name {
            border-top: 1px solid #000;
            padding-top: 3px;
            display: inline-block;
            min-width: 180px;
            font-size: 7.5pt;
        }
        .footer-text {
            text-align: center;
            margin-top: 15px;
            font-size: 7.5pt;
            font-style: italic;
        }
        .footer-company {
            font-weight: bold;
            font-style: normal;
        }
        .code-bottom-right {
            position: absolute;
            bottom: 15px;
            right: 15px;
            font-size: 7pt;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header-logo">
        @if(file_exists($logo_path))
            <img src="{{ $logo_path }}" alt="PGN Logo">
        @endif
    </div>

    <div class="title">BERITA ACARA GAS IN</div>

    <div class="ba-number">BAGI.</div>

    <div class="date-info">
        Pada hari ini <span class="date-space"></span>, telah dilakukan penyalaan Gas pertama kali ("Tanggal Dimulai") kepada
    </div>

    <div class="info-section-wrapper">
        <div class="section-header">Informasi Pelanggan</div>

        <table class="info-table">
        <tr>
            <td class="info-label" style="width: 140px;">No. ID Pelanggan</td>
            <td class="info-value" style="width: 30%;">
                @php
                    $reffId = $gasIn->reff_id_pelanggan;
                    // Add 00 prefix if not already 8 digits
                    if (is_numeric($reffId) && strlen($reffId) < 8) {
                        $reffId = str_pad($reffId, 8, '00', STR_PAD_LEFT);
                    }
                @endphp
                {{ $reffId }}
            </td>
            <td class="info-label" style="width: 130px;">Nama Lengkap</td>
            <td class="info-value">{{ $customer->nama_pelanggan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Alamat</td>
            <td class="info-value" colspan="3">{{ $customer->alamat ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label"></td>
            <td class="info-value"></td>
            <td class="info-label" style="width: 50px;">RT</td>
            <td class="info-value" style="width: 80px;">{{ $customer->rt ?? '-' }}</td>
            <td class="info-label" style="width: 50px; padding-left: 15px;">RW</td>
            <td class="info-value">{{ $customer->rw ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Kelurahan</td>
            <td class="info-value">{{ $customer->kelurahan ?? '-' }}</td>
            <td class="info-label">Kecamatan</td>
            <td class="info-value" colspan="3">DEPOK</td>
        </tr>
        <tr>
            <td class="info-label">Kota / Kabupaten</td>
            <td class="info-value">SLEMAN</td>
            <td class="info-label">Provinsi</td>
            <td class="info-value" colspan="3">D.I. YOGYAKARTA</td>
        </tr>
        <tr>
            <td class="info-label">Latitude/Latitude</td>
            <td class="info-value">{{ $customer->latitude ?? '-' }}, {{ $customer->longitude ?? '-' }}</td>
            <td class="info-label">Kode Pos</td>
            <td class="info-value" colspan="3">
                @php
                    $kodePosSleman = [
                        'CATUR TUNGGAL' => ['KARANGGAYAM' => '52107', 'KARANGWUNI' => '52107', 'MANGGUNG' => '52107', 'MRICAN' => '52107', 'SANTREN' => '52107', 'SLEMAN 1 KAB SLEMAN' => '52106'],
                        'CATURTUNGGAL' => ['KARANGGAYAM' => '52101', 'KARANGWUNI' => '52107', 'KOCORAN' => '52107', 'MANGGUNG' => '25090', 'MRICAN' => '52107', 'SAMIRONO' => '52118', 'SANTREN' => '52107', 'SLEMAN 1 KAB SLEMAN' => '44709'],
                        'CONDONG CATUR' => ['DABAG' => '52107', 'KALIWARU' => '52109', 'PRINGWULUNG' => '52107', 'SOROPADAN' => '00446', 'SOROPADAN (PRINGWULUNG)' => '45111'],
                        'CONDONGCATUR' => ['GANDOK' => '25090', 'KALIWARU' => '25090', 'PRINGWULUNG' => '25090']
                    ];

                    $kelurahan = strtoupper($customer->kelurahan ?? '');
                    $dusun = strtoupper($customer->dusun ?? '');
                    $kodePos = '-';

                    if (isset($kodePosSleman[$kelurahan])) {
                        if ($dusun && isset($kodePosSleman[$kelurahan][$dusun])) {
                            $kodePos = $kodePosSleman[$kelurahan][$dusun];
                        } else {
                            $kodePos = reset($kodePosSleman[$kelurahan]);
                        }
                    }
                @endphp
                {{ $kodePos }}
            </td>
        </tr>
    </table>
    </div>

    <div class="checklist-section">
        <div class="checklist-title">Bersama ini telah dilakukan hal-hal sbb:</div>

        <div class="checklist-item">
            <div class="bullet">●</div>
            <div class="text">Berita Acara Hasil Pengujian pipa instalasi</div>
            <div class="status"><strong>OK</strong> &nbsp;&nbsp; Tersedia</div>
        </div>

        <div class="checklist-item">
            <div class="bullet">●</div>
            <div class="text">Tersedia prosedur Gas In ke peralatan gas milik pelanggan</div>
            <div class="status"><strong>OK</strong> &nbsp;&nbsp; Terlaksana</div>
        </div>

        <div class="checklist-item">
            <div class="bullet">●</div>
            <div class="text">Tersedia perlengkapan K3PL yang memadai</div>
            <div class="status"><strong>OK</strong> &nbsp;&nbsp; Tersedia</div>
        </div>

        <div class="checklist-item">
            <div class="bullet">●</div>
            <div class="text">Sosialisasi pengoperasian & pemeliharaan kepada pelanggan</div>
            <div class="status"><strong>OK</strong> &nbsp;&nbsp; Terlaksana</div>
        </div>

        <div class="checklist-item">
            <div class="bullet">●</div>
            <div class="text">Meter terkalibrasi</div>
            <div class="status"><strong>OK</strong> &nbsp;&nbsp; Tersedia</div>
        </div>
    </div>

    <div class="section-header">Data Meter Gas terpasang:</div>

    <div class="meter-section">
        <div class="meter-grid">
            <div class="meter-row">
                <div class="meter-cell">
                    <div class="meter-label">Jenis Meter:</div>
                    <div class="meter-value">{{ $gasIn->jenis_meter ?? 'Meter konvensional / Smart Meter' }}</div>
                </div>
                <div class="meter-cell">
                    <div class="meter-label">SN Meter</div>
                    <div class="meter-value">{{ $gasIn->sn_meter ?? '-' }}</div>
                </div>
            </div>
            <div class="meter-row">
                <div class="meter-cell">
                    <div class="meter-label">Qmin/Qmax:</div>
                    <div class="meter-value">{{ $gasIn->qmin_qmax ?? '-' }} m3/jam</div>
                </div>
                <div class="meter-cell">
                    <div class="meter-label">Stand meter awal</div>
                    <div class="meter-value">{{ $gasIn->stand_meter_awal ?? '-' }}</div>
                </div>
            </div>
            <div class="meter-row">
                <div class="meter-cell">
                    <div class="meter-label">Awal Kalibrasi:</div>
                    <div class="meter-value">{{ $gasIn->awal_kalibrasi ?? '(MM YYYY)' }}</div>
                </div>
                <div class="meter-cell">
                    <div class="meter-label">Suhu:</div>
                    <div class="meter-value">{{ $gasIn->suhu ?? '-' }}°C {{ $gasIn->tekanan ? '(jika ada)' : '' }}</div>
                </div>
            </div>
            <div class="meter-row">
                <div class="meter-cell" colspan="2">
                    <div class="meter-label">Tekanan:</div>
                    <div class="meter-value">{{ $gasIn->tekanan ?? '-' }} Bar</div>
                </div>
            </div>
        </div>
    </div>

    <div class="responsibility-text">
        Dengan dilakukannya Gas In ini, maka Pelanggan menyetujui untuk bertanggung jawab atas pengoperasian dan perawatan
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <span class="signature-label">Pelanggan</span>
            <div class="signature-name">
                ( {{ $customer->nama_pelanggan ?? '.............................' }} )
            </div>
        </div>
        <div class="signature-box">
            <span class="signature-label">Instalatur</span>
            <div class="signature-name">
                ( {{ $gasIn->instalatur_name ?? '.............................' }} )
            </div>
        </div>
    </div>

    <div class="footer-text">
        Terima kasih atas kepercayaan Saudara kepada kami,<br>
        <span class="footer-company">PT Perusahaan Gas Negara Tbk.</span>
    </div>

    <div class="code-bottom-right">
        O-001/06.02F/F05
    </div>
</body>
</html>
