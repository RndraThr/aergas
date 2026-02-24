<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Service Regulator</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            font-size: 10pt;
            line-height: 1.3;
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
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .title {
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 15px 0;
            text-transform: uppercase;
        }

        .date-section {
            margin-bottom: 15px;
            text-align: center;
        }

        .customer-section {
            background: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-bottom: 15px;
        }

        .info-row {
            margin-bottom: 5px;
            display: flex;
        }

        .info-label {
            width: 150px;
            font-weight: bold;
        }

        .info-value {
            flex: 1;
            border-bottom: 1px solid #333;
            padding-bottom: 2px;
        }

        .installation-section {
            margin: 20px 0;
        }

        .section-title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 15px;
            background: #0066cc;
            color: white;
            padding: 8px;
            text-align: center;
        }

        .material-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .material-table th,
        .material-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }

        .material-table th {
            background: #e9ecef;
            font-weight: bold;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .mgrt-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }

        .mgrt-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .notes-section {
            margin: 20px 0;
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #0066cc;
        }

        .signature-section {
            margin-top: 40px;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-top: 30px;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            margin: 60px 10px 10px 10px;
        }

        .approval-section {
            margin-top: 30px;
            background: #e8f5e8;
            padding: 15px;
            border: 1px solid #28a745;
        }
    </style>
</head>

