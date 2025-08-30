<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Course\Models\Course;

class FixBannerPaths extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:banner-paths {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix malformed banner_image paths in courses table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('🔍 Scanning for malformed banner paths...');
        
        $courses = Course::whereNotNull('banner_image')->get();
        $fixed = 0;
        $total = $courses->count();
        
        $this->info("📊 Found {$total} courses with banner images");
        
        foreach ($courses as $course) {
            $originalPath = $course->banner_image;
            $fixedPath = $this->fixPath($originalPath);
            
            if ($originalPath !== $fixedPath) {
                if ($dryRun) {
                    $this->warn("Course {$course->id}: '{$originalPath}' → '{$fixedPath}'");
                } else {
                    $course->update(['banner_image' => $fixedPath]);
                    $this->info("✅ Fixed Course {$course->id}: '{$originalPath}' → '{$fixedPath}'");
                }
                $fixed++;
            }
        }
        
        if ($dryRun) {
            $this->info("📋 Dry run complete. {$fixed} out of {$total} paths would be fixed.");
            $this->info("Run without --dry-run to apply changes.");
        } else {
            $this->info("🎉 Fixed {$fixed} out of {$total} banner paths.");
        }
    }
    
    private function fixPath(string $path): string
    {
        // If path starts with /storage/, remove it since Resources will add it back
        if (str_starts_with($path, '/storage/')) {
            $path = ltrim($path, '/storage/');
        }
        
        // Clean up duplicate storage paths like "courses/storage/uploads"
        if (str_contains($path, 'courses/storage/')) {
            $path = str_replace('courses/storage/', '', $path);
        }
        
        return $path;
    }
}
