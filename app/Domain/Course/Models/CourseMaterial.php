<?php

namespace App\Domain\Course\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'type',
        'file_url',
        'file_name',
        'file_size',
        'mime_type',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'file_size' => 'integer',
        'order' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFileTypeIconAttribute(): string
    {
        return match ($this->type) {
            'pdf' => '/pdf.webp',
            'document' => '/pdf.webp',
            'video' => '/play.webp',
            'image' => '/image-icon.webp',
            'link' => '/link-icon.webp',
            default => '/pdf.webp'
        };
    }

    public function isDownloadable(): bool
    {
        return in_array($this->type, ['pdf', 'document', 'video', 'image']);
    }

    public function isExternal(): bool
    {
        return $this->type === 'link' || str_starts_with($this->file_url, 'http');
    }
}