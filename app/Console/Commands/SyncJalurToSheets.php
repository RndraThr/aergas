<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JalurLoweringData;
use App\Models\JalurJointData;
use App\Models\JalurLineNumber;
use App\Services\GoogleSheetsService;
use Illuminate\Support\Facades\Log;

class SyncJalurToSheets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jalur:sync-sheets {--type=all : Type of sync (all, lowering, joint)} {--delay=1 : Delay in seconds between requests to avoid rate limits}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all existing Joint and Lowering data to Google Sheets';

    /**
     * Execute the console command.
     */
    public function handle(GoogleSheetsService $service)
    {
        $type = $this->option('type');
        $delay = (int) $this->option('delay');

        $this->info("Starting Sync Process (Type: $type, Delay: {$delay}s)...");

        if ($type === 'all' || $type === 'lowering') {
            $this->syncLowering($service, $delay);
        }

        if ($type === 'all' || $type === 'joint') {
            $this->syncJoint($service, $delay);
        }

        $this->info("Sync Completed!");
        return 0;
    }

    private function syncLowering(GoogleSheetsService $service, int $delay)
    {
        $count = JalurLoweringData::count();
        $this->info("Syncing {$count} Lowering records...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        JalurLoweringData::orderBy('tanggal_jalur')->chunk(50, function ($lowerings) use ($service, $bar, $delay) {
            foreach ($lowerings as $lowering) {
                try {
                    $service->syncLowering($lowering);
                    sleep($delay); // Rate limiting
                } catch (\Exception $e) {
                    Log::error("Failed to sync Lowering ID {$lowering->id}: " . $e->getMessage());
                    $this->error("\nFailed to sync Lowering ID {$lowering->id}");
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    private function syncJoint(GoogleSheetsService $service, int $delay)
    {
        $count = JalurJointData::count();
        $this->info("Syncing {$count} Joint records...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        JalurJointData::orderBy('tanggal_joint')->chunk(50, function ($joints) use ($service, $bar, $delay) {
            foreach ($joints as $joint) {
                try {
                    // Fix missing Line Number relationship (for Diameter Sync)
                    if (!$joint->line_number_id && $joint->joint_line_from) {
                        $line = JalurLineNumber::where('line_number', $joint->joint_line_from)->first();
                        if ($line) {
                            $joint->line_number_id = $line->id;
                            $joint->saveQuietly(); // Save without triggering events
                        }
                    }

                    $service->syncJointRow($joint);
                    sleep($delay); // Rate limiting
                } catch (\Exception $e) {
                    Log::error("Failed to sync Joint ID {$joint->id}: " . $e->getMessage());
                    $this->error("\nFailed to sync Joint ID {$joint->id}");
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }
}
