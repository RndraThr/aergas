<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service untuk generate PDF dari template dengan mapping koordinat
 */
class PdfTemplateService
{
    protected string $templatesPath;
    protected string $outputPath;

    public function __construct()
    {
        $this->templatesPath = storage_path('app/templates');
        $this->outputPath = storage_path('app/generated');

        if (!file_exists($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }
    }

    /**
     * Generate Berita Acara MGRT dari template PDF
     * Menerima CalonPelanggan langsung (tidak perlu SR data)
     * 
     * @param \App\Models\CalonPelanggan $customer
     * @return array
     */
    public function generateBaMgrt($customer): array
    {
        try {
            if (!$customer) {
                throw new \Exception('Data pelanggan tidak ditemukan');
            }

            $templateFile = $this->templatesPath . '/BERITA ACARA MGRT.pdf';

            if (!file_exists($templateFile)) {
                throw new \Exception('Template PDF tidak ditemukan: ' . $templateFile);
            }

            // Create FPDI instance
            $pdf = new Fpdi();

            // Define font path if not already defined
            if (!defined('FPDF_FONTPATH')) {
                define('FPDF_FONTPATH', storage_path('fonts'));
            }

            // Import first page from template
            $pageCount = $pdf->setSourceFile($templateFile);
            $templateId = $pdf->importPage(1);

            // Get template size
            $size = $pdf->getTemplateSize($templateId);

            // Add page with same size as template
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);

            // Use template as background
            $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

            // Set font for writing text
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0, 0, 0);

            // Tahun (tahun saat print)
            $pdf->SetXY(126, 44);
            $pdf->Cell(30, 5, Carbon::now()->format('Y'));

            // =========================================
            // MAPPING KOORDINAT - SESUAIKAN DENGAN PDF
            // Format: $pdf->SetXY(X, Y); $pdf->Cell(width, height, 'text');
            // Koordinat (0,0) = pojok kiri atas, satuan mm
            // =========================================

            // No Reff ID
            $pdf->SetXY(67, 69.5);
            $pdf->Cell(60, 5, $this->formatReffId($customer->reff_id_pelanggan));

            // Nama Calon Pelanggan (jarak ~7mm dari reff id)
            $pdf->SetXY(67, 81);
            $pdf->Cell(100, 5, $customer->nama_pelanggan ?? '-');

            // Alamat (jarak ~7mm dari nama)
            $pdf->SetXY(67, 92.5);
            $pdf->Cell(120, 5, $customer->alamat ?? '-');

            // Kelurahan (jarak ~7mm dari alamat)
            $pdf->SetXY(67, 104);
            $pdf->Cell(80, 5, $customer->kelurahan ?? '-');


            // =========================================
            // TANDA TANGAN - Nama Customer (Center Aligned)
            // Cell(width, height, text, border, ln, align)
            // align: 'L'=left, 'C'=center, 'R'=right
            // =========================================
            // Sesuaikan koordinat X, Y dan width sesuai area tanda tangan di PDF
            $pdf->SetXY(41, 215.5);  // Koordinat area tanda tangan (sesuaikan!)
            $pdf->Cell(50, 5, '( ' . ($customer->nama_pelanggan ?? '                                              ') . ' )', 0, 0, 'C');

            // If there are more pages in template, import them
            for ($i = 2; $i <= $pageCount; $i++) {
                $templateId = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);
            }

