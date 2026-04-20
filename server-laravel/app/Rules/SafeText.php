<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeText implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        // 1. Bloquear etiquetas HTML (XSS preventivo)
        if (preg_match('/<[^>]*>/', $value)) {
            $fail('El campo :attribute contiene caracteres HTML no permitidos.');
            return;
        }

        // 2. Bloquear comentarios SQL
        // -- (comentario de línea), /* ... */ (comentario de bloque)
        if (preg_match('/(--|\/\*|\*\/)/', $value)) {
            $fail('El campo :attribute contiene patrones de comentarios SQL sospechosos.');
            return;
        }

        // 3. Bloquear secuencias de múltiples sentencias (; seguido de palabras clave)
        // Ejemplo: "; DROP", "; DELETE", "; UPDATE"
        if (preg_match('/;\s*(DROP|DELETE|UPDATE|SELECT|INSERT|TRUNCATE|DATABASE|TABLE)/i', $value)) {
            $fail('El campo :attribute contiene secuencias de comandos no permitidas.');
            return;
        }

        // 4. Bloquear patrones de scripts conocidos
        $dangerousPatterns = [
            'javascript:',
            'data:text/html',
            'vbscript:',
            'onload=',
            'onerror=',
            'onclick=',
            '<script',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (stripos($value, $pattern) !== false) {
                $fail('El campo :attribute contiene contenido potencialmente malicioso.');
                return;
            }
        }
    }
}
