<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Imports\CalonPelangganImport;
use App\Imports\CoordinateImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function formCalonPelanggan()
    {
        return view('imports.calon-pelanggan'); // pastikan view-nya ada
    }

    public function importCalonPelanggan(Request $request)
    {
        $request->validate([
            'file'        => ['required','file','mimes:xlsx,xls,csv'],
            'mode'        => ['required','in:dry-run,commit'],
            'heading_row' => ['nullable','integer','min:1'],
            'save_report' => ['nullable','boolean'],
        ]);

        // anti-timeout & hemat memori
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        DB::connection()->disableQueryLog();

        $dryRun     = $request->input('mode') === 'dry-run';
        $headingRow = (int) $request->input('heading_row', 1);

        $import = new CalonPelangganImport($dryRun, $headingRow);
        Excel::import($import, $request->file('file'));

        $results = $import->getResults();

        if ($request->boolean('save_report')) {
            $path = 'import-reports/calon-pelanggan-'.now()->format('Ymd_His').'.json';
            Storage::disk('local')->put($path, json_encode($results, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            $results['report_path'] = $path;
        }

        return back()->with('import_results', $results);
    }

    // opsional: download file report json
    public function downloadReport(Request $request)
    {
        $path = $request->query('path');
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return response()->download(Storage::disk('local')->path($path), basename($path), [
            'Content-Type' => 'application/json'
        ]);
    }

    public function downloadTemplateCalonPelanggan()
    {
        $templateData = [
            [
                'ID Reff',
                'Nama',
                'Alamat',
                'RT',
                'RW',
                'Nomor Ponsel',
                'Kelurahan',
                'Padukuhan'
            ],
            [
                'ABC001',
                'John Doe',
                'Jl. Malioboro No. 123',
                '01',
                '02',
                '08123456789',
                'Caturtunggal',
                'Mrican'
            ]
        ];

        return Excel::download(new class($templateData) implements
            \Maatwebsite\Excel\Concerns\FromArray,
            \Maatwebsite\Excel\Concerns\WithHeadings
        {
            private $data;

            public function __construct($data) {
                $this->data = $data;
            }

            public function array(): array {
                return array_slice($this->data, 1);
            }

            public function headings(): array {
                return $this->data[0];
            }
        }, 'template_import_calon_pelanggan.xlsx');
    }

    // =============== COORDINATE IMPORT METHODS ===============

    public function formCoordinates()
    {
        return view('imports.coordinates');
    }

    public function importCoordinates(Request $request)
    {
        $request->validate([
            'file'        => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'mode'        => ['required', 'in:dry-run,commit'],
            'heading_row' => ['nullable', 'integer', 'min:1'],
            'save_report' => ['nullable', 'boolean'],
        ]);

        // Anti-timeout & memory optimization
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        DB::connection()->disableQueryLog();

        $dryRun = $request->input('mode') === 'dry-run';
        $headingRow = (int) $request->input('heading_row', 1);

        $import = new CoordinateImport($dryRun, $headingRow);
        Excel::import($import, $request->file('file'));

        $results = $import->getResults();

        if ($request->boolean('save_report')) {
            $path = 'import-reports/coordinates-' . now()->format('Ymd_His') . '.json';
            Storage::disk('local')->put($path, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $results['report_path'] = $path;
        }

        return back()->with('coordinate_import_results', $results);
    }

    public function downloadTemplateCoordinates()
    {
        $templateData = [
            [
                'reff_id',
                'lat',
                'lng'
            ],
            [
                '437024',
                '-7.7650486',
                '110.3845224'
            ],
            [
                '437039',
                '-7.7670702',
                '110.3853741'
            ],
            [
                '437032',
                '-7.7668456',
                '110.3854718'
            ]
        ];

        return Excel::download(new class($templateData) implements
            \Maatwebsite\Excel\Concerns\FromArray,
            \Maatwebsite\Excel\Concerns\WithHeadings
        {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return array_slice($this->data, 1);
            }

            public function headings(): array
            {
                return $this->data[0];
            }
        }, 'template_import_coordinates.xlsx');
    }

}
