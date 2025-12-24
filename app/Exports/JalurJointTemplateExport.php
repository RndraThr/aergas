<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class JalurJointTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function headings(): array
    {
        return [
            'joint_number',
            'tanggal_joint',
            'diameter',
            'joint_line_from',
            'joint_line_to',
            'joint_line_optional',
            'tipe_penyambungan',
            'keterangan',
        ];
    }

    public function array(): array
    {
        return [
            [
                'KRG-CP001',
                '2025-01-15',
                '63',
                '63-KRG-LN001',
                '63-KRG-LN002',
                '',
                'EF',
                'Contoh joint Coupler',
            ],
            [
                'GDK-ECP001',
                '2025-01-16',
                '90',
                '90-GDK-LN001',
                '90-GDK-LN002',
                '',
                'BF',
                'Contoh joint End Cap',
            ],
            [
                'KRG-EL90001',
                '2025-01-17',
                '110',
                '110-KRG-LN003',
                '110-KRG-LN004',
                '',
                'EF',
                'Contoh joint Elbow 90',
            ],
            [
                'GDK-TE001',
                '2025-01-18',
                '63',
                '63-GDK-LN005',
                '63-GDK-LN006',
                '63-GDK-LN007',
                'EF',
                'Contoh joint Equal Tee (wajib 3 line)',
            ],
            [
                'KRG-RD001',
                '2025-01-19',
                '160',
                '160-KRG-LN008',
                '160-KRG-LN009',
                '',
                'BF',
                'Contoh joint Reducer',
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,  // joint_number (dengan hyperlink foto)
            'B' => 15,  // tanggal_joint
            'C' => 12,  // diameter
            'D' => 20,  // joint_line_from
            'E' => 20,  // joint_line_to
            'F' => 20,  // joint_line_optional
            'G' => 18,  // tipe_penyambungan
            'H' => 30,  // keterangan
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '7C3AED'], // Purple-600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Data rows styling
        $sheet->getStyle('A2:H6')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Center align for specific columns
        $sheet->getStyle('A2:C6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G2:G6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Add notes/instructions
        $sheet->setCellValue('A8', 'INSTRUKSI PENGISIAN:');
        $sheet->setCellValue('A9', '1. joint_number: Format {CLUSTER}-{FITTING}{CODE}. Contoh: KRG-CP001, GDK-EL90002, KRG-TE003');
        $sheet->setCellValue('A10', '   - PENTING: Cell joint_number harus memiliki HYPERLINK ke Google Drive foto evidence');
        $sheet->setCellValue('A11', '   - Cara: Isi text joint_number → Klik kanan cell → Link/Hyperlink → Paste URL Google Drive');
        $sheet->setCellValue('A12', '2. tanggal_joint: Format YYYY-MM-DD atau tanggal Excel biasa');
        $sheet->setCellValue('A13', '3. diameter: Pilihan: 63, 90, 110, 160, 180, 200 (tanpa tanda petik)');
        $sheet->setCellValue('A14', '4. joint_line_from & joint_line_to: Nomor line yang akan disambung. Harus ada di database dan diameter sama.');
        $sheet->setCellValue('A15', '   - Jika line sudah ada sebelumnya, isi dengan "EXISTING" (tanpa tanda petik)');
        $sheet->setCellValue('A16', '5. joint_line_optional: WAJIB diisi jika menggunakan Equal Tee (TE) untuk koneksi 3-arah. Bisa isi "EXISTING" jika sudah ada.');
        $sheet->setCellValue('A17', '6. tipe_penyambungan: Pilihan: EF atau BF');
        $sheet->setCellValue('A18', '7. keterangan: Optional, untuk catatan tambahan');

        $sheet->getStyle('A8')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => '7C3AED'],
            ],
        ]);

        $sheet->getStyle('A9:A18')->applyFromArray([
            'font' => [
                'size' => 10,
                'color' => ['rgb' => '666666'],
            ],
        ]);

        // Add fitting type reference
        $sheet->setCellValue('A20', 'REFERENSI FITTING TYPE CODE:');
        $sheet->setCellValue('A21', 'CP = Coupler | ECP = End Cap | EL90 = Elbow 90 | EL45 = Elbow 45 | TE = Equal Tee');
        $sheet->setCellValue('A22', 'RD = Reducer | FA = Flange Adaptor | VL = Valve | TF = Transition Fitting | TS = Tapping Saddle');

        $sheet->getStyle('A20')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => '059669'], // Green-600
            ],
        ]);

        $sheet->getStyle('A21:A22')->applyFromArray([
            'font' => [
                'size' => 10,
                'color' => ['rgb' => '059669'],
            ],
        ]);

        return $sheet;
    }
}
