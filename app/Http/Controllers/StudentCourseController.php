<?php

namespace App\Http\Controllers;

use App\Domain\Course\Services\StudentCourseViewService;
use App\Domain\Student\Services\StudentProgressService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentCourseController extends Controller
{
    private StudentCourseViewService $courseViewService;
    private StudentProgressService $progressService;

    public function __construct(
        StudentCourseViewService $courseViewService,
        StudentProgressService $progressService
    ) {
        $this->courseViewService = $courseViewService;
        $this->progressService = $progressService;
    }

    /**
     * Get student's course view data (compatible with backup structure)
     */
    public function show(string $courseId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            $courseData = $this->courseViewService->getCourseViewData($courseId, $studentId);

            return response()->json([
                'success' => true,
                'data' => $courseData,
                'message' => 'Course data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Get course progress for student
     */
    public function getProgress(string $courseId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            $courseData = $this->courseViewService->getCourseViewData($courseId, $studentId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'progress' => $courseData['progress'],
                    'grades' => $courseData['grades'],
                    'certificates' => $courseData['certificates']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Mark component as completed
     */
    public function markComponentCompleted(Request $request, string $courseId, string $componentId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            // TODO: Implementar lógica para marcar componente como completado
            // Actualizar CourseProgress
            
            return response()->json([
                'success' => true,
                'message' => 'Component marked as completed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update module progress
     */
    public function updateModuleProgress(Request $request, string $courseId, string $moduleId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            $completedComponents = $request->input('completed_components', 0);
            $totalComponents = $request->input('total_components', 1);
            
            $completionPercentage = ($completedComponents / $totalComponents) * 100;
            
            // TODO: Implementar actualización de progreso en CourseProgress
            
            return response()->json([
                'success' => true,
                'data' => [
                    'completion_percentage' => $completionPercentage,
                    'completed_components' => $completedComponents,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get course materials
     */
    public function getMaterials(string $courseId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            $courseData = $this->courseViewService->getCourseViewData($courseId, $studentId);
            
            return response()->json([
                'success' => true,
                'data' => $courseData['materials']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get course activities
     */
    public function getActivities(string $courseId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            $courseData = $this->courseViewService->getCourseViewData($courseId, $studentId);
            
            return response()->json([
                'success' => true,
                'data' => $courseData['activities']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get student enrollments
     */
    public function getEnrollments(): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            // Obtener los enrollments del estudiante con información del curso
            $enrollments = \App\Domain\Course\Models\CourseEnrollment::with(['course'])
                ->where('student_id', $studentId)
                ->get()
                ->map(function ($enrollment) {
                    return [
                        'id' => $enrollment->id,
                        'user_id' => $enrollment->student_id,
                        'course_id' => $enrollment->course_id,
                        'status' => $enrollment->status,
                        'progress_percentage' => $enrollment->progress_percentage,
                        'enrolled_at' => $enrollment->enrolled_at,
                        'completed_at' => $enrollment->completed_at,
                        'course' => $enrollment->course ? [
                            'id' => $enrollment->course->id,
                            'title' => $enrollment->course->title,
                            'description' => $enrollment->course->description,
                            'slug' => $enrollment->course->slug,
                            'banner_image' => $enrollment->course->banner_image,
                        ] : null
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $enrollments,
                'message' => 'Enrollments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get student courses
     */
    public function getCourses(): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            // Por ahora devolver un array vacío hasta que se implemente la lógica completa
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Courses retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get course view for student
     */
    public function getCourseView(string $courseId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            $courseData = $this->courseViewService->getCourseViewData($courseId, $studentId);
            
            return response()->json([
                'success' => true,
                'data' => $courseData,
                'message' => 'Course view retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Enroll student in course
     */
    public function enroll(Request $request, string $courseId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            // TODO: Implementar lógica de enrollamiento
            
            return response()->json([
                'success' => true,
                'message' => 'Student enrolled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Complete component
     */
    public function completeComponent(Request $request, string $courseId, string $componentId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            // TODO: Implementar lógica para completar componente
            
            return response()->json([
                'success' => true,
                'message' => 'Component completed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Start quiz
     */
    public function startQuiz(Request $request, string $quizId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            // TODO: Implementar lógica para iniciar quiz
            
            return response()->json([
                'success' => true,
                'message' => 'Quiz started successfully',
                'data' => ['attempt_id' => 'temp_' . time()]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Complete quiz
     */
    public function completeQuiz(Request $request, string $attemptId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            // TODO: Implementar lógica para completar quiz
            
            return response()->json([
                'success' => true,
                'message' => 'Quiz completed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Generate certificate
     */
    public function generateCertificate(Request $request, string $courseId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            $result = $this->progressService->generateCertificate($studentId, (int) $courseId);
            
            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get progress for a specific unit
     */
    public function getUnitProgress(string $courseId, string $unitId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            $progress = $this->progressService->getCourseProgress($studentId, (int) $courseId);
            
            // Find specific unit progress
            $unitProgress = collect($progress['units_progress'])->firstWhere('unit_id', (int) $unitId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'course_id' => $courseId,
                    'unit_id' => $unitId,
                    'user_id' => $studentId,
                    'progress_percentage' => $unitProgress['progress'] ?? 0,
                    'modules' => $unitProgress['modules'] ?? [],
                    'last_accessed' => now()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update or insert progress
     */
    public function upsertProgress(Request $request): JsonResponse
    {
        $request->validate([
            'course_id' => 'required|integer',
            'unit_id' => 'required|integer',
            'module_id' => 'nullable|integer',
            'progress_percentage' => 'required|integer|min:0|max:100'
        ]);

        try {
            $studentId = Auth::id();
            
            $result = $this->progressService->updateProgress(
                $studentId,
                $request->input('course_id'),
                $request->input('unit_id'),
                $request->input('module_id'),
                $request->input('progress_percentage')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Progress updated successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get student dashboard data
     */
    public function getDashboard(): JsonResponse
    {
        try {
            $studentId = Auth::id();
            $dashboardData = $this->progressService->getStudentDashboard($studentId);
            
            return response()->json([
                'success' => true,
                'data' => $dashboardData,
                'message' => 'Dashboard data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}