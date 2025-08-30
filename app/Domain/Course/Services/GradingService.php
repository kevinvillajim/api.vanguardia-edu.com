<?php

namespace App\Domain\Course\Services;

use App\Domain\Course\Models\Certificate;
use App\Domain\Course\Models\CourseEnrollment;
use App\Domain\Course\Models\CourseProgress;
use App\Domain\Course\Models\QuizAttempt;
use App\Domain\Course\Models\StudentActivity;
use Illuminate\Support\Facades\DB;

class GradingService
{
    /**
     * Calcula el promedio del curso interactivo (quizzes)
     */
    public function calculateInteractiveAverage(CourseEnrollment $enrollment): float
    {
        $quizAttempts = QuizAttempt::where('enrollment_id', $enrollment->id)
            ->where('status', 'completed')
            ->get()
            ->groupBy('quiz_id');

        if ($quizAttempts->isEmpty()) {
            return 0;
        }

        $scores = [];
        foreach ($quizAttempts as $quizId => $attempts) {
            // Tomar el mejor intento de cada quiz
            $bestAttempt = $attempts->sortByDesc('percentage')->first();
            $scores[] = $bestAttempt->percentage;
        }

        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0;
    }

    /**
     * Calcula el promedio de actividades del profesor
     */
    public function calculateActivitiesAverage(CourseEnrollment $enrollment): float
    {
        $activities = StudentActivity::where('enrollment_id', $enrollment->id)
            ->whereHas('activity', function ($query) {
                $query->where('is_mandatory', true);
            })
            ->whereNotNull('score')
            ->with('activity')
            ->get();

        if ($activities->isEmpty()) {
            return 0;
        }

        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($activities as $submission) {
            $activity = $submission->activity;
            $percentage = ($submission->score / $activity->max_score) * 100;
            $totalWeightedScore += $percentage * $activity->weight;
            $totalWeight += $activity->weight;
        }

        return $totalWeight > 0 ? round($totalWeightedScore / $totalWeight, 2) : 0;
    }

    /**
     * Calcula el promedio final del estudiante
     */
    public function calculateFinalScore(CourseEnrollment $enrollment): float
    {
        $settings = $this->getGradeWeights();

        $interactiveScore = $this->calculateInteractiveAverage($enrollment);
        $activitiesScore = $this->calculateActivitiesAverage($enrollment);

        $interactiveWeight = $settings['interactive'] / 100;
        $activitiesWeight = $settings['activities'] / 100;

        return round(
            ($interactiveScore * $interactiveWeight) +
            ($activitiesScore * $activitiesWeight),
            2
        );
    }

    /**
     * Calcula el progreso del curso
     */
    public function calculateCourseProgress(CourseEnrollment $enrollment): float
    {
        $course = $enrollment->course;
        $modules = $course->modules()->with('components', 'quizzes')->get();

        $totalMandatory = 0;
        $completedMandatory = 0;

        foreach ($modules as $module) {
            // Componentes obligatorios
            $mandatoryComponents = $module->components()
                ->where('is_mandatory', true)
                ->pluck('id');

            $totalMandatory += $mandatoryComponents->count();

            $completedComponents = CourseProgress::where('enrollment_id', $enrollment->id)
                ->where('type', 'component')
                ->whereIn('reference_id', $mandatoryComponents)
                ->where('is_completed', true)
                ->count();

            $completedMandatory += $completedComponents;

            // Quizzes obligatorios
            $mandatoryQuizzes = $module->quizzes()
                ->where('is_mandatory', true)
                ->pluck('id');

            $totalMandatory += $mandatoryQuizzes->count();

            $completedQuizzes = QuizAttempt::where('enrollment_id', $enrollment->id)
                ->whereIn('quiz_id', $mandatoryQuizzes)
                ->where('status', 'completed')
                ->distinct('quiz_id')
                ->count('quiz_id');

            $completedMandatory += $completedQuizzes;
        }

        return $totalMandatory > 0 ?
            round(($completedMandatory / $totalMandatory) * 100, 2) : 0;
    }

