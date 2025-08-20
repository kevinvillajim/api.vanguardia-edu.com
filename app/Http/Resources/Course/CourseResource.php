<?php

namespace App\Http\Resources\Course;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'banner_image' => $this->banner_image ? asset('storage/'.$this->banner_image) : null,
            'difficulty_level' => $this->difficulty_level,
            'duration_hours' => $this->duration_hours,
            'price' => $this->price,
            'rating' => $this->rating,
            'enrollment_count' => $this->enrollment_count,
            'is_featured' => $this->is_featured,
            'teacher' => [
                'id' => $this->teacher->id,
                'name' => $this->teacher->name,
                'avatar' => $this->teacher->avatar ?? null,
            ],
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'total_lessons' => $this->when(
                $this->relationLoaded('modules'),
                function () {
                    return $this->modules->sum(function ($module) {
                        return $module->lessons->count();
                    });
                }
            ),
            'created_at' => $this->created_at,
        ];
    }
}
