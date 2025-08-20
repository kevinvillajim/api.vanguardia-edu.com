<?php

namespace App\Http\Controllers\Api\V2;

use App\Domain\Course\Repositories\CourseRepositoryInterface;
use App\Domain\Course\Services\CourseService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CreateCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Resources\Course\CourseDetailResource;
use App\Http\Resources\Course\CourseResource;
use App\Http\Resources\Course\EnrollmentResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    private CourseService $courseService;

    private CourseRepositoryInterface $courseRepository;

    public function __construct(
        CourseService $courseService,
        CourseRepositoryInterface $courseRepository
    ) {
        $this->courseService = $courseService;
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
    public function show(string $slug): JsonResponse
    {
        $course = $this->courseRepository->findBySlug($slug);

        if (! $course) {
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
    }

    /**
     * Crear un nuevo curso (solo profesores)
     */
    public function store(CreateCourseRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Verificar que el usuario sea profesor
            if ($user->role != 3) { // 3 = Teacher
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear cursos',
                ], 403);
            }

            $course = $this->courseService->createCourse(
                $request->validated(),
                $user->id
            );

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
}
