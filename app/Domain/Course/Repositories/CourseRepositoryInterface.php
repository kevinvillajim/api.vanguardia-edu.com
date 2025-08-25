<?php

namespace App\Domain\Course\Repositories;

use App\Domain\Course\Models\Course;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CourseRepositoryInterface
{
    public function findById(int $id): ?Course;

    public function findBySlug(string $slug): ?Course;

    public function getAllPublished(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getAll(): Collection;

    public function getByTeacher(int $teacherId, array $filters = []): Collection;

    public function getFeatured(int $limit = 6): Collection;

    public function getByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Course;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function publish(int $id): bool;

    public function unpublish(int $id): bool;

    public function incrementEnrollmentCount(int $id): bool;

    public function decrementEnrollmentCount(int $id): bool;

    public function updateRating(int $id, float $rating): bool;

    public function searchCourses(string $query, array $filters = []): LengthAwarePaginator;

    // ====== MVP EXTENSIONS ======
    
    /**
     * Find course by ID with all related content (units, modules, components)
     */
    public function findWithContent(int $id): ?Course;

    /**
     * Add unit to course
     */
    public function addUnit(int $courseId, array $unitData);

    /**
     * Add module to unit
     */
    public function addModule(int $unitId, array $moduleData);

    /**
     * Add component to module
     */
    public function addComponent(int $moduleId, array $componentData);

    /**
     * Update course with complete structure (units, modules, components)
     */
    public function updateWithStructure(int $courseId, array $courseData): Course;
}
