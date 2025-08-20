<?php

namespace App\Domain\Course\Services;

use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseActivity;
use App\Domain\Course\Models\CourseModule;
use App\Domain\Course\Models\ModuleComponent;
use App\Domain\Course\Models\Quiz;
use App\Domain\Course\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

class CourseCloneService
{
    /**
     * Clona un curso completo con todas sus dependencias
     */
    public function cloneCourse(
        Course $originalCourse,
        int $teacherId,
        array $options = []
    ): Course {
        $defaultOptions = [
            'clone_modules' => true,
            'clone_activities' => true,
            'clone_quizzes' => true,
            'clone_components' => true,
            'reset_enrollments' => true,
            'suffix' => ' (Copia)',
        ];

        $options = array_merge($defaultOptions, $options);

        return DB::transaction(function () use ($originalCourse, $teacherId, $options) {
            // 1. Clonar el curso base
            $clonedCourse = $this->cloneCourseBase($originalCourse, $teacherId, $options);

            // 2. Clonar módulos si está habilitado
            if ($options['clone_modules']) {
                $this->cloneModules($originalCourse, $clonedCourse, $options);
            }

            // 3. Clonar actividades si está habilitado
            if ($options['clone_activities']) {
                $this->cloneActivities($originalCourse, $clonedCourse, $teacherId);
            }

            // 4. Registrar la clonación
            $this->recordClone($originalCourse, $clonedCourse, $teacherId, $options);

            return $clonedCourse->fresh();
        });
    }

    /**
     * Clona la información base del curso
     */
    private function cloneCourseBase(Course $original, int $teacherId, array $options): Course
    {
        $clonedData = $original->toArray();

        // Remover campos que no deben clonarse
        unset($clonedData['id'], $clonedData['created_at'], $clonedData['updated_at']);

        // Personalizar campos específicos
        $clonedData['title'] = $original->title.$options['suffix'];
        $clonedData['slug'] = null; // Se generará automáticamente
        $clonedData['teacher_id'] = $teacherId;
        $clonedData['is_published'] = false; // Los cursos clonados inician como borrador
        $clonedData['enrollment_count'] = 0;
        $clonedData['rating'] = null;

        return Course::create($clonedData);
    }

    /**
     * Clona todos los módulos del curso
     */
    private function cloneModules(Course $original, Course $cloned, array $options): void
    {
        $originalModules = $original->modules()->orderBy('order_index')->get();

        foreach ($originalModules as $originalModule) {
            $clonedModule = $this->cloneModule($originalModule, $cloned->id);

            // Clonar componentes del módulo
            if ($options['clone_components']) {
                $this->cloneModuleComponents($originalModule, $clonedModule);
            }

            // Clonar quizzes del módulo
            if ($options['clone_quizzes']) {
                $this->cloneModuleQuizzes($originalModule, $clonedModule);
            }
        }
    }

    /**
     * Clona un módulo específico
     */
    private function cloneModule(CourseModule $original, int $courseId): CourseModule
    {
        $moduleData = $original->toArray();
        unset($moduleData['id'], $moduleData['created_at'], $moduleData['updated_at']);

        $moduleData['course_id'] = $courseId;

        return CourseModule::create($moduleData);
    }

    /**
     * Clona los componentes de un módulo
     */
    private function cloneModuleComponents(CourseModule $originalModule, CourseModule $clonedModule): void
    {
        $originalComponents = $originalModule->components()->orderBy('order')->get();

        foreach ($originalComponents as $originalComponent) {
            $componentData = $originalComponent->toArray();
            unset($componentData['id'], $componentData['created_at'], $componentData['updated_at']);

            $componentData['module_id'] = $clonedModule->id;

            // Si hay archivos, se mantienen las URLs originales
            // En un entorno real, podrías querer copiar los archivos físicamente

            ModuleComponent::create($componentData);
        }
    }

    /**
     * Clona los quizzes de un módulo
     */
    private function cloneModuleQuizzes(CourseModule $originalModule, CourseModule $clonedModule): void
    {
        $originalQuizzes = $originalModule->quizzes()->orderBy('order')->get();

        foreach ($originalQuizzes as $originalQuiz) {
            $clonedQuiz = $this->cloneQuiz($originalQuiz, $clonedModule->id);
            $this->cloneQuizQuestions($originalQuiz, $clonedQuiz);
        }
    }

    /**
     * Clona un quiz específico
     */
    private function cloneQuiz(Quiz $original, int $moduleId): Quiz
    {
        $quizData = $original->toArray();
        unset($quizData['id'], $quizData['created_at'], $quizData['updated_at']);

        $quizData['module_id'] = $moduleId;

        return Quiz::create($quizData);
    }

    /**
     * Clona las preguntas de un quiz
     */
    private function cloneQuizQuestions(Quiz $originalQuiz, Quiz $clonedQuiz): void
    {
        $originalQuestions = $originalQuiz->questions()->orderBy('order')->get();

        foreach ($originalQuestions as $originalQuestion) {
            $questionData = $originalQuestion->toArray();
            unset($questionData['id'], $questionData['created_at'], $questionData['updated_at']);

            $questionData['quiz_id'] = $clonedQuiz->id;

            QuizQuestion::create($questionData);
        }
    }

