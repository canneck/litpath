<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RuleEngine
{
    protected $rulesPath;

    public function __construct()
    {
        // Ruta al archivo JSON de reglas (ajusta si usas otra ubicación)
        $this->rulesPath = storage_path('rules/user_preferences_rules.json');
    }

    /**
     * Evalúa las preferencias del usuario contra las reglas y ejecuta acciones.
     * @param array $userPreferences Ej: ["favorite_genres" => ["fantasía"], "favorite_authors" => ["J.K. Rowling"]]
     * @return array Recomendaciones que coinciden.
     */
    public function evaluate(array $userPreferences)
    {
        // Verifica si el archivo existe
        if (!file_exists($this->rulesPath)) {
            throw new \Exception("¡Archivo de reglas no encontrado en: " . $this->rulesPath);
        }

        // Lee y decodifica el JSON
        $rulesData = json_decode(file_get_contents($this->rulesPath), true);
        $rules = $rulesData['rules'] ?? [];
        $recommendations = [];

        // Itera cada regla y verifica coincidencias
        foreach ($rules as $rule) {
            if ($this->matchesConditions($rule['condition'], $userPreferences)) {
                $recommendations = array_merge(
                    $recommendations,
                    $this->processAction($rule['action'], $userPreferences)
                );
            }
        }

        // Limita el total de recomendaciones a 5
        return array_slice($recommendations, 0, 5);
    }

    /**
     * Ejecuta la acción de la regla, consultando Open Library según tipo.
     */
    private function processAction(array $action, array $userPreferences): array
    {
        $results = [];
        $limit = $action['limit'] ?? 5;

        if ($action['type'] === 'openlibrary_genre' && !empty($userPreferences[$action['query_field']])) {
            foreach ($userPreferences[$action['query_field']] as $genre) {
                $response = Http::get('https://openlibrary.org/subjects/' . urlencode($genre) . '.json', [
                    'limit' => $limit
                ]);
                $works = $response->json('works') ?? [];
                $results = array_merge($results, $works);
            }
        }

        if ($action['type'] === 'openlibrary_author' && !empty($userPreferences[$action['query_field']])) {
            foreach ($userPreferences[$action['query_field']] as $author) {
                $response = Http::get('https://openlibrary.org/search.json', [
                    'author' => $author,
                    'limit' => $limit
                ]);
                $docs = $response->json('docs') ?? [];
                $results = array_merge($results, $docs);
            }
        }

        return $results;
    }

    /**
     * Compara las condiciones de una regla con las preferencias del usuario.
     * Ahora permite condiciones vacías para listas (arrays).
     */
    private function matchesConditions(array $conditions, array $userPreferences): bool
    {
        foreach ($conditions as $key => $value) {
            // Si la condición es un array vacío, solo verifica que el usuario tenga ese campo y que sea un array no vacío
            if (is_array($value) && $value === []) {
                if (!isset($userPreferences[$key]) || !is_array($userPreferences[$key]) || empty($userPreferences[$key])) {
                    return false;
                }
            } else {
                // Si el usuario no tiene la clave o el valor no coincide, la regla no aplica
                if (!isset($userPreferences[$key]) || $userPreferences[$key] != $value) {
                    return false;
                }
            }
        }
        return true; // Todas las condiciones coinciden
    }
}
