<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('role')->default(2); // 1 para admin, 2 para estudiante
        });

        // Crear usuario administrador predeterminado
        DB::table('users')->insert([
            'name' => 'Kevin',
            'email' => 'kevinvillajim@hotmail.com',
            'password' => Hash::make('dalcroze77aA@'),
            'ci' => '1720598877',
            'role' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@admin',
            'password' => Hash::make('C4p4c1t4c10nAdm1n'),
            'ci' => '1234567890',
            'role' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        // Eliminar el usuario administrador predeterminado
        DB::table('users')->where('email', 'admin@admin')->delete();
    }
};
