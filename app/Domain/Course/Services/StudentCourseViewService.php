<?php

namespace App\Domain\Course\Services;

use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseEnrollment;
use App\Domain\Course\Models\CourseProgress;
use App\Domain\Course\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;

class StudentCourseViewService
{
    public function getCourseViewData(string $courseId, int $studentId): array
    {
        $course = Course::with([
            'units.modules.components' => function ($query) {
                $query->orderBy('order');
            },
            'units.modules.quiz.questions' => function ($query) {
                $query->orderBy('order');
            },
            'units' => function ($query) {
                $query->orderBy('order');
            },
            'materials' => function ($query) {
                $query->active()->ordered();
            },
            'activities' => function ($query) {
                $query->orderBy('order');
            }
        ])->findOrFail($courseId);

        // Verificar inscripción
        $enrollment = CourseEnrollment::where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            throw new \Exception('Student not enrolled in this course');
        }

        // Obtener progreso por módulo
        $moduleProgress = $this->getModuleProgress($courseId, $studentId);
        
        // Obtener intentos de quizzes
        $quizAttempts = $this->getQuizAttempts($courseId, $studentId);

        // Obtener actividades del estudiante
        $studentActivities = $this->getStudentActivities($courseId, $studentId);

        // Calcular progreso general
        $overallProgress = $this->calculateOverallProgress($course, $moduleProgress);
        
        // Calcular calificaciones
        $grades = $this->calculateGrades($course, $quizAttempts, $studentActivities);

        // Verificar certificados
        $certificates = $this->checkCertificates($course, $overallProgress, $grades);