    /**
     * Verifica si el estudiante puede obtener certificado virtual
     */
    public function canGetVirtualCertificate(CourseEnrollment $enrollment): bool
    {
        $progress = $this->calculateCourseProgress($enrollment);
        $threshold = $this->getSystemSetting('certificate_virtual_threshold', 80);

        return $progress >= $threshold;
    }

    /**
     * Verifica si el estudiante puede obtener certificado completo
     */
    public function canGetCompleteCertificate(CourseEnrollment $enrollment): bool
    {
        if (! $this->canGetVirtualCertificate($enrollment)) {
            return false;
        }

        $finalScore = $this->calculateFinalScore($enrollment);
        $threshold = $this->getSystemSetting('certificate_complete_threshold', 70);

        return $finalScore >= $threshold;
    }

    /**
     * Genera un certificado si el estudiante es elegible
     */
    public function generateCertificate(CourseEnrollment $enrollment, string $type): ?Certificate
    {
        // Verificar si ya existe un certificado válido
        $existingCertificate = Certificate::where('enrollment_id', $enrollment->id)
            ->where('type', $type)
            ->where('is_valid', true)
            ->first();

        if ($existingCertificate) {
            return $existingCertificate;
        }

        // Verificar elegibilidad
        $canGenerate = $type === 'virtual' ?
            $this->canGetVirtualCertificate($enrollment) :
            $this->canGetCompleteCertificate($enrollment);

        if (! $canGenerate) {
            return null;
        }

        // Generar certificado
        return DB::transaction(function () use ($enrollment, $type) {
            $certificate = Certificate::create([
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'course_id' => $enrollment->course_id,
                'type' => $type,
                'certificate_number' => $this->generateCertificateNumber($enrollment, $type),
                'issued_at' => now(),
                'final_score' => $this->calculateFinalScore($enrollment),
                'course_progress' => $this->calculateCourseProgress($enrollment),
                'interactive_average' => $this->calculateInteractiveAverage($enrollment),
                'activities_average' => $this->calculateActivitiesAverage($enrollment),
                'metadata' => [
                    'course_title' => $enrollment->course->title,
                    'student_name' => $enrollment->student->name,
                    'teacher_name' => $enrollment->course->teacher->name,
                    'completion_date' => now()->toDateString(),
                ],
                'is_valid' => true,
            ]);

            // Disparar evento de certificado generado
            // event(new CertificateGenerated($certificate));

            return $certificate;
        });
    }

    /**
     * Genera número único de certificado
     */
    private function generateCertificateNumber(CourseEnrollment $enrollment, string $type): string
    {
        $prefix = $type === 'virtual' ? 'VRT' : 'CMP';
        $courseCode = str_pad($enrollment->course_id, 4, '0', STR_PAD_LEFT);
        $studentCode = str_pad($enrollment->student_id, 5, '0', STR_PAD_LEFT);
        $timestamp = now()->format('YmdHis');

        return "{$prefix}-{$courseCode}-{$studentCode}-{$timestamp}";
    }

    /**
     * Obtiene los pesos de calificación del sistema
     */
    private function getGradeWeights(): array
    {
        $settings = DB::table('system_settings')
            ->where('key', 'grade_weights')
            ->first();

        if ($settings && $settings->type === 'json') {
            return json_decode($settings->value, true);
        }

        return [
            'interactive' => 50,
            'activities' => 50,
        ];
    }

    /**
     * Obtiene una configuración del sistema
     */
    private function getSystemSetting(string $key, $default = null)
    {
        $setting = DB::table('system_settings')
            ->where('key', $key)
            ->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => $setting->value === 'true',
            'json' => json_decode($setting->value, true),
            default => $setting->value
        };
    }

    /**
     * Actualiza el progreso del enrollment
     */
    public function updateEnrollmentProgress(CourseEnrollment $enrollment): void
    {
        $progress = $this->calculateCourseProgress($enrollment);

        $enrollment->update([
            'progress_percentage' => $progress,
        ]);

        // Si el progreso es 100%, marcar como completado
        if ($progress >= 100) {
            $enrollment->markAsCompleted();

            // Intentar generar certificados automáticamente
            $this->generateCertificate($enrollment, 'virtual');
            if ($this->canGetCompleteCertificate($enrollment)) {
                $this->generateCertificate($enrollment, 'complete');
            }
        }
    }
}
