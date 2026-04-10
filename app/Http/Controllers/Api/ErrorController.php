<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Error;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ErrorController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
            'server_name' => ['nullable', 'string', 'max:255'],
        ]);

        $solution = detectarSolucao($validated['message']);

        $error = Error::create([
            'server_name' => $validated['server_name'] ?? null,
            'message' => $validated['message'],
            'solution' => $solution,
        ]);

        return response()->json([
            'status' => 'ok',
            'id' => $error->id,
            'solution' => $solution,
        ]);
    }
}
