<?php

namespace App\Http\Resources\Course;

use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'enrolled_at' => $this->enrolled_at,
            'completed_at' => $this->completed_at,
            'progress_percentage' => $this->progress_percentage,
            'status' => $this->status,
            'course' => new CourseResource($this->course),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
