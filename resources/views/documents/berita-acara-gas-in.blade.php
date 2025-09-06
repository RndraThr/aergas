<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Gas In</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            margin: 15px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: right;
            margin-bottom: 20px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo {
            width: 60px;
            height: auto;
        }
        
        .company-name {
            font-size: 12pt;
            font-weight: bold;
            color: #0066cc;
        }
        
        .title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            color: #0099cc;
            margin: 20px 0;
            text-transform: uppercase;
        }
        
        .date-badge {
            background: linear-gradient(45deg, #0099cc, #00ccff);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .info-section {
            background: linear-gradient(45deg, #0099cc, #00ccff);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .info-title {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 12pt;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .info-item {
            margin-bottom: 8px;
        }
        
        .info-label {
            font-size: 9pt;
            opacity: 0.9;
        }
        
        .info-value {
            font-weight: bold;
            font-size: 11pt;
        }
        
        .checklist-section {
            margin: 20px 0;
        }
        
        .checklist-title {
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }
        
        .checklist-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            padding: 5px;
        }
        
        .checkmark {
            width: 20px;
            height: 20px;
            background: #00cc66;
            color: white;
            text-align: center;
            line-height: 20px;
            border-radius: 3px;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .data-meter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .meter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .meter-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #0099cc;
        }
        
        .signature-section {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        .signature-box {
            background: linear-gradient(45deg, #0099cc, #00ccff);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .signature-label {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .signature-name {
            border-top: 2px solid rgba(255,255,255,0.5);
            padding-top: 10px;
            margin-top: auto;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            font-size: 9pt;
            text-align: center;
            color: #666;
            font-style: italic;
        }
        
        .code-corner {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 8pt;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-section" style="justify-content: flex-end;">
                @if(file_exists($logo_path))
                    <img src="{{ $logo_path }}" alt="PGN Logo" class="logo">
                @endif
                <div class="company-name">
                    PERTAMINA<br>
                    <span style="font-size: 10pt; color: #666;">GAS NEGARA</span>
                </div>
            </div>
        </div>

        <div class="title">BERITA ACARA GAS IN</div>

        <div class="date-badge">
            <strong>{{ app(App\Services\BeritaAcaraService::class)->formatDateIndonesian($date)['full'] }}</strong><br>
            <small>telah dilakukan penyalaan Gas pertama kali Tanggal Dimulai :</small>
        </div>

        <div class="info-section">
            <div class="info-title">Informasi Pelanggan</div>
            <div class="info-grid">
                <div>
                    <div class="info-item">
                        <div class="info-label">No. ID Pelanggan</div>
                        <div class="info-value">{{ $gasIn->reff_id_pelanggan }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Alamat</div>
                        <div class="info-value">{{ $customer->alamat ?? '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Kelurahan</div>
                        <div class="info-value">{{ $customer->kelurahan ?? '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Kota/Kabupaten</div>
                        <div class="info-value">{{ $customer->kota ?? 'Depok' }}</div>
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value">{{ $customer->nama_pelanggan ?? '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">RT</div>
                        <div class="info-value">{{ $customer->rt ?? '1' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">RW</div>
                        <div class="info-value">{{ $customer->rw ?? '5' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Kecamatan</div>
                        <div class="info-value">{{ $customer->kecamatan ?? 'Sukmajaya' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Provinsi</div>
                        <div class="info-value">{{ $customer->provinsi ?? 'Jawa Barat' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Kode Pos</div>
                        <div class="info-value">{{ $customer->kode_pos ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="checklist-section">
            <div class="checklist-title">Bersama ini telah dilakukan hal-hal sbb:</div>
            <div class="checklist-item">
                <div class="checkmark">✓</div>
                <div><strong>Berita Acara Hasil Pengujian pipa Instalasi</strong> <span style="float: right;"><strong>OK</strong> &nbsp; Tersedia &nbsp; <span class="checkmark">✓</span></span></div>
            </div>
            <div class="checklist-item">
                <div class="checkmark">✓</div>
                <div><strong>Tersedia prosedur Gas In ke peralatan gas milik pelanggan</strong> <span style="float: right;"><strong>OK</strong> &nbsp; Terlaksana &nbsp; <span class="checkmark">✓</span></span></div>
            </div>
            <div class="checklist-item">
                <div class="checkmark">✓</div>
                <div><strong>Tersedia perlengkapan KSKR yang memadai</strong> <span style="float: right;"><strong>OK</strong> &nbsp; Tersedia &nbsp; <span class="checkmark">✓</span></span></div>
            </div>
            <div class="checklist-item">
                <div class="checkmark">✓</div>
                <div><strong>Sosialisasi pengoperasian & pemeliharaan kepada pelanggan</strong> <span style="float: right;"><strong>OK</strong> &nbsp; Terlaksana &nbsp; <span class="checkmark">✓</span></span></div>
            </div>
            <div class="checklist-item">
                <div class="checkmark">✓</div>
                <div><strong>Meter terkalibrasi</strong> <span style="float: right;"><strong>OK</strong> &nbsp; Terlaksana &nbsp; <span class="checkmark">✓</span></span></div>
            </div>
        </div>

        <div class="data-meter-section">
            <div class="checklist-title" style="margin-bottom: 15px;">Data Meter Pemasangan:</div>
            <div class="meter-grid">
                <div class="meter-item">
                    <div class="info-label">Jenis Meter</div>
                    <div class="info-value">{{ $gasIn->jenis_meter ?? 'Meter konvensional / Smart Meter' }}</div>
                </div>
                <div class="meter-item">
                    <div class="info-label">SN Meter</div>
                    <div class="info-value">{{ $gasIn->sn_meter ?? '0156203' }}</div>
                </div>
                <div class="meter-item">
                    <div class="info-label">Stand meter awal</div>
                    <div class="info-value">{{ $gasIn->stand_meter_awal ?? '00103' }}</div>
                </div>
                <div class="meter-item">
                    <div class="info-label">Terkalibrasi</div>
                    <div class="info-value">{{ $gasIn->terkalibrasi ?? 'Bar' }}</div>
                </div>
                <div class="meter-item">
                    <div class="info-label">Suhu</div>
                    <div class="info-value">{{ $gasIn->suhu ?? '°C' }}</div>
                </div>
                <div class="meter-item">
                    <div class="info-label">Awal Kalibrasi</div>
                    <div class="info-value">{{ $gasIn->awal_kalibrasi ?? '(MM-YYYY)' }}</div>
                </div>
            </div>
        </div>

        <div style="margin: 20px 0; font-size: 10pt; text-align: justify;">
            <strong>Dengan dilakukannya Gas In ini, maka Pelanggan menyatakan untuk bertanggung jawab atas pengoperasian dan perawatan instalasi internal, barang/komponen instalasi/peralatan yang telah dipasang dan bersedia melakukan pembayaran/tagihan.</strong>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">Pelanggan</div>
                <div class="signature-name">{{ $customer->nama_pelanggan ?? '.....................' }}</div>
            </div>
            <div class="signature-box">
                <div class="signature-label">Petugas</div>
                <div class="signature-name">{{ $gasIn->createdBy->name ?? '.....................' }}</div>
            </div>
        </div>

        <div class="footer">
            Terima kasih atas kepercayaan Standart kepada kami,<br>
            <strong>PT Perusahaan Gas Negara Tbk.</strong>
        </div>

        <div class="code-corner">
            Q-001/06.02F/F05
        </div>
    </div>
</body>
</html>