            // Generate filename
            // Sanitize Reff ID to remove invalid filename characters
            $safeReffId = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $customer->reff_id_pelanggan);
            $filename = sprintf(
                'BA_MGRT_%s_%s_%s.pdf',
                str_replace('.', '_', $safeReffId),
                $customer->id,
                Carbon::now()->format('Ymd_His')
            );
            $outputFile = $this->outputPath . '/' . $filename;

            // Output PDF
            $pdf->Output($outputFile, 'F');

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $outputFile
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate BA MGRT from PDF template', [
                'customer_id' => $customer->reff_id_pelanggan ?? null,
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
     * Generate Isometrik SR dari template PDF
     * Menerima CalonPelanggan langsung
     * 
     * @param \App\Models\CalonPelanggan $customer
     * @return array
     */
    public function generateIsometrikSr($customer): array
    {
        try {
            if (!$customer) {
                throw new \Exception('Data pelanggan tidak ditemukan');
            }

            $templateFile = $this->templatesPath . '/ISOMETRIK SR.pdf';

            if (!file_exists($templateFile)) {
                throw new \Exception('Template PDF tidak ditemukan: ' . $templateFile);
            }

            // Define font path if not already defined
            if (!defined('FPDF_FONTPATH')) {
                define('FPDF_FONTPATH', storage_path('fonts'));
            }

            // Create FPDI instance
            $pdf = new Fpdi();

            // Add custom fonts
            $pdf->AddFont('Calibri', '', 'calibri.php');
            $pdf->AddFont('Calibri', 'B', 'calibrib.php');

            // Import first page from template
            $pageCount = $pdf->setSourceFile($templateFile);
            $templateId = $pdf->importPage(1);

            // Get template size
            $size = $pdf->getTemplateSize($templateId);

            // Add page with same size as template
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);

            // Use template as background
            $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

            // Set font for writing text
            $pdf->SetFont('Calibri', '', 10);
            $pdf->SetTextColor(0, 0, 0);

            // =========================================
            // MAPPING KOORDINAT ISOMETRIK SR
            // Silakan sesuaikan X dan Y berdasarkan template
            // =========================================

            // NO REG (Reff_id)
            $pdf->SetXY(226, 15.5); // Placeholder coordinate
            $pdf->Cell(60, 5, $this->formatReffId($customer->reff_id_pelanggan));

            // NAMA
            $pdf->SetXY(226, 24.10); // Placeholder coordinate
            $pdf->Cell(100, 5, $customer->nama_pelanggan ?? '');

            // ALAMAT
            $pdf->SetXY(226, 29.25); // Coordinate
            // Angka 58 = Lebar text area
            // Angka 3.5 = Tinggi per baris (Spasi antar baris). Ubah angka ini untuk mengatur kerapatan.
            $pdf->MultiCell(58, 3.5, $customer->alamat ?? '', 0, 'L');

            // SEKTOR (Padukuhan)
            $pdf->SetXY(226, 36.5); // Placeholder coordinate
            $pdf->Cell(80, 5, $customer->padukuhan ?? '');

            // RT
            $pdf->SetXY(261.5, 36.5); // Placeholder coordinate
            $pdf->Cell(30, 5, $customer->rt ?? '');

            // RW
            $pdf->SetXY(276, 36.5); // Placeholder coordinate
            $pdf->Cell(30, 5, $customer->rw ?? '');

            // KELURAHAN
            $pdf->SetXY(226, 40.75); // Placeholder coordinate
            $pdf->Cell(80, 5, $customer->kelurahan ?? '');


            // If there are more pages in template, import them
            for ($i = 2; $i <= $pageCount; $i++) {
                $templateId = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);
            }

            // Generate filename
            // Sanitize Reff ID to remove invalid filename characters
            $safeReffId = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $customer->reff_id_pelanggan);
            $filename = sprintf(
                'ISOMETRIK_SR_%s_%s_%s.pdf',
                str_replace('.', '_', $safeReffId),
                $customer->id,
                Carbon::now()->format('Ymd_His')
            );
            $outputFile = $this->outputPath . '/' . $filename;

            // Output PDF
            $pdf->Output($outputFile, 'F');

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $outputFile
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate Isometrik SR', [
                'customer_id' => $customer->reff_id_pelanggan ?? null,
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
     * Get coordinate mapping configuration for debugging/adjustment
     */
    public function getCoordinateConfig(): array
    {
        return [
            'tanggal' => ['x' => 45, 'y' => 52],
            'nama_pelanggan' => ['x' => 45, 'y' => 68],
            'alamat' => ['x' => 45, 'y' => 75],
            'kelurahan' => ['x' => 45, 'y' => 82],
            'no_seri_mgrt' => ['x' => 45, 'y' => 95],
            'reff_id_pelanggan' => ['x' => 45, 'y' => 102],
        ];
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

        return sprintf('%d %s %d', $date->day, $months[$date->month], $date->year);
    }

    /**
     * Cleanup old generated files (older than 24 hours)
     */
    public function cleanupOldFiles(): int
    {
        $count = 0;
        $files = glob($this->outputPath . '/*.pdf');
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
