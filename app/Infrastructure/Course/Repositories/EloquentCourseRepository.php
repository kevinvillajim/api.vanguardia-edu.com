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
        return Course::with(['teacher', 'category', 'modules.lessons'])
            ->find($id);
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
}
