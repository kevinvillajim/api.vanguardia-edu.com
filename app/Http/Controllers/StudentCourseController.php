<?php

namespace App\Http\Controllers;

use App\Domain\Course\Services\StudentCourseViewService;
use App\Domain\Student\Services\StudentProgressService;
use App\Domain\Course\Services\UnitProgressService;
use App\Domain\Course\Models\CourseEnrollment;
use App\Domain\Course\Models\Certificate;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentCourseController extends Controller
{
    private StudentCourseViewService $courseViewService;
    private StudentProgressService $progressService;
    private UnitProgressService $unitProgressService;

    public function __construct(
        StudentCourseViewService $courseViewService,
        StudentProgressService $progressService,
        UnitProgressService $unitProgressService
    ) {
        $this->courseViewService = $courseViewService;
        $this->progressService = $progressService;
        $this->unitProgressService = $unitProgressService;
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
     * Get course data for student (alias for show method)
     */
    public function getCourse(string $courseId): JsonResponse
    {
        return $this->show($courseId);
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
     * Get student dashboard data with enrollments and certificates
     */
    public function getDashboard(): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            // Get student enrollments with course and teacher info
            $enrollments = CourseEnrollment::where('student_id', $studentId)
                ->with([
                    'course' => function($query) {
                        $query->select('id', 'title', 'description', 'banner_image', 'teacher_id');
                    },
                    'course.teacher' => function($query) {
                        $query->select('id', 'name');
                    },
                    'certificates' => function($query) {
                        $query->where('is_valid', true)
                              ->select('id', 'enrollment_id', 'type', 'certificate_number', 'issued_at', 'final_score', 'course_progress');
                    }
                ])
                ->get();
            
            // Calculate stats
            $totalEnrollments = $enrollments->count();
            $activeEnrollments = $enrollments->where('status', 'active')->count();
            $completedCourses = $enrollments->where('status', 'completed')->count();
            $totalCertificates = $enrollments->sum(function($enrollment) {
                return $enrollment->certificates->count();
            });
            $averageProgress = $enrollments->where('status', '!=', 'dropped')->avg('progress_percentage') ?? 0;
            
            // Format enrollments for frontend
            $formattedEnrollments = $enrollments->map(function ($enrollment) {
                $certificates = [];
                foreach ($enrollment->certificates as $cert) {
                    $certificates[$cert->type] = [
                        'id' => $cert->id,
                        'type' => $cert->type,
                        'certificate_number' => $cert->certificate_number,
                        'issued_at' => $cert->issued_at->toISOString(),
                        'final_score' => $cert->final_score,
                        'course_progress' => $cert->course_progress,
                        'download_url' => url("/api/certificates/{$cert->id}/download")
                    ];
                }
                
                return [
                    'id' => $enrollment->id,
                    'course_id' => $enrollment->course_id,
                    'course_title' => $enrollment->course->title,
                    'course_description' => $enrollment->course->description,
                    'course_banner_url' => $enrollment->course->banner_image,
                    'teacher_name' => $enrollment->course->teacher->name ?? 'N/A',
                    'status' => $enrollment->status,
                    'progress_percentage' => $enrollment->progress_percentage,
                    'enrolled_at' => $enrollment->enrolled_at->toISOString(),
                    'completed_at' => $enrollment->completed_at?->toISOString(),
                    'can_generate_certificate' => $enrollment->canGetVirtualCertificate() || $enrollment->canGetCompleteCertificate(),
                    'certificates' => $certificates ?: null
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'total_enrollments' => $totalEnrollments,
                        'active_enrollments' => $activeEnrollments,
                        'completed_courses' => $completedCourses,
                        'total_certificates' => $totalCertificates,
                        'average_progress' => round($averageProgress, 1)
                    ],
                    'enrollments' => $formattedEnrollments,
                    'recent_activity' => [] // TODO: Implement if needed
                ],
                'message' => 'Dashboard data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update unit progress with breakpoint tracking
     */
    public function updateUnitProgressBreakpoints(Request $request): JsonResponse
    {
        $request->validate([
            'enrollment_id' => 'required|integer',
            'unit_id' => 'required|integer', 
            'scroll_progress' => 'required|numeric|min:0|max:100',
            'activities_progress' => 'required|numeric|min:0|max:100',
            'completed_components' => 'array',
            'completed_components.*' => 'string',
            'metadata' => 'array'
        ]);

        try {
            $result = $this->unitProgressService->updateUnitProgress(
                $request->input('enrollment_id'),
                $request->input('unit_id'),
                $request->input('scroll_progress'),
                $request->input('activities_progress'),
                $request->input('completed_components', []),
                $request->input('metadata', [])
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get unit progress with breakpoints
     */
    public function getUnitProgressBreakpoints(string $enrollmentId, string $unitId): JsonResponse
    {
        try {
            $result = $this->unitProgressService->getUnitProgress((int)$enrollmentId, (int)$unitId);
            
            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Check if final quiz is accessible for a unit
     */
    public function checkFinalQuizAccess(string $enrollmentId, string $unitId): JsonResponse
    {
        try {
            $canAccess = $this->unitProgressService->canAccessFinalQuiz((int)$enrollmentId, (int)$unitId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'enrollment_id' => (int)$enrollmentId,
                    'unit_id' => (int)$unitId,
                    'can_access_final_quiz' => $canAccess
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
     * Get course-wide progress summary with all units
     */
    public function getCourseProgressSummary(string $enrollmentId): JsonResponse
    {
        try {
            $result = $this->unitProgressService->getCourseProgressSummary((int)$enrollmentId);
            
            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get all breakpoints for a unit (debugging/analytics)
     */
    public function getUnitBreakpoints(string $enrollmentId, string $unitId): JsonResponse
    {
        try {
            $breakpoints = $this->unitProgressService->getUnitBreakpoints((int)$enrollmentId, (int)$unitId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'enrollment_id' => (int)$enrollmentId,
                    'unit_id' => (int)$unitId,
                    'breakpoints' => $breakpoints
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
     * Get all certificates for current student
     */
    public function getCertificates(): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            $certificates = Certificate::where('student_id', $studentId)
                ->where('is_valid', true)
                ->with(['course:id,title', 'enrollment:id,course_id,progress_percentage'])
                ->orderBy('issued_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $certificates,
                'message' => 'Certificates retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get certificates for a specific enrollment
     */
    public function getEnrollmentCertificates(string $enrollmentId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            // Verify enrollment belongs to student
            $enrollment = CourseEnrollment::where('id', $enrollmentId)
                ->where('student_id', $studentId)
                ->firstOrFail();
            
            $certificates = Certificate::where('enrollment_id', $enrollmentId)
                ->where('is_valid', true)
                ->with(['course:id,title'])
                ->orderBy('issued_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $certificates,
                'message' => 'Enrollment certificates retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Check certificate eligibility for enrollment
     */
    public function checkCertificateEligibility(Request $request, string $enrollmentId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            $type = $request->input('type', 'virtual'); // 'virtual' or 'complete'
            
            // Verify enrollment belongs to student
            $enrollment = CourseEnrollment::where('id', $enrollmentId)
                ->where('student_id', $studentId)
                ->with('course')
                ->firstOrFail();
            
            $canGenerate = false;
            
            if ($type === 'virtual') {
                $canGenerate = $enrollment->canGetVirtualCertificate();
            } elseif ($type === 'complete') {
                $canGenerate = $enrollment->canGetCompleteCertificate();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'can_generate' => $canGenerate,
                    'type' => $type,
                    'enrollment_id' => $enrollmentId
                ],
                'message' => 'Certificate eligibility checked successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => ['can_generate' => false]
            ], 500);
        }
    }

    /**
     * Download certificate PDF
     */
    public function downloadCertificate(string $certificateId): Response
    {
        try {
            $studentId = Auth::id();
            
            $certificate = Certificate::where('id', $certificateId)
                ->where('student_id', $studentId)
                ->where('is_valid', true)
                ->with(['course', 'student', 'enrollment'])
                ->firstOrFail();

            // Check if PDF exists, if not, generate it
            if (!$certificate->pdfExists()) {
                $certificate->generatePDF();
            }
            
            // Get the file path
            $filePath = storage_path("app/public/{$certificate->file_url}");
            
            if (!file_exists($filePath)) {
                throw new \Exception('Certificate PDF file not found');
            }
            
            return response()->download(
                $filePath,
                "certificate-{$certificate->certificate_number}.pdf",
                [
                    'Content-Type' => 'application/pdf',
                ]
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Generate certificate for enrollment
     */
    public function generateCertificate(Request $request, string $enrollmentId): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:virtual,complete'
        ]);

        try {
            $studentId = Auth::id();
            $type = $request->input('type');
            
            // Verify enrollment belongs to student
            $enrollment = CourseEnrollment::where('id', $enrollmentId)
                ->where('student_id', $studentId)
                ->with('course')
                ->firstOrFail();
            
            $certificateService = new \App\Domain\Course\Services\CertificateService();
            
            if ($type === 'virtual') {
                $certificate = $certificateService->generateVirtualCertificate($enrollment);
            } else {
                $certificate = $certificateService->generateCompleteCertificate($enrollment);
            }
            
            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'No eres elegible para este tipo de certificado'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $certificate,
                'message' => 'Certificate generated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get student activity (recent enrollments, progress updates, etc.)
     */
    public function getActivity(Request $request): JsonResponse
    {
        try {
            $studentId = $request->input('user_id', Auth::id());
            $limit = $request->input('limit', 10);

            // Get recent enrollments and progress updates
            $recentEnrollments = CourseEnrollment::where('student_id', $studentId)
                ->with(['course:id,title'])
                ->latest('enrolled_at')
                ->limit($limit)
                ->get()
                ->map(function ($enrollment) {
                    return [
                        'id' => $enrollment->id,
                        'type' => 'enrollment',
                        'course_title' => $enrollment->course->title,
                        'progress' => $enrollment->progress_percentage,
                        'status' => $enrollment->status,
                        'date' => $enrollment->enrolled_at->toISOString(),
                        'description' => "Enrolled in {$enrollment->course->title}"
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $recentEnrollments->toArray(),
                'message' => 'Student activity retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}