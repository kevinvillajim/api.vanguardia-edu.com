<?php

namespace App\Domain\Course\Services;

use App\Domain\Course\Models\Certificate;
use App\Domain\Course\Models\CourseEnrollment;
use App\Services\CourseConfigService;

class CertificateService
{
    /**
     * Verifica automáticamente y genera certificados para una inscripción
     */
    public function checkAndGenerateForEnrollment(CourseEnrollment $enrollment): array
    {
        if (!CourseConfigService::shouldAutoGenerateCertificates()) {
            return [
                'virtual' => null,
                'complete' => null,
                'message' => 'Generación automática de certificados deshabilitada'
            ];
        }

        $generated = [];
        
        // Verificar certificado virtual
        $virtualCertificate = $this->generateVirtualCertificate($enrollment);
        if ($virtualCertificate) {
            $generated['virtual'] = $virtualCertificate;
        }

        // Verificar certificado completo
        $completeCertificate = $this->generateCompleteCertificate($enrollment);
        if ($completeCertificate) {
            $generated['complete'] = $completeCertificate;
        }

        return $generated;
    }

    /**
     * Genera certificado virtual si es elegible
     */
    public function generateVirtualCertificate(CourseEnrollment $enrollment): ?Certificate
    {
        // Verificar si ya tiene certificado virtual válido
        $existingVirtual = $enrollment->certificates()
            ->virtual()
            ->valid()
            ->first();

        if ($existingVirtual) {
            return $existingVirtual;
        }

        // Verificar elegibilidad
        if (!$enrollment->canGetVirtualCertificate()) {
            return null;
        }

        return Certificate::generateForEnrollment($enrollment, 'virtual');
    }

    /**
     * Genera certificado completo si es elegible
     */
    public function generateCompleteCertificate(CourseEnrollment $enrollment): ?Certificate
    {
        // Verificar si ya tiene certificado completo válido
        $existingComplete = $enrollment->certificates()
            ->complete()
            ->valid()
            ->first();

        if ($existingComplete) {
            return $existingComplete;
        }

        // Verificar elegibilidad
        if (!$enrollment->canGetCompleteCertificate()) {
            return null;
        }

        return Certificate::generateForEnrollment($enrollment, 'complete');
    }

    /**
     * Obtiene certificados existentes de una inscripción
     */
    public function getExistingCertificates(CourseEnrollment $enrollment): array
    {
        return [
            'virtual' => $enrollment->certificates()->virtual()->valid()->first(),
            'complete' => $enrollment->certificates()->complete()->valid()->first(),
        ];
    }

    /**
     * Invalida certificados de una inscripción
     */
    public function invalidateCertificates(CourseEnrollment $enrollment, string $reason = 'Inscripción modificada'): void
    {
        $enrollment->certificates()
            ->where('is_valid', true)
            ->update([
                'is_valid' => false,
                'invalidation_reason' => $reason,
            ]);
    }

    /**
     * Obtiene estadísticas de certificados para un curso
     */
    public function getCourseStats(int $courseId): array
    {
        $totalEnrollments = CourseEnrollment::where('course_id', $courseId)
            ->where('status', 'active')
            ->count();

        $virtualCertificates = Certificate::where('course_id', $courseId)
            ->where('type', 'virtual')
            ->where('is_valid', true)
            ->count();

        $completeCertificates = Certificate::where('course_id', $courseId)
            ->where('type', 'complete')
            ->where('is_valid', true)
            ->count();

        return [
            'total_enrollments' => $totalEnrollments,
            'virtual_certificates' => $virtualCertificates,
            'complete_certificates' => $completeCertificates,
            'virtual_rate' => $totalEnrollments > 0 ? round(($virtualCertificates / $totalEnrollments) * 100, 1) : 0,
            'complete_rate' => $totalEnrollments > 0 ? round(($completeCertificates / $totalEnrollments) * 100, 1) : 0,
        ];
    }

    /**
     * Obtiene configuración actual de certificados para mostrar en frontend
     */
    public function getCertificateConfig(): array
    {
        $thresholds = CourseConfigService::getCertificateThresholds();
        $config = CourseConfigService::getCertificateConfig();
        
        return [
            'thresholds' => $thresholds,
            'auto_generate' => CourseConfigService::shouldAutoGenerateCertificates(),
            'config' => $config,
        ];
    }
}