<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateTestTeacher extends Seeder
{
    public function run()
    {
        // Crear profesor de prueba
        $teacher = User::updateOrCreate(
            ['email' => 'teacher.test@example.com'],
            [
                'name' => 'Profesor Test',
                'password' => Hash::make('test123456'),
                'role' => 3, // Teacher role (1=admin, 2=student, 3=teacher)
                'active' => 1,
                'ci' => '9999999999', // CI requerido
                'email_verified_at' => now(),
            ]
        );

        echo "Teacher creado: {$teacher->email} / test123456\n";
    }
}