<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserImportController extends Controller
{
    public function import(Request $request)
    {

        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');
        Log::info('Inicio de la importación de usuarios');

        // Validar que el archivo sea un CSV o TXT
        $request->validate([
            'file' => 'required|mimes:csv,txt',
        ]);

        // Obtener el path real del archivo subido y abrirlo para lectura
        $path = $request->file('file')->getRealPath();
        Log::info("Path del archivo: $path");

        $file = fopen($path, 'r');
        Log::info('Archivo abierto correctamente');

        // Leer la primera línea para obtener el encabezado
        $header = fgetcsv($file, 0, ';');
        Log::info('Encabezado leído: ' . implode(', ', $header));

        $csvData = [];
        $rowNumber = 1; // Para seguimiento de filas

        // Leer el resto del archivo
        while ($row = fgetcsv($file, 0, ';')) {
            $csvData[] = array_combine($header, $row);
            Log::info("Fila $rowNumber leída: " . implode(', ', $row));
            $rowNumber++;
        }

        // Cerrar el archivo
        fclose($file);
        Log::info('Archivo cerrado correctamente');

        // Obtener todos los usuarios existentes e indexarlos por su correo electrónico
        $existingUsers = User::all()->keyBy('email');
        Log::info('Usuarios existentes cargados');

        // Obtener los correos electrónicos del CSV
        $emailsInCSV = collect($csvData)->pluck('email')->all();
        Log::info('Correos electrónicos en CSV: ' . implode(', ', $emailsInCSV));

        $errors = []; // Array para almacenar los errores

        // Crear o actualizar usuarios
        foreach ($csvData as $data) {
            try {
                Log::info("Procesando usuario: {$data['email']}");
                if (isset($existingUsers[$data['email']])) {
                    // Si el usuario existe, actualizarlo
                    $user = $existingUsers[$data['email']];
                    $user->update([
                        'name' => $data['name'],
                        'ci' => $data['ci'],
                        'active' => $data['active'],
                    ]);
                    Log::info("Usuario actualizado: {$data['email']}");
                } else {
                    // Si el usuario no existe, crearlo
                    User::create([
                        'email' => $data['email'],
                        'name' => $data['name'],
                        'ci' => $data['ci'],
                        'active' => $data['active'],
                        'password' =>
                        Hash::make(
                            'C4p4c1t4c10n'
                        ),
                    ]);
                    Log::info("Usuario creado: {$data['email']}");
                }
            } catch (\Exception $e) {
                // Agregar el error al array
                $errors[] = [
                    'email' => $data['email'],
                    'error' => $e->getMessage(),
                ];

                // Registrar el error en el log para revisarlo más tarde
                Log::error("Error importando usuario {$data['email']}: {$e->getMessage()}");
            }
        }

        // Eliminar usuarios que no están en el CSV
        User::whereNotIn('email', $emailsInCSV)
            ->where('email', '!=', 'admin@admin')
            ->where('email', '!=', 'kevinvillajim@hotmail.com')
            ->delete();
        Log::info('Usuarios no presentes en el CSV eliminados');

        // Retornar el resultado de la importación
        Log::info('Finalización de la importación de usuarios');
        return response()->json([
            'message' => 'Users imported successfully',
            'errors' => $errors, // Incluir los errores en la respuesta
        ], 200);
    }
}
