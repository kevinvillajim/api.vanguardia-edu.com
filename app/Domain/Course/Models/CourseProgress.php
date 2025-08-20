<?php

namespace App\Domain\Course\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseProgress extends Model
{
    use HasFactory;

    protected $table = 'course_progress';

    protected $fillable = [
        'enrollment_id',
        'student_id',
        'course_id',
        'module_id',
        'component_id',
        'type',
        'reference_id',
        'is_completed',
        'started_at',
        'completed_at',
        'time_spent',
        'score',
        'metadata',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_spent' => 'integer',
        'score' => 'decimal:2',
        'metadata' => 'array',
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

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(ModuleComponent::class, 'component_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeInProgress($query)
    {
        return $query->where('is_completed', false)
            ->whereNotNull('started_at');
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function markAsStarted(): void
    {
        if (! $this->started_at) {
            $this->update(['started_at' => now()]);
        }
    }

    public function markAsCompleted(?float $score = null): void
    {
        $timeSpent = $this->started_at ?
            now()->diffInSeconds($this->started_at) : 0;

        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'time_spent' => $timeSpent,
            'score' => $score,
        ]);
    }

    public function getTimeSpentFormatted(): string
    {
        if (! $this->time_spent) {
            return '0:00';
        }

        $hours = floor($this->time_spent / 3600);
        $minutes = floor(($this->time_spent % 3600) / 60);
        $seconds = $this->time_spent % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public static function trackProgress(
        int $enrollmentId,
        string $type,
        int $referenceId,
        array $additionalData = []
    ): self {
        $enrollment = CourseEnrollment::find($enrollmentId);

        return self::updateOrCreate(
            [
                'enrollment_id' => $enrollmentId,
                'type' => $type,
                'reference_id' => $referenceId,
            ],
            array_merge([
                'student_id' => $enrollment->student_id,
                'course_id' => $enrollment->course_id,
                'module_id' => $additionalData['module_id'] ?? null,
                'component_id' => $additionalData['component_id'] ?? null,
                'metadata' => $additionalData['metadata'] ?? [],
            ], $additionalData)
        );
    }
}
