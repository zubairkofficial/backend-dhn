<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instruction;

class InstructionController extends Controller
{
    public function index()
    {
        return Instruction::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255'
        ]);

        return Instruction::create($validated);
    }

    public function show(Instruction $instruction)
    {
        return $instruction;
    }

    public function update(Request $request, Instruction $instruction)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255'
        ]);

        $instruction->update($validated);
        return $instruction;
    }

    public function destroy(Instruction $instruction)
    {
        $instruction->delete();
        return response()->json(['message' => 'Instruction deleted successfully']);
    }
}
