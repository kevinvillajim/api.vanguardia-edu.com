<?php

namespace App\Http\Controllers\Api\V2;

use App\Domain\Course\Services\StudentCourseViewService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentCourseController extends Controller
{
    private StudentCourseViewService $courseViewService;

    public function __construct(StudentCourseViewService $courseViewService)
    {
        $this->courseViewService = $courseViewService;
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
}