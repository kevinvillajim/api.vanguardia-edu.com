<?php

namespace App\Infrastructure\Course\Repositories;

use App\Domain\Course\Models\Course;
use App\Domain\Course\Repositories\CourseRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentCourseRepository implements CourseRepositoryInterface
{
    public function findById(int $id): ?Course
    {
        return Course::with([
            'teacher', 
            'category', 
            'units.modules.components',
            'units.modules.lessons',
            'modules.lessons', // Compatibilidad
            'modules.components' // Compatibilidad
        ])->find($id);
    }

    public function findBySlug(string $slug): ?Course
    {
        return Course::with(['teacher', 'category', 'modules.lessons'])
            ->where('slug', $slug)
            ->first();
    }

    public function getAllPublished(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Course::with(['teacher', 'category'])
            ->published();

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['difficulty_level'])) {
            $query->where('difficulty_level', $filters['difficulty_level']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (isset($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'rating':
                    $query->orderBy('rating', 'desc');
                    break;
                case 'popular':
                    $query->orderBy('enrollment_count', 'desc');
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function getAll(): Collection
    {
        return Course::with(['teacher', 'category'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByTeacher(int $teacherId, array $filters = []): Collection
    {
        $query = Course::with(['category', 'modules'])
            ->byTeacher($teacherId);

        if (isset($filters['published'])) {
            $query->where('is_published', $filters['published']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getFeatured(int $limit = 6): Collection
    {
        return Course::with(['teacher', 'category'])
            ->published()
            ->featured()
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return Course::with(['teacher', 'category'])
            ->published()
            ->where('category_id', $categoryId)
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Course
    {
        return DB::transaction(function () use ($data) {
            return Course::create($data);
        });
    }

    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $course = Course::findOrFail($id);

            return $course->update($data);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $course = Course::findOrFail($id);

            return $course->delete();
        });
    }

    public function publish(int $id): bool
    {
        $course = Course::findOrFail($id);

        return $course->update(['is_published' => true]);
    }

    public function unpublish(int $id): bool
    {
        $course = Course::findOrFail($id);

        return $course->update(['is_published' => false]);
    }

    public function incrementEnrollmentCount(int $id): bool
    {
        $course = Course::findOrFail($id);

        return $course->increment('enrollment_count');
    }

    public function decrementEnrollmentCount(int $id): bool
    {
        $course = Course::findOrFail($id);

        return $course->decrement('enrollment_count');
    }

    public function updateRating(int $id, float $rating): bool
    {
        $course = Course::findOrFail($id);

        return $course->update(['rating' => $rating]);
    }

    public function searchCourses(string $query, array $filters = []): LengthAwarePaginator
    {
        $searchQuery = Course::with(['teacher', 'category'])
            ->published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            });

        if (isset($filters['category_id'])) {
            $searchQuery->where('category_id', $filters['category_id']);
        }

        if (isset($filters['difficulty_level'])) {
            $searchQuery->where('difficulty_level', $filters['difficulty_level']);
        }

        return $searchQuery->orderBy('rating', 'desc')
            ->paginate(15);
    }

    // ====== MVP EXTENSIONS ======

    public function findWithContent(int $id): ?Course
    {
        return Course::with([
            'teacher',
            'category',
            'units' => function ($query) {
                $query->orderBy('order_index');
            },
            'units.modules' => function ($query) {
                $query->orderBy('order_index');
            },
            'units.modules.components' => function ($query) {
                $query->where('is_active', true)->orderBy('order');
            }
        ])->find($id);
    }

    public function addUnit(int $courseId, array $unitData)
    {
        $course = Course::findOrFail($courseId);
        
        $unitData['course_id'] = $courseId;
        
        $unitId = DB::table('course_units')->insertGetId([
            'course_id' => $courseId,
            'title' => $unitData['title'],
            'description' => $unitData['description'] ?? null,
            'banner_image' => $unitData['banner_image'] ?? null,
            'order_index' => $unitData['order_index'] ?? 1,
            'is_published' => $unitData['is_published'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Devolver el objeto completo en lugar de solo el ID
        return DB::table('course_units')->where('id', $unitId)->first();
    }

    public function addModule(int $unitId, array $moduleData)
    {
        // Get the course_id from the unit
        $unit = DB::table('course_units')->where('id', $unitId)->first();
        
        if (!$unit) {
            throw new \Exception('Unit not found');
        }

        $moduleId = DB::table('course_modules')->insertGetId([
            'course_id' => $unit->course_id,
            'unit_id' => $unitId,
            'title' => $moduleData['title'],
            'description' => $moduleData['description'] ?? null,
            'order_index' => $moduleData['order_index'] ?? 1,
            'is_published' => $moduleData['is_published'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Devolver el objeto completo
        return DB::table('course_modules')->where('id', $moduleId)->first();
    }

    public function addComponent(int $moduleId, array $componentData)
    {
        $componentId = DB::table('module_components')->insertGetId([
            'module_id' => $moduleId,
            'type' => $componentData['type'],
            'title' => $componentData['title'],
            'content' => $componentData['content'],
            'file_url' => $componentData['file_url'] ?? null,
            'metadata' => $componentData['metadata'] ?? null,
            'duration' => $componentData['duration'] ?? null,
            'order' => $componentData['order'] ?? 0,
            'is_mandatory' => $componentData['is_mandatory'] ?? true,
            'is_active' => $componentData['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Devolver el objeto completo
        return DB::table('module_components')->where('id', $componentId)->first();
    }

    public function updateWithStructure(int $courseId, array $courseData): Course
    {
        return DB::transaction(function () use ($courseId, $courseData) {
            $course = Course::findOrFail($courseId);
            
            // Update basic course info
            $basicData = array_intersect_key($courseData, [
                'title' => '',
                'description' => '',
                'difficulty_level' => '',
                'duration_hours' => '',
                'price' => '',
                'banner_image' => '',
                'is_featured' => '',
                'slug' => ''
            ]);
            
            if (!empty($basicData)) {
                $course->update($basicData);
            }
            
            return $course->fresh();
        });
    }
}
