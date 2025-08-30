<?php

namespace App\Domain\User\Services;

use App\Models\User;
use App\Models\UserAvatar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    /**
     * Obtiene el perfil básico de un usuario (SIN avatar - consulta rápida)
     */
    public function getUserProfile(int $userId): array
    {
        $user = User::select([
            'id', 'name', 'email', 'ci', 'phone', 'bio', 'role', 
            'active', 'has_avatar', 'created_at', 'updated_at'
        ])->findOrFail($userId);
        
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'ci' => $user->ci,
            'phone' => $user->phone,
            'bio' => $user->bio,
            'role' => $user->role,
            'active' => $user->active,
            'has_avatar' => $user->has_avatar,
            'avatar_url' => $user->has_avatar ? "/api/teacher/profile/avatar?t=" . $user->updated_at->timestamp : null,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    /**
     * Obtiene solo los datos del avatar de un usuario
     */
    public function getUserAvatar(int $userId): ?array
    {
        $avatar = UserAvatar::where('user_id', $userId)->first();
        
        if (!$avatar) {
            return null;
        }
        
        return [
            'data_url' => $avatar->data_url,
            'mime_type' => $avatar->mime_type,
            'file_size' => $avatar->file_size,
            'formatted_size' => $avatar->formatted_size,
            'updated_at' => $avatar->updated_at,
            'cache_key' => $avatar->updated_at->timestamp, // Para cache busting
        ];
    }

    /**
     * Actualiza la información del perfil de un usuario
     */
    public function updateProfile(int $userId, array $data): array
    {
        $user = User::findOrFail($userId);
        
        // Campos que se pueden actualizar
        $fillableFields = ['name', 'phone', 'bio', 'ci'];
        $updateData = [];
        
        // Solo incluir campos que están presentes en la data y son fillable
        foreach ($fillableFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }
        
        // Validar CI único si se está cambiando
        if (isset($updateData['ci']) && $updateData['ci'] !== $user->ci) {
            $existingUser = User::where('ci', $updateData['ci'])
                ->where('id', '!=', $userId)
                ->first();
                
            if ($existingUser) {
                throw ValidationException::withMessages([
                    'ci' => ['El CI ya está siendo utilizado por otro usuario.']
                ]);
            }
        }
        
        if (!empty($updateData)) {
            $user->update($updateData);
            $user->refresh();
        }
        
        return $this->getUserProfile($userId);
    }

    /**
     * Actualiza la foto de perfil de un usuario (guarda en Base64)
     */
    public function updateAvatar(int $userId, UploadedFile $file): array
    {
        $user = User::findOrFail($userId);
        
        // Validar tamaño máximo (2MB)
        if ($file->getSize() > 2 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'avatar' => ['El archivo no puede ser mayor a 2MB.']
            ]);
        }
        
        // Convertir archivo a Base64
        $fileContent = file_get_contents($file->getRealPath());
        $base64 = base64_encode($fileContent);
        
        // Datos del avatar
        $avatarData = [
            'user_id' => $userId,
            'avatar_base64' => $base64,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
        
        // Crear o actualizar avatar
        UserAvatar::updateOrCreate(
            ['user_id' => $userId],
            $avatarData
        );
        
        // Actualizar flag has_avatar en users
        $user->update(['has_avatar' => true]);
        
        return $this->getUserProfile($userId);
    }

    /**
     * Elimina la foto de perfil de un usuario
     */
    public function removeAvatar(int $userId): array
    {
        $user = User::findOrFail($userId);
        
        // Eliminar registro del avatar
        UserAvatar::where('user_id', $userId)->delete();
        
        // Actualizar flag has_avatar en users
        $user->update(['has_avatar' => false]);
        
        return $this->getUserProfile($userId);
    }

    /**
     * Cambia la contraseña del usuario
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = User::findOrFail($userId);
        
        // Verificar contraseña actual
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual es incorrecta.']
            ]);
        }
        
        // Actualizar contraseña
        $user->update([
            'password' => Hash::make($newPassword),
            'password_changed' => 1
        ]);
        
        return true;
    }

    /**
     * Valida los datos de perfil
     */
    public function validateProfileData(array $data, int $userId = null): array
    {
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'ci' => 'nullable|string|max:20|unique:users,ci' . ($userId ? ",$userId" : ''),
        ];

        return $rules;
    }

    /**
     * Valida los datos de cambio de contraseña
     */
    public function validatePasswordData(): array
    {
        return [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string',
        ];
    }

    /**
     * Obtiene estadísticas del profesor para su perfil
     */
    public function getTeacherStats(int $teacherId): array
    {
        $user = User::findOrFail($teacherId);
        
        // Verificar que es un profesor
        if ($user->role !== 3) {
            return [];
        }
        
        // Obtener estadísticas de cursos
        $totalCourses = \App\Domain\Course\Models\Course::where('teacher_id', $teacherId)->count();
        $publishedCourses = \App\Domain\Course\Models\Course::where('teacher_id', $teacherId)
            ->where('is_published', true)
            ->count();
            
        // Total de estudiantes
        $totalStudents = \App\Domain\Course\Models\CourseEnrollment::whereHas('course', function ($query) use ($teacherId) {
            $query->where('teacher_id', $teacherId);
        })->distinct('student_id')->count();
        
        return [
            'total_courses' => $totalCourses,
            'published_courses' => $publishedCourses,
            'draft_courses' => $totalCourses - $publishedCourses,
            'total_students' => $totalStudents,
            'member_since' => $user->created_at->format('Y-m-d'),
        ];
    }
}