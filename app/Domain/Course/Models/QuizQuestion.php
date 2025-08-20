<?php

namespace App\Domain\Course\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'question',
        'type',
        'options',
        'correct_answers',
        'explanation',
        'points',
        'order',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answers' => 'array',
        'points' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function checkAnswer($userAnswer): bool
    {
        switch ($this->type) {
            case 'multiple_choice':
                return in_array($userAnswer, $this->correct_answers);

            case 'true_false':
                return $userAnswer === $this->correct_answers[0];

            case 'short_answer':
                // Comparación insensible a mayúsculas y espacios
                $normalizedAnswer = strtolower(trim($userAnswer));
                foreach ($this->correct_answers as $correct) {
                    if ($normalizedAnswer === strtolower(trim($correct))) {
                        return true;
                    }
                }

                return false;

            case 'essay':
                // Los ensayos requieren calificación manual
                return null;

            default:
                return false;
        }
    }

    public function getFormattedOptions(): array
    {
        if ($this->type !== 'multiple_choice' || ! $this->options) {
            return [];
        }

        return array_map(function ($option, $index) {
            return [
                'id' => $index,
                'text' => $option,
                'letter' => chr(65 + $index), // A, B, C, D...
            ];
        }, $this->options, array_keys($this->options));
    }
}
