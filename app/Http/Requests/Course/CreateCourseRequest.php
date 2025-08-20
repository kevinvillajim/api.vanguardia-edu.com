<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class CreateCourseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'nullable|exists:course_categories,id',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'duration_hours' => 'nullable|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'is_featured' => 'nullable|boolean',

            // Módulos opcionales al crear
            'modules' => 'nullable|array',
            'modules.*.title' => 'required_with:modules|string|max:255',
            'modules.*.description' => 'nullable|string',
            'modules.*.order_index' => 'nullable|integer|min:0',
            'modules.*.is_published' => 'nullable|boolean',

            // Lecciones opcionales
            'modules.*.lessons' => 'nullable|array',
            'modules.*.lessons.*.title' => 'required_with:modules.*.lessons|string|max:255',
            'modules.*.lessons.*.description' => 'nullable|string',
            'modules.*.lessons.*.duration_minutes' => 'nullable|integer|min:0',
            'modules.*.lessons.*.is_preview' => 'nullable|boolean',
            'modules.*.lessons.*.is_published' => 'nullable|boolean',
            'modules.*.lessons.*.order_index' => 'nullable|integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'El título del curso es obligatorio',
            'description.required' => 'La descripción del curso es obligatoria',
            'difficulty_level.required' => 'El nivel de dificultad es obligatorio',
            'difficulty_level.in' => 'El nivel de dificultad debe ser: beginner, intermediate o advanced',
            'banner_image.image' => 'El archivo debe ser una imagen',
            'banner_image.max' => 'La imagen no debe superar los 5MB',
            'category_id.exists' => 'La categoría seleccionada no existe',
        ];
    }
}
