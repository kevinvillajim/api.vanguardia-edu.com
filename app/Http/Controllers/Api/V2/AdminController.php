<?php

namespace App\Http\Controllers\Api\V2;

use App\Domain\Course\Models\Certificate;
use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseEnrollment;
use App\Domain\Course\Services\CourseCloneService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    protected CourseCloneService $cloneService;

    public function __construct(CourseCloneService $cloneService)
    {
        $this->cloneService = $cloneService;
    }

    /**
     * Obtiene configuraciones del sistema
     */
    public function getSystemSettings(): JsonResponse
    {
        $settings = DB::table('system_settings')
            ->get()
            ->groupBy('category')
            ->map(function ($categorySettings) {
                return $categorySettings->mapWithKeys(function ($setting) {
                    $value = $this->parseSettingValue($setting->value, $setting->type);

                    return [$setting->key => $value];
                });
            });

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Actualiza configuraciones del sistema
     */
    public function updateSystemSettings(Request $request): JsonResponse
    {
        $request->validate([
            'certificates.virtual_threshold' => 'integer|min:50|max:100',
            'certificates.complete_threshold' => 'integer|min:50|max:100',
            'certificates.allow_retry' => 'boolean',
            'certificates.auto_generate' => 'boolean',
            'grading.interactive_weight' => 'integer|min:0|max:100',
            'grading.activities_weight' => 'integer|min:0|max:100',
            'grading.passing_grade' => 'integer|min:0|max:100',
            'grading.max_quiz_attempts' => 'integer|min:1|max:10',
            'courses.max_students_per_course' => 'integer|min:1|max:1000',
            'courses.allow_self_enrollment' => 'boolean',
            'courses.require_approval' => 'boolean',
            'courses.default_course_duration' => 'integer|min:1|max:365',
            'notifications.email_on_completion' => 'boolean',
            'notifications.email_on_grading' => 'boolean',
            'notifications.reminder_days' => 'integer|min:1|max:30',
            'notifications.digest_frequency' => Rule::in(['daily', 'weekly', 'monthly']),
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->all() as $category => $settings) {
                if (! is_array($settings)) {
                    continue;
                }

                foreach ($settings as $key => $value) {
                    $settingKey = $category === 'grading' && $key === 'grade_weights'
                        ? 'grade_weights'
                        : "{$category}_{$key}";

                    if ($key === 'grade_weights') {
                        $value = json_encode($value);
                        $type = 'json';
                    } else {
                        $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string');
                        $value = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
                    }

                    DB::table('system_settings')
                        ->updateOrInsert(
                            ['key' => $settingKey],
                            [
                                'value' => $value,
                                'type' => $type,
                                'category' => $category,
                                'updated_at' => now(),
                            ]
                        );
                }
            }
        });

        // Limpiar cache de configuraciones
        Cache::forget('system_settings');

        return response()->json([
            'success' => true,
            'message' => 'Configuraciones actualizadas exitosamente',
        ]);
    }

    /**
     * Dashboard del administrador con estadísticas
     */
    public function getDashboard(): JsonResponse
    {
        $stats = [
            'courses' => [
                'total' => Course::count(),
                'published' => Course::where('is_published', true)->count(),
                'draft' => Course::where('is_published', false)->count(),
                'this_month' => Course::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'enrollments' => [
                'total' => CourseEnrollment::count(),
                'active' => CourseEnrollment::where('status', 'active')->count(),
                'completed' => CourseEnrollment::where('status', 'completed')->count(),
                'this_month' => CourseEnrollment::where('enrolled_at', '>=', now()->startOfMonth())->count(),
            ],
            'certificates' => [
                'total' => Certificate::where('is_valid', true)->count(),
                'virtual' => Certificate::where('type', 'virtual')->where('is_valid', true)->count(),
                'complete' => Certificate::where('type', 'complete')->where('is_valid', true)->count(),
                'this_month' => Certificate::where('issued_at', '>=', now()->startOfMonth())->count(),
            ],
            'users' => [
                'total' => DB::table('users')->count(),
                'students' => DB::table('users')->where('role', 'student')->count(),
                'teachers' => DB::table('users')->where('role', 'teacher')->count(),
                'admins' => DB::table('users')->where('role', 'admin')->count(),
            ],
        ];

        // Estadísticas de actividad reciente
        $recentActivity = [
            'new_enrollments' => CourseEnrollment::with(['student', 'course'])
                ->where('enrolled_at', '>=', now()->subDays(7))
                ->orderBy('enrolled_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($enrollment) {
                    return [
                        'student_name' => $enrollment->student->name,
                        'course_title' => $enrollment->course->title,
                        'enrolled_at' => $enrollment->enrolled_at,
                    ];
                }),
            'new_certificates' => Certificate::with(['student', 'course'])
                ->where('issued_at', '>=', now()->subDays(7))
                ->orderBy('issued_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($certificate) {
                    return [
                        'student_name' => $certificate->student->name,
                        'course_title' => $certificate->course->title,
                        'type' => $certificate->type,
                        'issued_at' => $certificate->issued_at,
                    ];
                }),
        ];

        // Cursos más populares
        $popularCourses = Course::select('courses.*', DB::raw('COUNT(course_enrollments.id) as enrollments_count'))
            ->leftJoin('course_enrollments', 'courses.id', '=', 'course_enrollments.course_id')
            ->where('courses.is_published', true)
            ->groupBy('courses.id')
            ->orderBy('enrollments_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'enrollments_count' => $course->enrollments_count,
                    'teacher_name' => $course->teacher->name ?? 'N/A',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_activity' => $recentActivity,
                'popular_courses' => $popularCourses,
            ],
        ]);
    }

    /**
     * Obtiene todos los cursos con información administrativa
     */
    public function getAllCourses(Request $request): JsonResponse
    {
        $query = Course::with(['teacher', 'enrollments', 'certificates'])
            ->withCount(['enrollments', 'modules', 'activities']);

        // Filtros
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('is_published', $request->status === 'published');
        }

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $courses = $query->orderBy($request->get('sort', 'created_at'), $request->get('order', 'desc'))
            ->paginate($request->get('per_page', 20));

        $coursesData = $courses->getCollection()->map(function ($course) {
            $activeEnrollments = $course->enrollments->where('status', 'active')->count();
            $completedEnrollments = $course->enrollments->where('status', 'completed')->count();
            $certificatesIssued = $course->certificates->where('is_valid', true)->count();

            return [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'teacher_name' => $course->teacher->name ?? 'N/A',
                'is_published' => $course->is_published,
                'modules_count' => $course->modules_count,
                'activities_count' => $course->activities_count,
                'enrollments_count' => $course->enrollments_count,
                'active_enrollments' => $activeEnrollments,
                'completed_enrollments' => $completedEnrollments,
                'certificates_issued' => $certificatesIssued,
                'created_at' => $course->created_at,
                'clone_history' => $this->cloneService->getCloneHistory($course),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $coursesData,
            'pagination' => [
                'total' => $courses->total(),
                'per_page' => $courses->perPage(),
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
            ],
        ]);
    }

    /**
     * Obtiene estadísticas de certificaciones
     */
    public function getCertificationStats(): JsonResponse
    {
        $stats = [
            'overview' => [
                'total_certificates' => Certificate::where('is_valid', true)->count(),
                'virtual_certificates' => Certificate::where('type', 'virtual')->where('is_valid', true)->count(),
                'complete_certificates' => Certificate::where('type', 'complete')->where('is_valid', true)->count(),
                'invalidated_certificates' => Certificate::where('is_valid', false)->count(),
            ],
            'by_month' => Certificate::select(
                DB::raw('YEAR(issued_at) as year'),
                DB::raw('MONTH(issued_at) as month'),
                DB::raw('COUNT(*) as count'),
                'type'
            )
                ->where('is_valid', true)
                ->where('issued_at', '>=', now()->subYear())
                ->groupBy('year', 'month', 'type')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get()
                ->groupBy(['year', 'month']),
            'by_course' => Certificate::select('courses.title', DB::raw('COUNT(*) as count'))
                ->join('courses', 'certificates.course_id', '=', 'courses.id')
                ->where('certificates.is_valid', true)
                ->groupBy('courses.id', 'courses.title')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
            'current_thresholds' => [
            'virtual_threshold' => $this->getSystemSetting('certificate_virtual_threshold', 80),
            'complete_threshold' => $this->getSystemSetting('certificate_complete_threshold', 70),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Obtiene estadísticas de clonación
     */
    public function getCloneStats(): JsonResponse
    {
        $stats = $this->cloneService->getCloneStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Invalida un certificado
     */
    public function invalidateCertificate(Request $request, int $certificateId): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $certificate = Certificate::findOrFail($certificateId);
        $certificate->invalidate($request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Certificado invalidado exitosamente',
        ]);
    }

    /**
     * Analiza el valor de configuración según su tipo
     */
    private function parseSettingValue(string $value, string $type)
    {
        return match ($type) {
            'boolean' => $value === 'true',
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value
        };
    }

    /**
     * Obtiene una configuración específica del sistema
     */
    private function getSystemSetting(string $key, $default = null)
    {
        $setting = DB::table('system_settings')->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return $this->parseSettingValue($setting->value, $setting->type);
    }
}
