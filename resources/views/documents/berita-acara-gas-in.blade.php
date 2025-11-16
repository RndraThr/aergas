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
            font-size: 9pt;
            line-height: 1.2;
            color: #000;
            background-color: white; /* Background keseluruhan diubah menjadi putih */
            position: relative;
            min-height: 100vh;
        }
        .header-logo {
            text-align: right;
            margin-bottom: 20px; /* Spasi antara logo dan judul ditambah lagi */
            margin-top: -8px;
            padding-right: 0;
        }
        .header-logo img {
            width: 200px; /* Logo lebih kecil */
            height: auto;
            display: block;
            margin-left: auto;
            margin-right: 0;
        }
        .title {
            text-align: center;
            font-size: 16.5pt; /* Judul lebih besar sedikit */
            font-weight: bold;
            color: #0099cc;
            margin: 8px 0 5px 0;
            letter-spacing: 0.5px;
        }
        .ba-number {
            text-align: left;
            font-size: 9.5pt;
            margin-bottom: 5px;
            font-style: italic; /* BAGI. dibuat miring */
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
            font-size: 9pt;
            line-height: 1.4;
        }
        .date-space {
            display: inline-block;
            width: 250px;
            border-bottom: 1px solid white;
            padding: 0 0 5px 0;
            margin: 0 5px;
            text-align: left;
            line-height: 1.4;
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
            font-size: 9.5pt;
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
            padding: 8px 10px 8px 10px;
            vertical-align: top;
            font-size: 9pt;
        }
        .info-label {
            color: #000;
            font-size: 9pt;
            font-weight: normal;
            white-space: nowrap;
            padding-right: 10px;
            padding-top: 8px !important;
            padding-bottom: 8px !important;
        }
        .info-value {
            font-weight: normal;
            border-bottom: 1px solid #333;
            font-size: 9pt;
            padding-top: 8px !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            padding-bottom: 0px !important;
            text-align: left;
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
            font-size: 9.5pt;
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
            padding-left: 6px;
            vertical-align: middle;
            font-size: 8pt;
            width: 48%;
        }
        .checklist-item .status {
            display: table-cell;
            text-align: left;
            width: 50%;
            vertical-align: middle;
            font-size: 8pt;
            font-weight: bold;
        }
        .meter-section {
            margin: 8px 0;
            font-size: 8pt;
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        .meter-column {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding-right: 10px;
        }
        .meter-column-right {
            display: table-cell;
            width: 52%;
            vertical-align: top;
        }
        .meter-line {
            margin-bottom: 6px;
            display: block;
            width: 100%;
        }
        .meter-item {
            display: flex;
            align-items: baseline;
        }
        .meter-label-inline {
            display: inline-block;
            font-size: 8pt;
            width: 100px;
            text-align: left;
            vertical-align: baseline;
        }
        .meter-colon {
            display: inline-block;
            margin: 0 3px;
            vertical-align: baseline;
        }
        .meter-value-inline {
            display: inline-block;
            border-bottom: 1px solid #333;
            font-size: 8pt;
            width: calc(100% - 185px);
            min-height: 14px;
            vertical-align: baseline;
            padding-bottom: 2px;
        }
        .meter-value-inline-long {
            display: inline-block;
            border-bottom: 1px solid #333;
            font-size: 8pt;
            width: calc(100% - 115px);
            min-height: 14px;
            vertical-align: baseline;
            padding-bottom: 2px;
        }
        .meter-value-inline-short {
            display: inline-block;
            border-bottom: 1px solid #333;
            width: 60px;
            font-size: 8pt;
            vertical-align: baseline;
            padding-bottom: 2px;
        }
        .meter-value-inline-pressure {
            display: inline-block;
            border-bottom: 1px solid #333;
            font-size: 8pt;
            width: 80px;
            min-height: 14px;
            vertical-align: baseline;
            padding-bottom: 2px;
        }
        .meter-value-inline-temp {
            display: inline-block;
            border-bottom: 1px solid #333;
            font-size: 8pt;
            width: 60px;
            min-height: 14px;
            vertical-align: baseline;
            padding-bottom: 2px;
        }
        .meter-unit {
            display: inline-block;
            margin-left: 3px;
            margin-right: 15px;
            font-size: 8pt;
            vertical-align: baseline;
        }
        .meter-spacer {
            width: 20px; /* Spasi antara Tekanan dan Suhu */
        }
        .responsibility-text {
            margin: 10px 0;
            padding: 8px;
            background-color: #f8f9fa;
            border-left: 3px solid #0099cc;
            font-size: 8pt;
            line-height: 1.3;
            text-align: justify;
        }
        .signature-wrapper {
            margin-top: 15px;
            width: 60%;
        }
        .signature-table {
            width: 100%;
            border: 2px solid #0099cc;
            border-collapse: collapse;
        }
        .signature-table td {
            border: 2px solid #0099cc;
            padding: 10px;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }
        .signature-label {
            font-weight: bold;
            margin-bottom: 50px;
            display: block;
            font-size: 8.5pt;
        }
        .signature-name {
            padding-top: 3px;
            display: inline-block;
            min-width: 150px;
            font-size: 8pt;
        }
        .footer-wrapper {
            position: relative;
            margin-top: 20px;
        }
        .footer-text {
            text-align: left;
            font-size: 7.5pt;
            margin-bottom: 10px;
        }
        .footer-company {
            font-weight: bold;
            font-style: normal;
        }
        .footer-image-cell {
            position: fixed;
            bottom: 0;
            right: 0;
            text-align: right;
            width: auto;
            padding-right: 0;
            margin-bottom: 0;
        }
        .footer-image {
            max-width: 200px;
            height: auto;
            display: block;
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

    <div class="ba-number">BAGI <span style="color: #000000; ">{{ $customer->no_bagi ?? '-' }}</span></div>

    <div class="date-info">
        {{-- @php
            $tanggalGasIn = $gasIn->tanggal_gas_in ? \Carbon\Carbon::parse($gasIn->tanggal_gas_in) : \Carbon\Carbon::now();

            // Array nama hari dalam bahasa Indonesia
            $namaHari = [
                'Sunday' => 'Minggu',
                'Monday' => 'Senin',
                'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis',
                'Friday' => 'Jumat',
                'Saturday' => 'Sabtu'
            ];

            // Array nama bulan dalam bahasa Indonesia
            $namaBulan = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];

            $hariNama = $namaHari[$tanggalGasIn->format('l')];
            $tanggal = $tanggalGasIn->format('d');
            $bulan = $namaBulan[$tanggalGasIn->format('n')];
            $tahun = $tanggalGasIn->format('Y');

            $tanggalLengkap = "{$hariNama}, {$tanggal} {$bulan} {$tahun}";
        @endphp --}}
        <table style="width: 100%; border: 0; margin: 0; padding: 0; border-collapse: collapse;">
            <tr>
                <td style="border: 0; padding: 0; margin: 0; width: auto; vertical-align: baseline; white-space: nowrap;">Pada hari ini</td>
                <td style="border: 0; border-bottom: 1px solid white; padding: 0 5px 2px 0; margin: 0; width: 250px; vertical-align: baseline;"></td>
                <td style="border: 0; padding: 0; margin: 0; width: auto; vertical-align: baseline;">, telah dilakukan penyalaan Gas pertama kali ("Tanggal Dimulai")</td>
            </tr>
        </table>
        kepada
    </div>

    <div class="info-section-wrapper">
        <div class="section-header">Informasi Pelanggan</div>
        <div style="padding-bottom: 10px;  padding-right: 10px;">
            <table class="info-table">
            <tr>
                <td class="info-label" style="width: 10px;">No. ID Pelanggan</td>
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
                <td class="info-label" style="width: 10px;">Nama Lengkap</td>
                <td class="info-value" colspan="3">{{ $customer->nama_pelanggan ?? '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">Alamat</td>
                <td class="info-value" colspan="5">{{ $customer->alamat ?? '-' }}</td>
            </tr>
            <tr>
                <td class="info-label"></td>
                <td class="info-value"></td>
                <td class="info-label" style="width: 30px;">RT</td>
                <td class="info-value" style="width: 100px;">{{ $customer->rt ?? '-' }}</td>
                <td class="info-label" style="width: 30px; padding-left: 15px;">RW</td>
                <td class="info-value" style="width: 100px;">{{ $customer->rw ?? '-' }}</td>
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

    </div>

    <div class="checklist-section">
        <div class="checklist-title">Bersama ini telah dilakukan hal-hal sbb:</div>

        <div class="checklist-item">
            <div class="bullet"></div>
            <div class="text">Berita Acara Hasil Pengujian pipa instalasi</div>
            <div class="status"><strong>OK</strong> &nbsp;&nbsp; Tersedia</div>
        </div>

        <div class="checklist-item">
            <div class="bullet"></div>
            <div class="text">Tersedia prosedur Gas In ke peralatan gas milik pelanggan</div>
            <div class="status"><strong>OK</strong> &nbsp;&nbsp; Terlaksana</div>
        </div>

        <div class="checklist-item">
            <div class="bullet"></div>
            <div class="text">Tersedia perlengkapan K3PL yang memadai</div>
            <div class="status"><strong>OK</strong> &nbsp;&nbsp; Tersedia</div>
        </div>

        <div class="checklist-item">
            <div class="bullet"></div>
            <div class="text">Sosialisasi pengoperasian & pemeliharaan kepada pelanggan</div>
            <div class="status"><strong>OK</strong> &nbsp;&nbsp; Terlaksana</div>
        </div>

        <div class="checklist-item">
            <div class="bullet"></div>
            <div class="text">Meter terkalibrasi</div>
            <div class="status"><strong>OK</strong> &nbsp;&nbsp; Tersedia</div>
        </div>
    </div>

    <div class="checklist-title">Data Meter Gas terpasang:</div>

    <table class="info-table" style="font-size: 8pt;">
        <tr>
            <td class="info-label" style="width: 80px; font-size: 8pt; padding-left: 0 !important;">Jenis Meter</td>
            <td class="info-value" style="width: 35%; font-size: 8pt; padding-bottom: 8px !important; border-bottom: 0 !important;">: Meter konvensional / Smart Meter</td>
            <td class="info-label" style="width: 110px; font-size: 8pt;">SN Meter</td>
            <td class="info-value" style="font-size: 8pt; padding-bottom: 8px !important;" colspan="3">: </td>
        </tr>
        <tr>
            <td class="info-label" style="width: 80px; font-size: 8pt; padding-left: 0 !important;">Qmin/Qmax</td>
            <td style="padding: 8.5px 0 2px 0; vertical-align: top; font-size: 8pt; width: 35%;">
                <table style="width: 67%; border: 0; border-collapse: collapse; margin: 0;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="border-bottom: 1px solid #333; padding-bottom: 8px; padding-top: 0; font-size: 8pt; padding-left: 0; border-top: 0; border-left: 0; border-right: 0; line-height: 1;">:</td>
                        <td style="text-align: center; border-bottom: 1px solid #333; padding-bottom: 8px; padding-top: 0; font-size: 8pt; padding-left: 0; border-top: 0; border-left: 0; border-right: 0; line-height: 1;">0,016 / 2,5</td>
                        <td style="text-align: right; width: 45px; font-size: 8pt; border: 0; padding: 0; padding-right: 0; padding-top: 0 !important; line-height: 1;">m3/jam</td>
                    </tr>
                </table>
            </td>
            <td class="info-label" style="font-size: 8pt; padding-top: 9px !important;">Stand meter awal</td>
            <td class="info-value" style="font-size: 8pt;" colspan="3">: </td>
        </tr>
        <tr>
            <td class="info-label" style="width: 80px; font-size: 8pt; padding-left: 0 !important;">Awal Kalibrasi</td>
            <td style="padding: 8.5px 0 2px 0; vertical-align: top; font-size: 8pt; width: 35%;">
                <table style="width: 76%; border: 0; border-collapse: collapse; margin: 0;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="border-bottom: 1px solid #333; padding-bottom: 8px; padding-top: 0; font-size: 8pt; padding-left: 0; border-top: 0; border-left: 0; border-right: 0; line-height: 1;">:</td>
                        <td style="border-bottom: 1px solid #333; padding-bottom: 8px; padding-top: 0; font-size: 8pt; padding-left: 0; border-top: 0; border-left: 0; border-right: 0; line-height: 1;"></td>
                        <td style="text-align: right; width: 45px; font-size: 8pt; border: 0; padding: 0; padding-right: 0; padding-top: 2px !important; line-height: 1; padding-left: 10px !important;">(MM/YYYY)</td>
                    </tr>
                </table>
            </td>
            <td class="info-label" style="font-size: 8pt;">Tekanan</td>
            <td style="padding: 8.5px 0 2px 0; vertical-align: top; font-size: 8pt;" colspan="3">
                <table style="width: 100%; border: 0; border-collapse: collapse; margin: 0;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="border-bottom: 1px solid #333; padding-bottom: 8px; padding-top: 0; font-size: 8pt; padding-left: 0; padding-right: 5px; border-top: 0; border-left: 0; border-right: 0; line-height: 1; width: 45px;">: </td>
                        <td style="text-align: right; width: 25px; font-size: 8pt; border: 0; padding: 0; padding-right: 0; padding-top: 2px; line-height: 1;">Bar</td>
                        <td style="font-size: 8pt; padding: 0 0 0 10px; width: 40px;">Suhu</td>
                        <td style="border-bottom: 1px solid #333; padding-top: 1.5px !important; font-size: 8pt; padding-left: 0; padding-right: 2px; border-top: 0; border-left: 0; border-right: 0; line-height: 1; width: 45px;">: </td>
                        <td style="text-align: right; width: 18px; font-size: 8pt; border: 0; padding: 0; padding-right: 0; padding-top: 2px; line-height: 1;">°C</td>
                        <td style="text-align: left; font-size: 8pt; border: 0; padding: 0; padding-right: 0; padding-top: 2px; line-height: 1; padding-left: 3px;">(jika ada)</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="responsibility-text">
        Dengan dilakukannya Gas In ini, maka Pelanggan menyetujui untuk bertanggung jawab atas pengoperasian dan perawatan
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
                    <span class="signature-label">Instalatur</span>
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
            <span class="footer-company">PT Perusahaan Gas Negara Tbk.</span>
        </div>
        <div class="footer-image-cell">
            @php
                $footer_image_path = public_path('assets/FOOTER_GAS_IN_BA.png');
            @endphp
            @if(file_exists($footer_image_path))
                <img src="{{ $footer_image_path }}" alt="Footer" class="footer-image">
            @endif
        </div>
    </div>
</body>
</html>
