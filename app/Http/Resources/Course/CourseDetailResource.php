<?php

namespace App\Http\Resources\Course;

use App\Helpers\StorageHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'learning_objectives' => $this->learning_objectives ?? [],
            'prerequisites' => $this->prerequisites ?? [],
            'banner_image' => $this->banner_image ? StorageHelper::courseBannerUrl($this->banner_image) : null,
            'difficulty_level' => $this->difficulty_level,
            'duration_hours' => $this->duration_hours,
            'price' => $this->price,
            'rating' => $this->rating,
            'enrollment_count' => $this->enrollment_count,
            'is_published' => $this->is_published,
            'is_featured' => $this->is_featured,
            'teacher' => [
                'id' => $this->teacher->id,
                'name' => $this->teacher->name,
                'email' => $this->teacher->email,
                'avatar' => $this->teacher->avatar ?? null,
            ],
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            // Incluir units con su estructura completa
            'units' => $this->when(
                $this->relationLoaded('units'),
                function () {
                    return $this->units->map(function ($unit) {
                        return [
                            'id' => $unit->id,
                            'title' => $unit->title,
                            'description' => $unit->description,
                            'banner_image' => $unit->banner_image ? StorageHelper::courseBannerUrl($unit->banner_image, $this->id) : null,
                            'order_index' => $unit->order_index,
                            'is_published' => $unit->is_published,
                            'modules' => $unit->modules->map(function ($module) {
                                return [
                                    'id' => $module->id,
                                    'title' => $module->title,
                                    'description' => $module->description,
                                    'order_index' => $module->order_index,
                                    'is_published' => $module->is_published,
                                    'components' => $module->components->map(function ($component) {
                                        return [
                                            'id' => $component->id,
                                            'type' => $component->type,
                                            'title' => $component->title,
                                            'content' => is_string($component->content) ? json_decode($component->content, true) : $component->content,
                                            'file_url' => $component->file_url,
                                            'metadata' => is_string($component->metadata) ? json_decode($component->metadata, true) : $component->metadata,
                                            'duration' => $component->duration,
                                            'order' => $component->order,
                                            'is_mandatory' => $component->is_mandatory,
                                            'is_active' => $component->is_active,
                                        ];
                                    }),
                                    'lessons' => $module->lessons->map(function ($lesson) {
                                        return [
                                            'id' => $lesson->id,
                                            'title' => $lesson->title,
                                            'description' => $lesson->description,
                                            'duration_minutes' => $lesson->duration_minutes,
                                            'is_preview' => $lesson->is_preview,
                                            'is_published' => $lesson->is_published,
                                            'order_index' => $lesson->order_index,
                                        ];
                                    }),
                                ];
                            }),
                        ];
                    });
                }
            ),
            // Mantener compatibilidad con estructura anterior
            'modules' => $this->when(
                $this->relationLoaded('modules'),
                function () {
                    return $this->modules->map(function ($module) {
                        return [
                            'id' => $module->id,
                            'title' => $module->title,
                            'description' => $module->description,
                            'order_index' => $module->order_index,
                            'is_published' => $module->is_published,
                            'lessons' => $module->lessons->map(function ($lesson) {
                                return [
                                    'id' => $lesson->id,
                                    'title' => $lesson->title,
                                    'description' => $lesson->description,
                                    'duration_minutes' => $lesson->duration_minutes,
                                    'is_preview' => $lesson->is_preview,
                                    'is_published' => $lesson->is_published,
                                    'order_index' => $lesson->order_index,
                                ];
                            }),
                        ];
                    });
                }
            ),
            'total_modules' => $this->modules->count(),
            'total_lessons' => $this->modules->sum(function ($module) {
                return $module->lessons->count();
            }),
            'total_duration_minutes' => $this->modules->sum(function ($module) {
                return $module->lessons->sum('duration_minutes');
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