<body>
    <div class="header">
        @if(file_exists($logo_path))
            <img src="{{ $logo_path }}" alt="PGN Logo" class="logo">
        @endif
        <div class="company-name">
            PT PERUSAHAAN GAS NEGARA (PERSERO) TBK.<br>
            Area Jakarta, Bogor dan Bekasi
        </div>

        <div class="title">
            Berita Acara<br>
            Pemasangan Service Regulator (SR)
        </div>
    </div>

    <div class="date-section">
        Pada hari ini <strong>{{ app(App\Services\BeritaAcaraService::class)->getIndonesianDayName($date) }}</strong>,
        tanggal <strong>{{ $date->format('j') }}</strong>
        bulan <strong>{{ app(App\Services\BeritaAcaraService::class)->getIndonesianMonthName($date) }}</strong>
        tahun <strong>{{ $date->format('Y') }}</strong><br>
        telah dilakukan pemasangan Service Regulator di lokasi:
    </div>

    <div class="customer-section">
        <h3 style="margin-top: 0; color: #0066cc;">INFORMASI PELANGGAN</h3>
        <div class="info-row">
            <div class="info-label">Reference ID</div>
            <div class="info-value">{{ $sr->reff_id_pelanggan }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Nama Pelanggan</div>
            <div class="info-value">{{ $customer->nama_pelanggan ?? '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Alamat</div>
            <div class="info-value">{{ $customer->alamat ?? '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Kelurahan</div>
            <div class="info-value">{{ $customer->kelurahan ?? '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">No. Telepon</div>
            <div class="info-value">{{ $customer->no_telepon ?? '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Tanggal Pemasangan</div>
            <div class="info-value">{{ $sr->tanggal_pemasangan ? $sr->tanggal_pemasangan->format('d/m/Y') : '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Petugas SR</div>
            <div class="info-value">{{ $sr->createdBy->name ?? '-' }}</div>
        </div>
    </div>

    @if($sr->no_seri_mgrt || $sr->merk_brand_mgrt)
        <div class="mgrt-info">
            <h3 style="margin-top: 0; color: #856404;">INFORMASI METER GAS RUMAH TANGGA (MGRT)</h3>
            <div class="mgrt-grid">
                @if($sr->no_seri_mgrt)
                    <div class="info-row">
                        <div class="info-label">No. Seri MGRT</div>
                        <div class="info-value" style="border-bottom: 1px solid #856404;">{{ $sr->no_seri_mgrt }}</div>
                    </div>
                @endif
                @if($sr->merk_brand_mgrt)
                    <div class="info-row">
                        <div class="info-label">Merk/Brand MGRT</div>
                        <div class="info-value" style="border-bottom: 1px solid #856404;">{{ $sr->merk_brand_mgrt }}</div>
                    </div>
                @endif
            </div>
            @if($sr->jenis_tapping)
                <div class="info-row" style="margin-top: 10px;">
                    <div class="info-label">Jenis Tapping</div>
                    <div class="info-value" style="border-bottom: 1px solid #856404;">{{ $sr->jenis_tapping }}</div>
                </div>
            @endif
        </div>
    @endif

    <div class="installation-section">
        <div class="section-title">MATERIAL YANG DIGUNAKAN</div>

        <table class="material-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="50%">Jenis Material</th>
                    <th width="15%">Quantity</th>
                    <th width="15%">Satuan</th>
                    <th width="15%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $materials = [
                        ['name' => 'Tapping Saddle', 'qty' => $sr->qty_tapping_saddle, 'unit' => 'pcs'],
                        ['name' => 'Coupler 20mm', 'qty' => $sr->qty_coupler_20mm, 'unit' => 'pcs'],
                        ['name' => 'Pipa PE 20mm', 'qty' => $sr->panjang_pipa_pe_20mm_m, 'unit' => 'meter'],
                        ['name' => 'Elbow 90 x 20mm', 'qty' => $sr->qty_elbow_90x20, 'unit' => 'pcs'],
                        ['name' => 'Transition Fitting', 'qty' => $sr->qty_transition_fitting, 'unit' => 'pcs'],
                        ['name' => 'Pondasi Tiang SR', 'qty' => $sr->panjang_pondasi_tiang_sr_m, 'unit' => 'meter'],
                        ['name' => 'Pipa Galvanize 3/4"', 'qty' => $sr->panjang_pipa_galvanize_3_4_m, 'unit' => 'meter'],
                        ['name' => 'Klem Pipa', 'qty' => $sr->qty_klem_pipa, 'unit' => 'pcs'],
                        ['name' => 'Ball Valve 3/4"', 'qty' => $sr->qty_ball_valve_3_4, 'unit' => 'pcs'],
                        ['name' => 'Double Nipple 3/4"', 'qty' => $sr->qty_double_nipple_3_4, 'unit' => 'pcs'],
                        ['name' => 'Long Elbow 3/4"', 'qty' => $sr->qty_long_elbow_3_4, 'unit' => 'pcs'],
                        ['name' => 'Regulator Service', 'qty' => $sr->qty_regulator_service, 'unit' => 'pcs'],
                        ['name' => 'Coupling MGRT', 'qty' => $sr->qty_coupling_mgrt, 'unit' => 'pcs'],
                        ['name' => 'Meter Gas Rumah Tangga', 'qty' => $sr->qty_meter_gas_rumah_tangga, 'unit' => 'pcs'],
                        ['name' => 'Casing 1"', 'qty' => $sr->panjang_casing_1_inch_m, 'unit' => 'meter'],
                        ['name' => 'Sealtape', 'qty' => $sr->qty_sealtape, 'unit' => 'pcs'],
                    ];
                    $no = 1;
                @endphp

                @foreach($materials as $material)
                    @if($material['qty'] && $material['qty'] > 0)
                        <tr>
                            <td class="center">{{ $no++ }}</td>
                            <td>{{ $material['name'] }}</td>
                            <td class="center">{{ $material['qty'] }}</td>
                            <td class="center">{{ $material['unit'] }}</td>
                            <td>&nbsp;</td>
                        </tr>
                    @endif
                @endforeach

                @for($i = $no; $i <= 5; $i++)
                    <tr>
                        <td class="center">{{ $i }}</td>
                        <td>&nbsp;</td>
                        <td class="center">&nbsp;</td>
                        <td class="center">&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    @if($sr->notes)
        <div class="notes-section">
            <h4 style="margin-top: 0; color: #0066cc;">CATATAN PETUGAS:</h4>
            <p>{{ $sr->notes }}</p>
        </div>
    @endif

    <div
        style="margin: 20px 0; font-size: 9pt; text-align: justify; background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;">
        <strong>PERNYATAAN:</strong><br>
        Dengan ditandatanganinya Berita Acara ini, maka Pelanggan menyatakan bahwa:
        <ol style="margin: 10px 0; padding-left: 20px;">
            <li>Pemasangan Service Regulator telah selesai dilakukan sesuai dengan standar teknis PGN</li>
            <li>Material yang digunakan telah sesuai dengan spesifikasi yang diperlukan</li>
            <li>Pelanggan bertanggung jawab atas perawatan dan pemeliharaan instalasi setelah diserahterimakan</li>
            <li>Pelanggan telah menerima penjelasan mengenai cara pengoperasian dan maintenance MGRT</li>
        </ol>
        <strong>Demikian Berita Acara ini dibuat dengan sebenar-benarnya untuk digunakan sebagaimana mestinya.</strong>
    </div>

    <div class="signature-section">
        <div class="signature-grid">
            <div class="signature-box">
                <div><strong>Pelanggan</strong></div>
                <div class="signature-line"></div>
                <div><strong>{{ $customer->nama_pelanggan ?? '(....................................)' }}</strong></div>
            </div>
            <div class="signature-box">
                <div><strong>Petugas SR - PGN</strong></div>
                <div class="signature-line"></div>
                <div><strong>{{ $sr->createdBy->name ?? '(....................................)' }}</strong></div>
            </div>
        </div>
    </div>

    @if($sr->tracer_approved_at || $sr->cgp_approved_at)
        <div class="approval-section">
            <h4 style="margin-top: 0; color: #155724;">VALIDASI & APPROVAL</h4>
            @if($sr->tracer_approved_at)
                <div style="margin-bottom: 10px;">
                    <strong>Tracer Approved:</strong> {{ $sr->tracer_approved_at->format('d/m/Y H:i') }}
                    oleh {{ $sr->tracerApprovedBy->name ?? '-' }}
                </div>
            @endif
            @if($sr->cgp_approved_at)
                <div style="margin-bottom: 10px;">
                    <strong>CGP Approved:</strong> {{ $sr->cgp_approved_at->format('d/m/Y H:i') }}
                    oleh {{ $sr->cgpApprovedBy->name ?? '-' }}
                </div>
            @endif
        </div>
    @endif
</body>

</html>