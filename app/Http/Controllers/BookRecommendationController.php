<?php

namespace App\Http\Controllers;

use App\Services\RuleEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\RecommendationFeedback;

class BookRecommendationController extends Controller
{
    public function recommend(Request $request)
    {
        try {
            // Validar entrada: ahora esperamos arrays de strings o null
            $validatedData = $request->validate([
                'favorite_genres' => 'nullable|array',
                'favorite_genres.*' => 'string|max:100',
                'favorite_authors' => 'nullable|array',
                'favorite_authors.*' => 'string|max:100',
            ]);

            // Si el usuario envía un solo género/autor como string, conviértelo a array
            if ($request->has('favorite_genres') && is_string($request->favorite_genres)) {
                $validatedData['favorite_genres'] = [$request->favorite_genres];
            }
            if ($request->has('favorite_authors') && is_string($request->favorite_authors)) {
                $validatedData['favorite_authors'] = [$request->favorite_authors];
            }

            $engine = new RuleEngine();
            $recommendations = $engine->evaluate($validatedData);

            if (empty($recommendations)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay recomendaciones para tus preferencias.',
                ], 200);
            }

            $resume = collect($recommendations)->map(function ($item) {
                return [
                    'title' => $item['title'] ?? 'Sin título',
                    'authors' => isset($item['authors']) && is_array($item['authors'])
                        ? collect($item['authors'])->pluck('name')
                        : [],
                ];
            });

            return response()->json([
                'success' => true,
                'recommendations' => $resume,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Exception $e) {
            Log::error("Error en RuleEngine: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno al procesar recomendaciones.',
                'error_details' => env('APP_DEBUG') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function feedback(Request $request)
    {
        $validated = $request->validate([
            'stars' => 'required|integer|min:0|max:5',
            'user_id' => 'nullable|integer',
        ]);

        $feedback = RecommendationFeedback::create($validated);

        return response()->json([
            'success' => true,
            'feedback' => $feedback,
        ]);
    }
}
