<?php

namespace App\Domain\Course\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseUnit extends Model
{
    protected $table = 'course_units';

    protected $fillable = [
        'course_id',
        'title',
        'description', 
        'banner_image',
        'order_index',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'order_index' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class, 'unit_id')->orderBy('order_index');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(CourseQuiz::class, 'unit_id');
    }

    public function getProgressAttribute()
    {
        // Calculate progress based on module completion
        // This will be implemented when we add student progress tracking
        return 0;
    }
}