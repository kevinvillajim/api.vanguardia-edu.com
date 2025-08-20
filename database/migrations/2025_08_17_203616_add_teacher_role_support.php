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
        // Crear usuario profesor de ejemplo
        DB::table('users')->insert([
            'name' => 'Profesor Demo',
            'email' => 'profesor@demo.com',
            'password' => Hash::make('password123'),
            'ci' => '9876543210',
            'role' => 3, // Rol 3 para profesor
            'active' => true,
            'password_changed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear estudiante de ejemplo también
        DB::table('users')->insert([
            'name' => 'Estudiante Demo',
            'email' => 'estudiante@demo.com',
            'password' => Hash::make('password123'),
            'ci' => '1122334455',
            'role' => 2, // Rol 2 para estudiante
            'active' => true,
            'password_changed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('email', 'profesor@demo.com')->delete();
        DB::table('users')->where('email', 'estudiante@demo.com')->delete();
    }
};
