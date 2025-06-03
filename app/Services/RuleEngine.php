<?php

namespace App\Services;

class RuleEngine
{
    protected $rulesPath;

    public function __construct()
    {
        // Ruta al archivo JSON de reglas (ajusta si usas otra ubicación)
        $this->rulesPath = storage_path('rules/user_preferences_rules.json');
    }

    /**
     * Evalúa las preferencias del usuario contra las reglas.
     * @param array $userPreferences Ej: ["favorite_genre" => "fantasía", "favorite_author" => "J.K. Rowling"]
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
                $recommendations[] = $rule['action'];
            }
        }

        return $recommendations;
    }

    /**
     * Compara las condiciones de una regla con las preferencias del usuario.
     */
    private function matchesConditions(array $conditions, array $userPreferences): bool
    {
        foreach ($conditions as $key => $value) {
            // Si el usuario no tiene la clave o el valor no coincide, la regla no aplica
            if (!isset($userPreferences[$key]) || $userPreferences[$key] != $value) {
                return false;
            }
        }
        return true; // Todas las condiciones coinciden
    }
}
