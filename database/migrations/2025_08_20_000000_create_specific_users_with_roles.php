<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminar usuarios existentes con estos emails si existen
        DB::table('users')->whereIn('email', [
            'admin@admin.com',
            'test@test.com', 
            'profesor@profesor.com'
        ])->delete();

        // Crear usuarios específicos solicitados
        $users = [
            [
                'name' => 'Administrador',
                'email' => 'admin@admin.com',
                'password' => Hash::make('dalcroze77aA@'),
                'ci' => '1111111111',
                'role' => 1, // Admin
                'active' => true,
                'password_changed' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Usuario Test',
                'email' => 'test@test.com',
                'password' => Hash::make('dalcroze77aA@'),
                'ci' => '2222222222',
                'role' => 2, // Estudiante
                'active' => true,
                'password_changed' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Profesor Principal',
                'email' => 'profesor@profesor.com',
                'password' => Hash::make('dalcroze77aA@'),
                'ci' => '3333333333',
                'role' => 3, // Profesor
                'active' => true,
                'password_changed' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        DB::table('users')->insert($users);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar los usuarios creados
        DB::table('users')->whereIn('email', [
            'admin@admin.com',
            'test@test.com',
            'profesor@profesor.com'
        ])->delete();
    }
};