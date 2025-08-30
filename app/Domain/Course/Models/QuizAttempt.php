<?php

namespace App\Domain\Course\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'student_id',
        'enrollment_id',
        'attempt_number',
        'started_at',
        'completed_at',
        'score',
        'percentage',
        'answers',
        'question_scores',
        'status',
        'time_spent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'answers' => 'array',
        'question_scores' => 'array',
        'time_spent' => 'integer',
        'attempt_number' => 'integer',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'enrollment_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPassed(): bool
    {
        return $this->percentage >= $this->quiz->passing_score;
    }

    public function getTimeSpentFormatted(): string
    {
        if (! $this->time_spent) {
            return '0:00';
        }

        $minutes = floor($this->time_spent / 60);
        $seconds = $this->time_spent % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function complete(array $answers): void
    {
        $quiz = $this->quiz;
        $questions = $quiz->questions;
        $totalPoints = 0;
        $earnedPoints = 0;
        $questionScores = [];

        foreach ($questions as $question) {
            $userAnswer = $answers[$question->id] ?? null;
            $isCorrect = $question->checkAnswer($userAnswer);

            if ($isCorrect === true) {
                $earnedPoints += $question->points;
                $questionScores[$question->id] = $question->points;
            } elseif ($isCorrect === null) {
                // Essay question - needs manual grading
                $questionScores[$question->id] = null;
            } else {
                $questionScores[$question->id] = 0;
            }

            $totalPoints += $question->points;
        }

        $percentage = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
        $timeSpent = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;

        $this->update([
            'completed_at' => now(),
            'score' => $earnedPoints,
            'percentage' => $percentage,
            'answers' => $answers,
            'question_scores' => $questionScores,
            'status' => 'completed',
            'time_spent' => $timeSpent,
        ]);
    }
}
