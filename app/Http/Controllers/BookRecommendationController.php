<?php

namespace App\Http\Controllers;

use App\Services\RuleEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class BookRecommendationController extends Controller
{
    public function recommend(Request $request)
    {
        try {
            // Validar entrada
            $validatedData = $request->validate([
                'favorite_genre' => 'nullable|string|max:100',
                'favorite_author' => 'nullable|string|max:100',
            ]);

            $engine = new RuleEngine();
            $recommendations = $engine->evaluate($validatedData);

            if (empty($recommendations)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay recomendaciones para tus preferencias.',
                ], 200); // 200 porque no es un error del servidor
            }

            return response()->json([
                'success' => true,
                'recommendations' => $recommendations,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Captura errores de validación (ej: campo no string)
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors' => $e->errors(),
            ], 422); // Código HTTP 422 (Unprocessable Entity)

        } catch (Exception $e) {
            // Captura cualquier otro error (ej: archivo JSON roto)
            Log::error("Error en RuleEngine: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno al procesar recomendaciones.',
                'error_details' => env('APP_DEBUG') ? $e->getMessage() : null, // Solo en desarrollo
            ], 500); // Código HTTP 500 (Error interno)
        }
    }
}
