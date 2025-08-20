<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category_id' => 'nullable|exists:course_categories,id',
            'difficulty_level' => 'sometimes|required|in:beginner,intermediate,advanced',
            'duration_hours' => 'nullable|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'is_featured' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'El título del curso es obligatorio',
            'description.required' => 'La descripción del curso es obligatoria',
            'difficulty_level.in' => 'El nivel de dificultad debe ser: beginner, intermediate o advanced',
            'banner_image.image' => 'El archivo debe ser una imagen',
            'banner_image.max' => 'La imagen no debe superar los 5MB',
            'category_id.exists' => 'La categoría seleccionada no existe',
        ];
    }
}
