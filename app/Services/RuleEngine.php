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

        $allGroupedResults = [];

        foreach ($rules as $rule) {
            if ($this->matchesConditions($rule['condition'], $userPreferences)) {
                $grouped = $this->processAction($rule['action'], $userPreferences);
                $allGroupedResults = array_merge($allGroupedResults, $grouped);
            }
        }

        // Intercala y limita a 5 recomendaciones
        return $this->interleaveArrays($allGroupedResults, 5);
    }

    /**
     * Ejecuta la acción de la regla, consultando Open Library según tipo.
     */
    private function processAction(array $action, array $userPreferences): array
    {
        $groupedResults = [];
        $limit = $action['limit'] ?? 5;

        if ($action['type'] === 'openlibrary_genre' && !empty($userPreferences[$action['query_field']])) {
            foreach ($userPreferences[$action['query_field']] as $genre) {
                $response = Http::get('https://openlibrary.org/subjects/' . urlencode($genre) . '.json', [
                    'limit' => $limit
                ]);
                $works = $response->json('works') ?? [];
                $groupedResults[] = $works;
            }
        }

        if ($action['type'] === 'openlibrary_author' && !empty($userPreferences[$action['query_field']])) {
            foreach ($userPreferences[$action['query_field']] as $author) {
                $response = Http::get('https://openlibrary.org/search.json', [
                    'author' => $author,
                    'limit' => $limit
                ]);
                $docs = $response->json('docs') ?? [];
                $groupedResults[] = $docs;
            }
        }

        return $groupedResults;
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

    /**
     * Interleave multiple arrays into one, up to a specified limit.
     */
    private function interleaveArrays(array $arrays, int $limit): array
    {
        $result = [];
        $pointers = array_fill(0, count($arrays), 0);

        while (count($result) < $limit) {
            $added = false;
            foreach ($arrays as $i => $arr) {
                if (isset($arr[$pointers[$i]])) {
                    $result[] = $arr[$pointers[$i]];
                    $pointers[$i]++;
                    $added = true;
                    if (count($result) >= $limit) break;
                }
            }
            if (!$added) break; // No quedan más elementos
        }
        return $result;
    }
}
