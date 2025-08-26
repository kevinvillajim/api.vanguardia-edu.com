<?php

namespace App\Console\Commands;

use App\Models\CourseDraft;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DraftCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drafts:cleanup {--days=30 : Number of days to keep drafts} {--keep=1 : Number of recent drafts to keep per course}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old course drafts to optimize database storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $keepCount = (int) $this->option('keep');
        
        $this->info("Starting draft cleanup...");
        $this->info("- Removing drafts older than {$days} days");
        $this->info("- Keeping {$keepCount} most recent draft(s) per course");
        
        // 1. Eliminar drafts antiguos (más de X días)
        $cutoffDate = Carbon::now()->subDays($days);
        $oldDraftsCount = CourseDraft::where('created_at', '<', $cutoffDate)->count();
        
        if ($oldDraftsCount > 0) {
            CourseDraft::where('created_at', '<', $cutoffDate)->delete();
            $this->info("✅ Deleted {$oldDraftsCount} old drafts (older than {$days} days)");
        } else {
            $this->info("ℹ️ No old drafts found to delete");
        }
        
        // 2. Para cada curso, mantener solo los N drafts más recientes
        $courseIds = CourseDraft::distinct('course_id')->pluck('course_id');
        $cleanedCourses = 0;
        $totalCleaned = 0;
        
        foreach ($courseIds as $courseId) {
            // Obtener drafts ordenados por fecha (más recientes primero)
            $drafts = CourseDraft::where('course_id', $courseId)
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Si hay más drafts de los que queremos mantener
            if ($drafts->count() > $keepCount) {
                $draftsToDelete = $drafts->skip($keepCount);
                $deletedCount = $draftsToDelete->count();
                
                // Eliminar los drafts extras
                CourseDraft::whereIn('id', $draftsToDelete->pluck('id'))->delete();
                
                $cleanedCourses++;
                $totalCleaned += $deletedCount;
            }
        }
        
        if ($totalCleaned > 0) {
            $this->info("✅ Cleaned {$totalCleaned} excess drafts from {$cleanedCourses} courses");
        } else {
            $this->info("ℹ️ No excess drafts found to clean");
        }
        
        // 3. Estadísticas finales
        $remainingDrafts = CourseDraft::count();
        $coursesWithDrafts = CourseDraft::distinct('course_id')->count();
        
        $this->info("📊 Final statistics:");
        $this->info("- Remaining drafts: {$remainingDrafts}");
        $this->info("- Courses with drafts: {$coursesWithDrafts}");
        $this->info("- Average drafts per course: " . ($coursesWithDrafts > 0 ? round($remainingDrafts / $coursesWithDrafts, 2) : 0));
        
        $this->info("🎉 Draft cleanup completed successfully!");
        
        return Command::SUCCESS;
    }
}
