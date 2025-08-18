<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CalonPelangganTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'Nama',
            'Nomor Ponsel',
            'Alamat',
            'RT',
            'RW',
            'Kelurahan',
            'Padukuhan',
            'ID REFF.',
        ];
    }

    public function array(): array
    {
        return [[
            'Budi',
            '08123456789',
            'Jl. Melati No. 10',
            '01',
            '02',
            'Condongcatur',
            'Karanganyar',
            'REF-0001',
        ]];
    }
}
