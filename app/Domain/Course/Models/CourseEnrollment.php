<?php

namespace App\Domain\Course\Models;

use App\Models\User as UserModel;
use App\Services\CourseConfigService;
use App\Domain\Course\Services\CertificateService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'student_id',
        'enrolled_at',
        'completed_at',
        'dropped_at',
        'progress_percentage',
        'status',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'dropped_at' => 'datetime',
        'progress_percentage' => 'decimal:2',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_COMPLETED = 'completed';

    const STATUS_DROPPED = 'dropped';

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'student_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress_percentage' => 100,
        ]);
    }

    public function updateProgress($percentage)
    {
        $this->update([
            'progress_percentage' => min(100, max(0, $percentage)),
        ]);

        if ($percentage >= 100) {
            $this->markAsCompleted();
        }

        // Generar certificados automáticamente si está habilitado
        $this->checkAndGenerateCertificates();
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function progress(): HasMany
    {
        return $this->hasMany(CourseProgress::class, 'enrollment_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(StudentActivity::class, 'enrollment_id');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'enrollment_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'enrollment_id');
    }

    public function calculateProgress(): array
    {
        $course = $this->course;
        $modules = $course->modules()->with(['lessons', 'quizzes'])->get();

        $totalComponents = 0;
        $completedComponents = 0;
        $totalModules = $modules->count();
        $completedModules = 0;

        $quizScores = [];
        $activityScores = [];

        foreach ($modules as $module) {
            $moduleComponents = ModuleComponent::where('module_id', $module->id)
                ->where('is_mandatory', true)
                ->count();

            $moduleCompleted = CourseProgress::where('enrollment_id', $this->id)
                ->where('module_id', $module->id)
                ->where('type', 'component')
                ->where('is_completed', true)
                ->count();

            $totalComponents += $moduleComponents;
            $completedComponents += $moduleCompleted;

            // Verificar quizzes del módulo
            $moduleQuizzes = $module->quizzes;
            foreach ($moduleQuizzes as $quiz) {
                $attempt = QuizAttempt::where('enrollment_id', $this->id)
                    ->where('quiz_id', $quiz->id)
                    ->where('status', 'completed')
                    ->orderBy('percentage', 'desc')
                    ->first();

                if ($attempt) {
                    $quizScores[] = $attempt->percentage;
                }
            }

            // Si todos los componentes obligatorios del módulo están completos
            if ($moduleComponents > 0 && $moduleCompleted >= $moduleComponents) {
                $completedModules++;
            }
        }

        // Calcular promedio de actividades
        $activities = StudentActivity::where('enrollment_id', $this->id)
            ->whereNotNull('score')
            ->get();

        foreach ($activities as $activity) {
            if ($activity->activity->max_score > 0) {
                $activityScores[] = ($activity->score / $activity->activity->max_score) * 100;
            }
        }

        $overallProgress = $totalComponents > 0 ?
            ($completedComponents / $totalComponents) * 100 : 0;

        return [
            'overall' => round($overallProgress, 2),
            'modules_completed' => $completedModules,
            'total_modules' => $totalModules,
            'components_completed' => $completedComponents,
            'total_components' => $totalComponents,
            'quiz_average' => count($quizScores) > 0 ?
                round(array_sum($quizScores) / count($quizScores), 2) : null,
            'activities_average' => count($activityScores) > 0 ?
                round(array_sum($activityScores) / count($activityScores), 2) : null,
        ];
    }

    public function calculateFinalScore(): float
    {
        $progress = $this->calculateProgress();
        $settings = $this->getGradeWeights();

        $interactiveScore = $progress['quiz_average'] ?? 0;
        $activitiesScore = $progress['activities_average'] ?? 0;

        $interactiveWeight = $settings['interactive'] / 100;
        $activitiesWeight = $settings['activities'] / 100;

        $finalScore = ($interactiveScore * $interactiveWeight) +
                     ($activitiesScore * $activitiesWeight);

        return round($finalScore, 2);
    }

    private function getGradeWeights(): array
    {
        return CourseConfigService::getNormalizedGradeWeights();
    }

    public function canGetVirtualCertificate(): bool
    {
        $progress = $this->calculateProgress();
        $thresholds = CourseConfigService::getCertificateThresholds();
        
        // Use calculated progress if available, otherwise fall back to stored progress_percentage
        $overallProgress = $progress['overall'] > 0 ? $progress['overall'] : (float) $this->progress_percentage;

        return $overallProgress >= $thresholds['virtual_certificate'];
    }

    public function canGetCompleteCertificate(): bool
    {
        if (! $this->canGetVirtualCertificate()) {
            return false;
        }

        $finalScore = $this->calculateFinalScore();
        $thresholds = CourseConfigService::getCertificateThresholds();

        return $finalScore >= $thresholds['complete_certificate'];
    }

    public function getExistingCertificates(): array
    {
        return [
            'virtual' => $this->certificates()->virtual()->valid()->first(),
            'complete' => $this->certificates()->complete()->valid()->first(),
        ];
    }

    /**
     * Verifica automáticamente y genera certificados si es elegible
     */
    public function checkAndGenerateCertificates(): array
    {
        $certificateService = new CertificateService();
        return $certificateService->checkAndGenerateForEnrollment($this);
    }
}
