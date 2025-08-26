<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'user_id',
        'draft_data',
        'draft_type'
    ];

    protected $casts = [
        'draft_data' => 'array'
    ];

    /**
     * Relación con el curso
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Limpiar drafts antiguos manteniendo solo los N más recientes
     */
    public static function cleanupOldDrafts(int $courseId, int $userId, int $keepCount = 3): void
    {
        self::where('course_id', $courseId)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->skip($keepCount)
            ->delete();
    }

    /**
     * Obtener el draft más reciente para un curso y usuario
     */
    public static function getLatest(int $courseId, int $userId): ?self
    {
        return self::where('course_id', $courseId)
            ->where('user_id', $userId)
            ->latest()
            ->first();
    }
}
