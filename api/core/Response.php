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

    public static function error(string $mensaje, int $status = 400): void
    {
        self::json(['ok' => false, 'error' => $mensaje], $status);
    }

    // Lee el body JSON de la petición
    public static function body(): array
    {
        $datos = json_decode(file_get_contents('php://input'), true);
        return is_array($datos) ? $datos : [];
    }
}
