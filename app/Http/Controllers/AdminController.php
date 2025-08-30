<?php

namespace App\Http\Controllers;

use App\Domain\Course\Models\Certificate;
use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseEnrollment;
use App\Domain\Course\Services\CourseCloneService;
use App\Domain\Admin\Services\AdminService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    protected CourseCloneService $cloneService;
    protected AdminService $adminService;

    public function __construct(CourseCloneService $cloneService, AdminService $adminService)
    {
        $this->cloneService = $cloneService;
        $this->adminService = $adminService;
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
        try {
            $dashboardData = $this->adminService->getDashboardStats();
            
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

    // ========================================
    // USER MANAGEMENT METHODS
    // ========================================

    /**
     * Obtiene lista de usuarios con paginación
     */
    public function getUsers(Request $request): JsonResponse
    {
        try {
            $query = \App\Models\User::query();

            // Filtros opcionales
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->has('active')) {
                $query->where('active', $request->active);
            }

            $users = $query->orderBy($request->get('sort', 'created_at'), $request->get('order', 'desc'))
                ->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
                'message' => 'Usuarios obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene un usuario específico
     */
    public function getUser(int $userId): JsonResponse
    {
        try {
            $user = \App\Models\User::findOrFail($userId);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Usuario obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }
    }

    /**
     * Crea un nuevo usuario
     */
    public function createUser(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'role' => 'required|integer|in:1,2,3',
                'active' => 'boolean',
            ]);

            $user = \App\Models\User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => $request->role,
                'active' => $request->get('active', true),
            ]);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Usuario creado exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Actualiza un usuario
     */
    public function updateUser(Request $request, int $userId): JsonResponse
    {
        try {
            $user = \App\Models\User::findOrFail($userId);

            $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $userId,
                'password' => 'sometimes|string|min:8',
                'role' => 'sometimes|integer|in:1,2,3',
                'active' => 'sometimes|boolean',
            ]);

            $updateData = $request->only(['name', 'email', 'role', 'active']);

            if ($request->has('password')) {
                $updateData['password'] = bcrypt($request->password);
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $user->fresh(),
                'message' => 'Usuario actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Elimina un usuario
     */
    public function deleteUser(int $userId): JsonResponse
    {
        try {
            $user = \App\Models\User::findOrFail($userId);

            // No permitir eliminar al usuario actual
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propia cuenta',
                ], 400);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Resetea la contraseña de un usuario
     */
    public function resetUserPassword(Request $request, int $userId): JsonResponse
    {
        try {
            $request->validate([
                'password' => 'required|string|min:8',
            ]);

            $user = \App\Models\User::findOrFail($userId);

            $user->update([
                'password' => bcrypt($request->password),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña restablecida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Exporta usuarios a archivo CSV
     */
    public function exportUsers(Request $request)
    {
        try {
            $query = \App\Models\User::query();

            // Aplicar filtros si existen
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->has('active')) {
                $query->where('active', $request->active);
            }

            $users = $query->orderBy('created_at', 'desc')->get();

            // Crear contenido CSV
            $csvContent = "ID,Nombre,Email,CI,Teléfono,Rol,Activo,Email Verificado,Fecha de Registro\n";
            
            foreach ($users as $user) {
                $roleNames = [1 => 'Admin', 2 => 'Estudiante', 3 => 'Profesor'];
                $roleName = $roleNames[$user->role] ?? 'Desconocido';
                $active = $user->active ? 'Sí' : 'No';
                $emailVerified = $user->email_verified_at ? 'Sí' : 'No';
                $createdAt = $user->created_at->format('d/m/Y H:i');
                
                $csvContent .= "{$user->id}," . 
                              "\"{$user->name}\"," . 
                              "\"{$user->email}\"," . 
                              "\"{$user->ci}\"," . 
                              "\"{$user->phone}\"," . 
                              "\"{$roleName}\"," . 
                              "\"{$active}\"," . 
                              "\"{$emailVerified}\"," . 
                              "\"{$createdAt}\"\n";
            }

            // Retornar el archivo CSV
            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="usuarios_' . date('Y-m-d') . '.csv"');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Importa usuarios desde un archivo CSV
     */
    public function importUsers(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt',
            ]);

            // Por ahora, retornar un mensaje indicando que la funcionalidad está pendiente
            return response()->json([
                'success' => false,
                'message' => 'Funcionalidad de importación en desarrollo',
            ], 501);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // ========================================
    // REPORTS AND ANALYTICS METHODS
    // ========================================

    /**
     * Get detailed user report
     */
    public function getUserReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['role', 'date_from', 'date_to']);
            $report = $this->adminService->getDetailedUserReport($filters);

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'User report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get detailed course report
     */
    public function getCourseReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['teacher_id', 'is_published']);
            $report = $this->adminService->getDetailedCourseReport($filters);

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Course report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get system health status
     */
    public function getSystemHealth(): JsonResponse
    {
        try {
            $health = $this->adminService->getSystemHealth();

            return response()->json([
                'success' => true,
                'data' => $health,
                'message' => 'System health retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
