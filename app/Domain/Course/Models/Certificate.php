<?php

namespace App\Domain\Course\Models;

use App\Models\User;
use App\Services\CourseConfigService;
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
        $thresholds = CourseConfigService::getCertificateThresholds();
        $progress = $enrollment->calculateProgress();
        
        // Use calculated progress if available, otherwise fall back to stored progress_percentage
        $overallProgress = $progress['overall'] > 0 ? $progress['overall'] : (float) $enrollment->progress_percentage;

        // Verificar elegibilidad para certificado virtual
        if ($type === 'virtual') {
            if ($overallProgress < $thresholds['virtual_certificate']) {
                return null;
            }
        }

        // Verificar elegibilidad para certificado completo
        if ($type === 'complete') {
            $finalScore = $enrollment->calculateFinalScore();
            if ($overallProgress < $thresholds['virtual_certificate'] ||
                $finalScore < $thresholds['complete_certificate']) {
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

        // Generate PDF after saving
        $certificate->generatePDF();
        
        return $certificate;
    }

    /**
     * Genera el archivo PDF del certificado
     */
    public function generatePDF(): string
    {
        try {
            $pdf = \PDF::loadView('certificates.template', ['certificate' => $this]);
            
            // Configure PDF settings
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'serif',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'debugKeepTemp' => false,
            ]);
            
            // Create storage directory if it doesn't exist
            $storageDir = storage_path('app/public/certificates');
            if (!file_exists($storageDir)) {
                mkdir($storageDir, 0755, true);
            }
            
            // Generate filename
            $filename = "certificate_{$this->id}_{$this->certificate_number}.pdf";
            $filePath = "{$storageDir}/{$filename}";
            
            // Save PDF file
            $pdf->save($filePath);
            
            // Update file_url in database
            $this->file_url = "certificates/{$filename}";
            $this->save();
            
            \Log::info("Certificate PDF generated successfully: {$filename}");
            
            return $filePath;
            
        } catch (\Exception $e) {
            \Log::error("Error generating certificate PDF: " . $e->getMessage());
            throw new \Exception("Error generating certificate PDF: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la URL pública del PDF
     */
    public function getPdfUrl(): ?string
    {
        if (!$this->file_url) {
            return null;
        }
        
        return url("storage/{$this->file_url}");
    }

    /**
     * Verifica si el PDF existe en el sistema de archivos
     */
    public function pdfExists(): bool
    {
        if (!$this->file_url) {
            return false;
        }
        
        $filePath = storage_path("app/public/{$this->file_url}");
        return file_exists($filePath);
    }

    private static function getSystemSettings(): array
    {
        return CourseConfigService::getCertificateThresholds();
    }
}
