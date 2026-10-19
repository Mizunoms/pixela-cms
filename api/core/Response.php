<?php
// Respuestas JSON con formato uniforme

class Response
{
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function ok($data = null, string $mensaje = 'OK', int $status = 200): void
    {
        self::json(['ok' => true, 'mensaje' => $mensaje, 'data' => $data], $status);
    }

    public static function creado($data = null, string $mensaje = 'Creado'): void
    {
        self::ok($data, $mensaje, 201);
    }

    public static function error(string $mensaje, int $status = 400): void
    {
        self::json(['ok' => false, 'error' => $mensaje], $status);
    }

    // Lee el body JSON de la petición (vacío = [], JSON roto = error 400)
    public static function body(): array
    {
        $crudo = file_get_contents('php://input');
        if ($crudo === '' || $crudo === false) {
            return [];
        }
        $datos = json_decode($crudo, true);
        if (!is_array($datos)) {
            throw new ApiException('El cuerpo de la petición debe ser un objeto JSON válido', 400);
        }
        return $datos;
    }
}
