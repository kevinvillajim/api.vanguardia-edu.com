<?php

namespace App\Http\Controllers;

use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseEnrollment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    /**
     * Obtiene estadísticas del profesor
     */
    public function getStats(): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            
            // Obtener estadísticas básicas
            $totalCourses = Course::where('teacher_id', $teacherId)->count();
            $publishedCourses = Course::where('teacher_id', $teacherId)
                ->where('is_published', true)
                ->count();
            
            // Total de estudiantes inscritos en todos los cursos del profesor
            $totalStudents = CourseEnrollment::whereHas('course', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })->distinct('student_id')->count();
            
            // Estudiantes activos (con progreso reciente)
            $activeStudents = CourseEnrollment::whereHas('course', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->where('status', 'active')
            ->where('updated_at', '>=', now()->subDays(30))
            ->distinct('student_id')
            ->count();
            
            // Promedio de progreso general
            $avgProgress = CourseEnrollment::whereHas('course', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->where('status', 'active')
            ->avg('progress_percentage') ?? 0;
            
            // Cursos completados
            $completedEnrollments = CourseEnrollment::whereHas('course', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->where('status', 'completed')
            ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_courses' => $totalCourses,
                    'published_courses' => $publishedCourses,
                    'draft_courses' => $totalCourses - $publishedCourses,
                    'total_students' => $totalStudents,
                    'active_students' => $activeStudents,
                    'completed_enrollments' => $completedEnrollments,
                    'average_progress' => round($avgProgress, 1),
                    'engagement_rate' => $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100, 1) : 0,
                ],
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene actividad reciente del profesor
     */
    public function getRecentActivity(Request $request): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            $limit = $request->get('limit', 10);
            
            // Obtener enrollments recientes en cursos del profesor
            $recentEnrollments = CourseEnrollment::with(['course', 'student'])
                ->whereHas('course', function ($query) use ($teacherId) {
                    $query->where('teacher_id', $teacherId);
                })
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
            
            $activities = [];
            
            foreach ($recentEnrollments as $enrollment) {
                $activities[] = [
                    'id' => $enrollment->id,
                    'type' => 'enrollment',
                    'title' => 'Nuevo estudiante inscrito',
                    'description' => $enrollment->student->name . ' se inscribió en ' . $enrollment->course->title,
                    'student_name' => $enrollment->student->name,
                    'course_name' => $enrollment->course->title,
                    'date' => $enrollment->created_at,
                    'status' => $enrollment->status,
                    'progress' => $enrollment->progress_percentage,
                ];
            }
            
            // Ordenar por fecha más reciente
            usort($activities, function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            // Limitar el resultado
            $activities = array_slice($activities, 0, $limit);

            return response()->json([
                'success' => true,
                'data' => $activities,
                'message' => 'Actividad reciente obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene el dashboard completo del profesor
     */
    public function getDashboard(): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            
            // Obtener estadísticas
            $statsResponse = $this->getStats();
            $stats = $statsResponse->getData(true)['data'];
            
            // Obtener actividad reciente
            $activityResponse = $this->getRecentActivity(new Request(['limit' => 5]));
            $recentActivity = $activityResponse->getData(true)['data'];
            
            // Obtener cursos del profesor
            $courses = Course::where('teacher_id', $teacherId)
                ->with(['enrollments'])
                ->withCount(['enrollments'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'title' => $course->title,
                        'is_published' => $course->is_published,
                        'enrollments_count' => $course->enrollments_count,
                        'created_at' => $course->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_activity' => $recentActivity,
                    'recent_courses' => $courses,
                ],
                'message' => 'Dashboard obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene estudiantes del profesor (de todos sus cursos)
     */
    public function getStudents(Request $request): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            
            $query = CourseEnrollment::with(['student', 'course'])
                ->whereHas('course', function ($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                });
            
            // Filtros opcionales
            if ($request->has('course_id')) {
                $query->where('course_id', $request->course_id);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('student', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            $enrollments = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));
            
            $studentsData = $enrollments->getCollection()->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'student_id' => $enrollment->student_id,
                    'student_name' => $enrollment->student->name,
                    'student_email' => $enrollment->student->email,
                    'course_id' => $enrollment->course_id,
                    'course_title' => $enrollment->course->title,
                    'status' => $enrollment->status,
                    'progress_percentage' => $enrollment->progress_percentage,
                    'enrolled_at' => $enrollment->enrolled_at,
                    'completed_at' => $enrollment->completed_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $studentsData,
                'pagination' => [
                    'current_page' => $enrollments->currentPage(),
                    'last_page' => $enrollments->lastPage(),
                    'per_page' => $enrollments->perPage(),
                    'total' => $enrollments->total(),
                ],
                'message' => 'Estudiantes obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene las inscripciones de un curso específico del profesor
     */
    public function getCourseEnrollments($courseId): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            
            // Verificar que el curso pertenece al profesor
            $course = Course::where('id', $courseId)
                ->where('teacher_id', $teacherId)
                ->first();
            
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado o no autorizado'
                ], 404);
            }
            
            // Obtener inscripciones del curso
            $enrollments = CourseEnrollment::with(['student'])
                ->where('course_id', $courseId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($enrollment) {
                    return [
                        'id' => $enrollment->id,
                        'student_id' => $enrollment->student_id,
                        'student_name' => $enrollment->student->name,
                        'student_email' => $enrollment->student->email,
                        'status' => $enrollment->status,
                        'progress_percentage' => $enrollment->progress_percentage,
                        'enrolled_at' => $enrollment->enrolled_at,
                        'completed_at' => $enrollment->completed_at,
                        'last_activity' => $enrollment->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $enrollments,
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'total_enrollments' => $enrollments->count(),
                ],
                'message' => 'Inscripciones obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}