    /**
     * Clona las actividades del curso
     */
    private function cloneActivities(Course $original, Course $cloned, int $teacherId): void
    {
        $originalActivities = $original->activities()->orderBy('order')->get();

        foreach ($originalActivities as $originalActivity) {
            $activityData = $originalActivity->toArray();
            unset($activityData['id'], $activityData['created_at'], $activityData['updated_at']);

            $activityData['course_id'] = $cloned->id;
            $activityData['teacher_id'] = $teacherId;

            // Resetear fechas de entrega (agregar 1 año desde la fecha actual)
            if ($activityData['due_date']) {
                $activityData['due_date'] = now()->addYear()->format('Y-m-d H:i:s');
            }

            CourseActivity::create($activityData);
        }
    }

    /**
     * Registra la operación de clonación
     */
    private function recordClone(Course $original, Course $cloned, int $teacherId, array $options): void
    {
        DB::table('course_clones')->insert([
            'original_course_id' => $original->id,
            'cloned_course_id' => $cloned->id,
            'cloned_by' => $teacherId,
            'cloned_at' => now(),
            'clone_options' => json_encode($options),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Obtiene el historial de clonaciones de un curso
     */
    public function getCloneHistory(Course $course): array
    {
        // Cursos clonados desde este
        $clonedFrom = DB::table('course_clones as cc')
            ->join('courses as c', 'cc.cloned_course_id', '=', 'c.id')
            ->join('users as u', 'cc.cloned_by', '=', 'u.id')
            ->where('cc.original_course_id', $course->id)
            ->select([
                'c.id',
                'c.title',
                'c.is_published',
                'u.name as cloned_by_name',
                'cc.cloned_at',
                'cc.clone_options',
            ])
            ->get()
            ->toArray();

        // Curso original de este (si es una copia)
        $originalCourse = DB::table('course_clones as cc')
            ->join('courses as c', 'cc.original_course_id', '=', 'c.id')
            ->join('users as u', 'cc.cloned_by', '=', 'u.id')
            ->where('cc.cloned_course_id', $course->id)
            ->select([
                'c.id',
                'c.title',
                'c.is_published',
                'u.name as cloned_by_name',
                'cc.cloned_at',
                'cc.clone_options',
            ])
            ->first();

        return [
            'is_clone' => ! is_null($originalCourse),
            'original_course' => $originalCourse,
            'cloned_courses' => $clonedFrom,
            'total_clones' => count($clonedFrom),
        ];
    }

    /**
     * Clona un curso para un nuevo período académico
     */
    public function cloneForNewPeriod(
        Course $originalCourse,
        int $teacherId,
        ?string $periodSuffix = null
    ): Course {
        $suffix = $periodSuffix ?: ' ('.now()->format('Y').')';

        return $this->cloneCourse($originalCourse, $teacherId, [
            'clone_modules' => true,
            'clone_activities' => true,
            'clone_quizzes' => true,
            'clone_components' => true,
            'reset_enrollments' => true,
            'suffix' => $suffix,
        ]);
    }

    /**
     * Clona solo la estructura del curso (sin contenido específico)
     */
    public function cloneStructureOnly(Course $originalCourse, int $teacherId): Course
    {
        return $this->cloneCourse($originalCourse, $teacherId, [
            'clone_modules' => true,
            'clone_activities' => false,
            'clone_quizzes' => false,
            'clone_components' => true,
            'reset_enrollments' => true,
            'suffix' => ' (Estructura)',
        ]);
    }

    /**
     * Valida si un usuario puede clonar un curso
     */
    public function canCloneCourse(Course $course, int $userId): bool
    {
        $user = \App\Models\User::find($userId);

        if (! $user) {
            return false;
        }

        // Los administradores pueden clonar cualquier curso
        if ($user->role === 'admin') {
            return true;
        }

        // Los profesores pueden clonar sus propios cursos
        if ($user->role === 'teacher' && $course->teacher_id === $userId) {
            return true;
        }

        // Los profesores pueden clonar cursos publicados de otros profesores
        if ($user->role === 'teacher' && $course->is_published) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene estadísticas de clonación del sistema
     */
    public function getCloneStatistics(): array
    {
        $totalClones = DB::table('course_clones')->count();

        $mostClonedCourses = DB::table('course_clones as cc')
            ->join('courses as c', 'cc.original_course_id', '=', 'c.id')
            ->select('c.title', DB::raw('COUNT(*) as clone_count'))
            ->groupBy('cc.original_course_id', 'c.title')
            ->orderBy('clone_count', 'desc')
            ->limit(10)
            ->get();

        $clonesByMonth = DB::table('course_clones')
            ->select(
                DB::raw('YEAR(cloned_at) as year'),
                DB::raw('MONTH(cloned_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->where('cloned_at', '>=', now()->subYear())
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return [
            'total_clones' => $totalClones,
            'most_cloned_courses' => $mostClonedCourses,
            'clones_by_month' => $clonesByMonth,
        ];
    }
}
