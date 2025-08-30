<?php

namespace App\Domain\Course\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'description',
        'order_index',
        'duration_minutes',
        'is_preview',
        'is_published',
    ];

    protected $casts = [
        'is_preview' => 'boolean',
        'is_published' => 'boolean',
        'order_index' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(LessonContent::class, 'lesson_id')->orderBy('order_index');
    }

    public function getCourseAttribute()
    {
        return $this->module->course;
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopePreviewable($query)
    {
        return $query->where('is_preview', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    public function isAccessibleBy($userId): bool
    {
        // Si es preview, es accesible para todos
        if ($this->is_preview) {
            return true;
        }

        // Verificar si el usuario está inscrito en el curso
        return $this->course->isEnrolledBy($userId);
    }
}
