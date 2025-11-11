<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Bulanan HSE - {{ $date->format('F Y') }}</title>
    <style>
        @page {
            margin: 10mm;
            size: A4 landscape;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.2;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px solid #FF6600;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18pt;
            color: #333;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 11pt;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data {
            border: 1px solid #333;
        }
        table.data th {
            background-color: #FF6600;
            color: white;
            border: 1px solid #333;
            padding: 5px;
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
        }
        table.data td {
            border: 1px solid #333;
            padding: 4px;
            font-size: 7pt;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            background-color: #FF6600;
            color: white;
            padding: 6px;
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 8px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        .stat-card {
            border: 2px solid #FF6600;
            padding: 10px;
            text-align: center;
        }
        .stat-label {
            font-size: 8pt;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 16pt;
            font-weight: bold;
            color: #FF6600;
        }
        .stat-unit {
            font-size: 8pt;
            color: #999;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 7pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
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
            width: 24%;
            padding: 2px;
            vertical-align: top;
        }
        .photo-container {
            border: 1px solid #ddd;
            padding: 3px;
            margin-bottom: 5px;
            page-break-inside: avoid;
            min-height: 60px;
            max-height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-container img {
            max-width: 100%;
            max-height: 170px;
            width: auto;
            height: auto;
            display: block;
            object-fit: contain;
        }
        .photo-caption {
            margin-top: 3px;
            font-size: 6pt;
            color: #666;
        }
        .photo-category {
            font-weight: bold;
            color: #FF6600;
            font-size: 7pt;
        }
        .report-date-header {
            background-color: #f5f5f5;
            padding: 4px 6px;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 4px;
            border-left: 3px solid #FF6600;
            font-size: 8pt;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>LAPORAN BULANAN HSE</h1>
        <p>Health, Safety & Environment Management System</p>
        <p><strong>{{ $date->format('F Y') }}</strong></p>
    </div>

    <!-- Statistik Utama -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Laporan</div>
            <div class="stat-value">{{ $stats['total_reports'] }}</div>
            <div class="stat-unit">hari kerja</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Pekerja</div>
            <div class="stat-value">{{ number_format($stats['total_workers']) }}</div>
            <div class="stat-unit">orang</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total JKA</div>
            <div class="stat-value">{{ number_format($stats['total_jka']) }}</div>
            <div class="stat-unit">jam</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Rata-rata Pekerja/Hari</div>
            <div class="stat-value">{{ $stats['avg_workers_per_day'] }}</div>
            <div class="stat-unit">orang</div>
        </div>
    </div>

    <!-- Tabel Laporan Harian -->
    <div class="section">
        <div class="section-title">RINGKASAN LAPORAN HARIAN</div>
        <table class="data">
            <thead>
                <tr>
                    <th width="3%">No</th>
                    <th width="8%">Tanggal</th>
                    <th width="5%">Cuaca</th>
                    <th width="7%">Pekerja</th>
                    <th width="7%">JKA</th>
                    <th width="25%">Jenis Pekerjaan</th>
                    <th width="20%">Program HSE</th>
                    <th width="7%">TBM</th>
                    <th width="8%">Status</th>
                    <th width="10%">Pembuat</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $index => $report)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td style="text-align: center;">{{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('d M') }}</td>
                    <td style="text-align: center; font-size: 10pt;">{!! $report->getCuacaIcon() !!}</td>
                    <td style="text-align: center;">{{ $report->total_pekerja }}</td>
                    <td style="text-align: center;">{{ number_format($report->jka_hari_ini) }}</td>
                    <td>
                        @if($report->pekerjaanHarian->count() > 0)
                            @foreach($report->pekerjaanHarian->take(2) as $pekerjaan)
                            • {{ Str::limit($pekerjaan->jenis_pekerjaan, 25) }}<br>
                            @endforeach
                            @if($report->pekerjaanHarian->count() > 2)
                            <em style="color: #666;">+{{ $report->pekerjaanHarian->count() - 2 }}</em>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($report->programHarian->count() > 0)
                            @foreach($report->programHarian->take(2) as $program)
                            • {{ Str::limit($program->nama_program, 20) }}<br>
                            @endforeach
                            @if($report->programHarian->count() > 2)
                            <em style="color: #666;">+{{ $report->programHarian->count() - 2 }}</em>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @if($report->toolboxMeeting)
                        ✓ {{ $report->toolboxMeeting->jumlah_peserta }} org
                        @else
                        -
                        @endif
                    </td>
                    <td style="text-align: center; font-size: 7pt;">{{ ucfirst($report->status) }}</td>
                    <td style="font-size: 7pt;">{{ Str::limit($report->creator->name ?? 'System', 15) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 15px; color: #999;">Tidak ada laporan bulan ini</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($reports->count() > 0)
    <!-- Rekapitulasi Tenaga Kerja -->
    <div class="section">
        <div class="section-title">REKAPITULASI TENAGA KERJA</div>
        <table class="data" style="width: 50%;">
            <thead>
                <tr>
                    <th>Kategori Team</th>
                    <th>Total Orang</th>
                    <th>Persentase</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalWorkers = $reports->sum('total_pekerja');
                    $categories = ['KSM', 'PGN-CGP', 'OMM'];
                @endphp
                @foreach($categories as $category)
                @php
                    $categoryTotal = $reports->sum(function($report) use ($category) {
                        return $report->tenagaKerja->where('kategori_team', $category)->sum('jumlah_orang');
                    });
                @endphp
                <tr>
                    <td><strong>{{ $category }}</strong></td>
                    <td style="text-align: center;">{{ $categoryTotal }}</td>
                    <td style="text-align: center;">{{ $totalWorkers > 0 ? round(($categoryTotal / $totalWorkers) * 100, 1) : 0 }}%</td>
                </tr>
                @endforeach
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td>TOTAL</td>
                    <td style="text-align: center;">{{ $totalWorkers }}</td>
                    <td style="text-align: center;">100%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Rekapitulasi Status Laporan -->
    <div class="section">
        <div class="section-title">REKAPITULASI STATUS LAPORAN</div>
        <table class="data" style="width: 50%;">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Jumlah</th>
                    <th>Persentase</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $statusCounts = $reports->groupBy('status')->map->count();
                    $totalReports = $reports->count();
                @endphp
                @foreach(['draft', 'submitted', 'approved', 'rejected'] as $status)
                <tr>
                    <td><strong>{{ ucfirst($status) }}</strong></td>
                    <td style="text-align: center;">{{ $statusCounts[$status] ?? 0 }}</td>
                    <td style="text-align: center;">{{ $totalReports > 0 ? round((($statusCounts[$status] ?? 0) / $totalReports) * 100, 1) : 0 }}%</td>
                </tr>
                @endforeach
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td>TOTAL</td>
                    <td style="text-align: center;">{{ $totalReports }}</td>
                    <td style="text-align: center;">100%</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Lampiran Foto Dokumentasi -->
    @php
        $allPhotos = collect();
        foreach($reports as $report) {
            foreach($report->photos as $photo) {
                $allPhotos->push([
                    'photo' => $photo,
                    'report' => $report,
                ]);
            }
        }
    @endphp

    @if($allPhotos->count() > 0)
    <div style="page-break-before: always;"></div>
    <div class="section">
        <div class="section-title">LAMPIRAN FOTO DOKUMENTASI</div>

        @foreach($reports as $report)
            @if($report->photos->count() > 0)
            <div class="report-date-header">
                {{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('d F Y') }}
            </div>

            @php
                $photosByCategory = $report->photos->groupBy('photo_category');
            @endphp

            @foreach(['pekerjaan', 'tbm', 'kondisi_site', 'apd', 'housekeeping', 'incident'] as $category)
                @if($photosByCategory->has($category))
                <div style="margin-bottom: 10px;">
                    <div style="font-weight: bold; color: #FF6600; margin-bottom: 3px; font-size: 8pt;">
                        {{ strtoupper($photosByCategory[$category]->first()->getCategoryLabel()) }}
                    </div>

                    <div class="photo-grid">
                        @foreach($photosByCategory[$category]->chunk(4) as $photoChunk)
                        <div class="photo-row">
                            @foreach($photoChunk as $photo)
                            <div class="photo-cell">
                                <div class="photo-container">
                                    @php
                                        try {
                                            $thumbnailUrl = "https://drive.google.com/thumbnail?id={$photo->drive_file_id}&sz=w400";
                                            $imageContent = @file_get_contents($thumbnailUrl);

                                            if ($imageContent !== false) {
                                                $base64 = base64_encode($imageContent);
                                                $mimeType = 'image/jpeg';
                                                $src = "data:{$mimeType};base64,{$base64}";
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
                                    <div style="background: #f0f0f0; padding: 15px; text-align: center; color: #999; font-size: 6pt;">
                                        Foto tidak dapat dimuat
                                    </div>
                                    @endif
                                </div>
                                <div class="photo-caption">
                                    @if($photo->keterangan)
                                    <div style="margin-bottom: 1px;">{{ Str::limit($photo->keterangan, 30) }}</div>
                                    @endif
                                    <div style="font-size: 5pt; color: #999;">
                                        {{ $photo->created_at->format('d/m H:i') }}
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
            @endif
        @endforeach
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p><strong>PT. KIAN SANTANG MULIATAMA TBK - Health, Safety & Environment Management System</strong></p>
        <p>Laporan digenerate pada {{ now()->format('d F Y, H:i') }} WIB | Dokumen ini dihasilkan secara otomatis oleh sistem AERGAS</p>
    </div>
</body>
</html>
