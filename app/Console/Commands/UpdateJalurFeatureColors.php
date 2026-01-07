<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MapGeometricFeature;
use App\Models\JalurLineNumber;
use Illuminate\Support\Facades\Log;

class UpdateJalurFeatureColors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jalur:update-feature-colors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update map feature colors based on diameter for existing assigned jalur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎨 Updating Jalur Feature Colors Based on Diameter');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Color scheme
        $diameterColors = [
            '63' => '#3B82F6',   // Blue
            '90' => '#F59E0B',   // Orange
            '180' => '#EF4444'   // Red
        ];

        $diameterWeights = [
            '63' => 3,
            '90' => 4,
            '180' => 5
        ];

        // Find all assigned jalur features (has line_number_id)
        $features = MapGeometricFeature::whereNotNull('line_number_id')
            ->with('lineNumber')
            ->get();

        if ($features->isEmpty()) {
            $this->info('✅ No assigned jalur features found.');
            return 0;
        }

        $this->info("Found {$features->count()} assigned jalur features");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($features->count());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($features as $feature) {
            $progressBar->setMessage("Processing feature #{$feature->id}...");
            $progressBar->advance();

            try {
                if (!$feature->lineNumber) {
                    $skipped++;
                    Log::warning("Feature {$feature->id} has line_number_id but no lineNumber relationship");
                    continue;
                }

                $diameter = $feature->lineNumber->diameter;
                $color = $diameterColors[$diameter] ?? '#3B82F6';
                $weight = $diameterWeights[$diameter] ?? 3;

                // Update style_properties
                $feature->update([
                    'style_properties' => [
                        'color' => $color,
                        'weight' => $weight,
                        'opacity' => 0.8,
                        'dashArray' => null
                    ]
                ]);

                $updated++;

                Log::info("Updated feature {$feature->id} with diameter {$diameter} to color {$color}");

            } catch (\Exception $e) {
                $errors++;
                Log::error("Failed to update feature {$feature->id}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary Table
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 Summary:');
        $this->newLine();

        $this->table(
            ['Status', 'Count'],
            [
                ['✅ Updated', $updated],
                ['⊘ Skipped', $skipped],
                ['❌ Errors', $errors],
                ['Total', $features->count()],
            ]
        );

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Color breakdown
        $this->info('🎨 Color Distribution:');
        $this->newLine();

        $colorBreakdown = $features->filter(function ($f) {
            return $f->lineNumber !== null;
        })->groupBy(function ($f) {
            return $f->lineNumber->diameter;
        });

        foreach (['63', '90', '180'] as $diameter) {
            $count = $colorBreakdown->get($diameter, collect())->count();
            $color = $diameterColors[$diameter];
            $this->line("  Ø{$diameter}mm → {$color}: {$count} features");
        }

        $this->newLine();
        $this->info('✅ Feature colors updated successfully!');
        $this->info('Refresh your browser to see the changes.');

        return 0;
    }
}
