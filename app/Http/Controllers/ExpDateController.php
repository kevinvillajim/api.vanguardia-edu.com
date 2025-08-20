<?php

namespace App\Http\Controllers;

use App\Models\ExpDate;
use Illuminate\Http\Request;

class ExpDateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ExpDate::all();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'curso_id' => 'required',
            'dead_line' => 'required|date',
        ]);

        // Verificar si ya existe una entrada con el mismo curso_id
        $existingExpDate = ExpDate::where('curso_id', $request->curso_id)->first();

        if ($existingExpDate) {
            return response()->json(['message' => 'ExpDate already exists for this course'], 409);
        }

        $expDate = ExpDate::create([
            'curso_id' => $request->curso_id,
            'dead_line' => $request->dead_line,
        ]);

        return response()->json($expDate, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return ExpDate::find($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExpDate $expDate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $expDate = ExpDate::find($id);
        $expDate->update($request->all());

        return response()->json($expDate, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpDate $expDate)
    {
        //
    }

    public function updateByCursoId(Request $request, $curso_id)
    {
        $validatedData = $request->validate([
            'dead_line' => 'required|date',
        ]);

        $expDate = ExpDate::where('curso_id', $curso_id)->first();

        if (! $expDate) {
            return response()->json(['message' => 'ExpDate not found'], 404);
        }

        $expDate->update($validatedData);

        return response()->json($expDate, 200);
    }
}
