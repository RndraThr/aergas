<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PhotoApproval;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrate jalur photo paths from old structure to new structure:
     * OLD: JALUR_LOWERING/{ClusterName}/{LineNumber}/{Date}/
     * NEW: jalur_lowering/{cluster_slug}/{LineNumber}/{Date}/
     *
     * OLD: aergas_approved_jalur/{cluster_slug}/{line}/{date}/LOWERING/
     * NEW: jalur_lowering_approved/{cluster_slug}/{line}/{date}/
     */
    public function up(): void
    {
        Log::info('Starting jalur photo path migration to new folder structure');

        $photos = PhotoApproval::whereIn('module_name', ['jalur_lowering', 'jalur_joint'])
            ->whereNotNull('storage_path')
            ->get();

        $disk = Storage::disk('public');
        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($photos as $photo) {
            try {
                $oldPath = $photo->storage_path;
                $newPath = null;
                $shouldMove = false;

                // Pattern 1: JALUR_LOWERING/{ClusterName}/... → jalur_lowering/{cluster_slug}/...
                if (preg_match('/^JALUR_LOWERING\/([^\/]+)\/(.+)$/', $oldPath, $matches)) {
                    $clusterName = $matches[1];
                    $restOfPath = $matches[2];
                    $clusterSlug = Str::slug($clusterName, '_');
                    $newPath = "jalur_lowering/{$clusterSlug}/{$restOfPath}";
                    $shouldMove = true;
                }
                // Pattern 2: JALUR_JOINT/{ClusterName}/... → jalur_joint/{cluster_slug}/...
                elseif (preg_match('/^JALUR_JOINT\/([^\/]+)\/(.+)$/', $oldPath, $matches)) {
                    $clusterName = $matches[1];
                    $restOfPath = $matches[2];
                    $clusterSlug = Str::slug($clusterName, '_');
                    $newPath = "jalur_joint/{$clusterSlug}/{$restOfPath}";
                    $shouldMove = true;
                }
                // Pattern 3: aergas_approved_jalur/{cluster}/{line}/{date}/LOWERING/... → jalur_lowering_approved/{cluster}/{line}/{date}/...
                elseif (preg_match('/^aergas_approved_jalur\/([^\/]+)\/([^\/]+)\/([^\/]+)\/LOWERING\/(.+)$/', $oldPath, $matches)) {
                    $cluster = $matches[1];
                    $line = $matches[2];
                    $date = $matches[3];
                    $filename = $matches[4];
                    $newPath = "jalur_lowering_approved/{$cluster}/{$line}/{$date}/{$filename}";
                    $shouldMove = true;
                }
                // Pattern 4: aergas_approved_jalur/{cluster}/{line}/{date}/JOINT/... → jalur_joint_approved/{cluster}/{line}/{date}/...
                elseif (preg_match('/^aergas_approved_jalur\/([^\/]+)\/([^\/]+)\/([^\/]+)\/JOINT\/(.+)$/', $oldPath, $matches)) {
                    $cluster = $matches[1];
                    $line = $matches[2];
                    $date = $matches[3];
                    $filename = $matches[4];
                    $newPath = "jalur_joint_approved/{$cluster}/{$line}/{$date}/{$filename}";
                    $shouldMove = true;
                }

                if ($shouldMove && $newPath && $newPath !== $oldPath) {
                    // Physical file move
                    if ($disk->exists($oldPath)) {
                        // Create target directory if not exists
                        $targetDir = dirname($newPath);
                        if (!$disk->exists($targetDir)) {
                            $disk->makeDirectory($targetDir, 0755, true);
                        }

                        // Move file
                        if ($disk->move($oldPath, $newPath)) {
                            // Update database
                            $photo->update([
                                'storage_path' => $newPath,
                                'photo_url' => Storage::url($newPath)
                            ]);

                            $migrated++;
                            Log::info("Migrated photo {$photo->id}: {$oldPath} → {$newPath}");
                        } else {
                            $failed++;
                            Log::error("Failed to move file for photo {$photo->id}: {$oldPath}");
                        }
                    } else {
                        // File doesn't exist in old path, just update database
                        $photo->update([
                            'storage_path' => $newPath,
                            'photo_url' => Storage::url($newPath)
                        ]);

                        $skipped++;
                        Log::warning("File not found for photo {$photo->id}, updated database only: {$oldPath}");
                    }
                } else {
                    $skipped++;
                }

            } catch (\Exception $e) {
                $failed++;
                Log::error("Migration failed for photo {$photo->id}: " . $e->getMessage());
            }
        }

        Log::info("Jalur photo path migration completed", [
            'total' => $photos->count(),
            'migrated' => $migrated,
            'skipped' => $skipped,
            'failed' => $failed
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Rolling back jalur photo path migration');

        $photos = PhotoApproval::whereIn('module_name', ['jalur_lowering', 'jalur_joint'])
            ->whereNotNull('storage_path')
            ->get();

        $disk = Storage::disk('public');
        $reverted = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($photos as $photo) {
            try {
                $oldPath = $photo->storage_path;
                $newPath = null;
                $shouldMove = false;

                // Reverse Pattern 1: jalur_lowering/{cluster_slug}/... → JALUR_LOWERING/{ClusterName}/...
                if (preg_match('/^jalur_lowering\/([^\/]+)\/(.+)$/', $oldPath, $matches)) {
                    $clusterSlug = $matches[1];
                    $restOfPath = $matches[2];
                    // Convert slug back to title case (best effort)
                    $clusterName = ucwords(str_replace('_', ' ', $clusterSlug));
                    $newPath = "JALUR_LOWERING/{$clusterName}/{$restOfPath}";
                    $shouldMove = true;
                }
                // Reverse Pattern 2: jalur_joint/{cluster_slug}/... → JALUR_JOINT/{ClusterName}/...
                elseif (preg_match('/^jalur_joint\/([^\/]+)\/(.+)$/', $oldPath, $matches)) {
                    $clusterSlug = $matches[1];
                    $restOfPath = $matches[2];
                    $clusterName = ucwords(str_replace('_', ' ', $clusterSlug));
                    $newPath = "JALUR_JOINT/{$clusterName}/{$restOfPath}";
                    $shouldMove = true;
                }
                // Reverse Pattern 3: jalur_lowering_approved/... → aergas_approved_jalur/.../LOWERING/
                elseif (preg_match('/^jalur_lowering_approved\/([^\/]+)\/([^\/]+)\/([^\/]+)\/(.+)$/', $oldPath, $matches)) {
                    $cluster = $matches[1];
                    $line = $matches[2];
                    $date = $matches[3];
                    $filename = $matches[4];
                    $newPath = "aergas_approved_jalur/{$cluster}/{$line}/{$date}/LOWERING/{$filename}";
                    $shouldMove = true;
                }
                // Reverse Pattern 4: jalur_joint_approved/... → aergas_approved_jalur/.../JOINT/
                elseif (preg_match('/^jalur_joint_approved\/([^\/]+)\/([^\/]+)\/([^\/]+)\/(.+)$/', $oldPath, $matches)) {
                    $cluster = $matches[1];
                    $line = $matches[2];
                    $date = $matches[3];
                    $filename = $matches[4];
                    $newPath = "aergas_approved_jalur/{$cluster}/{$line}/{$date}/JOINT/{$filename}";
                    $shouldMove = true;
                }

                if ($shouldMove && $newPath && $newPath !== $oldPath) {
                    if ($disk->exists($oldPath)) {
                        // Create target directory
                        $targetDir = dirname($newPath);
                        if (!$disk->exists($targetDir)) {
                            $disk->makeDirectory($targetDir, 0755, true);
                        }

                        // Move file back
                        if ($disk->move($oldPath, $newPath)) {
                            $photo->update([
                                'storage_path' => $newPath,
                                'photo_url' => Storage::url($newPath)
                            ]);

                            $reverted++;
                            Log::info("Reverted photo {$photo->id}: {$oldPath} → {$newPath}");
                        } else {
                            $failed++;
                            Log::error("Failed to revert file for photo {$photo->id}: {$oldPath}");
                        }
                    } else {
                        // Update database only
                        $photo->update([
                            'storage_path' => $newPath,
                            'photo_url' => Storage::url($newPath)
                        ]);

                        $skipped++;
                        Log::warning("File not found for photo {$photo->id}, updated database only: {$oldPath}");
                    }
                } else {
                    $skipped++;
                }

            } catch (\Exception $e) {
                $failed++;
                Log::error("Rollback failed for photo {$photo->id}: " . $e->getMessage());
            }
        }

        Log::info("Jalur photo path rollback completed", [
            'total' => $photos->count(),
            'reverted' => $reverted,
            'skipped' => $skipped,
            'failed' => $failed
        ]);
    }
};
