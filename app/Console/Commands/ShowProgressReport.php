<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CalonPelanggan;

class ShowProgressReport extends Command
{
    protected $signature = 'customer:progress-report';
    protected $description = 'Show detailed progress status report for all customers';

    public function handle()
    {
        $this->info('=== CUSTOMER PROGRESS STATUS REPORT ===');
        $this->newLine();

        // Overall statistics
        $allCustomers = CalonPelanggan::where('status', '!=', 'batal')->get();
        $byProgress = $allCustomers->groupBy('progress_status');

        $this->info('OVERVIEW BY PROGRESS STATUS:');
        $this->line(str_repeat('=', 70));

        $tableData = [];
        $progressMap = [
            'validasi' => 0,
            'sk' => 25,
            'sr' => 50,
            'gas_in' => 75,
            'done' => 100
        ];

        foreach ($progressMap as $status => $percentage) {
            $count = $byProgress->get($status)?->count() ?? 0;
            $percent = $allCustomers->count() > 0
                ? round(($count / $allCustomers->count()) * 100, 1)
                : 0;

            $tableData[] = [
                ucfirst($status),
                $count,
                $this->getProgressBar($percent, 30),
                "{$percent}%",
                "{$percentage}% Progress"
            ];
        }

        $this->table(
            ['Status', 'Count', 'Distribution', 'Share', 'Module Progress'],
            $tableData
        );

        $this->line(str_repeat('=', 70));
        $this->info("Total Active Customers: {$allCustomers->count()}");
        $this->newLine();

        // Customers with DONE status (100% completed)
        $doneCustomers = CalonPelanggan::where('progress_status', 'done')
            ->with(['skData', 'srData', 'gasInData'])
            ->get();

        if ($doneCustomers->count() > 0) {
            $this->info("CUSTOMERS WITH 100% COMPLETION (DONE):");
            $this->line(str_repeat('-', 70));

            $doneData = [];
            foreach ($doneCustomers as $customer) {
                $sk = $customer->skData;
                $sr = $customer->srData;
                $gasIn = $customer->gasInData;

                $allCompleted = $sk && $sk->module_status === 'completed'
                    && $sr && $sr->module_status === 'completed'
                    && $gasIn && $gasIn->module_status === 'completed';

                $doneData[] = [
                    $customer->reff_id_pelanggan,
                    substr($customer->nama_pelanggan, 0, 30),
                    $sk?->module_status ?? 'N/A',
                    $sr?->module_status ?? 'N/A',
                    $gasIn?->module_status ?? 'N/A',
                    $allCompleted ? '✓' : '✗'
                ];
            }

            $this->table(
                ['Reff ID', 'Nama', 'SK Status', 'SR Status', 'Gas-In Status', 'Valid'],
                $doneData
            );
        } else {
            $this->warn('No customers have reached 100% completion yet.');
        }

        $this->newLine();

        // Summary
        $validCount = $this->countValidProgress();
        $totalCount = $allCustomers->count();
        $validPercent = $totalCount > 0 ? round(($validCount / $totalCount) * 100, 1) : 0;

        $this->info('VALIDATION SUMMARY:');
        $this->line("✓ Valid Progress Status: {$validCount}/{$totalCount} ({$validPercent}%)");

        if ($validCount === $totalCount) {
            $this->info('All customers have correct progress status!');
        } else {
            $invalidCount = $totalCount - $validCount;
            $this->warn("{$invalidCount} customers need progress status update");
            $this->info('Run: php artisan customer:sync-progress');
        }

        return Command::SUCCESS;
    }

    private function getProgressBar($percentage, $width = 20): string
    {
        $filled = (int) round(($percentage / 100) * $width);
        $empty = $width - $filled;
        return str_repeat('█', $filled) . str_repeat('░', $empty);
    }

    private function countValidProgress(): int
    {
        $customers = CalonPelanggan::with(['skData', 'srData', 'gasInData'])
            ->where('status', '!=', 'batal')
            ->get();

        $validCount = 0;

        foreach ($customers as $customer) {
            $sk = $customer->skData;
            $sr = $customer->srData;
            $gasIn = $customer->gasInData;

            $expected = $this->calculateExpectedProgress($sk, $sr, $gasIn);

            if ($customer->progress_status === $expected) {
                $validCount++;
            }
        }

        return $validCount;
    }

    private function calculateExpectedProgress($sk, $sr, $gasIn): string
    {
        if ($gasIn) {
            if ($gasIn->module_status === 'completed') {
                return 'done';
            }
            if (in_array($gasIn->module_status, ['draft', 'ai_validation', 'tracer_review', 'cgp_review', 'scheduled'])) {
                return 'gas_in';
            }
        }

        if ($sr) {
            if ($sr->module_status === 'completed') {
                return 'gas_in';
            }
            if (in_array($sr->module_status, ['draft', 'ai_validation', 'tracer_review', 'cgp_review', 'scheduled'])) {
                return 'sr';
            }
        }

        if ($sk) {
            if ($sk->module_status === 'completed') {
                return 'sr';
            }
            if (in_array($sk->module_status, ['draft', 'ai_validation', 'tracer_review', 'cgp_review', 'scheduled'])) {
                return 'sk';
            }
        }

        return 'validasi';
    }
}
