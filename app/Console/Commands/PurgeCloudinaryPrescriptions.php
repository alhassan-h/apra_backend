<?php

namespace App\Console\Commands;

use App\Models\Prescription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeCloudinaryPrescriptions extends Command
{
    protected $signature = 'cloudinary:purge-prescriptions
                            {--purge-db : Also delete all prescription records from the database}
                            {--dry-run : List what would be deleted without actually deleting anything}';

    protected $description = 'Delete all prescription images from Cloudinary (optionally wipe database records too)';

    public function handle(): int
    {
        $dryRun  = $this->option('dry-run');
        $purgeDb = $this->option('purge-db');

        $prescriptions = Prescription::whereNotNull('image_url')->get(['id', 'prescription_number', 'image_url']);

        if ($prescriptions->isEmpty()) {
            $this->info('No prescriptions with image URLs found.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d prescription(s) with Cloudinary images.', $prescriptions->count()));

        if ($dryRun) {
            $this->warn('[DRY RUN] No changes will be made.');
        }

        $deleted  = 0;
        $failed   = 0;
        $skipped  = 0;

        foreach ($prescriptions as $prescription) {
            $publicId = $this->extractPublicId($prescription->image_url);

            if (! $publicId) {
                $this->warn("  SKIP  [{$prescription->prescription_number}] Could not parse public_id from: {$prescription->image_url}");
                $skipped++;
                continue;
            }

            $this->line("  → [{$prescription->prescription_number}] public_id: {$publicId}");

            if ($dryRun) {
                $deleted++;
                continue;
            }

            try {
                $result = cloudinary()->uploadApi()->destroy($publicId, ['resource_type' => 'image']);

                if (($result['result'] ?? '') === 'ok' || ($result['result'] ?? '') === 'not found') {
                    $this->info("  ✓ Deleted from Cloudinary");
                    $deleted++;
                } else {
                    $this->error("  ✗ Cloudinary returned: " . json_encode($result));
                    $failed++;
                }
            } catch (\Throwable $e) {
                $this->error("  ✗ Error: " . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Cloudinary: {$deleted} deleted, {$failed} failed, {$skipped} skipped.");

        if ($purgeDb && ! $dryRun) {
            if (! $this->confirm('This will delete ALL prescription records and related data from the database. Are you sure?')) {
                $this->warn('Database purge cancelled.');
                return self::SUCCESS;
            }

            // Cascade order: corrections → reviews → extractions → prescriptions
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('prescription_corrections')->truncate();
            DB::table('prescription_reviews')->truncate();
            DB::table('prescription_extractions')->truncate();
            DB::table('prescriptions')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->info('Database records purged.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Extract the Cloudinary public_id (no extension) from a secure_url.
     *
     * Input:  https://res.cloudinary.com/{cloud}/image/upload/v1234567890/apra/prescriptions/abc.jpg
     * Output: apra/prescriptions/abc
     */
    private function extractPublicId(string $url): ?string
    {
        // Match everything after /upload/ (optionally skip a version segment v\d+/)
        if (! preg_match('#/upload/(?:v\d+/)?(.+)$#', $url, $matches)) {
            return null;
        }

        $path = $matches[1];

        // Strip file extension
        $dotPos = strrpos($path, '.');
        if ($dotPos !== false) {
            $path = substr($path, 0, $dotPos);
        }

        return $path;
    }
}
