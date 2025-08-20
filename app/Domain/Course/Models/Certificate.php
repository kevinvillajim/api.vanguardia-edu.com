<?php

namespace App\Domain\Course\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'student_id',
        'course_id',
        'type',
        'certificate_number',
        'issued_at',
        'final_score',
        'course_progress',
        'interactive_average',
        'activities_average',
        'metadata',
        'file_url',
        'is_valid',
        'invalidation_reason',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'final_score' => 'decimal:2',
        'course_progress' => 'decimal:2',
        'interactive_average' => 'decimal:2',
        'activities_average' => 'decimal:2',
        'metadata' => 'array',
        'is_valid' => 'boolean',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'enrollment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeVirtual($query)
    {
        return $query->where('type', 'virtual');
    }

    public function scopeComplete($query)
    {
        return $query->where('type', 'complete');
    }

    public function isVirtual(): bool
    {
        return $this->type === 'virtual';
    }

    public function isComplete(): bool
    {
        return $this->type === 'complete';
    }

    public function invalidate(string $reason): void
    {
        $this->update([
            'is_valid' => false,
            'invalidation_reason' => $reason,
        ]);
    }

    public function generateCertificateNumber(): string
    {
        $prefix = $this->type === 'virtual' ? 'VRT' : 'CMP';
        $courseId = str_pad($this->course_id, 4, '0', STR_PAD_LEFT);
        $studentId = str_pad($this->student_id, 5, '0', STR_PAD_LEFT);
        $timestamp = now()->format('YmdHis');

        return "{$prefix}-{$courseId}-{$studentId}-{$timestamp}";
    }

    public static function generateForEnrollment(CourseEnrollment $enrollment, string $type): ?self
    {
        $settings = self::getSystemSettings();
        $progress = $enrollment->calculateProgress();

        // Verificar elegibilidad para certificado virtual
        if ($type === 'virtual') {
            if ($progress['overall'] < $settings['virtual_threshold']) {
                return null;
            }
        }

        // Verificar elegibilidad para certificado completo
        if ($type === 'complete') {
            $finalScore = $enrollment->calculateFinalScore();
            if ($progress['overall'] < $settings['virtual_threshold'] ||
                $finalScore < $settings['complete_threshold']) {
                return null;
            }
        }

        $certificate = new self([
            'enrollment_id' => $enrollment->id,
            'student_id' => $enrollment->student_id,
            'course_id' => $enrollment->course_id,
            'type' => $type,
            'issued_at' => now(),
            'course_progress' => $progress['overall'],
            'interactive_average' => $progress['quiz_average'] ?? 0,
            'activities_average' => $progress['activities_average'] ?? 0,
            'final_score' => $enrollment->calculateFinalScore(),
            'metadata' => [
                'course_name' => $enrollment->course->title,
                'student_name' => $enrollment->student->name,
                'completion_date' => now()->toDateString(),
                'modules_completed' => $progress['modules_completed'],
                'total_modules' => $progress['total_modules'],
            ],
            'is_valid' => true,
        ]);

        $certificate->certificate_number = $certificate->generateCertificateNumber();
        $certificate->save();

        return $certificate;
    }

    private static function getSystemSettings(): array
    {
        // Obtener configuraciones del sistema
        return [
            'virtual_threshold' => 80,
            'complete_threshold' => 70,
        ];
    }
}
