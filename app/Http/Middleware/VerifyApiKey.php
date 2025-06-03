<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if ($apiKey !== env('LITPATH_API_KEY')) {
            return response()->json([
                'success' => false,
                'message' => 'API Key inválida o faltante.',
            ], 401); // HTTP 401 Unauthorized
        }

        return $next($request);
    }
}
