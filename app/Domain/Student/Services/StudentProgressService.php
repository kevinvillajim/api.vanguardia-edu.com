<?php

namespace App\Domain\Student\Services;

use Illuminate\Support\Facades\DB;
use App\Domain\Course\Models\Course;

class StudentProgressService
{
    /**
     * Update student progress for a specific unit/module
     */
    public function updateProgress(int $studentId, int $courseId, int $unitId, ?int $moduleId = null, int $progressPercentage = 100): array
    {
        return DB::transaction(function () use ($studentId, $courseId, $unitId, $moduleId, $progressPercentage) {
            // Update or create progress record
            $progress = DB::table('course_progress')->updateOrInsert(
                [
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'unit_id' => $unitId,
                    'module_id' => $moduleId,
                ],
                [
                    'progress_percentage' => $progressPercentage,
                    'completed_at' => $progressPercentage >= 100 ? now() : null,
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );

            // Calculate overall course progress
            $overallProgress = $this->calculateCourseProgress($studentId, $courseId);

            return [
                'unit_progress' => $progressPercentage,
                'course_progress' => $overallProgress,
                'is_completed' => $overallProgress >= 100
            ];
        });
    }

    /**
     * Get student progress for a course
     */
    public function getCourseProgress(int $studentId, int $courseId): array
    {
        $progress = DB::table('course_progress')
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->get();

        $overallProgress = $this->calculateCourseProgress($studentId, $courseId);

        return [
            'course_id' => $courseId,
            'student_id' => $studentId,
            'overall_progress' => $overallProgress,
            'units_progress' => $progress->groupBy('unit_id')->map(function ($unitProgress) {
                return [
                    'unit_id' => $unitProgress->first()->unit_id,
                    'progress' => $unitProgress->avg('progress_percentage'),
                    'modules' => $unitProgress->map(function ($module) {
                        return [
                            'module_id' => $module->module_id,
                            'progress' => $module->progress_percentage,
                            'completed_at' => $module->completed_at,
                        ];
                    })->values()->all()
                ];
            })->values()->all(),
            'is_completed' => $overallProgress >= 100,
            'can_generate_certificate' => $overallProgress >= 100
        ];
    }

    /**
     * Get student dashboard data
     */
    public function getStudentDashboard(int $studentId): array
    {
        // Get enrolled courses with progress
        $enrolledCourses = DB::table('course_enrollments')
            ->join('courses', 'course_enrollments.course_id', '=', 'courses.id')
            ->leftJoin('users', 'courses.teacher_id', '=', 'users.id')
            ->where('course_enrollments.student_id', $studentId)
            ->where('course_enrollments.status', 'active')
            ->select([
                'courses.*',
                'users.name as teacher_name',
                'course_enrollments.enrolled_at',
                'course_enrollments.status as enrollment_status'
            ])
            ->get();

        $coursesWithProgress = $enrolledCourses->map(function ($course) use ($studentId) {
            $progress = $this->calculateCourseProgress($studentId, $course->id);
            $course->progress = $progress;
            $course->is_completed = $progress >= 100;
            return $course;
        });

        // Get certificates
        $certificates = DB::table('certificates')
            ->join('courses', 'certificates.course_id', '=', 'courses.id')
            ->where('certificates.student_id', $studentId)
            ->select([
                'certificates.*',
                'courses.title as course_title'
            ])
            ->get();

        // Calculate stats
        $totalCourses = $enrolledCourses->count();
        $completedCourses = $coursesWithProgress->where('is_completed', true)->count();
        $averageProgress = $coursesWithProgress->avg('progress');

        return [
            'stats' => [
                'total_courses' => $totalCourses,
                'completed_courses' => $completedCourses,
                'in_progress_courses' => $totalCourses - $completedCourses,
                'average_progress' => round($averageProgress, 1),
                'total_certificates' => $certificates->count()
            ],
            'enrolled_courses' => $coursesWithProgress->values()->all(),
            'recent_certificates' => $certificates->take(5)->values()->all(),
            'recent_activity' => $this->getRecentActivity($studentId, 10)
        ];
    }

    /**
     * Generate certificate for completed course
     */
    public function generateCertificate(int $studentId, int $courseId): array
    {
        // Verify course completion
        $progress = $this->calculateCourseProgress($studentId, $courseId);
        
        if ($progress < 100) {
            throw new \Exception('Course must be 100% completed to generate certificate');
        }

        // Check if certificate already exists
        $existingCertificate = DB::table('certificates')
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->first();

        if ($existingCertificate) {
            return [
                'success' => true,
                'message' => 'Certificate already exists',
                'certificate' => $existingCertificate,
                'download_url' => $this->generateCertificateUrl($existingCertificate->id)
            ];
        }

        return DB::transaction(function () use ($studentId, $courseId) {
            // Get course and student info
            $course = Course::find($courseId);
            $student = DB::table('users')->where('id', $studentId)->first();
            
            if (!$course || !$student) {
                throw new \Exception('Course or student not found');
            }

            // Create certificate record
            $certificateId = DB::table('certificates')->insertGetId([
                'student_id' => $studentId,
                'course_id' => $courseId,
                'certificate_number' => $this->generateCertificateNumber($studentId, $courseId),
                'issued_at' => now(),
                'student_name' => $student->name,
                'course_title' => $course->title,
                'course_duration_hours' => $course->duration_hours,
                'grade' => 'Aprobado', // For MVP, everyone who completes gets "Aprobado"
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $certificate = DB::table('certificates')->where('id', $certificateId)->first();

            return [
                'success' => true,
                'message' => 'Certificate generated successfully',
                'certificate' => $certificate,
                'download_url' => $this->generateCertificateUrl($certificateId)
            ];
        });
    }

    // ===== PRIVATE METHODS =====

    private function calculateCourseProgress(int $studentId, int $courseId): float
    {
        // Get total units for the course
        $totalUnits = DB::table('course_units')
            ->where('course_id', $courseId)
            ->where('is_published', true)
            ->count();

        if ($totalUnits === 0) {
            return 0;
        }

        // Get completed units (units with 100% progress)
        $completedUnits = DB::table('course_progress')
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('progress_percentage', '>=', 100)
            ->distinct('unit_id')
            ->count();

        return min(100, ($completedUnits / $totalUnits) * 100);
    }

    private function generateCertificateNumber(int $studentId, int $courseId): string
    {
        return 'CERT-' . str_pad($courseId, 3, '0', STR_PAD_LEFT) . '-' . 
               str_pad($studentId, 4, '0', STR_PAD_LEFT) . '-' . 
               date('Y');
    }

    private function generateCertificateUrl(int $certificateId): string
    {
        // For MVP, return a simple URL. In production, this would generate a PDF
        return url("/api/certificates/{$certificateId}/download");
    }

    private function getRecentActivity(int $studentId, int $limit = 10): array
    {
        return DB::table('course_progress')
            ->join('courses', 'course_progress.course_id', '=', 'courses.id')
            ->join('course_units', 'course_progress.unit_id', '=', 'course_units.id')
            ->where('course_progress.student_id', $studentId)
            ->select([
                'course_progress.*',
                'courses.title as course_title',
                'course_units.title as unit_title'
            ])
            ->orderBy('course_progress.updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'type' => $activity->progress_percentage >= 100 ? 'completed' : 'progress',
                    'course_title' => $activity->course_title,
                    'unit_title' => $activity->unit_title,
                    'progress' => $activity->progress_percentage,
                    'date' => $activity->updated_at,
                ];
            })
            ->values()
            ->all();
    }
}