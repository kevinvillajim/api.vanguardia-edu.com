<?php

namespace App\Domain\Course\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitProgressBreakpoint extends Model
{
    use HasFactory;

    protected $table = 'unit_progress_breakpoints';

    protected $fillable = [
        'enrollment_id',
        'student_id',
        'course_id',
        'unit_id',
        'breakpoint_percentage',
        'scroll_progress',
        'activities_progress',
        'combined_progress',
        'metadata',
        'intelligent_progress_enabled',
        'reached_at',
    ];

    protected $casts = [
        'scroll_progress' => 'decimal:2',
        'activities_progress' => 'decimal:2',
        'combined_progress' => 'decimal:2',
        'metadata' => 'array',
        'intelligent_progress_enabled' => 'boolean',
        'reached_at' => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'enrollment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(CourseUnit::class, 'unit_id');
    }

    /**
     * Get the breakpoints for a specific unit
     */
    public static function getUnitBreakpoints(int $enrollmentId, int $unitId): array
    {
        return self::where('enrollment_id', $enrollmentId)
            ->where('unit_id', $unitId)
            ->orderBy('breakpoint_percentage')
            ->get()
            ->keyBy('breakpoint_percentage')
            ->toArray();
    }

    /**
     * Record a breakpoint if it doesn't exist yet and the progress threshold is reached
     */
    public static function recordBreakpoint(
        int $enrollmentId,
        int $studentId,
        int $courseId,
        int $unitId,
        float $scrollProgress,
        float $activitiesProgress,
        float $combinedProgress,
        bool $intelligentProgressEnabled,
        array $metadata = []
    ): ?self {
        // Determine which breakpoint(s) should be recorded based on combined progress
        $breakpoints = [25, 50, 75, 100];
        $breakpointsToRecord = [];

        foreach ($breakpoints as $breakpoint) {
            if ($combinedProgress >= $breakpoint) {
                $breakpointsToRecord[] = $breakpoint;
            }
        }

        if (empty($breakpointsToRecord)) {
            return null;
        }

        // Only record the highest unrecorded breakpoint to avoid duplicates
        $existingBreakpoints = self::where('enrollment_id', $enrollmentId)
            ->where('unit_id', $unitId)
            ->pluck('breakpoint_percentage')
            ->toArray();

        $newBreakpoints = array_diff($breakpointsToRecord, $existingBreakpoints);
        $highestNewBreakpoint = max($newBreakpoints ?: [0]);

        if ($highestNewBreakpoint === 0) {
            return null; // No new breakpoints to record
        }

        return self::create([
            'enrollment_id' => $enrollmentId,
            'student_id' => $studentId,
            'course_id' => $courseId,
            'unit_id' => $unitId,
            'breakpoint_percentage' => $highestNewBreakpoint,
            'scroll_progress' => $scrollProgress,
            'activities_progress' => $activitiesProgress,
            'combined_progress' => $combinedProgress,
            'metadata' => $metadata,
            'intelligent_progress_enabled' => $intelligentProgressEnabled,
            'reached_at' => now(),
        ]);
    }

    /**
     * Check if final quiz access is allowed (requires 100% breakpoint)
     */
    public static function canAccessFinalQuiz(int $enrollmentId, int $unitId, bool $intelligentProgressEnabled): bool
    {
        if (!$intelligentProgressEnabled) {
            // If intelligent progress is disabled, always allow access
            return true;
        }

        // Check if 100% breakpoint has been reached
        return self::where('enrollment_id', $enrollmentId)
            ->where('unit_id', $unitId)
            ->where('breakpoint_percentage', 100)
            ->exists();
    }

    /**
     * Get progress summary for a unit
     */
    public static function getUnitProgressSummary(int $enrollmentId, int $unitId): array
    {
        $breakpoints = self::where('enrollment_id', $enrollmentId)
            ->where('unit_id', $unitId)
            ->orderBy('breakpoint_percentage')
            ->get();

        $highestBreakpoint = $breakpoints->max('breakpoint_percentage') ?? 0;
        $latestBreakpoint = $breakpoints->sortByDesc('reached_at')->first();

        return [
            'highest_breakpoint_reached' => $highestBreakpoint,
            'current_scroll_progress' => $latestBreakpoint?->scroll_progress ?? 0,
            'current_activities_progress' => $latestBreakpoint?->activities_progress ?? 0,
            'current_combined_progress' => $latestBreakpoint?->combined_progress ?? 0,
            'intelligent_progress_enabled' => $latestBreakpoint?->intelligent_progress_enabled ?? false,
            'can_access_final_quiz' => $highestBreakpoint >= 100,
            'breakpoints_reached' => $breakpoints->pluck('breakpoint_percentage')->toArray(),
            'last_update' => $latestBreakpoint?->reached_at?->toISOString(),
        ];
    }
}