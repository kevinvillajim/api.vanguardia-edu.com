<?php

namespace App\Domain\Course\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'teacher_id',
        'title',
        'description',
        'type',
        'max_score',
        'weight',
        'due_date',
        'is_mandatory',
        'attachments',
        'instructions',
        'order',
        'is_active',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'attachments' => 'array',
        'instructions' => 'array',
        'max_score' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(StudentActivity::class, 'activity_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('due_date', '>', now())
            ->orderBy('due_date', 'asc');
    }

    public function scopePastDue($query)
    {
        return $query->where('due_date', '<', now());
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast();
    }

    public function getSubmissionForStudent($studentId)
    {
        return $this->submissions()->where('student_id', $studentId)->first();
    }
}
