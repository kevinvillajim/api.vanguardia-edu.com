<?php

namespace App\Services;

class CourseConfigService
{
    /**
     * Obtiene los umbrales de certificados
     */
    public static function getCertificateThresholds(): array
    {
        return [
            'pass' => config('course.pass_threshold'),
            'virtual_certificate' => config('course.virtual_certificate_threshold'),
            'complete_certificate' => config('course.complete_certificate_threshold'),
        ];
    }

    /**
     * Obtiene la configuración de calificaciones
     */
    public static function getGradingConfig(): array
    {
        return config('course.grading');
    }

    /**
     * Obtiene la configuración de certificados
     */
    public static function getCertificateConfig(): array
    {
        return config('course.certificates');
    }

    /**
     * Obtiene la configuración de inscripciones
     */
    public static function getEnrollmentConfig(): array
    {
        return config('course.enrollment');
    }

    /**
     * Verifica si una característica está habilitada
     */
    public static function isFeatureEnabled(string $feature): bool
    {
        return config("course.features.{$feature}", false);
    }

    /**
     * Obtiene el límite máximo de estudiantes por curso
     */
    public static function getMaxStudentsPerCourse(): ?int
    {
        $max = config('course.enrollment.max_students_per_course');
        return $max === 'unlimited' ? null : (int) $max;
    }

    /**
     * Obtiene los pesos de calificación normalizados (que sumen 100)
     */
    public static function getNormalizedGradeWeights(): array
    {
        $config = self::getGradingConfig();
        $total = $config['quiz_weight'] + $config['activity_weight'];
        
        if ($total === 0) {
            return ['interactive' => 50, 'activities' => 50];
        }

        return [
            'interactive' => round(($config['quiz_weight'] / $total) * 100, 2),
            'activities' => round(($config['activity_weight'] / $total) * 100, 2),
        ];
    }

    /**
     * Verifica si los certificados automáticos están habilitados
     */
    public static function shouldAutoGenerateCertificates(): bool
    {
        return config('course.auto_generate_certificates', true);
    }

    /**
     * Obtiene las notificaciones configuradas
     */
    public static function getNotificationConfig(): array
    {
        return config('course.notifications');
    }

    /**
     * Obtiene los límites del sistema
     */
    public static function getSystemLimits(): array
    {
        return config('course.limits');
    }

    /**
     * Obtiene la configuración de progreso
     */
    public static function getProgressConfig(): array
    {
        return config('course.progress');
    }

    /**
     * Calcula si un estudiante puede aprobar con un porcentaje dado
     */
    public static function canPass(float $percentage): bool
    {
        return $percentage >= config('course.pass_threshold', 60);
    }

    /**
     * Obtiene los hitos de progreso configurados
     */
    public static function getProgressMilestones(): array
    {
        $milestones = config('course.notifications.progress_milestones', [25, 50, 75, 90]);
        return array_map('intval', $milestones);
    }

    /**
     * Obtiene toda la configuración de cursos de una vez (útil para frontend)
     */
    public static function getAllConfig(): array
    {
        return [
            'thresholds' => self::getCertificateThresholds(),
            'grading' => self::getGradingConfig(),
            'certificates' => self::getCertificateConfig(),
            'enrollment' => self::getEnrollmentConfig(),
            'features' => config('course.features'),
            'notifications' => self::getNotificationConfig(),
            'limits' => self::getSystemLimits(),
            'progress' => self::getProgressConfig(),
            'analytics' => config('course.analytics'),
        ];
    }
}