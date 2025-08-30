<?php

namespace App\Domain\Course\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'type',
        'title',
        'content',
        'file_url',
        'metadata',
        'duration',
        'order',
        'is_mandatory',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'duration' => 'integer',
        'order' => 'integer',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public function getEstimatedDuration(): int
    {
        if ($this->duration) {
            return $this->duration;
        }

        // Estimaciones por defecto según el tipo
        return match ($this->type) {
            'video' => $this->metadata['duration'] ?? 10,
            'reading' => ceil(str_word_count($this->content ?? '') / 200), // 200 palabras por minuto
            'document' => 15,
            'audio' => $this->metadata['duration'] ?? 10,
            'interactive' => 20,
            'banner' => 1,
            'image' => 1,
            default => 5
        };
    }

    public function isMedia(): bool
    {
        return in_array($this->type, ['video', 'audio', 'image']);
    }

    public function isDocument(): bool
    {
        return in_array($this->type, ['document', 'reading']);
    }

    public function getIcon(): string
    {
        return match ($this->type) {
            'video' => 'play-circle',
            'audio' => 'volume-2',
            'reading' => 'book-open',
            'document' => 'file-text',
            'image' => 'image',
            'banner' => 'flag',
            'interactive' => 'cpu',
            default => 'file'
        };
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'video' => 'Video',
            'audio' => 'Audio',
            'reading' => 'Lectura',
            'document' => 'Documento',
            'image' => 'Imagen',
            'banner' => 'Banner',
            'interactive' => 'Contenido Interactivo',
            default => 'Archivo'
        };
    }
}
