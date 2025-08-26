<?php

namespace App\Domain\Course\Services;

use App\Domain\Course\DTOs\CreateCourseDTO;
use App\Domain\Course\DTOs\ModuleDTO;
use App\Domain\Course\DTOs\ComponentDTO;
use App\Domain\Course\Models\Course;
use App\Domain\Course\Repositories\CourseRepositoryInterface;
use App\Domain\Course\Factories\ComponentFactory;
use App\Helpers\StorageHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseManagementService
{
    public function __construct(
        private CourseRepositoryInterface $courseRepository,
        private ComponentFactory $componentFactory
    ) {}

    /**
     * Create a complete course with units, modules and components
     */
    public function createCourse(CreateCourseDTO $courseData, int $teacherId): Course
    {
        return DB::transaction(function () use ($courseData, $teacherId) {
            // 1. Create base course
            $courseArray = $courseData->toArray();
            $courseArray['teacher_id'] = $teacherId;
            $courseArray['slug'] = $this->generateSlug($courseData->title);
            
            $course = $this->courseRepository->create($courseArray);

            // 2. Create units, modules and components if provided
            if (!empty($courseData->modules)) {
                foreach ($courseData->modules as $unitData) {
                    $this->addUnitToCourse($course->id, $unitData);
                }
            }

            return $this->courseRepository->findWithContent($course->id);
        });
    }

    /**
     * Add unit with modules and components to course
     */
    public function addUnitToCourse(int $courseId, array $unitData): mixed
    {
        return DB::transaction(function () use ($courseId, $unitData) {
            // Create unit
            $unitArray = [
                'title' => $unitData['title'],
                'description' => $unitData['description'] ?? null,
                'banner_image' => $unitData['banner_image'] ?? null,
                'order_index' => $unitData['order_index'] ?? 1,
                'is_published' => $unitData['is_published'] ?? true,
            ];

            $unit = $this->courseRepository->addUnit($courseId, $unitArray);

            // Add modules if provided
            if (!empty($unitData['modules'])) {
                foreach ($unitData['modules'] as $moduleData) {
                    $this->addModuleToUnit($unit->id, $moduleData);
                }
            }

            return $unit;
        });
    }

    /**
     * Add module with components to unit
     */
    public function addModuleToUnit(int $unitId, array $moduleData): mixed
    {
        return DB::transaction(function () use ($unitId, $moduleData) {
            $moduleDTO = ModuleDTO::fromArray($moduleData);
            $module = $this->courseRepository->addModule($unitId, $moduleDTO->toArray());

            // Add components if provided
            if (!empty($moduleData['components'])) {
                foreach ($moduleData['components'] as $componentData) {
                    $this->addComponentToModule($module->id, $componentData);
                }
            }

            return $module;
        });
    }

    /**
     * Add component to module
     */
    public function addComponentToModule(int $moduleId, array $componentData): mixed
    {
        $componentDTO = ComponentDTO::fromArray($componentData);
        
        // Use factory to create specific component type
        $processedComponent = $this->componentFactory->createComponent(
            $componentDTO->type,
            $componentDTO->content,
            $moduleId
        );

        $finalData = array_merge($componentDTO->toArray(), [
            'content' => json_encode($processedComponent['content']),
            'metadata' => $processedComponent['metadata'] ? json_encode($processedComponent['metadata']) : null,
        ]);

        return $this->courseRepository->addComponent($moduleId, $finalData);
    }

    /**
     * Publish course (make it available to students)
     */
    public function publishCourse(int $courseId, int $teacherId): Course
    {
        $course = $this->courseRepository->findById($courseId);

        if (!$course) {
            throw new \Exception('Course not found');
        }

        if ($course->teacher_id !== $teacherId) {
            throw new \Exception('Unauthorized to publish this course');
        }

        // Validate course is ready for publication
        $this->validateCourseForPublication($course);

        $this->courseRepository->publish($courseId);

        return $this->courseRepository->findWithContent($courseId);
    }

    /**
     * Update course with complete structure
     */
    public function updateCourse(int $courseId, array $courseData, int $teacherId): Course
    {
        return DB::transaction(function () use ($courseId, $courseData, $teacherId) {
            $course = $this->courseRepository->findById($courseId);

            if (!$course || $course->teacher_id !== $teacherId) {
                throw new \Exception('Course not found or unauthorized');
            }

            // Update base course data
            $updateData = [];
            foreach (['title', 'description', 'difficulty_level', 'duration_hours', 'price', 'banner_image'] as $field) {
                if (isset($courseData[$field])) {
                    $updateData[$field] = $courseData[$field];
                }
            }

            if (isset($updateData['title'])) {
                $updateData['slug'] = $this->generateSlug($updateData['title']);
            }

            if (!empty($updateData)) {
                $this->courseRepository->update($courseId, $updateData);
            }

            return $this->courseRepository->findWithContent($courseId);
        });
    }

    /**
     * Upload course banner image
     */
    public function uploadBannerImage(UploadedFile $file, int $courseId): string
    {
        // Validate file
        $file->validate([
            'mimes:jpeg,png,jpg,webp',
            'max:5120' // 5MB
        ]);

        // Generate filename
        $filename = 'course_' . $courseId . '_banner_' . time() . '.' . $file->getClientOriginalExtension();
        
        // Store file
        $path = $file->storeAs("courses/curso{$courseId}", $filename, 'public');
        
        return $path; // Return the relative path, not the full URL
    }

    /**
     * Get teacher courses
     */
    public function getTeacherCourses(int $teacherId): \Illuminate\Support\Collection
    {
        return $this->courseRepository->getByTeacher($teacherId);
    }

    /**
     * Delete course
     */
    public function deleteCourse(int $courseId, int $teacherId): bool
    {
        $course = $this->courseRepository->findById($courseId);

        if (!$course || $course->teacher_id !== $teacherId) {
            throw new \Exception('Course not found or unauthorized');
        }

        return $this->courseRepository->delete($courseId);
    }

    /**
     * Delete unit and all its modules and components
     */
    public function deleteUnit(int $unitId): bool
    {
        return DB::transaction(function () use ($unitId) {
            // Delete components first
            $modules = DB::table('course_modules')->where('unit_id', $unitId)->get();
            foreach ($modules as $module) {
                DB::table('module_components')->where('module_id', $module->id)->delete();
            }

            // Delete modules
            DB::table('course_modules')->where('unit_id', $unitId)->delete();

            // Delete unit
            DB::table('course_units')->where('id', $unitId)->delete();

            return true;
        });
    }

    /**
     * Delete module and all its components
     */
    public function deleteModule(int $moduleId): bool
    {
        return DB::transaction(function () use ($moduleId) {
            // Delete components first
            DB::table('module_components')->where('module_id', $moduleId)->delete();

            // Delete module
            DB::table('course_modules')->where('id', $moduleId)->delete();

            return true;
        });
    }

    /**
     * Delete component
     */
    public function updateComponent(int $componentId, array $data): array
    {
        // Get current component
        $currentComponent = DB::table('module_components')->where('id', $componentId)->first();
        
        if (!$currentComponent) {
            throw new \Exception('Component not found');
        }

        // Prepare update data
        $updateData = [];
        
        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }
        
        if (isset($data['content'])) {
            $updateData['content'] = json_encode($data['content']);
        }
        
        if (isset($data['metadata'])) {
            $updateData['metadata'] = json_encode($data['metadata']);
        }
        
        if (isset($data['is_mandatory'])) {
            $updateData['is_mandatory'] = (bool) $data['is_mandatory'];
        }
        
        if (isset($data['order'])) {
            $updateData['order'] = (int) $data['order'];
        }

        // Update component
        DB::table('module_components')
            ->where('id', $componentId)
            ->update($updateData);

        // Return updated component
        $updatedComponent = DB::table('module_components')->where('id', $componentId)->first();
        
        return [
            'id' => $updatedComponent->id,
            'type' => $updatedComponent->type,
            'title' => $updatedComponent->title,
            'content' => $updatedComponent->content ? json_decode($updatedComponent->content, true) : null,
            'metadata' => $updatedComponent->metadata ? json_decode($updatedComponent->metadata, true) : null,
            'is_mandatory' => (bool) $updatedComponent->is_mandatory,
            'order' => $updatedComponent->order,
            'module_id' => $updatedComponent->module_id
        ];
    }

    public function deleteComponent(int $componentId): bool
    {
        DB::table('module_components')->where('id', $componentId)->delete();
        return true;
    }

    // ===== PRIVATE METHODS =====

    private function generateSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->courseRepository->findBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function validateCourseForPublication(Course $course): void
    {
        $errors = [];

        if (empty($course->title)) {
            $errors[] = 'Course title is required';
        }

        if (empty($course->description)) {
            $errors[] = 'Course description is required';
        }

        // Check if course has at least one unit with content
        $courseWithContent = $this->courseRepository->findWithContent($course->id);
        
        if (!$courseWithContent->units || $courseWithContent->units->isEmpty()) {
            $errors[] = 'Course must have at least one unit';
        } else {
            $hasContent = false;
            foreach ($courseWithContent->units as $unit) {
                if ($unit->modules && $unit->modules->isNotEmpty()) {
                    foreach ($unit->modules as $module) {
                        if ($module->components && $module->components->isNotEmpty()) {
                            $hasContent = true;
                            break 2;
                        }
                    }
                }
            }

            if (!$hasContent) {
                $errors[] = 'Course must have at least one module with content';
            }
        }

        if (!empty($errors)) {
            throw new \Exception('Course validation failed: ' . implode(', ', $errors));
        }
    }
}