<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DocumentTemplateService
{
    /**
     * Path ke folder templates
     */
    protected string $templatesPath;

    /**
     * Path ke folder output sementara
     */
    protected string $outputPath;

    public function __construct()
    {
        $this->templatesPath = storage_path('app/templates');
        $this->outputPath = storage_path('app/generated');

        // Buat folder output jika belum ada
        if (!file_exists($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }
    }

    /**
     * Generate Berita Acara SK dari template Word
     * 
     * Placeholder yang perlu ada di template Word:
     * ${nama_pelanggan}, ${alamat}, ${rt}, ${rw}, ${kelurahan}, ${kecamatan},
     * ${kota}, ${provinsi}, ${reff_id_pelanggan}, ${tanggal}, ${tanggal_sk}, dll
     */
    public function generateBeritaAcaraSK($sk): array
    {
        try {
            $sk->loadMissing('calonPelanggan');
            $customer = $sk->calonPelanggan;

            if (!$customer) {
                throw new \Exception('Data pelanggan tidak ditemukan');
            }

            $templateFile = $this->templatesPath . '/BERTIA ACARA SK.docx';

            if (!file_exists($templateFile)) {
                throw new \Exception('Template Berita Acara SK tidak ditemukan');
            }

            $templateProcessor = new TemplateProcessor($templateFile);

            // Data pelanggan
            $templateProcessor->setValue('nama_pelanggan', $customer->nama_pelanggan ?? '-');
            $templateProcessor->setValue('alamat', $customer->alamat ?? '-');
            $templateProcessor->setValue('rt', $customer->rt ?? '-');
            $templateProcessor->setValue('rw', $customer->rw ?? '-');
            $templateProcessor->setValue('kelurahan', $customer->kelurahan ?? '-');
            $templateProcessor->setValue('kecamatan', $customer->kecamatan ?? '-');
            $templateProcessor->setValue('kota', $customer->kota ?? '-');
            $templateProcessor->setValue('provinsi', $customer->provinsi ?? '-');
            $templateProcessor->setValue('kode_pos', $customer->kode_pos ?? '-');
            $templateProcessor->setValue('latitude', $customer->latitude ?? '-');
            $templateProcessor->setValue('longitude', $customer->longitude ?? '-');
            $templateProcessor->setValue('no_bagi', $customer->no_bagi ?? '-');

            // Data SK
            $templateProcessor->setValue('reff_id_pelanggan', $this->formatReffId($sk->reff_id_pelanggan));

            // Format tanggal Indonesia
            $tanggalSk = $sk->tanggal_sk ? Carbon::parse($sk->tanggal_sk) : Carbon::now();
            $templateProcessor->setValue('tanggal_sk', $this->formatTanggalIndonesia($tanggalSk));
            $templateProcessor->setValue('hari', $this->getNamaHari($tanggalSk));
            $templateProcessor->setValue('tanggal', $tanggalSk->format('d'));
            $templateProcessor->setValue('bulan', $this->getNamaBulan($tanggalSk));
            $templateProcessor->setValue('tahun', $tanggalSk->format('Y'));

            // Data teknis (jika ada di SK)
            $templateProcessor->setValue('panjang_pipa', $sk->panjang_pipa ?? '-');
            $templateProcessor->setValue('mandor', $sk->mandor ?? '-');

            // Generate nama file output
            $filename = sprintf(
                'BA_SK_%s_%s.docx',
                $sk->reff_id_pelanggan,
                Carbon::now()->format('Ymd_His')
            );
            $outputFile = $this->outputPath . '/' . $filename;

            $templateProcessor->saveAs($outputFile);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $outputFile,
                'download_url' => route('documents.download', ['type' => 'ba-sk', 'filename' => $filename])
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate BA SK from template', [
                'sk_id' => $sk->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate Berita Acara MGRT dari template Word dan convert ke PDF
     * 
     * Placeholder di template:
     * ${reff_id_pelanggan}, ${nama_pelanggan}, ${alamat}, ${kelurahan}, ${no_seri_mgrt}
     */
    public function generateBeritaAcaraMGRT($sr, bool $asPdf = true): array
    {
        try {
            $sr->loadMissing('calonPelanggan');
            $customer = $sr->calonPelanggan;

            if (!$customer) {
                throw new \Exception('Data pelanggan tidak ditemukan');
            }

            $templateFile = $this->templatesPath . '/BERITA ACARA MGRT.docx';

            if (!file_exists($templateFile)) {
                throw new \Exception('Template Berita Acara MGRT tidak ditemukan di: ' . $templateFile);
            }

            $templateProcessor = new TemplateProcessor($templateFile);

            // Fill placeholders with data
            $tanggalPemasangan = $sr->tanggal_pemasangan
                ? Carbon::parse($sr->tanggal_pemasangan)
                : Carbon::now();

            $templateProcessor->setValue('reff_id_pelanggan', $this->formatReffId($sr->reff_id_pelanggan));
            $templateProcessor->setValue('nama_pelanggan', $customer->nama_pelanggan ?? '-');
            $templateProcessor->setValue('alamat', $customer->alamat ?? '-');
            $templateProcessor->setValue('rt', $customer->rt ?? '-');
            $templateProcessor->setValue('rw', $customer->rw ?? '-');
            $templateProcessor->setValue('kelurahan', $customer->kelurahan ?? '-');
            $templateProcessor->setValue('kecamatan', $customer->kecamatan ?? '-');
            $templateProcessor->setValue('kota', $customer->kota ?? '-');
            $templateProcessor->setValue('no_seri_mgrt', $sr->no_seri_mgrt ?? '-');
            $templateProcessor->setValue('tanggal', $this->formatTanggalIndonesia($tanggalPemasangan));
            $templateProcessor->setValue('hari', $this->getNamaHari($tanggalPemasangan));
            $templateProcessor->setValue('bulan', $this->getNamaBulan($tanggalPemasangan));
            $templateProcessor->setValue('tahun', $tanggalPemasangan->format('Y'));

            // Generate filename
            $baseFilename = sprintf('BA_MGRT_%s_%s', $sr->reff_id_pelanggan, Carbon::now()->format('Ymd_His'));

            if ($asPdf) {
                // Save as temporary DOCX first
                $tempDocxPath = $this->outputPath . '/' . $baseFilename . '_temp.docx';
                $templateProcessor->saveAs($tempDocxPath);

                // Convert to PDF using PhpWord PDF Writer
                $pdfPath = $this->outputPath . '/' . $baseFilename . '.pdf';
                $this->convertDocxToPdf($tempDocxPath, $pdfPath);

                // Delete temp docx
                if (file_exists($tempDocxPath)) {
                    unlink($tempDocxPath);
                }

                return [
                    'success' => true,
                    'filename' => $baseFilename . '.pdf',
                    'path' => $pdfPath,
                    'mime_type' => 'application/pdf'
                ];
            } else {
                // Save as DOCX
                $docxPath = $this->outputPath . '/' . $baseFilename . '.docx';
                $templateProcessor->saveAs($docxPath);

                return [
                    'success' => true,
                    'filename' => $baseFilename . '.docx',
                    'path' => $docxPath,
                    'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to generate BA MGRT from template', [
                'sr_id' => $sr->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Convert DOCX to PDF using PhpWord PDF Writer
     */
    protected function convertDocxToPdf(string $docxPath, string $pdfPath): void
    {
        // Load the DOCX file
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($docxPath);

        // Configure PDF renderer (use DomPDF which is already installed)
        $rendererName = \PhpOffice\PhpWord\Settings::PDF_RENDERER_DOMPDF;
        $rendererLibraryPath = base_path('vendor/dompdf/dompdf');

        if (!file_exists($rendererLibraryPath)) {
            throw new \Exception('DomPDF library tidak ditemukan');
        }

        \PhpOffice\PhpWord\Settings::setPdfRendererName($rendererName);
        \PhpOffice\PhpWord\Settings::setPdfRendererPath($rendererLibraryPath);

        // Create PDF writer and save
        $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
        $pdfWriter->save($pdfPath);
    }

    /**
     * Generate Isometrik SK dari template Word
     */
    public function generateIsometrikSK($sk): array
    {
        try {
            $sk->loadMissing('calonPelanggan');
            $customer = $sk->calonPelanggan;

            if (!$customer) {
                throw new \Exception('Data pelanggan tidak ditemukan');
            }

            $templateFile = $this->templatesPath . '/ISOMETRIK SK.docx';

            if (!file_exists($templateFile)) {
                throw new \Exception('Template Isometrik SK tidak ditemukan');
            }

            $templateProcessor = new TemplateProcessor($templateFile);

            // Data pelanggan
            $templateProcessor->setValue('nama_pelanggan', $customer->nama_pelanggan ?? '-');
            $templateProcessor->setValue('alamat', $customer->alamat ?? '-');
            $templateProcessor->setValue('reff_id_pelanggan', $this->formatReffId($sk->reff_id_pelanggan));

            // Data SK/teknis
            $templateProcessor->setValue('panjang_pipa', $sk->panjang_pipa ?? '-');
            $templateProcessor->setValue('tanggal_sk', $sk->tanggal_sk ? Carbon::parse($sk->tanggal_sk)->format('d/m/Y') : '-');

            $filename = sprintf(
                'ISOMETRIK_SK_%s_%s.docx',
                $sk->reff_id_pelanggan,
                Carbon::now()->format('Ymd_His')
            );
            $outputFile = $this->outputPath . '/' . $filename;

            $templateProcessor->saveAs($outputFile);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $outputFile
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate Isometrik SK from template', [
                'sk_id' => $sk->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate Isometrik SR dari template Word
     */
    public function generateIsometrikSR($sr): array
    {
        try {
            $sr->loadMissing('calonPelanggan');
            $customer = $sr->calonPelanggan;

            if (!$customer) {
                throw new \Exception('Data pelanggan tidak ditemukan');
            }

            $templateFile = $this->templatesPath . '/ISOMETRIK SR.docx';

            if (!file_exists($templateFile)) {
                throw new \Exception('Template Isometrik SR tidak ditemukan');
            }

            $templateProcessor = new TemplateProcessor($templateFile);

            // Data pelanggan
            $templateProcessor->setValue('nama_pelanggan', $customer->nama_pelanggan ?? '-');
            $templateProcessor->setValue('alamat', $customer->alamat ?? '-');
            $templateProcessor->setValue('reff_id_pelanggan', $this->formatReffId($sr->reff_id_pelanggan));

            // Data SR/teknis
            $templateProcessor->setValue('panjang_pipa', $sr->panjang_pipa ?? '-');
            $templateProcessor->setValue('tanggal_sr', $sr->tanggal_sr ? Carbon::parse($sr->tanggal_sr)->format('d/m/Y') : '-');

            $filename = sprintf(
                'ISOMETRIK_SR_%s_%s.docx',
                $sr->reff_id_pelanggan,
                Carbon::now()->format('Ymd_His')
            );
            $outputFile = $this->outputPath . '/' . $filename;

            $templateProcessor->saveAs($outputFile);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $outputFile
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate Isometrik SR from template', [
                'sr_id' => $sr->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get semua placeholder yang ada di template
     * Berguna untuk debugging dan dokumentasi
     */
    public function getTemplatePlaceholders(string $templateName): array
    {
        try {
            $templateFile = $this->templatesPath . '/' . $templateName;

            if (!file_exists($templateFile)) {
                throw new \Exception("Template {$templateName} tidak ditemukan");
            }

            $templateProcessor = new TemplateProcessor($templateFile);
            $variables = $templateProcessor->getVariables();

            return [
                'success' => true,
                'template' => $templateName,
                'placeholders' => $variables
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Format Reff ID dengan leading zeros
     */
    protected function formatReffId($reffId): string
    {
        if (is_numeric($reffId) && strlen($reffId) < 8) {
            return str_pad($reffId, 8, '0', STR_PAD_LEFT);
        }
        return $reffId ?? '-';
    }

    /**
     * Format tanggal dalam Bahasa Indonesia
     */
    protected function formatTanggalIndonesia(Carbon $date): string
    {
        return sprintf(
            '%s, %d %s %d',
            $this->getNamaHari($date),
            $date->day,
            $this->getNamaBulan($date),
            $date->year
        );
    }

    /**
     * Nama hari dalam Bahasa Indonesia
     */
    protected function getNamaHari(Carbon $date): string
    {
        $days = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];

        return $days[$date->format('l')] ?? $date->format('l');
    }

    /**
     * Nama bulan dalam Bahasa Indonesia
     */
    protected function getNamaBulan(Carbon $date): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        return $months[$date->month] ?? $date->format('F');
    }

    /**
     * Cleanup file yang sudah lama (lebih dari 24 jam)
     */
    public function cleanupOldFiles(): int
    {
        $count = 0;
        $files = glob($this->outputPath . '/*.docx');
        $threshold = Carbon::now()->subHours(24)->timestamp;

        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }
}