        return [
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,
            'units' => $this->formatUnitsForView($course->units, $moduleProgress, $quizAttempts),
            'activities' => $this->formatActivitiesForView($course->activities, $studentActivities),
            'materials' => $this->formatMaterialsForView($course->materials),
            'progress' => $overallProgress,
            'grades' => $grades,
            'certificates' => $certificates,
        ];
    }

    private function getModuleProgress(string $courseId, int $studentId): array
    {
        $progress = CourseProgress::where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->get()
            ->keyBy('module_id');

        return $progress->toArray();
    }

    private function getQuizAttempts(string $courseId, int $studentId): array
    {
        $attempts = QuizAttempt::whereHas('quiz.module', function ($query) use ($courseId) {
            $query->where('course_id', $courseId);
        })
        ->where('student_id', $studentId)
        ->with('quiz')
        ->get()
        ->groupBy('quiz_id');

        $formatted = [];
        foreach ($attempts as $quizId => $quizAttempts) {
            $bestAttempt = $quizAttempts->sortByDesc('score')->first();
            $formatted[$quizId] = [
                'attempts' => $quizAttempts->count(),
                'best_score' => $bestAttempt ? $bestAttempt->score : null,
                'is_passed' => $bestAttempt ? $bestAttempt->score >= $bestAttempt->quiz->passing_score : false,
                'last_attempt_at' => $quizAttempts->max('completed_at'),
            ];
        }

        return $formatted;
    }

    private function getStudentActivities(string $courseId, int $studentId): array
    {
        // TODO: Implementar cuando se agregue la tabla student_activities
        return [];
    }

    private function calculateOverallProgress(Course $course, array $moduleProgress): array
    {
        $totalModules = 0;
        $completedModules = 0;
        $totalComponents = 0;
        $completedComponents = 0;

        foreach ($course->units as $unit) {
            foreach ($unit->modules as $module) {
                $totalModules++;
                $moduleId = $module->id;
                $moduleComponentsCount = $module->components->count();
                $totalComponents += $moduleComponentsCount;

                if (isset($moduleProgress[$moduleId])) {
                    $progress = $moduleProgress[$moduleId];
                    $completedComponents += $progress['completed_components'] ?? 0;
                    
                    if (($progress['completion_percentage'] ?? 0) >= 100) {
                        $completedModules++;
                    }
                }
            }
        }

        $interactiveProgress = $totalComponents > 0 ? ($completedComponents / $totalComponents) * 100 : 0;
        $overallProgress = $totalModules > 0 ? ($completedModules / $totalModules) * 100 : 0;

        return [
            'overall' => round($overallProgress, 2),
            'interactive' => round($interactiveProgress, 2),
            'activities' => 0, // TODO: Implementar cuando se agreguen actividades
        ];
    }

    private function calculateGrades(Course $course, array $quizAttempts, array $studentActivities): array
    {
        // Calcular promedio de quizzes
        $quizScores = [];
        foreach ($course->units as $unit) {
            foreach ($unit->modules as $module) {
                if ($module->quiz && isset($quizAttempts[$module->quiz->id])) {
                    $attempt = $quizAttempts[$module->quiz->id];
                    if ($attempt['best_score'] !== null) {
                        $quizScores[] = $attempt['best_score'];
                    }
                }
            }
        }

        $interactiveAverage = count($quizScores) > 0 ? array_sum($quizScores) / count($quizScores) : 0;

        // TODO: Implementar promedio de actividades
        $activitiesAverage = 0;

        // Calcular nota final (50% quizzes, 50% actividades por defecto)
        $interactiveWeight = 50; // TODO: Obtener de configuración
        $activitiesWeight = 50;
        
        $finalScore = ($interactiveAverage * $interactiveWeight / 100) + 
                     ($activitiesAverage * $activitiesWeight / 100);

        return [
            'interactiveAverage' => round($interactiveAverage, 2),
            'activitiesAverage' => round($activitiesAverage, 2),
            'finalScore' => round($finalScore, 2),
        ];
    }

    private function checkCertificates(Course $course, array $progress, array $grades): array
    {
        // TODO: Obtener umbrales de configuración
        $virtualThreshold = 80; // % de completitud mínima
        $completeThreshold = 70; // Promedio final mínimo

        $hasVirtual = $progress['interactive'] >= $virtualThreshold;
        $hasComplete = $hasVirtual && $grades['finalScore'] >= $completeThreshold;

        return [
            'virtual' => $hasVirtual,
            'complete' => $hasComplete,
        ];
    }

    private function formatModulesForView(
        $modules, 
        array $moduleProgress, 
        array $quizAttempts
    ): array {
        $formatted = [];

        foreach ($modules as $module) {
            $progress = $moduleProgress[$module->id] ?? null;
            $moduleProgressPercentage = $progress ? $progress['completion_percentage'] ?? 0 : 0;

            $components = [];
            foreach ($module->components as $component) {
                $components[] = [
                    'id' => $component->id,
                    'type' => $component->type,
                    'title' => $component->title,
                    'content' => $component->content,
                    'rich_content' => $component->rich_content,
                    'fileUrl' => $component->file_url,
                    'duration' => $component->estimated_duration,
                    'isCompleted' => false, // TODO: Implementar lógica de completitud
                    'isMandatory' => $component->is_mandatory,
                    'order' => $component->order,
                ];
            }

            $quiz = null;
            if ($module->quiz) {
                $quizData = $quizAttempts[$module->quiz->id] ?? null;
                $quiz = [
                    'id' => $module->quiz->id,
                    'title' => $module->quiz->title,
                    'questions' => $module->quiz->questions->count(),
                    'timeLimit' => $module->quiz->time_limit,
                    'attempts' => $quizData ? $quizData['attempts'] : 0,
                    'maxAttempts' => $module->quiz->max_attempts,
                    'bestScore' => $quizData ? $quizData['best_score'] : null,
                    'isPassed' => $quizData ? $quizData['is_passed'] : false,
                ];
            }

            $formatted[] = [
                'id' => $module->id,
                'title' => $module->title,
                'description' => $module->description,
                'components' => $components,
                'quiz' => $quiz,
                'progress' => $moduleProgressPercentage,
                'order' => $module->order_index,
            ];
        }

        return $formatted;
    }

    private function formatUnitsForView(
        $units, 
        array $moduleProgress, 
        array $quizAttempts
    ): array {
        $formatted = [];

        foreach ($units as $unit) {
            $unitModules = [];
            
            foreach ($unit->modules as $module) {
                $progress = $moduleProgress[$module->id] ?? null;
                $moduleProgressPercentage = $progress ? $progress['completion_percentage'] ?? 0 : 0;

                $components = [];
                foreach ($module->components as $component) {
                    $components[] = [
                        'id' => $component->id,
                        'type' => $component->type,
                        'title' => $component->title,
                        'content' => $component->content,
                        'rich_content' => $component->rich_content,
                        'fileUrl' => $component->file_url,
                        'duration' => $component->estimated_duration,
                        'isCompleted' => false, // TODO: Implementar lógica de completitud
                        'isMandatory' => $component->is_mandatory,
                        'order' => $component->order,
                    ];
                }

                $quiz = null;
                if ($module->quiz) {
                    $quizData = $quizAttempts[$module->quiz->id] ?? null;
                    $quiz = [
                        'id' => $module->quiz->id,
                        'title' => $module->quiz->title,
                        'questions' => $module->quiz->questions->count(),
                        'timeLimit' => $module->quiz->time_limit,
                        'attempts' => $quizData ? $quizData['attempts'] : 0,
                        'maxAttempts' => $module->quiz->max_attempts,
                        'bestScore' => $quizData ? $quizData['best_score'] : null,
                        'isPassed' => $quizData ? $quizData['is_passed'] : false,
                    ];
                }

                $unitModules[] = [
                    'id' => $module->id,
                    'title' => $module->title,
                    'description' => $module->description,
                    'components' => $components,
                    'quiz' => $quiz,
                    'progress' => $moduleProgressPercentage,
                    'order' => $module->order_index,
                ];
            }

            $formatted[] = [
                'id' => $unit->id,
                'title' => $unit->title,
                'description' => $unit->description,
                'order' => $unit->order,
                'modules' => $unitModules,
            ];
        }

        return $formatted;
    }

    private function formatActivitiesForView($activities, array $studentActivities): array
    {
        $formatted = [];

        foreach ($activities as $activity) {
            $studentActivity = $studentActivities[$activity->id] ?? null;

            $formatted[] = [
                'id' => $activity->id,
                'title' => $activity->title,
                'description' => $activity->description,
                'type' => $activity->type,
                'dueDate' => $activity->due_date ? $activity->due_date->format('Y-m-d H:i:s') : null,
                'status' => $studentActivity ? $studentActivity['status'] : 'pending',
                'score' => $studentActivity ? $studentActivity['score'] : null,
                'maxScore' => $activity->max_score,
                'feedback' => $studentActivity ? $studentActivity['feedback'] : null,
            ];
        }

        return $formatted;
    }

    private function formatMaterialsForView($materials): array
    {
        $formatted = [];

        foreach ($materials as $material) {
            $formatted[] = [
                'id' => $material->id,
                'title' => $material->title,
                'description' => $material->description,
                'type' => $material->type,
                'fileUrl' => $material->file_url,
                'fileName' => $material->file_name,
            ];
        }

        return $formatted;
    }
}