<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CalonPelanggan;

class SyncCustomerProgressStatus extends Command
{
    protected $signature = 'customer:sync-progress {--dry-run : Run without making changes} {--reff-id= : Sync specific customer by reff_id}';
    protected $description = 'Sync customer progress_status based on actual module completion status';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $reffId = $this->option('reff-id');

        $this->info('=== SYNCING CUSTOMER PROGRESS STATUS ===');
        $this->newLine();

        // Get customers
        $query = CalonPelanggan::with(['skData', 'srData', 'gasInData'])
            ->where('status', '!=', 'batal');

        if ($reffId) {
            $query->where('reff_id_pelanggan', $reffId);
        }

        $customers = $query->get();

        if ($customers->isEmpty()) {
            $this->warn('No customers found.');
            return;
        }

        $this->info("Total customers to check: {$customers->count()}");
        $this->newLine();

        $mismatched = [];
        $fixed = 0;

        foreach ($customers as $customer) {
            $sk = $customer->skData;
            $sr = $customer->srData;
            $gasIn = $customer->gasInData;

            // Determine expected progress based on module status
            $expected = $this->determineExpectedProgress($sk, $sr, $gasIn);

            if ($customer->progress_status !== $expected) {
                $mismatched[] = [
                    'customer' => $customer,
                    'current' => $customer->progress_status,
                    'expected' => $expected,
                    'sk_status' => $sk?->module_status ?? '-',
                    'sr_status' => $sr?->module_status ?? '-',
                    'gas_in_status' => $gasIn?->module_status ?? '-',
                ];

                if (!$dryRun) {
                    $customer->progress_status = $expected;
                    $customer->save();
                    $fixed++;
                }
            }
        }

        // Display results
        if (empty($mismatched)) {
            $this->info('✓ All customers have correct progress_status!');
            return;
        }

        $this->warn("Found " . count($mismatched) . " customers with mismatched progress_status:");
        $this->newLine();

        // Table output
        $tableData = [];
        foreach ($mismatched as $item) {
            $tableData[] = [
                $item['customer']->reff_id_pelanggan,
                substr($item['customer']->nama_pelanggan, 0, 25),
                $item['current'],
                $item['expected'],
                $item['sk_status'],
                $item['sr_status'],
                $item['gas_in_status'],
            ];
        }

        $this->table(
            ['Reff ID', 'Nama', 'Current', 'Expected', 'SK Status', 'SR Status', 'Gas-In Status'],
            $tableData
        );

        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes made. Run without --dry-run to fix.');
        } else {
            $this->info("✓ Fixed {$fixed} customers!");
        }
    }

    private function determineExpectedProgress($sk, $sr, $gasIn): string
    {
        // Check Gas-In status (highest priority)
        if ($gasIn) {
            if ($gasIn->module_status === 'completed') {
                return 'done';
            }
            if (in_array($gasIn->module_status, ['draft', 'ai_validation', 'tracer_review', 'cgp_review', 'scheduled'])) {
                return 'gas_in';
            }
        }

        // Check SR status
        if ($sr) {
            if ($sr->module_status === 'completed') {
                return 'gas_in';
            }
            if (in_array($sr->module_status, ['draft', 'ai_validation', 'tracer_review', 'cgp_review', 'scheduled'])) {
                return 'sr';
            }
        }

        // Check SK status
        if ($sk) {
            if ($sk->module_status === 'completed') {
                return 'sr';
            }
            if (in_array($sk->module_status, ['draft', 'ai_validation', 'tracer_review', 'cgp_review', 'scheduled'])) {
                return 'sk';
            }
        }

        // Default to validasi if no modules exist or all are rejected
        return 'validasi';
    }
}
