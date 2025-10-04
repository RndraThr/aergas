<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CalonPelanggan;

class VerifyCustomerProgress extends Command
{
    protected $signature = 'customer:verify-progress {--limit=20 : Number of customers to display}';
    protected $description = 'Verify customer progress status matches their module completion';

    public function handle()
    {
        $this->info('=== CUSTOMER PROGRESS STATUS VERIFICATION ===');
        $this->newLine();

        $limit = (int) $this->option('limit');

        // Get sample customers
        $customers = CalonPelanggan::with(['skData', 'srData', 'gasInData'])
            ->where('status', '!=', 'batal')
            ->limit($limit)
            ->get();

        $this->info("Checking {$customers->count()} sample customers...");
        $this->newLine();

        $tableData = [];
        $totalValid = 0;
        $totalInvalid = 0;

        foreach ($customers as $customer) {
            $sk = $customer->skData;
            $sr = $customer->srData;
            $gasIn = $customer->gasInData;

            // Calculate expected progress
            $expected = $this->calculateExpectedProgress($sk, $sr, $gasIn);
            $isValid = $customer->progress_status === $expected;

            if ($isValid) {
                $totalValid++;
            } else {
                $totalInvalid++;
            }

            $tableData[] = [
                $customer->reff_id_pelanggan,
                substr($customer->nama_pelanggan, 0, 25),
                $customer->progress_status,
                $expected,
                $sk?->module_status ?? '-',
                $sr?->module_status ?? '-',
                $gasIn?->module_status ?? '-',
                $isValid ? '✓' : '✗'
            ];
        }

        $this->table(
            ['Reff ID', 'Nama', 'Current', 'Expected', 'SK', 'SR', 'Gas-In', 'Valid'],
            $tableData
        );

        $this->newLine();
        $this->info("Valid: {$totalValid} | Invalid: {$totalInvalid}");
        $this->newLine();

        // Show overall statistics
        $this->showStatistics();

        return $totalInvalid === 0 ? Command::SUCCESS : Command::FAILURE;
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

    private function showStatistics()
    {
        $this->info('OVERALL STATISTICS:');
        $this->line(str_repeat('-', 50));

        $allCustomers = CalonPelanggan::where('status', '!=', 'batal')->get();
        $statusCount = $allCustomers->groupBy('progress_status');

        $data = [];
        foreach (['validasi', 'sk', 'sr', 'gas_in', 'done'] as $status) {
            $count = $statusCount->get($status)?->count() ?? 0;
            $percentage = $allCustomers->count() > 0
                ? round(($count / $allCustomers->count()) * 100, 1)
                : 0;

            $data[] = [
                $status,
                $count,
                $this->getProgressBar($percentage),
                "{$percentage}%"
            ];
        }

        $this->table(
            ['Status', 'Count', 'Distribution', '%'],
            $data
        );

        $this->line(str_repeat('-', 50));
        $this->info("Total: {$allCustomers->count()} active customers");
    }

    private function getProgressBar($percentage, $width = 20): string
    {
        $filled = (int) round(($percentage / 100) * $width);
        $empty = $width - $filled;
        return str_repeat('█', $filled) . str_repeat('░', $empty);
    }
}
