<?php

namespace App\Http\Controllers;

use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseEnrollment;
use App\Domain\Course\Services\CertificateService;
use App\Domain\User\Services\ProfileService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
                })
                ->whereHas('student', function ($q) {
                    $q->where('role', 2); // Solo estudiantes
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

    /**
     * Obtiene el perfil del profesor autenticado (SIN avatar - consulta rápida)
     */
    public function getProfile(): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            $profileService = new \App\Domain\User\Services\ProfileService();
            
            $profile = $profileService->getUserProfile($teacherId);
            $stats = $profileService->getTeacherStats($teacherId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'profile' => $profile,
                    'stats' => $stats,
                ],
                'message' => 'Perfil obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene solo el avatar del profesor autenticado
     */
    public function getAvatar(): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            $profileService = new \App\Domain\User\Services\ProfileService();
            
            $avatar = $profileService->getUserAvatar($teacherId);
            
            if (!$avatar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avatar no encontrado'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $avatar,
                'message' => 'Avatar obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualiza el perfil del profesor autenticado
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            $profileService = new \App\Domain\User\Services\ProfileService();
            
            // Obtener solo los datos del request
            $data = $request->only(['name', 'phone', 'bio', 'ci']);
            
            // Validar datos
            $rules = $profileService->validateProfileData($data, $teacherId);
            $validator = Validator::make($data, $rules);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $updatedProfile = $profileService->updateProfile($teacherId, $data);
            
            return response()->json([
                'success' => true,
                'data' => $updatedProfile,
                'message' => 'Perfil actualizado exitosamente'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualiza la foto de perfil del profesor
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            
            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo de imagen inválido',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $profileService = new \App\Domain\User\Services\ProfileService();
            $updatedProfile = $profileService->updateAvatar($teacherId, $request->file('avatar'));
            
            return response()->json([
                'success' => true,
                'data' => $updatedProfile,
                'message' => 'Foto de perfil actualizada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina la foto de perfil del profesor
     */
    public function removeAvatar(): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            $profileService = new \App\Domain\User\Services\ProfileService();
            
            $updatedProfile = $profileService->removeAvatar($teacherId);
            
            return response()->json([
                'success' => true,
                'data' => $updatedProfile,
                'message' => 'Foto de perfil eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambia la contraseña del profesor
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            $profileService = new \App\Domain\User\Services\ProfileService();
            
            $validator = Validator::make($request->all(), $profileService->validatePasswordData());
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $profileService->changePassword(
                $teacherId,
                $request->current_password,
                $request->new_password
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // =============================================================================
    // GESTIÓN DE INSCRIPCIONES DE ESTUDIANTES
    // =============================================================================

    /**
     * Inscribe un estudiante a un curso del profesor
     */
    public function enrollStudent(Request $request, $courseId): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|exists:users,id',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
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
            
            // Verificar que el usuario es estudiante (role = 2)
            $student = \App\Models\User::where('id', $request->student_id)
                ->where('role', 2)
                ->first();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estudiante no encontrado'
                ], 404);
            }
            
            // Verificar si ya está inscrito
            $existingEnrollment = CourseEnrollment::where('course_id', $courseId)
                ->where('student_id', $request->student_id)
                ->first();
            
            if ($existingEnrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'El estudiante ya está inscrito en este curso'
                ], 409);
            }
            
            // Crear la inscripción
            $enrollment = CourseEnrollment::create([
                'course_id' => $courseId,
                'student_id' => $request->student_id,
                'enrolled_at' => now(),
                'status' => CourseEnrollment::STATUS_ACTIVE,
                'progress_percentage' => 0,
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $enrollment->id,
                    'student_name' => $student->name,
                    'student_email' => $student->email,
                    'course_title' => $course->title,
                    'enrolled_at' => $enrollment->enrolled_at,
                    'status' => $enrollment->status,
                ],
                'message' => 'Estudiante inscrito exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene estudiantes disponibles para inscribir (no inscritos en el curso)
     */
    public function getAvailableStudents($courseId, Request $request): JsonResponse
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
            
            // Obtener estudiantes ya inscritos en este curso
            $enrolledStudentIds = CourseEnrollment::where('course_id', $courseId)
                ->pluck('student_id')
                ->toArray();
            
            // Obtener estudiantes disponibles (role = 2 y no inscritos)
            $query = \App\Models\User::where('role', 2)
                ->whereNotIn('id', $enrolledStudentIds);
            
            // Filtro de búsqueda opcional
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('ci', 'like', "%{$search}%");
                });
            }
            
            $students = $query->orderBy('name')
                ->limit(50) // Limitar resultados para performance
                ->get(['id', 'name', 'email', 'ci']);
            
            return response()->json([
                'success' => true,
                'data' => $students,
                'message' => 'Estudiantes disponibles obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Desinscribe un estudiante de un curso
     */
    public function unenrollStudent($courseId, $studentId): JsonResponse
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
            
            // Buscar la inscripción
            $enrollment = CourseEnrollment::where('course_id', $courseId)
                ->where('student_id', $studentId)
                ->with('student')
                ->first();
            
            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscripción no encontrada'
                ], 404);
            }
            
            // Cambiar estado a 'dropped' en lugar de eliminar
            $enrollment->update([
                'status' => CourseEnrollment::STATUS_DROPPED,
                'dropped_at' => now(),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'student_name' => $enrollment->student->name,
                    'course_title' => $course->title,
                    'dropped_at' => $enrollment->dropped_at,
                ],
                'message' => 'Estudiante desinscrito exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualiza el estado de una inscripción
     */
    public function updateEnrollmentStatus(Request $request, $courseId, $enrollmentId): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:active,completed,dropped',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estado inválido',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
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
            
            // Buscar la inscripción
            $enrollment = CourseEnrollment::where('id', $enrollmentId)
                ->where('course_id', $courseId)
                ->with('student')
                ->first();
            
            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscripción no encontrada'
                ], 404);
            }
            
            // Actualizar estado
            $updateData = ['status' => $request->status];
            
            if ($request->status === 'completed') {
                $updateData['completed_at'] = now();
                $updateData['progress_percentage'] = 100;
            } elseif ($request->status === 'dropped') {
                $updateData['dropped_at'] = now();
            } elseif ($request->status === 'active') {
                $updateData['completed_at'] = null;
                $updateData['dropped_at'] = null;
            }
            
            $enrollment->update($updateData);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $enrollment->id,
                    'student_name' => $enrollment->student->name,
                    'status' => $enrollment->status,
                    'progress_percentage' => $enrollment->progress_percentage,
                    'updated_at' => $enrollment->updated_at,
                ],
                'message' => 'Estado de inscripción actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // =============================================================================
    // GESTIÓN DE CERTIFICADOS
    // =============================================================================

    /**
     * Genera certificados manualmente para una inscripción
     */
    public function generateCertificate(Request $request, $courseId, $enrollmentId): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:virtual,complete',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de certificado inválido',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
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
            
            // Buscar la inscripción
            $enrollment = CourseEnrollment::where('id', $enrollmentId)
                ->where('course_id', $courseId)
                ->with(['student', 'course'])
                ->first();
            
            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscripción no encontrada'
                ], 404);
            }
            
            $certificateService = new CertificateService();
            
            if ($request->type === 'virtual') {
                $certificate = $certificateService->generateVirtualCertificate($enrollment);
            } else {
                $certificate = $certificateService->generateCompleteCertificate($enrollment);
            }
            
            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'El estudiante no cumple los requisitos para este certificado'
                ], 400);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $certificate->id,
                    'type' => $certificate->type,
                    'certificate_number' => $certificate->certificate_number,
                    'student_name' => $enrollment->student->name,
                    'course_title' => $enrollment->course->title,
                    'issued_at' => $certificate->issued_at,
                    'final_score' => $certificate->final_score,
                ],
                'message' => 'Certificado generado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de certificados de un curso
     */
    public function getCourseStats($courseId): JsonResponse
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
            
            $certificateService = new CertificateService();
            $stats = $certificateService->getCourseStats($courseId);
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas de certificados obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene la configuración actual de certificados
     */
    public function getCertificateConfig(): JsonResponse
    {
        try {
            $certificateService = new CertificateService();
            $config = $certificateService->getCertificateConfig();
            
            return response()->json([
                'success' => true,
                'data' => $config,
                'message' => 'Configuración de certificados obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene los cursos del profesor para inscripciones
     */
    public function getCourses(): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            
            $courses = Course::where('teacher_id', $teacherId)
                ->withCount(['enrollments'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'title' => $course->title,
                        'description' => $course->description,
                        'is_published' => $course->is_published,
                        'enrollments_count' => $course->enrollments_count,
                        'created_at' => $course->created_at,
                        'updated_at' => $course->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $courses,
                'message' => 'Cursos del profesor obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene todos los estudiantes disponibles del sistema para asignarles cursos
     */
    public function getAllAvailableStudents(Request $request): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            
            // Obtener todos los cursos del profesor para mostrar opciones de asignación
            $teacherCourseIds = Course::where('teacher_id', $teacherId)
                ->where('is_published', true)
                ->pluck('id')
                ->toArray();
            
            // Query base para estudiantes (role = 2)
            $query = \App\Models\User::where('role', 2);
            
            // Filtro de búsqueda opcional
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('ci', 'like', "%{$search}%");
                });
            }
            
            // Paginación
            $perPage = $request->get('per_page', 12);
            $page = $request->get('page', 1);
            
            $students = $query->orderBy('name')
                ->with(['enrollments' => function ($query) use ($teacherCourseIds) {
                    // Solo traer inscripciones de cursos del profesor actual
                    $query->whereIn('course_id', $teacherCourseIds)
                          ->with('course:id,title');
                }])
                ->paginate($perPage, ['id', 'name', 'email', 'ci'], 'page', $page);
            
            // Formatear la respuesta con información adicional de inscripciones
            $formattedStudents = $students->getCollection()->map(function ($student) use ($teacherCourseIds) {
                $enrolledCourseIds = $student->enrollments->pluck('course_id')->toArray();
                $availableCourseIds = array_diff($teacherCourseIds, $enrolledCourseIds);
                
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'ci' => $student->ci,
                    'enrolled_courses' => $student->enrollments->map(function ($enrollment) {
                        return [
                            'course_id' => $enrollment->course_id,
                            'course_title' => $enrollment->course->title,
                            'status' => $enrollment->status,
                            'progress_percentage' => $enrollment->progress_percentage
                        ];
                    }),
                    'available_course_ids' => $availableCourseIds,
                    'available_course_count' => count($availableCourseIds),
                    'total_enrolled_courses' => $student->enrollments->count()
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedStudents,
                'meta' => [
                    'current_page' => $students->currentPage(),
                    'per_page' => $students->perPage(),
                    'total' => $students->total(),
                    'last_page' => $students->lastPage(),
                    'from' => $students->firstItem(),
                    'to' => $students->lastItem()
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

    // =============================================================================
    // ANALYTICS
    // =============================================================================

    /**
     * Obtiene analytics generales del profesor
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        try {
            $teacherId = Auth::id();
            
            // Obtener parámetros de filtro
            $dateRange = $request->input('dateRange', '30d');
            $courseId = $request->input('courseId');
            
            // Calcular fechas según el rango
            $endDate = now();
            switch ($dateRange) {
                case '7d':
                    $startDate = now()->subDays(7);
                    break;
                case '90d':
                    $startDate = now()->subDays(90);
                    break;
                case '1y':
                    $startDate = now()->subYear();
                    break;
                default: // 30d
                    $startDate = now()->subDays(30);
            }

            // Query base para cursos del profesor
            $coursesQuery = Course::where('teacher_id', $teacherId);
            if ($courseId) {
                $coursesQuery->where('id', $courseId);
            }
            $courses = $coursesQuery->get();
            $courseIds = $courses->pluck('id');

            // Overview metrics
            $totalStudents = CourseEnrollment::whereIn('course_id', $courseIds)->distinct('student_id')->count();
            $totalCourses = $courses->count();
            $totalEnrollments = CourseEnrollment::whereIn('course_id', $courseIds)->count();
            $completedEnrollments = CourseEnrollment::whereIn('course_id', $courseIds)
                ->where('status', 'completed')
                ->count();
            
            $averageProgress = CourseEnrollment::whereIn('course_id', $courseIds)
                ->avg('progress_percentage') ?? 0;
            $completionRate = $totalEnrollments > 0 ? ($completedEnrollments / $totalEnrollments) * 100 : 0;

            // Student engagement - inscripciones por día
            $dailyEnrollments = CourseEnrollment::whereIn('course_id', $courseIds)
                ->where('created_at', '>=', $startDate)
                ->where('created_at', '<=', $endDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'count' => (int) $item->count
                    ];
                });

            // Course performance
            $coursePerformance = $courses->map(function ($course) {
                $enrollments = $course->enrollments()->count();
                $completions = $course->enrollments()->where('status', 'completed')->count();
                $avgProgress = $course->enrollments()->avg('progress_percentage') ?? 0;
                
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'enrollments' => $enrollments,
                    'completions' => $completions,
                    'avgRating' => $course->rating ?? 0,
                    'avgProgress' => round($avgProgress, 1),
                ];
            });

            // Completion trends por mes (últimos 12 meses)
            $completionTrends = [];
            for ($i = 11; $i >= 0; $i--) {
                $monthStart = now()->subMonths($i)->startOfMonth();
                $monthEnd = now()->subMonths($i)->endOfMonth();
                
                $completions = CourseEnrollment::whereIn('course_id', $courseIds)
                    ->where('status', 'completed')
                    ->whereBetween('completed_at', [$monthStart, $monthEnd])
                    ->count();

                $completionTrends[] = [
                    'month' => $monthStart->format('M'),
                    'completions' => $completions
                ];
            }

            // Recent activity
            $recentActivity = CourseEnrollment::whereIn('course_id', $courseIds)
                ->with(['student', 'course'])
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($enrollment) {
                    $type = 'enrollment';
                    $details = 'Se inscribió al curso';
                    
                    if ($enrollment->status === 'completed') {
                        $type = 'completion';
                        $details = 'Completó el curso';
                    } elseif ($enrollment->progress_percentage > 0) {
                        $type = 'progress';
                        $details = "Progreso: {$enrollment->progress_percentage}%";
                    }

                    return [
                        'timestamp' => $enrollment->updated_at->toISOString(),
                        'type' => $type,
                        'studentName' => $enrollment->student->name,
                        'courseName' => $enrollment->course->title,
                        'details' => $details
                    ];
                });

            // Estructura de respuesta compatible con el frontend
            $analyticsData = [
                'overview' => [
                    'totalStudents' => $totalStudents,
                    'totalCourses' => $totalCourses,
                    'averageProgress' => round($averageProgress, 1),
                    'completionRate' => round($completionRate, 1),
                ],
                'studentEngagement' => [
                    'dailyActiveUsers' => $dailyEnrollments->toArray(),
                    'weeklyEngagement' => [], // Se puede implementar más adelante
                    'mostActiveHours' => [], // Se puede implementar más adelante
                ],
                'coursePerformance' => [
                    'courses' => $coursePerformance->toArray()
                ],
                'progressMetrics' => [
                    'completionTrends' => $completionTrends,
                    'dropoffPoints' => [], // Se puede implementar analizando progress por módulos
                    'averageTimeToComplete' => [], // Se puede implementar calculando días entre inscripción y completado
                ],
                'recentActivity' => $recentActivity->toArray()
            ];

            return response()->json([
                'success' => true,
                'data' => $analyticsData,
                'message' => 'Analytics obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}