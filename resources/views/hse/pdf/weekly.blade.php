<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Mingguan HSE - {{ $startDate->format('d M') }} s/d {{ $endDate->format('d M Y') }}</title>
    <style>
        @page {
            margin: 15mm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 16pt;
            color: #333;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 10pt;
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
            padding: 6px;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
        }
        table.data td {
            border: 1px solid #333;
            padding: 5px;
            font-size: 8pt;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background-color: #FF6600;
            color: white;
            padding: 8px;
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 10px;
        }
        .stat-box {
            display: inline-block;
            border: 2px solid #FF6600;
            padding: 10px 15px;
            margin: 5px;
            text-align: center;
            min-width: 120px;
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
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }
        .status-draft { background-color: #e0e0e0; padding: 2px 6px; border-radius: 3px; }
        .status-submitted { background-color: #bbdefb; padding: 2px 6px; border-radius: 3px; }
        .status-approved { background-color: #c8e6c9; padding: 2px 6px; border-radius: 3px; }
        .status-rejected { background-color: #ffcdd2; padding: 2px 6px; border-radius: 3px; }
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
        .report-date-header {
            background-color: #f5f5f5;
            padding: 5px 8px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
            border-left: 4px solid #FF6600;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>LAPORAN MINGGUAN HSE</h1>
        <p>Health, Safety & Environment Management System</p>
        <p>Periode: {{ $startDate->format('d F Y') }} - {{ $endDate->format('d F Y') }}</p>
    </div>

    <!-- Statistik Ringkasan -->
    <div class="section" style="text-align: center;">
        <div class="stat-box">
            <div class="stat-label">Total Laporan</div>
            <div class="stat-value">{{ $reports->count() }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Total Pekerja</div>
            <div class="stat-value">{{ $reports->sum('total_pekerja') }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Total JKA</div>
            <div class="stat-value">{{ number_format($reports->sum('jka_hari_ini')) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Rata-rata Pekerja/Hari</div>
            <div class="stat-value">{{ $reports->count() > 0 ? round($reports->sum('total_pekerja') / $reports->count()) : 0 }}</div>
        </div>
    </div>

    <!-- Daftar Laporan Harian -->
    <div class="section">
        <div class="section-title">DAFTAR LAPORAN HARIAN</div>
        <table class="data">
            <thead>
                <tr>
                    <th width="3%">No</th>
                    <th width="12%">Tanggal</th>
                    <th width="8%">Cuaca</th>
                    <th width="10%">Pekerja</th>
                    <th width="12%">JKA</th>
                    <th width="30%">Pekerjaan</th>
                    <th width="15%">Program HSE</th>
                    <th width="10%">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $index => $report)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td style="text-align: center;">{{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('d/m/Y') }}</td>
                    <td style="text-align: center;">{!! $report->getCuacaIcon() !!}</td>
                    <td style="text-align: center;">{{ $report->total_pekerja }} orang</td>
                    <td style="text-align: center;">{{ number_format($report->jka_hari_ini) }} jam</td>
                    <td>
                        @if($report->pekerjaanHarian->count() > 0)
                            @foreach($report->pekerjaanHarian->take(2) as $pekerjaan)
                            • {{ $pekerjaan->jenis_pekerjaan }}<br>
                            @endforeach
                            @if($report->pekerjaanHarian->count() > 2)
                            <em style="color: #666;">+{{ $report->pekerjaanHarian->count() - 2 }} lainnya</em>
                            @endif
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td>
                        @if($report->programHarian->count() > 0)
                            @foreach($report->programHarian->take(2) as $program)
                            • {{ $program->nama_program }}<br>
                            @endforeach
                            @if($report->programHarian->count() > 2)
                            <em style="color: #666;">+{{ $report->programHarian->count() - 2 }} lainnya</em>
                            @endif
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <span class="status-{{ $report->status }}">{{ ucfirst($report->status) }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #999; padding: 20px;">Tidak ada laporan pada periode ini</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Rekapitulasi Tenaga Kerja per Kategori -->
    @if($reports->count() > 0)
    <div class="section">
        <div class="section-title">REKAPITULASI TENAGA KERJA PER KATEGORI</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Kategori</th>
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
                    <td style="text-align: center;">{{ $categoryTotal }} orang</td>
                    <td style="text-align: center;">{{ $totalWorkers > 0 ? round(($categoryTotal / $totalWorkers) * 100, 1) : 0 }}%</td>
                </tr>
                @endforeach
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td>TOTAL</td>
                    <td style="text-align: center;">{{ $totalWorkers }} orang</td>
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
                <div style="margin-bottom: 15px;">
                    <div style="font-weight: bold; color: #FF6600; margin-bottom: 5px; font-size: 9pt;">
                        {{ strtoupper($photosByCategory[$category]->first()->getCategoryLabel()) }}
                    </div>

                    <div class="photo-grid">
                        @foreach($photosByCategory[$category]->chunk(3) as $photoChunk)
                        <div class="photo-row">
                            @foreach($photoChunk as $photo)
                            <div class="photo-cell">
                                <div class="photo-container">
                                    @php
                                        try {
                                            $thumbnailUrl = "https://drive.google.com/thumbnail?id={$photo->drive_file_id}&sz=w600";
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
                                    <div style="background: #f0f0f0; padding: 20px; text-align: center; color: #999; font-size: 7pt;">
                                        Foto tidak dapat dimuat
                                    </div>
                                    @endif
                                </div>
                                <div class="photo-caption">
                                    @if($photo->keterangan)
                                    <div style="margin-bottom: 2px;">{{ $photo->keterangan }}</div>
                                    @endif
                                    <div style="font-size: 6pt; color: #999;">
                                        {{ $photo->uploader->name ?? 'System' }} - {{ $photo->created_at->format('d/m/Y H:i') }}
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
        <p>Laporan digenerate pada {{ now()->format('d F Y, H:i') }} WIB</p>
        <p><em>Dokumen ini dihasilkan secara otomatis oleh sistem AERGAS</em></p>
    </div>
</body>
</html>
