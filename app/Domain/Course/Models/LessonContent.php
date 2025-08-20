<?php

namespace App\Domain\Course\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'content_type',
        'content_data',
        'order_index',
        'is_required',
    ];

    protected $casts = [
        'content_data' => 'array',
        'is_required' => 'boolean',
        'order_index' => 'integer',
    ];

    const TYPE_VIDEO = 'video';

    const TYPE_TEXT = 'text';

    const TYPE_IMAGE = 'image';

    const TYPE_QUIZ = 'quiz';

    const TYPE_ACTIVITY = 'activity';

    const TYPE_FILE = 'file';

    const TYPE_CODE = 'code';

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('content_type', $type);
    }

    public function getFormattedContentAttribute()
    {
        switch ($this->content_type) {
            case self::TYPE_VIDEO:
                return $this->formatVideoContent();
            case self::TYPE_TEXT:
                return $this->formatTextContent();
            case self::TYPE_IMAGE:
                return $this->formatImageContent();
            case self::TYPE_QUIZ:
                return $this->formatQuizContent();
            case self::TYPE_ACTIVITY:
                return $this->formatActivityContent();
            case self::TYPE_FILE:
                return $this->formatFileContent();
            case self::TYPE_CODE:
                return $this->formatCodeContent();
            default:
                return $this->content_data;
        }
    }

    private function formatVideoContent()
    {
        return [
            'type' => 'video',
            'url' => $this->content_data['url'] ?? null,
            'provider' => $this->content_data['provider'] ?? 'local',
            'duration' => $this->content_data['duration'] ?? null,
            'thumbnail' => $this->content_data['thumbnail'] ?? null,
        ];
    }

    private function formatTextContent()
    {
        return [
            'type' => 'text',
            'content' => $this->content_data['content'] ?? '',
            'format' => $this->content_data['format'] ?? 'html',
        ];
    }

    private function formatImageContent()
    {
        return [
            'type' => 'image',
            'url' => $this->content_data['url'] ?? null,
            'alt' => $this->content_data['alt'] ?? '',
            'caption' => $this->content_data['caption'] ?? '',
        ];
    }

    private function formatQuizContent()
    {
        return [
            'type' => 'quiz',
            'quiz_id' => $this->content_data['quiz_id'] ?? null,
            'pass_percentage' => $this->content_data['pass_percentage'] ?? 70,
            'max_attempts' => $this->content_data['max_attempts'] ?? 3,
        ];
    }

    private function formatActivityContent()
    {
        return [
            'type' => 'activity',
            'title' => $this->content_data['title'] ?? '',
            'instructions' => $this->content_data['instructions'] ?? '',
            'due_date' => $this->content_data['due_date'] ?? null,
            'points' => $this->content_data['points'] ?? 0,
        ];
    }

    private function formatFileContent()
    {
        return [
            'type' => 'file',
            'url' => $this->content_data['url'] ?? null,
            'name' => $this->content_data['name'] ?? '',
            'size' => $this->content_data['size'] ?? 0,
            'mime_type' => $this->content_data['mime_type'] ?? '',
        ];
    }

    private function formatCodeContent()
    {
        return [
            'type' => 'code',
            'code' => $this->content_data['code'] ?? '',
            'language' => $this->content_data['language'] ?? 'javascript',
            'executable' => $this->content_data['executable'] ?? false,
        ];
    }
}
