<?php

namespace App\Services;

use App\Models\SkData;
use App\Models\SrData;
use App\Models\GasInData;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BeritaAcaraService
{
    /**
     * Generate Berita Acara SK (Sambungan Kompor dan Peralatan Gas)
     */
    public function generateSkBeritaAcara(SkData $sk): array
    {
        try {
            $sk->loadMissing('calonPelanggan');
            
            if (!$sk->calonPelanggan) {
                throw new \Exception('Data pelanggan tidak ditemukan');
            }

            $data = [
                'title' => 'Berita Acara Sambungan Kompor dan Peralatan Gas',
                'date' => Carbon::now(),
                'customer' => $sk->calonPelanggan,
                'sk' => $sk,
                'type' => 'sk',
                'logo_path' => public_path('assets/PGN.png')
            ];

            $pdf = Pdf::loadView('documents.berita-acara-sk', $data);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = sprintf('BA_SK_%s_%s.pdf', 
                $sk->reff_id_pelanggan,
                Carbon::now()->format('Ymd_His')
            );

            return [
                'success' => true,
                'filename' => $filename,
                'pdf' => $pdf
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate SK Berita Acara', [
                'sk_id' => $sk->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate Berita Acara SR (Service Regulator)
     */
    public function generateSrBeritaAcara(SrData $sr): array
    {
        try {
            $sr->loadMissing('calonPelanggan');
            
            if (!$sr->calonPelanggan) {
                throw new \Exception('Data pelanggan tidak ditemukan');
            }

            $data = [
                'title' => 'Berita Acara Service Regulator',
                'date' => Carbon::now(),
                'customer' => $sr->calonPelanggan,
                'sr' => $sr,
                'type' => 'sr',
                'logo_path' => public_path('assets/PGN.png')
            ];

            $pdf = Pdf::loadView('documents.berita-acara-sr', $data);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = sprintf('BA_SR_%s_%s.pdf', 
                $sr->reff_id_pelanggan,
                Carbon::now()->format('Ymd_His')
            );

            return [
                'success' => true,
                'filename' => $filename,
                'pdf' => $pdf
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate SR Berita Acara', [
                'sr_id' => $sr->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate Berita Acara Gas In
     */
    public function generateGasInBeritaAcara(GasInData $gasIn): array
    {
        try {
            $gasIn->loadMissing('calonPelanggan');
            
            if (!$gasIn->calonPelanggan) {
                throw new \Exception('Data pelanggan tidak ditemukan');
            }

            $data = [
                'title' => 'Berita Acara Gas In',
                'date' => Carbon::now(),
                'customer' => $gasIn->calonPelanggan,
                'gasIn' => $gasIn,
                'type' => 'gas_in',
                'logo_path' => public_path('assets/PGN.png')
            ];

            $pdf = Pdf::loadView('documents.berita-acara-gas-in', $data);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = sprintf('BA_GasIn_%s_%s.pdf', 
                $gasIn->reff_id_pelanggan,
                Carbon::now()->format('Ymd_His')
            );

            return [
                'success' => true,
                'filename' => $filename,
                'pdf' => $pdf
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate Gas In Berita Acara', [
                'gas_in_id' => $gasIn->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get Indonesian day name
     */
    public function getIndonesianDayName(Carbon $date): string
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
     * Get Indonesian month name
     */
    public function getIndonesianMonthName(Carbon $date): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$date->month] ?? $date->format('F');
    }

    /**
     * Format date for Berita Acara (Indonesian format)
     */
    public function formatDateIndonesian(Carbon $date): array
    {
        return [
            'day_name' => $this->getIndonesianDayName($date),
            'day' => $date->format('j'),
            'month' => $this->getIndonesianMonthName($date),
            'year' => $date->format('Y'),
            'full' => sprintf('%s %d-%d-%d', 
                $this->getIndonesianDayName($date),
                $date->format('j'),
                $date->format('n'),
                $date->format('Y')
            )
        ];
    }
}