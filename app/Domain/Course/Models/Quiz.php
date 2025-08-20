<?php

namespace App\Domain\Course\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'description',
        'time_limit',
        'max_attempts',
        'passing_score',
        'shuffle_questions',
        'show_correct_answers',
        'is_mandatory',
        'order',
        'is_active',
    ];

    protected $casts = [
        'time_limit' => 'integer',
        'max_attempts' => 'integer',
        'passing_score' => 'decimal:2',
        'shuffle_questions' => 'boolean',
        'show_correct_answers' => 'boolean',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function getTotalPoints(): float
    {
        return $this->questions()->sum('points');
    }

    public function getQuestionsCount(): int
    {
        return $this->questions()->count();
    }

    public function getStudentAttempts($studentId)
    {
        return $this->attempts()
            ->where('student_id', $studentId)
            ->orderBy('attempt_number', 'desc')
            ->get();
    }

    public function getLatestAttempt($studentId)
    {
        return $this->attempts()
            ->where('student_id', $studentId)
            ->orderBy('attempt_number', 'desc')
            ->first();
    }

    public function canStudentAttempt($studentId): bool
    {
        $attemptCount = $this->attempts()
            ->where('student_id', $studentId)
            ->where('status', 'completed')
            ->count();

        return $attemptCount < $this->max_attempts;
    }

    public function hasStudentPassed($studentId): bool
    {
        $bestAttempt = $this->attempts()
            ->where('student_id', $studentId)
            ->where('status', 'completed')
            ->orderBy('percentage', 'desc')
            ->first();

        return $bestAttempt && $bestAttempt->percentage >= $this->passing_score;
    }
}
