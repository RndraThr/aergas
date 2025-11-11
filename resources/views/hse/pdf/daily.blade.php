<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Harian HSE - {{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('d F Y') }}</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #FF6600;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 18pt;
            color: #FF6600;
            text-transform: uppercase;
        }
        .header .subtitle {
            font-size: 11pt;
            color: #666;
            font-weight: bold;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background-color: #FF6600;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 10px;
            border-radius: 3px;
        }
        .field-group {
            margin-bottom: 12px;
        }
        .field-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 3px;
        }
        .field-value {
            color: #333;
            padding-left: 10px;
        }
        .list-item {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #f9f9f9;
            border-left: 3px solid #FF6600;
        }
        .list-number {
            font-weight: bold;
            color: #FF6600;
        }
        .worker-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .worker-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        .worker-table td:first-child {
            font-weight: bold;
            width: 40%;
        }
        .total-row {
            background-color: #FF6600;
            color: white;
            font-weight: bold;
            padding: 8px !important;
        }
        .stats-box {
            background-color: #fff3e6;
            border: 2px solid #FF6600;
            padding: 12px;
            margin: 15px 0;
            text-align: center;
            border-radius: 5px;
        }
        .stats-value {
            font-size: 18pt;
            font-weight: bold;
            color: #FF6600;
        }
        .emergency-contact {
            margin-bottom: 8px;
            padding: 6px;
            background-color: #f5f5f5;
            border-radius: 3px;
        }
        .emergency-name {
            font-weight: bold;
            color: #333;
        }
        .emergency-phone {
            color: #FF6600;
        }
        .photo-section {
            page-break-before: always;
        }
        .photo-grid {
            display: table;
            width: 100%;
            margin-top: 10px;
        }
        .photo-row {
            display: table-row;
        }
        .photo-cell {
            display: table-cell;
            width: 32%;
            padding: 3px;
            vertical-align: top;
        }
        .photo-container {
            border: 1px solid #ddd;
            padding: 4px;
            margin-bottom: 8px;
            page-break-inside: avoid;
            min-height: 80px;
            max-height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-container img {
            max-width: 100%;
            max-height: 240px;
            width: auto;
            height: auto;
            display: block;
            object-fit: contain;
        }
        .photo-caption {
            margin-top: 8px;
            padding-top: 5px;
            font-size: 7pt;
            color: #666;
        }
        .photo-category {
            font-weight: bold;
            color: #FF6600;
            font-size: 8pt;
            margin-bottom: 3px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        ol, ul {
            margin: 5px 0;
            padding-left: 25px;
        }
        li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>LAPORAN HARIAN HSE</h1>
        <div class="subtitle">Health, Safety & Environment Management System</div>
    </div>

    <!-- Proyek -->
    <div class="section">
        <div class="section-title">PROYEK</div>
        <div class="field-value" style="font-size: 11pt; font-weight: bold;">
            {{ $report->nama_proyek }}
        </div>
    </div>

    <!-- Informasi Umum -->
    <div class="section">
        <div class="field-group">
            <div class="field-label">Pemberi Pekerjaan :</div>
            <div class="field-value">{{ $report->pemberi_pekerjaan }}</div>
        </div>

        <div class="field-group">
            <div class="field-label">Kontraktor :</div>
            <div class="field-value">{{ $report->kontraktor }}</div>
        </div>

        <div class="field-group">
            <div class="field-label">Sub Kontraktor / Mitra :</div>
            <div class="field-value">{{ $report->sub_kontraktor ?? '-' }}</div>
        </div>

        <div class="field-group">
            <div class="field-label">Tanggal :</div>
            <div class="field-value">{{ \Carbon\Carbon::parse($report->tanggal_laporan)->isoFormat('dddd, D MMMM YYYY') }}</div>
        </div>

        <div class="field-group">
            <div class="field-label">Cuaca :</div>
            <div class="field-value">{{ ucfirst(str_replace('_', ' ', $report->cuaca)) }}</div>
        </div>
    </div>

    <!-- Pekerjaan & Lokasi -->
    <div class="section">
        <div class="section-title">PEKERJAAN & LOKASI</div>
        @foreach($report->pekerjaanHarian as $index => $pekerjaan)
        <div class="list-item">
            <div class="list-number">{{ $index + 1 }}. {{ $pekerjaan->jenis_pekerjaan }}</div>
            <div style="margin-top: 5px;">{{ $pekerjaan->deskripsi_pekerjaan }}</div>
            <div style="margin-top: 5px; font-style: italic; color: #666;">
                *{{ $pekerjaan->lokasi_detail }}
            </div>
            @if($pekerjaan->google_maps_link)
            <div style="margin-top: 3px; font-size: 9pt; color: #0066cc;">
                {{ $pekerjaan->google_maps_link }}
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Jumlah Tenaga Kerja -->
    <div class="section">
        <div class="section-title">JUMLAH TENAGA KERJA</div>

        @php
            $workersByCategory = $report->tenagaKerja->groupBy('kategori_team');
        @endphp

        @foreach(['PGN-CGP', 'OMM', 'KSM'] as $category)
            @if($workersByCategory->has($category))
            <div style="margin-bottom: 15px;">
                <div style="font-weight: bold; margin-bottom: 8px; color: #FF6600;">Team {{ $category }}</div>
                <table class="worker-table">
                    @foreach($workersByCategory[$category] as $index => $worker)
                    <tr>
                        <td>{{ $index + 1 }}. {{ $worker->role_name }}</td>
                        <td style="text-align: right;">: {{ $worker->jumlah_orang }} Orang</td>
                    </tr>
                    @endforeach
                </table>
            </div>
            @endif
        @endforeach

        <div style="margin-top: 15px; padding: 10px; background-color: #333; color: white; text-align: center; font-weight: bold; border-radius: 3px;">
            TOTAL : {{ $report->total_pekerja }} ORANG
        </div>
    </div>

    <!-- JKA -->
    <div class="section">
        <div class="stats-box">
            <div style="margin-bottom: 5px;">Total JKA Hari ini tanggal {{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('d F Y') }}</div>
            <div class="stats-value">{{ $report->total_pekerja }} orang = {{ number_format($report->jka_hari_ini) }} jam</div>
        </div>
        <div class="stats-box">
            <div style="margin-bottom: 5px;">Total Jam Kerja Aman Kumulatif</div>
            <div class="stats-value">{{ number_format($report->jka_kumulatif) }} Jam</div>
        </div>
    </div>

    <!-- Emergency Contacts -->
    @if(isset($emergencyContacts) && $emergencyContacts->count() > 0)
    <div class="section">
        <div class="section-title">PETUGAS TANGGAP DARURAT DAN EVAKUASI TIER 1</div>

        @foreach($emergencyContacts as $contact)
        <div class="emergency-contact">
            <div class="emergency-name">{{ $contact->jabatan }} :</div>
            <div>{{ $contact->nama_petugas }}</div>
            <div class="emergency-phone">({{ $contact->nomor_telepon }})</div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Program HSE -->
    @if($report->programHarian->count() > 0)
    <div class="section">
        <div class="section-title">PROGRAM HSE</div>
        <ol>
            @foreach($report->programHarian as $program)
            <li>{{ $program->nama_program }}</li>
            @endforeach
        </ol>
    </div>
    @endif

    <!-- TBM -->
    @if($report->toolboxMeeting)
    <div class="section">
        <div class="section-title">MATERI TBM (TOOLBOX MEETING)</div>
        <div class="field-group">
            <div class="field-label">Waktu Pelaksanaan :</div>
            <div class="field-value">{{ \Carbon\Carbon::parse($report->toolboxMeeting->waktu_mulai)->format('H:i') }} WIB</div>
        </div>
        <div class="field-group">
            <div class="field-label">Jumlah Peserta :</div>
            <div class="field-value">{{ $report->toolboxMeeting->jumlah_peserta }} Orang</div>
        </div>
        <div style="margin-top: 10px;">
            <ol>
                @foreach($report->toolboxMeeting->materiList->sortBy('urutan') as $materi)
                <li>{{ $materi->materi_pembahasan }}</li>
                @endforeach
            </ol>
        </div>
    </div>
    @endif

    @if($report->catatan)
    <div class="section">
        <div class="section-title">CATATAN</div>
        <div class="field-value">{{ $report->catatan }}</div>
    </div>
    @endif

    <!-- Footer Page 1 -->
    <div class="footer">
        <div>Laporan dibuat oleh: <strong>{{ $report->creator->name ?? 'System' }}</strong></div>
        <div>{{ $report->created_at->format('d F Y, H:i') }} WIB</div>
        @if($report->status == 'approved')
        <div style="margin-top: 5px; color: #28a745;">
            ✓ Disetujui oleh: <strong>{{ $report->approver->name ?? '-' }}</strong> pada {{ \Carbon\Carbon::parse($report->approved_at)->format('d F Y, H:i') }} WIB
        </div>
        @endif
    </div>

    <!-- LAMPIRAN FOTO (Halaman Baru) -->
    @if($report->photos->count() > 0)
    <div class="photo-section">
        <div class="header">
            <h1>LAMPIRAN FOTO DOKUMENTASI</h1>
            <div class="subtitle">{{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('d F Y') }}</div>
        </div>

        @php
            $photosByCategory = $report->photos->groupBy('photo_category');
        @endphp

        @foreach(['pekerjaan', 'tbm', 'kondisi_site', 'apd', 'housekeeping', 'incident'] as $category)
            @if($photosByCategory->has($category))
            <div class="section">
                <div class="section-title">FOTO {{ strtoupper($photosByCategory[$category]->first()->getCategoryLabel()) }}</div>

                <div class="photo-grid">
                    @foreach($photosByCategory[$category]->chunk(3) as $photoChunk)
                    <div class="photo-row">
                        @foreach($photoChunk as $photo)
                        <div class="photo-cell">
                            <div class="photo-container">
                                @php
                                    // Get image from Google Drive and convert to base64
                                    // Use smaller thumbnail size (w600) to reduce memory usage
                                    try {
                                        $thumbnailUrl = "https://drive.google.com/thumbnail?id={$photo->drive_file_id}&sz=w600";
                                        $imageContent = @file_get_contents($thumbnailUrl);

                                        if ($imageContent !== false) {
                                            $base64 = base64_encode($imageContent);
                                            $mimeType = 'image/jpeg'; // Google Drive thumbnails are JPEG
                                            $src = "data:{$mimeType};base64,{$base64}";

                                            // Free memory immediately
                                            unset($imageContent);
                                        } else {
                                            $src = '';
                                        }
                                    } catch (\Exception $e) {
                                        $src = '';
                                    }
                                @endphp

                                @if($src)
                                <img src="{{ $src }}" alt="{{ $photo->getCategoryLabel() }}">
                                @else
                                <div style="background: #f0f0f0; padding: 40px; text-align: center; color: #999;">
                                    Foto tidak dapat dimuat
                                </div>
                                @endif

                                <div class="photo-caption">
                                    <div class="photo-category">{{ $photo->getCategoryLabel() }}</div>
                                    @if($photo->keterangan)
                                    <div>{{ $photo->keterangan }}</div>
                                    @endif
                                    <div style="font-size: 7pt; color: #999; margin-top: 5px;">
                                        Upload: {{ $photo->uploader->name ?? 'System' }} - {{ $photo->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    <!-- Final Footer -->
    <div class="footer" style="margin-top: 40px;">
        <div style="font-weight: bold; color: #FF6600;">PT. KIAN SANTANG MULIATAMA TBK</div>
        <div>Health, Safety & Environment Management System</div>
        <div style="margin-top: 5px; font-size: 8pt;">
            Dokumen ini dihasilkan secara otomatis oleh sistem AERGAS
        </div>
    </div>
</body>
</html>
