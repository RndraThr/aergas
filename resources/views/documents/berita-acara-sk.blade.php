<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Sambungan Kompor dan Peralatan Gas</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            font-size: 10pt;
            line-height: 1.2;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .title {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 15px 0;
        }

        .date-section {
            margin-bottom: 15px;
        }

        .customer-info {
            margin-bottom: 15px;
        }

        .info-row {
            margin-bottom: 3px;
        }

        .table-section {
            margin: 15px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .notes {
            margin: 20px 0;
            font-size: 9pt;
            text-align: justify;
        }

        .signature-section {
            margin-top: 30px;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-top: 80px;
        }

        .signature-left,
        .signature-right {
            width: 45%;
            text-align: center;
        }

        .underline {
            border-bottom: 1px solid black;
            display: inline-block;
            min-width: 150px;
            margin: 0 5px;
        }

        .footer-notes {
            margin-top: 20px;
            font-size: 8pt;
        }

        .footer-notes ol {
            padding-left: 15px;
        }

        .footer-notes li {
            margin-bottom: 3px;
        }
    </style>
</head>

<body>
    <div class="header">
        @if(file_exists($logo_path))
            <img src="{{ $logo_path }}" alt="PGN Logo" class="logo">
        @endif
        <div class="company-name">
            PT Perusahaan Gas Negara (Persero) Tbk.<br>
            Area Jakarta, Bogor dan Bekasi
        </div>

        <div class="title">
            BERITA ACARA<br>
            SAMBUNGAN KOMPOR DAN PERALATAN GAS
        </div>
    </div>

    <div class="date-section">
        Pada hari ini
        <span class="underline">{{ app(App\Services\BeritaAcaraService::class)->getIndonesianDayName($date) }}</span>
        Tanggal <span class="underline">{{ $date->format('j') }}</span>
        Bulan <span
            class="underline">{{ app(App\Services\BeritaAcaraService::class)->getIndonesianMonthName($date) }}</span>
        Tahun <span class="underline">{{ $date->format('Y') }}</span><br>
        telah diasaksikan di lokasi:
    </div>

    <div class="customer-info">
        <div class="info-row">
            Nama <span class="underline">{{ $customer->nama_pelanggan ?? '-' }}</span> (sesuai KTP/SIM/Paspor)
        </div>
        <div class="info-row">
            NIK (No KTP) <span class="underline">{{ $customer->nik ?? '-' }}</span>
        </div>
        <div class="info-row">
            NPWP (jika ada) <span class="underline">{{ $customer->npwp ?? '-' }}</span>
        </div>
        <div class="info-row">
            Alamat (sesuai KTP) <span class="underline">{{ $customer->alamat ?? '-' }}</span>
        </div>
        <div class="info-row" style="margin-left: 100px;">
            Kel. <span class="underline">{{ $customer->kelurahan ?? '-' }}</span>,
            Kec. <span class="underline">{{ $customer->kecamatan ?? '-' }}</span>,
            Kota <span class="underline">{{ $customer->kota ?? '-' }}</span>
        </div>
        <div class="info-row">
            No. HPWA <span class="underline">{{ $customer->no_telepon ?? '-' }}</span>
            e-Mail <span class="underline">{{ $customer->email ?? '-' }}</span>
        </div>
        <div class="info-row">
            ID Pelanggan*) <span class="underline">{{ $sk->reff_id_pelanggan }}</span> (sesuai yang diserahkan ke PGN)
        </div>
        <div class="info-row" style="font-size: 8pt;">
            *) Sambungan Gas dan Gas Meter SMS FON
        </div>
    </div>

    <div class="table-section">
        <strong>Kebutuhan Sambungan Kompor:</strong>
        <table>
            <thead>
                <tr>
                    <th class="center" width="8%">No.</th>
                    <th class="center" width="30%">Sambungan Kompor</th>
                    <th class="center" width="15%">Panjang</th>
                    <th class="center" width="15%">Satuan</th>
                    <th class="center" width="32%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="center">1</td>
                    <td>Pipa Instalasi</td>
                    <td class="center">{{ $sk->panjang_pipa_galvanize_3_4_m ?? '0' }}</td>
                    <td class="center">meter</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td class="center">2</td>
                    <td>&nbsp;</td>
                    <td class="center">&nbsp;</td>
                    <td class="center">&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-section">
        <strong>Kebutuhan Konversi peralatan Gas:</strong>
        <table>
            <thead>
                <tr>
                    <th class="center" width="8%">No.</th>
                    <th class="center" width="40%">Jenis Peralatan Gas</th>
                    <th class="center" width="20%">Jumlah Tungku</th>
                    <th class="center" width="32%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="center">1</td>
                    <td>Kompor</td>
                    <td class="center">{{ $sk->jumlah_tungku ?? '2' }}</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td class="center">2</td>
                    <td>&nbsp;</td>
                    <td class="center">&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="notes">
        <strong>Calon Pelanggan/Pelanggan bersedia melakukan pembayaran Biaya Berlangganan atas</strong><br>
        kebutuhan Sambungan Kompor dengan Peralatan Gas sebelum pemasangan Gas diuraikan sesuai dengan ketentuan PGN.
        Selanjutnya, pemeliharaan peralatan gas dan pipa instalasi menjadi tanggung jawab Pelanggan.<br><br>

        <strong>Demikian Berita Acara ini dibuat dengan sebenar-benarnya dan untuk digunakan sebagaimana
            mestinya.</strong>
    </div>

    <div class="signature-section">
        <div class="signature-row">
            <div class="signature-left">
                <div>Calon Pelanggan/Pelanggan,</div>
                <br><br><br><br>
                <div class="underline">{{ $customer->nama_pelanggan ?? '.................................' }}</div>
            </div>
            <div class="signature-right">
                <div>Pet. {{ $sk->createdBy->name ?? 'PGN' }},</div>
                <br><br><br><br>
                <div class="underline">{{ $sk->createdBy->name ?? '.................................' }}</div>
            </div>
        </div>
    </div>

    <div class="footer-notes">
        <strong>Keterangan (Khusus Pelanggan Rumah Tangga):</strong>
        <ol>
            <li>Pemasangan pipa kompor yang dipasang oleh PGN: Rp 5.15.000/Unit atau GDP≤ 15 meter*</li>
            <li>Apabila kebutuhan pipa kompor melebihi 15 meter, kelebihan akan dikenakan biaya sesuai PGN No. RTR
                15.000/Unit. Tagihan akan dikirimkan kemudian melalui tim PGN Rp.69.000/meter*</li>
            <li>Kebutuhan atas sambungan kompor dan konversi peralatan gas dikenakan biaya sesuai:</li>
            <li>• Pipa Instalasi maks. 100 meter (exc PPh)</li>
            <li>• Konversi Water Heater RT to Rp.152.000/Unit (exc PPh) • GDP• Rp.75.000/Unit (exc PPh)*</li>
            <li>Pelanggan/Calon Pelanggan agar mendeklarasikan (menginformasikan) total seluruh atas saran acara yang
                terlaksana koneksi telah selesai/diselesaikan melalui berbagai sarana yang tersedia (ATM,
                SMS/Internet/Mobile Banking, Alfamart, GoDay, Tokopedia, LinkAja dan lain-lain).</li>
        </ol>
    </div>
</body>

</html>