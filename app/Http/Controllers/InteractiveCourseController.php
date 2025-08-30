<?php

namespace App\Http\Controllers;

use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseEnrollment;
use App\Domain\Course\Models\CourseModule;
use App\Domain\Course\Models\CourseProgress;
use App\Domain\Course\Models\ModuleComponent;
use App\Domain\Course\Models\Quiz;
use App\Domain\Course\Models\QuizAttempt;
use App\Domain\Course\Models\StudentActivity;
use App\Domain\Course\Services\CourseCloneService;
use App\Domain\Course\Services\GradingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InteractiveCourseController extends Controller
{
    protected GradingService $gradingService;

    protected CourseCloneService $cloneService;

    public function __construct(GradingService $gradingService, CourseCloneService $cloneService)
    {
        $this->gradingService = $gradingService;
        $this->cloneService = $cloneService;
    }

    /**
     * Lista cursos del estudiante con progreso
     */
    public function getStudentCourses(): JsonResponse
    {
        $user = Auth::user();

        $enrollments = CourseEnrollment::with(['course.modules.components', 'course.activities'])
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->get();

        $courses = $enrollments->map(function ($enrollment) {
            $progress = $this->gradingService->calculateCourseProgress($enrollment);
            $finalScore = $this->gradingService->calculateFinalScore($enrollment);

            return [
                'id' => $enrollment->course->id,
                'title' => $enrollment->course->title,
                'description' => $enrollment->course->description,
                'banner_image' => $enrollment->course->banner_image,
                'progress' => $progress,
                'final_score' => $finalScore,
                'enrollment_id' => $enrollment->id,
                'enrolled_at' => $enrollment->enrolled_at,
                'certificates' => $enrollment->getExistingCertificates(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

    /**
     * Obtiene la vista detallada del curso para estudiante
     */
    public function getStudentCourseView(int $courseId): JsonResponse
    {
        $user = Auth::user();

        $enrollment = CourseEnrollment::with([
            'course.modules.components',
            'course.modules.quizzes.questions',
            'course.activities',
        ])
            ->where('course_id', $courseId)
            ->where('student_id', $user->id)
            ->firstOrFail();

        $course = $enrollment->course;

        // Calcular progreso y calificaciones
        $progress = $enrollment->calculateProgress();
        $finalScore = $this->gradingService->calculateFinalScore($enrollment);
        $interactiveAverage = $this->gradingService->calculateInteractiveAverage($enrollment);
        $activitiesAverage = $this->gradingService->calculateActivitiesAverage($enrollment);

        // Procesar módulos con estado del estudiante
        $modulesData = $course->modules->map(function ($module) use ($enrollment) {
            $components = $module->components->map(function ($component) use ($enrollment) {
                $progressRecord = CourseProgress::where('enrollment_id', $enrollment->id)
                    ->where('type', 'component')
                    ->where('reference_id', $component->id)
                    ->first();

                return [
                    'id' => $component->id,
                    'type' => $component->type,
                    'title' => $component->title,
                    'content' => $component->content,
                    'file_url' => $component->file_url,
                    'duration' => $component->getEstimatedDuration(),
                    'is_mandatory' => $component->is_mandatory,
                    'is_completed' => $progressRecord ? $progressRecord->is_completed : false,
                    'completed_at' => $progressRecord ? $progressRecord->completed_at : null,
                ];
            });

            $quiz = null;
            if ($module->quizzes->isNotEmpty()) {
                $moduleQuiz = $module->quizzes->first();
                $attempts = QuizAttempt::where('enrollment_id', $enrollment->id)
                    ->where('quiz_id', $moduleQuiz->id)
                    ->orderBy('attempt_number', 'desc')
                    ->get();

                $bestAttempt = $attempts->where('status', 'completed')
                    ->sortByDesc('percentage')
                    ->first();

                $quiz = [
                    'id' => $moduleQuiz->id,
                    'title' => $moduleQuiz->title,
                    'description' => $moduleQuiz->description,
                    'questions_count' => $moduleQuiz->questions->count(),
                    'time_limit' => $moduleQuiz->time_limit,
                    'max_attempts' => $moduleQuiz->max_attempts,
                    'attempts_used' => $attempts->where('status', 'completed')->count(),
                    'best_score' => $bestAttempt ? $bestAttempt->percentage : null,
                    'is_passed' => $bestAttempt ? $bestAttempt->isPassed() : false,
                    'can_attempt' => $moduleQuiz->canStudentAttempt($user->id),
                ];
            }

            return [
                'id' => $module->id,
                'title' => $module->title,
                'description' => $module->description,
                'components' => $components,
                'quiz' => $quiz,
                'progress' => $this->calculateModuleProgress($module, $enrollment),
            ];
        });

        // Procesar actividades con estado del estudiante
        $activitiesData = $course->activities->map(function ($activity) use ($enrollment) {
            $submission = StudentActivity::where('enrollment_id', $enrollment->id)
                ->where('activity_id', $activity->id)
                ->first();

            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'description' => $activity->description,
                'type' => $activity->type,
                'max_score' => $activity->max_score,
                'due_date' => $activity->due_date,
                'is_mandatory' => $activity->is_mandatory,
                'status' => $submission ? $submission->status : 'pending',
                'score' => $submission ? $submission->score : null,
                'feedback' => $submission ? $submission->feedback : null,
                'submitted_at' => $submission ? $submission->submitted_at : null,
                'graded_at' => $submission ? $submission->graded_at : null,
                'is_overdue' => $activity->isOverdue(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'modules' => $modulesData,
                'activities' => $activitiesData,
                'progress' => [
                    'overall' => $progress['overall'],
                    'interactive' => $progress['quiz_average'] ?? 0,
                    'activities' => $progress['activities_average'] ?? 0,
                ],
                'grades' => [
                    'interactive_average' => $interactiveAverage,
                    'activities_average' => $activitiesAverage,
                    'final_score' => $finalScore,
                ],
                'certificates' => [
                    'virtual' => $enrollment->canGetVirtualCertificate(),
                    'complete' => $enrollment->canGetCompleteCertificate(),
                    'existing' => $enrollment->getExistingCertificates(),
                ],
            ],
        ]);
    }

    /**
     * Marca un componente como completado
     */
    public function completeComponent(Request $request, int $courseId, int $componentId): JsonResponse
    {
        $user = Auth::user();

        $enrollment = CourseEnrollment::where('course_id', $courseId)
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $component = ModuleComponent::findOrFail($componentId);

        $progress = CourseProgress::trackProgress(
            $enrollment->id,
            'component',
            $componentId,
            [
                'module_id' => $component->module_id,
                'component_id' => $componentId,
            ]
        );

        $progress->markAsStarted();
        $progress->markAsCompleted();

        // Actualizar progreso general del enrollment
        $this->gradingService->updateEnrollmentProgress($enrollment);

        return response()->json([
            'success' => true,
            'message' => 'Componente marcado como completado',
            'data' => [
                'progress' => $enrollment->fresh()->calculateProgress(),
            ],
        ]);
    }

    /**
     * Inicia un intento de quiz
     */
    public function startQuizAttempt(Request $request, int $quizId): JsonResponse
    {
        $user = Auth::user();

        $quiz = Quiz::with('questions')->findOrFail($quizId);

        // Verificar enrollment
        $enrollment = CourseEnrollment::whereHas('course.modules.quizzes', function ($query) use ($quizId) {
            $query->where('id', $quizId);
        })
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        // Verificar si puede hacer otro intento
        if (! $quiz->canStudentAttempt($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Has alcanzado el máximo número de intentos',
            ], 400);
        }

        $attemptNumber = QuizAttempt::where('quiz_id', $quizId)
            ->where('student_id', $user->id)
            ->count() + 1;

        $attempt = QuizAttempt::create([
            'quiz_id' => $quizId,
            'student_id' => $user->id,
            'enrollment_id' => $enrollment->id,
            'attempt_number' => $attemptNumber,
            'started_at' => now(),
            'status' => 'in_progress',
            'answers' => [],
        ]);

        $questions = $quiz->questions->map(function ($question) {
            return [
                'id' => $question->id,
                'question' => $question->question,
                'type' => $question->type,
                'options' => $question->getFormattedOptions(),
                'points' => $question->points,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'attempt_id' => $attempt->id,
                'quiz' => [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'time_limit' => $quiz->time_limit,
                    'questions' => $questions,
                ],
            ],
        ]);
    }

    /**
     * Completa un intento de quiz
     */
    public function completeQuizAttempt(Request $request, int $attemptId): JsonResponse
    {
        $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'required',
        ]);

        $user = Auth::user();

        $attempt = QuizAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $attempt->complete($request->answers);

        // Actualizar progreso general
        $this->gradingService->updateEnrollmentProgress($attempt->enrollment);

        return response()->json([
            'success' => true,
            'message' => 'Quiz completado exitosamente',
            'data' => [
                'score' => $attempt->score,
                'percentage' => $attempt->percentage,
                'is_passed' => $attempt->isPassed(),
                'time_spent' => $attempt->getTimeSpentFormatted(),
            ],
        ]);
    }

    /**
     * Lista cursos del profesor
     */
    public function getTeacherCourses(): JsonResponse
    {
        $user = Auth::user();

        $courses = Course::with(['modules', 'activities', 'enrollments'])
            ->where('teacher_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $coursesData = $courses->map(function ($course) {
            return [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'is_published' => $course->is_published,
                'modules_count' => $course->modules->count(),
                'activities_count' => $course->activities->count(),
                'students_count' => $course->enrollments->where('status', 'active')->count(),
                'created_at' => $course->created_at,
                'clone_history' => $this->cloneService->getCloneHistory($course),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $coursesData,
        ]);
    }

    /**
     * Clona un curso
     */
    public function cloneCourse(Request $request, int $courseId): JsonResponse
    {
        $request->validate([
            'title_suffix' => 'nullable|string|max:50',
            'clone_modules' => 'boolean',
            'clone_activities' => 'boolean',
            'clone_quizzes' => 'boolean',
        ]);

        $user = Auth::user();
        $originalCourse = Course::findOrFail($courseId);

        // Verificar permisos
        if (! $this->cloneService->canCloneCourse($originalCourse, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para clonar este curso',
            ], 403);
        }

        $options = [
            'clone_modules' => $request->get('clone_modules', true),
            'clone_activities' => $request->get('clone_activities', true),
            'clone_quizzes' => $request->get('clone_quizzes', true),
            'clone_components' => true,
            'suffix' => $request->get('title_suffix', ' (Copia)'),
        ];

        $clonedCourse = $this->cloneService->cloneCourse($originalCourse, $user->id, $options);

        return response()->json([
            'success' => true,
            'message' => 'Curso clonado exitosamente',
            'data' => [
                'id' => $clonedCourse->id,
                'title' => $clonedCourse->title,
                'original_course_id' => $originalCourse->id,
            ],
        ]);
    }

    /**
     * Genera certificado para un estudiante
     */
    public function generateCertificate(Request $request, int $enrollmentId): JsonResponse
    {
        $request->validate([
            'type' => ['required', Rule::in(['virtual', 'complete'])],
        ]);

        $user = Auth::user();
        $enrollment = CourseEnrollment::findOrFail($enrollmentId);

        // Verificar que el estudiante pueda generar el certificado
        $canGenerate = $request->type === 'virtual'
            ? $enrollment->canGetVirtualCertificate()
            : $enrollment->canGetCompleteCertificate();

        if (! $canGenerate) {
            return response()->json([
                'success' => false,
                'message' => 'No cumples los requisitos para este certificado',
            ], 400);
        }

        $certificate = $this->gradingService->generateCertificate($enrollment, $request->type);

        if (! $certificate) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el certificado',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Certificado generado exitosamente',
            'data' => [
                'certificate_number' => $certificate->certificate_number,
                'type' => $certificate->type,
                'issued_at' => $certificate->issued_at,
                'file_url' => $certificate->file_url,
            ],
        ]);
    }

    /**
     * Calcula el progreso de un módulo específico
     */
    private function calculateModuleProgress(CourseModule $module, CourseEnrollment $enrollment): float
    {
        $mandatoryComponents = $module->components()->where('is_mandatory', true)->count();

        if ($mandatoryComponents === 0) {
            return 100;
        }

        $completedComponents = CourseProgress::where('enrollment_id', $enrollment->id)
            ->where('module_id', $module->id)
            ->where('type', 'component')
            ->where('is_completed', true)
            ->count();

        return round(($completedComponents / $mandatoryComponents) * 100, 2);
    }
}
