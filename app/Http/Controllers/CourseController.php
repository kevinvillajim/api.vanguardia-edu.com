<?php

namespace App\Http\Controllers;

use App\Domain\Course\Models\Course;
use App\Domain\Course\Repositories\CourseRepositoryInterface;
use App\Domain\Course\Services\CourseService;
use App\Domain\Course\Services\CourseManagementService;
use App\Domain\Course\DTOs\CreateCourseDTO;
use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CreateCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Resources\Course\CourseDetailResource;
use App\Http\Resources\Course\CourseResource;
use App\Http\Resources\Course\EnrollmentResource;
use App\Models\CourseDraft;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    private CourseService $courseService;
    private CourseManagementService $courseManagementService;
    private CourseRepositoryInterface $courseRepository;

    public function __construct(
        CourseService $courseService,
        CourseManagementService $courseManagementService,
        CourseRepositoryInterface $courseRepository
    ) {
        $this->courseService = $courseService;
        $this->courseManagementService = $courseManagementService;
        $this->courseRepository = $courseRepository;
    }

    /**
     * Lista de cursos publicados con filtros
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'category_id',
            'difficulty_level',
            'min_price',
            'max_price',
            'sort_by',
        ]);

        $courses = $this->courseRepository->getAllPublished($filters, $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => CourseResource::collection($courses),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
        ]);
    }

    /**
     * Detalle de un curso
     */
    public function show(int $courseId): JsonResponse
    {
        // Para teacher, cargar TODAS las relaciones necesarias
        $course = Course::with([
            'teacher',
            'category',
            'units' => function($query) {
                $query->orderBy('order_index');
            },
            'units.modules' => function($query) {
                $query->orderBy('order_index');
            },
            'units.modules.components' => function($query) {
                $query->where('is_active', true)->orderBy('order');
            },
            'units.modules.lessons' => function($query) {
                $query->orderBy('order_index');
            }
        ])->find($courseId);

        if (! $course) {
            return response()->json([
                'success' => false,
                'message' => 'Curso no encontrado',
            ], 404);
        }
        
        // Debug: Log para verificar que se cargan las relaciones
        \Log::info('CourseController::show - Curso cargado con estructura completa:', [
            'id' => $course->id,
            'title' => $course->title,
            'units_count' => $course->units->count(),
            'total_modules' => $course->units->sum(function($unit) {
                return $unit->modules->count();
            }),
            'total_components' => $course->units->sum(function($unit) {
                return $unit->modules->sum(function($module) {
                    return $module->components->count();
                });
            })
        ]);

        // Verificar si el usuario está inscrito
        $isEnrolled = false;
        if (Auth::check()) {
            $isEnrolled = $course->isEnrolledBy(Auth::id());
            
            // Si es el profesor del curso, incluir toda la información
            if ($course->teacher_id === Auth::id()) {
                return response()->json([
                    'success' => true,
                    'data' => new CourseDetailResource($course),
                    'is_enrolled' => true,
                    'is_teacher' => true
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => new CourseDetailResource($course),
            'is_enrolled' => $isEnrolled,
        ]);
    }

    /**
     * Crear un nuevo curso (solo profesores)
     */
    public function store(CreateCourseRequest $request): JsonResponse
    {
        try {
            \Log::info('Request data received:', $request->all());
            \Log::info('Request headers:', $request->headers->all());
            $user = Auth::user();

            // Verificar que el usuario sea profesor
            if ($user->role != 3) { // 3 = Teacher
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear cursos',
                ], 403);
            }

            // Create DTO from validated request
            $courseDTO = CreateCourseDTO::fromRequest($request->validated());
            
            // Create course using management service
            $course = $this->courseManagementService->createCourse($courseDTO, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Curso creado exitosamente',
                'data' => new CourseDetailResource($course),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Actualizar un curso (solo el profesor dueño)
     */
    public function update(UpdateCourseRequest $request, int $id): JsonResponse
    {
        try {
            $course = $this->courseRepository->findById($id);

            if (! $course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado',
                ], 404);
            }

            // Verificar que el usuario sea el profesor del curso
            if ($course->teacher_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar este curso',
                ], 403);
            }

            $this->courseService->updateCourse($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Curso actualizado exitosamente',
                'data' => new CourseDetailResource($course->fresh()),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Eliminar un curso (solo el profesor dueño)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $course = $this->courseRepository->findById($id);

            if (! $course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado',
                ], 404);
            }

            // Verificar que el usuario sea el profesor del curso
            if ($course->teacher_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar este curso',
                ], 403);
            }

            $this->courseRepository->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Curso eliminado exitosamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Inscribirse en un curso (estudiantes)
     */
    public function enroll(int $courseId): JsonResponse
    {
        try {
            $user = Auth::user();

            // Verificar que el usuario sea estudiante
            if ($user->role != 2) { // 2 = Student
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los estudiantes pueden inscribirse en cursos',
                ], 403);
            }

            $enrollment = $this->courseService->enrollStudent($courseId, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Te has inscrito exitosamente en el curso',
                'data' => new EnrollmentResource($enrollment),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Publicar un curso
     */
    public function publish(int $courseId): JsonResponse
    {
        try {
            $this->courseService->publishCourse($courseId, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Curso publicado exitosamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Despublicar un curso
     */
    public function unpublish(int $courseId): JsonResponse
    {
        try {
            $this->courseService->unpublishCourse($courseId, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Curso despublicado exitosamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cursos del profesor actual
     */
    public function myCourses(): JsonResponse
    {
        $user = Auth::user();

        if ($user->role != 3) { // 3 = Teacher
            return response()->json([
                'success' => false,
                'message' => 'Solo los profesores pueden acceder a esta sección',
            ], 403);
        }

        $courses = $this->courseService->getTeacherCourses($user->id);

        return response()->json([
            'success' => true,
            'data' => CourseResource::collection($courses),
        ]);
    }

    /**
     * Obtiene el contenido completo de un curso con todas sus unidades, módulos y componentes
     */
    public function getCourseContent(int $courseId): JsonResponse
    {
        try {
            // Verificar que el curso existe
            $course = $this->courseRepository->findById($courseId);
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado',
                ], 404);
            }

            // Obtener unidades del curso
            $units = \Illuminate\Support\Facades\DB::table('course_units')
                ->where('course_id', $courseId)
                ->where('is_published', true)
                ->orderBy('order_index')
                ->get();

            $courseContent = [];
            
            foreach ($units as $unit) {
                // Obtener módulos de la unidad
                $modules = \Illuminate\Support\Facades\DB::table('course_modules')
                    ->where('unit_id', $unit->id)
                    ->where('is_published', true)
                    ->orderBy('order_index')
                    ->get();

                $unitModules = [];
                
                foreach ($modules as $module) {
                    // Obtener componentes del módulo
                    $components = \Illuminate\Support\Facades\DB::table('module_components')
                        ->where('module_id', $module->id)
                        ->where('is_active', true)
                        ->orderBy('order')
                        ->get();

                    $moduleComponents = [];
                    
                    foreach ($components as $component) {
                        $content = json_decode($component->content, true);
                        
                        // Process URLs in content based on component type
                        if ($content) {
                            switch ($component->type) {
                                case 'banner':
                                    if (isset($content['img'])) {
                                        $content['img'] = StorageHelper::courseBannerUrl($content['img'], $courseId);
                                    }
                                    break;
                                case 'image':
                                    if (isset($content['img'])) {
                                        $content['img'] = StorageHelper::courseImageUrl($content['img'], $courseId);
                                    }
                                    break;
                                case 'video':
                                    if (isset($content['src'])) {
                                        $content['src'] = StorageHelper::courseVideoUrl($content['src'], $courseId);
                                    }
                                    if (isset($content['poster'])) {
                                        $content['poster'] = StorageHelper::courseImageUrl($content['poster'], $courseId);
                                    }
                                    break;
                            }
                        }
                        
                        $moduleComponents[] = [
                            'id' => $component->id,
                            'type' => $component->type,
                            'title' => $component->title,
                            'content' => $content,
                            'order' => $component->order,
                            'duration' => $component->duration,
                            'metadata' => $component->metadata ? json_decode($component->metadata, true) : null,
                        ];
                    }

                    $unitModules[] = [
                        'id' => $module->id,
                        'title' => $module->title,
                        'description' => $module->description,
                        'order_index' => $module->order_index,
                        'components' => $moduleComponents,
                    ];
                }

                // Obtener quiz de la unidad
                $quiz = \Illuminate\Support\Facades\DB::table('course_quizzes')
                    ->where('unit_id', $unit->id)
                    ->first();

                $courseContent[] = [
                    'id' => $unit->id,
                    'title' => $unit->title,
                    'description' => $unit->description,
                    'banner_image' => StorageHelper::courseBannerUrl($unit->banner_image, $courseId),
                    'order_index' => $unit->order_index,
                    'modules' => $unitModules,
                    'quiz' => $quiz ? [
                        'id' => $quiz->id,
                        'questions' => json_decode($quiz->questions, true),
                        'passing_score' => $quiz->passing_score,
                    ] : null,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => [
                        'id' => $course->id,
                        'title' => $course->title,
                        'description' => $course->description,
                        'banner_image' => StorageHelper::courseBannerUrl($course->banner_image, $courseId),
                        'difficulty_level' => $course->difficulty_level,
                        'duration_hours' => $course->duration_hours,
                    ],
                    'units' => $courseContent,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener contenido del curso: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene un curso por slug
     */
    public function getBySlug(string $slug): JsonResponse
    {
        try {
            $course = $this->courseRepository->findBySlug($slug);

            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado',
                ], 404);
            }

            // Verificar si el usuario está inscrito
            $isEnrolled = false;
            if (Auth::check()) {
                $isEnrolled = $course->isEnrolledBy(Auth::id());
            }

            return response()->json([
                'success' => true,
                'data' => new CourseDetailResource($course),
                'is_enrolled' => $isEnrolled,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener curso: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cursos en los que está inscrito el estudiante
     */
    public function myEnrollments(): JsonResponse
    {
        $user = Auth::user();

        if ($user->role != 2) { // 2 = Student
            return response()->json([
                'success' => false,
                'message' => 'Solo los estudiantes pueden acceder a esta sección',
            ], 403);
        }

        $enrollments = $this->courseService->getStudentCourses($user->id);

        return response()->json([
            'success' => true,
            'data' => EnrollmentResource::collection($enrollments),
        ]);
    }

    /**
     * Buscar cursos
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Por favor proporciona un término de búsqueda',
            ], 400);
        }

        $filters = $request->only(['category_id', 'difficulty_level']);
        $courses = $this->courseRepository->searchCourses($query, $filters);

        return response()->json([
            'success' => true,
            'data' => CourseResource::collection($courses),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
        ]);
    }

    /**
     * Cursos destacados
     */
    public function featured(): JsonResponse
    {
        $courses = $this->courseRepository->getFeatured(6);

        return response()->json([
            'success' => true,
            'data' => CourseResource::collection($courses),
        ]);
    }

    /**
     * Ver estudiantes inscritos en un curso (solo el profesor del curso)
     */
    public function getEnrolledStudents(int $courseId): JsonResponse
    {
        try {
            $course = $this->courseRepository->findById($courseId);

            if (! $course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado',
                ], 404);
            }

            // Verificar que el usuario sea el profesor del curso
            if ($course->teacher_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver los estudiantes de este curso',
                ], 403);
            }

            $enrollments = $this->courseService->getCourseEnrollments($courseId);

            return response()->json([
                'success' => true,
                'data' => EnrollmentResource::collection($enrollments),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Asignar estudiante a curso (solo profesores)
     */
    public function assignStudent(Request $request, int $courseId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $course = $this->courseRepository->findById($courseId);

            if (! $course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado',
                ], 404);
            }

            // Verificar que el usuario sea el profesor del curso
            if ($course->teacher_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para asignar estudiantes a este curso',
                ], 403);
            }

            // Verificar que el usuario a asignar sea estudiante
            $user = \App\Models\User::find($request->user_id);
            if ($user->role != 2) { // 2 = Student
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden asignar estudiantes a los cursos',
                ], 400);
            }

            $enrollment = $this->courseService->enrollStudent($courseId, $request->user_id);

            return response()->json([
                'success' => true,
                'message' => 'Estudiante asignado exitosamente al curso',
                'data' => new EnrollmentResource($enrollment),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remover estudiante de curso (solo profesores)
     */
    public function removeStudent(int $courseId, int $userId): JsonResponse
    {
        try {
            $course = $this->courseRepository->findById($courseId);

            if (! $course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado',
                ], 404);
            }

            // Verificar que el usuario sea el profesor del curso
            if ($course->teacher_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para remover estudiantes de este curso',
                ], 403);
            }

            $this->courseService->removeStudentFromCourse($courseId, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Estudiante removido exitosamente del curso',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // ====== MVP API ENDPOINTS ======

    /**
     * Add unit to course
     */
    public function addUnit(Request $request, int $courseId): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'banner_image' => 'nullable|string',
            'order_index' => 'nullable|integer|min:1',
            'modules' => 'nullable|array'
        ]);

        try {
            $user = Auth::user();
            $course = $this->courseRepository->findById($courseId);

            if (!$course || $course->teacher_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found or unauthorized'
                ], 404);
            }

            $unit = $this->courseManagementService->addUnitToCourse($courseId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Unit added successfully',
                'data' => $unit
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Add module to unit
     */
    public function addModule(Request $request, int $unitId): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'nullable|integer|min:1',
            'components' => 'nullable|array'
        ]);

        try {
            $user = Auth::user();
            
            // Verify ownership through unit -> course -> teacher
            $unit = \DB::table('course_units')
                ->join('courses', 'course_units.course_id', '=', 'courses.id')
                ->where('course_units.id', $unitId)
                ->where('courses.teacher_id', $user->id)
                ->first();

            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit not found or unauthorized'
                ], 404);
            }

            $module = $this->courseManagementService->addModuleToUnit($unitId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Module added successfully',
                'data' => $module
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Add component to module
     */
    public function addComponent(Request $request, int $moduleId): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:banner,video,reading,image,document,audio,quiz,interactive',
            'title' => 'required|string|max:255',
            'content' => 'required|array',
            'order' => 'nullable|integer|min:0',
            'duration' => 'nullable|integer|min:0',
            'is_mandatory' => 'nullable|boolean'
        ]);

        try {
            $user = Auth::user();
            
            // Verify ownership through module -> unit -> course -> teacher
            $module = \DB::table('course_modules')
                ->join('course_units', 'course_modules.unit_id', '=', 'course_units.id')
                ->join('courses', 'course_units.course_id', '=', 'courses.id')
                ->where('course_modules.id', $moduleId)
                ->where('courses.teacher_id', $user->id)
                ->first();

            if (!$module) {
                return response()->json([
                    'success' => false,
                    'message' => 'Module not found or unauthorized'
                ], 404);
            }

            $component = $this->courseManagementService->addComponentToModule($moduleId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Component added successfully',
                'data' => $component
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }


    /**
     * Upload banner image for course
     */
    public function uploadBanner(Request $request, int $courseId): JsonResponse
    {
        $request->validate([
            'banner' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120'
        ]);

        try {
            $user = Auth::user();
            $course = $this->courseRepository->findById($courseId);

            if (!$course || $course->teacher_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found or unauthorized'
                ], 404);
            }

            $imageUrl = $this->courseManagementService->uploadBannerImage(
                $request->file('banner'),
                $courseId
            );

            // Update course with new banner
            $this->courseRepository->update($courseId, ['banner_image' => $imageUrl]);

            return response()->json([
                'success' => true,
                'message' => 'Banner uploaded successfully',
                'data' => ['banner_url' => $imageUrl]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete a unit
     */
    public function deleteUnit(int $unitId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Verify ownership through unit -> course -> teacher
            $unit = \DB::table('course_units')
                ->join('courses', 'course_units.course_id', '=', 'courses.id')
                ->where('course_units.id', $unitId)
                ->where('courses.teacher_id', $user->id)
                ->first();

            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit not found or unauthorized'
                ], 404);
            }

            $this->courseManagementService->deleteUnit($unitId);

            return response()->json([
                'success' => true,
                'message' => 'Unit deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete a module
     */
    public function deleteModule(int $moduleId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Verify ownership through module -> unit -> course -> teacher
            $module = \DB::table('course_modules')
                ->join('course_units', 'course_modules.unit_id', '=', 'course_units.id')
                ->join('courses', 'course_units.course_id', '=', 'courses.id')
                ->where('course_modules.id', $moduleId)
                ->where('courses.teacher_id', $user->id)
                ->first();

            if (!$module) {
                return response()->json([
                    'success' => false,
                    'message' => 'Module not found or unauthorized'
                ], 404);
            }

            $this->courseManagementService->deleteModule($moduleId);

            return response()->json([
                'success' => true,
                'message' => 'Module deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete a component
     */
    public function deleteComponent(int $componentId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Verify ownership through component -> module -> unit -> course -> teacher
            $component = \DB::table('module_components')
                ->join('course_modules', 'module_components.module_id', '=', 'course_modules.id')
                ->join('course_units', 'course_modules.unit_id', '=', 'course_units.id')
                ->join('courses', 'course_units.course_id', '=', 'courses.id')
                ->where('module_components.id', $componentId)
                ->where('courses.teacher_id', $user->id)
                ->first();

            if (!$component) {
                return response()->json([
                    'success' => false,
                    'message' => 'Component not found or unauthorized'
                ], 404);
            }

            $this->courseManagementService->deleteComponent($componentId);

            return response()->json([
                'success' => true,
                'message' => 'Component deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update a component
     */
    public function updateComponent(Request $request, int $componentId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Verify ownership through component -> module -> unit -> course -> teacher
            $component = \DB::table('module_components')
                ->join('course_modules', 'module_components.module_id', '=', 'course_modules.id')
                ->join('course_units', 'course_modules.unit_id', '=', 'course_units.id')
                ->join('courses', 'course_units.course_id', '=', 'courses.id')
                ->where('module_components.id', $componentId)
                ->where('courses.teacher_id', $user->id)
                ->first();

            if (!$component) {
                return response()->json([
                    'success' => false,
                    'message' => 'Component not found or unauthorized'
                ], 404);
            }

            $updatedComponent = $this->courseManagementService->updateComponent($componentId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Component updated successfully',
                'data' => $updatedComponent
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Guardar borrador del curso
     * POST /api/teacher/courses/{id}/draft
     */
    public function saveDraft(Request $request, int $courseId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Verificar que el usuario es el propietario del curso
            $course = $this->courseRepository->findById($courseId);
            if (!$course || $course->teacher_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado o no autorizado'
                ], 404);
            }

            $validated = $request->validate([
                'draft_data' => 'required|array',
                'draft_type' => 'required|in:auto,manual'
            ]);

            // Limpiar drafts antiguos (mantener solo los 2 más recientes)
            CourseDraft::cleanupOldDrafts($courseId, $user->id, 2);
            
            // Crear nuevo draft
            $draft = CourseDraft::create([
                'course_id' => $courseId,
                'user_id' => $user->id,
                'draft_data' => $validated['draft_data'],
                'draft_type' => $validated['draft_type']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Borrador guardado exitosamente',
                'data' => [
                    'draft_id' => $draft->id,
                    'saved_at' => $draft->created_at
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error guardando borrador: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener último borrador del curso
     * GET /api/teacher/courses/{id}/draft
     */
    public function getLatestDraft(int $courseId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Verificar que el usuario es el propietario del curso
            $course = $this->courseRepository->findById($courseId);
            if (!$course || $course->teacher_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado o no autorizado'
                ], 404);
            }

            $draft = CourseDraft::getLatest($courseId, $user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'draft' => $draft ? $draft->draft_data : null,
                    'draft_type' => $draft ? $draft->draft_type : null,
                    'saved_at' => $draft ? $draft->created_at : null
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo borrador: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Limpiar borradores antiguos de un curso
     * DELETE /api/teacher/courses/{id}/drafts/cleanup
     */
    public function cleanupDrafts(int $courseId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Verificar que el usuario es el propietario del curso
            $course = $this->courseRepository->findById($courseId);
            if (!$course || $course->teacher_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado o no autorizado'
                ], 404);
            }

            // Eliminar todos los drafts excepto el más reciente
            CourseDraft::cleanupOldDrafts($courseId, $user->id, 1);

            return response()->json([
                'success' => true,
                'message' => 'Borradores limpiados exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error limpiando borradores: ' . $e->getMessage()
            ], 400);
        }
    }

}
