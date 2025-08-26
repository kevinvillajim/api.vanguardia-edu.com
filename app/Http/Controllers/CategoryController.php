<?php

namespace App\Http\Controllers;

use App\Domain\Course\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Obtener todas las categorías
     */
    public function index(Request $request): JsonResponse
    {
        // Verificar si es una validación de slug
        if ($request->has('validateSlug')) {
            $slug = $request->input('validateSlug');
            $excludeId = $request->input('excludeId');
            
            $query = CourseCategory::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            $exists = $query->exists();
            
            return response()->json([
                'success' => true,
                'available' => !$exists // disponible si NO existe
            ]);
        }
        
        $categories = CourseCategory::orderBy('name', 'asc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Crear nueva categoría (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:course_categories',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50'
        ]);

        $category = CourseCategory::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Categoría creada exitosamente'
        ], 201);
    }

    /**
     * Mostrar categoría específica
     */
    public function show($id): JsonResponse
    {
        $category = CourseCategory::with('courses')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Actualizar categoría (Admin only)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $category = CourseCategory::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255|unique:course_categories,name,' . $id,
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50'
        ]);

        $category->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Categoría actualizada exitosamente'
        ]);
    }

    /**
     * Eliminar categoría (Admin only)
     */
    public function destroy($id): JsonResponse
    {
        $category = CourseCategory::findOrFail($id);
        
        // Verificar si tiene cursos asociados
        if ($category->courses()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una categoría con cursos asociados'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente'
        ]);
    }

    /**
     * Crear nueva categoría para profesores en contexto de creación de cursos
     * Permite a profesores crear categorías con validación y moderación automática
     */
    public function storeForTeacher(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50'
        ]);

        // Generar slug automáticamente
        $slug = $this->generateSlug($request->input('name'));
        
        // Verificar que el slug sea único
        $existingCategory = CourseCategory::where('slug', $slug)->first();
        if ($existingCategory) {
            return response()->json([
                'success' => false,
                'message' => "Una categoría con el nombre \"{$request->input('name')}\" ya existe"
            ], 422);
        }

        // Crear categoría con configuración específica para profesores
        $category = CourseCategory::create([
            'name' => $request->input('name'),
            'slug' => $slug,
            'description' => $request->input('description'),
            'icon' => $request->input('icon')
        ]);

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Categoría creada exitosamente'
        ], 201);
    }

    /**
     * Generar slug único a partir del nombre
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
}
