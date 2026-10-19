<?php
// Validaciones reutilizables. Si un dato no es válido lanza ApiException (422).

class Validar
{
    public static function texto($valor, string $campo, int $max, bool $obligatorio = true): ?string
    {
        if ($valor === null || (is_string($valor) && trim($valor) === '')) {
            if ($obligatorio) {
                throw new ApiException("El campo '$campo' es obligatorio", 422);
            }
            return null;
        }
        if (!is_string($valor) && !is_numeric($valor)) {
            throw new ApiException("El campo '$campo' debe ser texto", 422);
        }
        $valor = trim((string) $valor);
        if (mb_strlen($valor) > $max) {
            throw new ApiException("El campo '$campo' admite máximo $max caracteres", 422);
        }
        return $valor;
    }

    public static function hex($valor, string $campo): string
    {
        if (!is_string($valor) || !preg_match('/^#[0-9A-Fa-f]{6}$/', $valor)) {
            throw new ApiException("El campo '$campo' debe ser un color HEX como #7C3AED", 422);
        }
        return strtoupper($valor);
    }

    public static function slug($valor, string $campo = 'slug'): string
    {
        $valor = self::texto($valor, $campo, 120);
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $valor)) {
            throw new ApiException("El '$campo' solo puede tener minúsculas, números y guiones (ej. mis-servicios)", 422);
        }
        return $valor;
    }

    public static function email($valor, string $campo, bool $obligatorio = true): ?string
    {
        $valor = self::texto($valor, $campo, 150, $obligatorio);
        if ($valor !== null && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException("El campo '$campo' no es un email válido", 422);
        }
        return $valor;
    }

    public static function idOpcional($valor, string $campo): ?int
    {
        if ($valor === null || $valor === '' || $valor === 0 || $valor === '0') {
            return null;
        }
        return self::id($valor, $campo);
    }

    public static function id($valor, string $campo = 'id'): int
    {
        if (filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw new ApiException("El campo '$campo' debe ser un número entero positivo", 422);
        }
        return (int) $valor;
    }

    public static function booleano($valor, string $campo): int
    {
        if (is_bool($valor)) {
            return $valor ? 1 : 0;
        }
        if (in_array($valor, [0, 1, '0', '1'], true)) {
            return (int) $valor;
        }
        throw new ApiException("El campo '$campo' debe ser true o false", 422);
    }

    // URL del menú: http(s)://..., ruta interna /algo o ancla #algo
    public static function enlace($valor, string $campo): string
    {
        $valor = self::texto($valor, $campo, 255);
        $esUrl = filter_var($valor, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $valor);
        if (!$esUrl && !preg_match('#^[/\#][^\s]*$#', $valor)) {
            throw new ApiException("El campo '$campo' debe ser una URL (https://...), una ruta (/contacto) o un ancla (#servicios)", 422);
        }
        return $valor;
    }

    // El id de la URL (/paginas/{id}) debe ser numérico; si no, la ruta no existe
    public static function idRuta(array $params, string $clave = 'id'): int
    {
        $valor = $params[$clave] ?? null;
        if (filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw new ApiException('Recurso no encontrado', 404);
        }
        return (int) $valor;
    }
}
