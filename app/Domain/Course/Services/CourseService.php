<?php

namespace App\Domain\Course\Services;

use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseEnrollment;
use App\Domain\Course\Models\CourseLesson;
use App\Domain\Course\Models\CourseModule;
use App\Domain\Course\Models\LessonContent;
use App\Domain\Course\Repositories\CourseRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseService
{
    private CourseRepositoryInterface $courseRepository;

    public function __construct(CourseRepositoryInterface $courseRepository)
    {
        $this->courseRepository = $courseRepository;
    }

    public function createCourse(array $data, int $teacherId): Course
    {
        return DB::transaction(function () use ($data, $teacherId) {
            // Procesar imagen del banner si existe y es un archivo
            if (isset($data['banner_image']) && $data['banner_image'] instanceof \Illuminate\Http\UploadedFile) {
                $data['banner_image'] = $this->uploadBannerImage($data['banner_image']);
            }
            // Si banner_image es string, se mantiene como está (ya es un path)

            // Agregar teacher_id
            $data['teacher_id'] = $teacherId;

            // Crear el curso
            $course = $this->courseRepository->create($data);

            // Crear módulos si se proporcionan
            if (isset($data['modules'])) {
                $this->createModules($course, $data['modules']);
            }

            return $course->fresh(['modules.lessons']);
        });
    }

    public function updateCourse(int $courseId, array $data): bool
    {
        return DB::transaction(function () use ($courseId, $data) {
            $course = $this->courseRepository->findById($courseId);

            if (! $course) {
                throw new Exception('Curso no encontrado');
            }

            // Procesar imagen del banner si existe y es un archivo
            if (isset($data['banner_image']) && $data['banner_image'] instanceof \Illuminate\Http\UploadedFile) {
                // Eliminar imagen anterior si existe
                if ($course->banner_image) {
                    Storage::delete($course->banner_image);
                }
                $data['banner_image'] = $this->uploadBannerImage($data['banner_image']);
            }
            // Si banner_image es string, se mantiene como está (ya es un path)

            // Actualizar el curso
            return $this->courseRepository->update($courseId, $data);
        });
    }

    public function createModules(Course $course, array $modules): void
    {
        foreach ($modules as $index => $moduleData) {
            $module = $course->modules()->create([
                'title' => $moduleData['title'],
                'description' => $moduleData['description'] ?? null,
                'order_index' => $moduleData['order_index'] ?? $index,
                'is_published' => $moduleData['is_published'] ?? true,
            ]);

            if (isset($moduleData['lessons'])) {
                $this->createLessons($module, $moduleData['lessons']);
            }
        }
    }

    public function createLessons(CourseModule $module, array $lessons): void
    {
        foreach ($lessons as $index => $lessonData) {
            $lesson = $module->lessons()->create([
                'title' => $lessonData['title'],
                'description' => $lessonData['description'] ?? null,
                'order_index' => $lessonData['order_index'] ?? $index,
                'duration_minutes' => $lessonData['duration_minutes'] ?? 0,
                'is_preview' => $lessonData['is_preview'] ?? false,
                'is_published' => $lessonData['is_published'] ?? true,
            ]);

            if (isset($lessonData['contents'])) {
                $this->createLessonContents($lesson, $lessonData['contents']);
            }
        }
    }

    public function createLessonContents(CourseLesson $lesson, array $contents): void
    {
        foreach ($contents as $index => $contentData) {
            $lesson->contents()->create([
                'content_type' => $contentData['content_type'],
                'content_data' => $this->processContentData($contentData),
                'order_index' => $contentData['order_index'] ?? $index,
                'is_required' => $contentData['is_required'] ?? false,
            ]);
        }
    }

    public function enrollStudent(int $courseId, int $studentId): CourseEnrollment
    {
        $course = $this->courseRepository->findById($courseId);

        if (! $course) {
            throw new Exception('Curso no encontrado');
        }

        if (! $course->is_published) {
            throw new Exception('Este curso no está disponible para inscripción');
        }

        // Verificar si ya está inscrito
        $existingEnrollment = CourseEnrollment::where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->first();

        if ($existingEnrollment) {
            if ($existingEnrollment->status === CourseEnrollment::STATUS_DROPPED) {
                // Reactivar inscripción
                $existingEnrollment->update([
                    'status' => CourseEnrollment::STATUS_ACTIVE,
                    'enrolled_at' => now(),
                ]);

                return $existingEnrollment;
            }

            throw new Exception('Ya estás inscrito en este curso');
        }

        // Crear nueva inscripción
        $enrollment = CourseEnrollment::create([
            'course_id' => $courseId,
            'student_id' => $studentId,
            'enrolled_at' => now(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
        ]);

        // Incrementar contador de inscripciones
        $this->courseRepository->incrementEnrollmentCount($courseId);

        return $enrollment;
    }

    public function getStudentCourses(int $studentId)
    {
        return CourseEnrollment::with(['course.teacher', 'course.category'])
            ->byStudent($studentId)
            ->active()
            ->get();
    }

    public function getTeacherCourses(int $teacherId)
    {
        return $this->courseRepository->getByTeacher($teacherId);
    }

    public function getCourseWithFullContent(int $courseId): ?Course
    {
        return Course::with([
            'teacher',
            'category',
            'modules.lessons.contents',
            'enrollments',
        ])->find($courseId);
    }

    public function publishCourse(int $courseId, int $teacherId): bool
    {
        $course = $this->courseRepository->findById($courseId);

        if (! $course) {
            throw new Exception('Curso no encontrado');
        }

        if ($course->teacher_id !== $teacherId) {
            throw new Exception('No tienes permisos para publicar este curso');
        }

        // Verificar que el curso tenga al menos un módulo con lecciones
        if ($course->modules->isEmpty()) {
            throw new Exception('El curso debe tener al menos un módulo');
        }

        $hasContent = $course->modules->some(function ($module) {
            return $module->lessons->isNotEmpty() || $module->components->isNotEmpty();
        });

        if (! $hasContent) {
            throw new Exception('El curso debe tener al menos una lección o componente');
        }

        return $this->courseRepository->publish($courseId);
    }

    public function unpublishCourse(int $courseId, int $teacherId): bool
    {
        $course = $this->courseRepository->findById($courseId);

        if (! $course) {
            throw new Exception('Curso no encontrado');
        }

        if ($course->teacher_id !== $teacherId) {
            throw new Exception('No tienes permisos para despublicar este curso');
        }

        return $this->courseRepository->unpublish($courseId);
    }

    private function uploadBannerImage($image): string
    {
        $filename = Str::random(40).'.'.$image->getClientOriginalExtension();
        $path = $image->storeAs('courses/banners', $filename, 'public');

        return $path;
    }

    private function processContentData(array $contentData): array
    {
        $processedData = $contentData['content_data'] ?? [];

        // Procesar archivos según el tipo de contenido
        switch ($contentData['content_type']) {
            case LessonContent::TYPE_VIDEO:
                if (isset($contentData['video_file'])) {
                    $processedData['url'] = $this->uploadContentFile($contentData['video_file'], 'videos');
                    $processedData['provider'] = 'local';
                }
                break;

            case LessonContent::TYPE_IMAGE:
                if (isset($contentData['image_file'])) {
                    $processedData['url'] = $this->uploadContentFile($contentData['image_file'], 'images');
                }
                break;

            case LessonContent::TYPE_FILE:
                if (isset($contentData['file'])) {
                    $file = $contentData['file'];
                    $processedData['url'] = $this->uploadContentFile($file, 'files');
                    $processedData['name'] = $file->getClientOriginalName();
                    $processedData['size'] = $file->getSize();
                    $processedData['mime_type'] = $file->getMimeType();
                }
                break;
        }

        return $processedData;
    }

    private function uploadContentFile($file, string $folder): string
    {
        $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs("courses/content/{$folder}", $filename, 'public');

        return $path;
    }

    /**
     * Obtener estudiantes inscritos en un curso
     */
    public function getCourseEnrollments(int $courseId)
    {
        return CourseEnrollment::with(['student', 'course'])
            ->where('course_id', $courseId)
            ->active()
            ->get();
    }

    /**
     * Remover estudiante de un curso
     */
    public function removeStudentFromCourse(int $courseId, int $studentId): bool
    {
        $enrollment = CourseEnrollment::where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->first();

        if (! $enrollment) {
            throw new Exception('El estudiante no está inscrito en este curso');
        }

        // Marcar como retirado en lugar de eliminar
        $enrollment->update([
            'status' => CourseEnrollment::STATUS_DROPPED,
            'dropped_at' => now(),
        ]);

        // Decrementar contador de inscripciones
        $this->courseRepository->decrementEnrollmentCount($courseId);

        return true;
    }
}
