<?php

namespace App\Domain\Course\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'short_description',
        'learning_objectives',
        'prerequisites',
        'banner_image',
        'teacher_id',
        'category_id',
        'difficulty_level',
        'duration_hours',
        'price',
        'is_published',
        'is_featured',
        'enrollment_count',
        'rating',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'duration_hours' => 'integer',
        'enrollment_count' => 'integer',
        'learning_objectives' => 'array',
        'prerequisites' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title);
            }
        });

        static::updating(function ($course) {
            if ($course->isDirty('title') && ! $course->isDirty('slug')) {
                $course->slug = Str::slug($course->title);
            }
        });
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(CourseUnit::class)->orderBy('order_index');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('order_index');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CourseActivity::class)->orderBy('order');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(CourseMaterial::class)->orderBy('order');
    }

    public function getTotalDurationAttribute(): int
    {
        return $this->modules()
            ->with('lessons')
            ->get()
            ->pluck('lessons')
            ->flatten()
            ->sum('duration_minutes');
    }

    public function getTotalLessonsAttribute(): int
    {
        return $this->modules()
            ->withCount('lessons')
            ->get()
            ->sum('lessons_count');
    }

    public function isEnrolledBy($userId): bool
    {
        return $this->enrollments()
            ->where('student_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }
}
