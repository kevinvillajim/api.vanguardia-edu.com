<?php

namespace App\Domain\Course\Services;

use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseEnrollment;
use App\Domain\Course\Models\CourseUnit;
use App\Domain\Course\Models\UnitProgressBreakpoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnitProgressService
{
    /**
     * Update unit progress with breakpoint tracking
     */
    public function updateUnitProgress(
        int $enrollmentId,
        int $unitId,
        float $scrollProgress,
        float $activitiesProgress,
        array $completedComponents = [],
        array $metadata = []
    ): array {
        return DB::transaction(function () use (
            $enrollmentId,
            $unitId,
            $scrollProgress,
            $activitiesProgress,
            $completedComponents,
            $metadata
        ) {
            // Get enrollment info
            $enrollment = CourseEnrollment::with(['course'])->findOrFail($enrollmentId);

            $unit = CourseUnit::findOrFail($unitId);
            $isIntelligentProgressEnabled = $enrollment->course->intelligent_progress_enabled ?? false;

            // Calculate combined progress based on intelligent progress settings
            $combinedProgress = $this->calculateCombinedProgress(
                $scrollProgress,
                $activitiesProgress,
                $isIntelligentProgressEnabled
            );

            // Prepare metadata with component information
            $breakpointMetadata = array_merge($metadata, [
                'completed_components' => $completedComponents,
                'total_components' => count($completedComponents), // This should be passed from frontend
                'unit_title' => $unit->title,
                'timestamp' => now()->toISOString(),
            ]);

            // Record breakpoint if threshold is reached
            $newBreakpoint = UnitProgressBreakpoint::recordBreakpoint(
                $enrollmentId,
                $enrollment->student_id,
                $enrollment->course_id,
                $unitId,
                $scrollProgress,
                $activitiesProgress,
                $combinedProgress,
                $isIntelligentProgressEnabled,
                $breakpointMetadata
            );

            // Get current progress summary
            $progressSummary = UnitProgressBreakpoint::getUnitProgressSummary($enrollmentId, $unitId);

            // Log progress update
            Log::info('Unit progress updated', [
                'enrollment_id' => $enrollmentId,
                'unit_id' => $unitId,
                'scroll_progress' => $scrollProgress,
                'activities_progress' => $activitiesProgress,
                'combined_progress' => $combinedProgress,
                'intelligent_progress_enabled' => $isIntelligentProgressEnabled,
                'new_breakpoint' => $newBreakpoint?->breakpoint_percentage,
            ]);

            return [
                'success' => true,
                'data' => [
                    'unit_id' => $unitId,
                    'scroll_progress' => $scrollProgress,
                    'activities_progress' => $activitiesProgress,
                    'combined_progress' => $combinedProgress,
                    'intelligent_progress_enabled' => $isIntelligentProgressEnabled,
                    'new_breakpoint_reached' => $newBreakpoint?->breakpoint_percentage,
                    'progress_summary' => $progressSummary,
                    'can_access_final_quiz' => $progressSummary['can_access_final_quiz'],
                ]
            ];
        });
    }

    /**
     * Get progress for a specific unit
     */
    public function getUnitProgress(int $enrollmentId, int $unitId): array
    {
        try {
            $enrollment = CourseEnrollment::with(['course'])->findOrFail($enrollmentId);
            $unit = CourseUnit::findOrFail($unitId);
            
            $progressSummary = UnitProgressBreakpoint::getUnitProgressSummary($enrollmentId, $unitId);
            $isIntelligentProgressEnabled = $enrollment->course->intelligent_progress_enabled ?? false;

            return [
                'success' => true,
                'data' => [
                    'enrollment_id' => $enrollmentId,
                    'unit_id' => $unitId,
                    'unit_title' => $unit->title,
                    'course_title' => $enrollment->course->title,
                    'intelligent_progress_enabled' => $isIntelligentProgressEnabled,
                    'progress_summary' => $progressSummary,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error getting unit progress', [
                'enrollment_id' => $enrollmentId,
                'unit_id' => $unitId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Check if final quiz is accessible for a unit
     */
    public function canAccessFinalQuiz(int $enrollmentId, int $unitId): bool
    {
        try {
            $enrollment = CourseEnrollment::with(['course'])->findOrFail($enrollmentId);
            $isIntelligentProgressEnabled = $enrollment->course->intelligent_progress_enabled ?? false;
            
            return UnitProgressBreakpoint::canAccessFinalQuiz($enrollmentId, $unitId, $isIntelligentProgressEnabled);
        } catch (\Exception $e) {
            Log::error('Error checking final quiz access', [
                'enrollment_id' => $enrollmentId,
                'unit_id' => $unitId,
                'error' => $e->getMessage(),
            ]);
            
            // In case of error, deny access for safety
            return false;
        }
    }

    /**
     * Get all breakpoints for a unit (for analytics/debugging)
     */
    public function getUnitBreakpoints(int $enrollmentId, int $unitId): array
    {
        return UnitProgressBreakpoint::getUnitBreakpoints($enrollmentId, $unitId);
    }

    /**
     * Get course-wide progress summary
     */
    public function getCourseProgressSummary(int $enrollmentId): array
    {
        try {
            $enrollment = CourseEnrollment::with(['course.units'])->findOrFail($enrollmentId);
            $course = $enrollment->course;
            $units = $course->units;

            $courseProgress = [];
            $totalUnits = count($units);
            $completedUnits = 0;

            foreach ($units as $unit) {
                $unitProgress = UnitProgressBreakpoint::getUnitProgressSummary($enrollmentId, $unit->id);
                
                if ($unitProgress['highest_breakpoint_reached'] >= 100) {
                    $completedUnits++;
                }

                $courseProgress[] = [
                    'unit_id' => $unit->id,
                    'unit_title' => $unit->title,
                    'progress' => $unitProgress,
                ];
            }

            $overallProgressPercentage = $totalUnits > 0 ? ($completedUnits / $totalUnits) * 100 : 0;

            return [
                'success' => true,
                'data' => [
                    'enrollment_id' => $enrollmentId,
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'intelligent_progress_enabled' => $course->intelligent_progress_enabled ?? false,
                    'overall_progress_percentage' => $overallProgressPercentage,
                    'completed_units' => $completedUnits,
                    'total_units' => $totalUnits,
                    'units_progress' => $courseProgress,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error getting course progress summary', [
                'enrollment_id' => $enrollmentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Calculate combined progress based on scroll and activities
     */
    private function calculateCombinedProgress(
        float $scrollProgress,
        float $activitiesProgress,
        bool $intelligentProgressEnabled
    ): float {
        if (!$intelligentProgressEnabled) {
            // If intelligent progress is disabled, use only scroll progress
            return min(100, max(0, $scrollProgress));
        }

        // Intelligent progress: 30% scroll + 70% activities
        $scrollWeight = 0.30;
        $activitiesWeight = 0.70;
        
        $combinedProgress = ($scrollProgress * $scrollWeight) + ($activitiesProgress * $activitiesWeight);
        
        return min(100, max(0, $combinedProgress));
    }

    /**
     * Reset unit progress (for testing/admin purposes)
     */
    public function resetUnitProgress(int $enrollmentId, int $unitId): bool
    {
        try {
            UnitProgressBreakpoint::where('enrollment_id', $enrollmentId)
                ->where('unit_id', $unitId)
                ->delete();

            Log::info('Unit progress reset', [
                'enrollment_id' => $enrollmentId,
                'unit_id' => $unitId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error resetting unit progress', [
                'enrollment_id' => $enrollmentId,
                'unit_id' => $unitId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}