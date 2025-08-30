<?php

namespace App\Domain\Course\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'student_id',
        'enrollment_id',
        'status',
        'submission_content',
        'attachments',
        'submitted_at',
        'score',
        'feedback',
        'graded_by',
        'graded_at',
        'attempts',
    ];

    protected $casts = [
        'attachments' => 'array',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'decimal:2',
        'attempts' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(CourseActivity::class, 'activity_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'enrollment_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeGraded($query)
    {
        return $query->where('status', 'graded');
    }

    public function scopeNeedsGrading($query)
    {
        return $query->where('status', 'submitted');
    }

    public function isOverdue(): bool
    {
        return $this->activity->isOverdue() && $this->status === 'pending';
    }

    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'graded', 'returned']);
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded' && $this->score !== null;
    }

    public function getPercentageScore(): ?float
    {
        if (! $this->score || ! $this->activity->max_score) {
            return null;
        }

        return ($this->score / $this->activity->max_score) * 100;
    }

    public function submit(array $data): void
    {
        $this->update([
            'submission_content' => $data['content'] ?? null,
            'attachments' => $data['attachments'] ?? [],
            'submitted_at' => now(),
            'status' => 'submitted',
            'attempts' => $this->attempts + 1,
        ]);
    }

    public function grade(float $score, ?string $feedback = null, ?int $graderId = null): void
    {
        $this->update([
            'score' => $score,
            'feedback' => $feedback,
            'graded_by' => $graderId,
            'graded_at' => now(),
            'status' => 'graded',
        ]);
    }
}
