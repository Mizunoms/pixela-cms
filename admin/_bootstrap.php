<?php
// Arranque común del panel: carga el .env y valida la sesión (JWT de la cookie httpOnly) en el SERVIDOR.
// Así /admin queda protegido aunque alguien adivine la ruta; la API además vuelve a validar cada petición.

declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$raiz = dirname(__DIR__);
$BASE = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');   // ej. /pixela-cms

if (!file_exists($raiz . '/.env')) {
    header('Location: ' . $BASE . '/instalar.php');
    exit;
}

require $raiz . '/vendor/autoload.php';
require $raiz . '/api/config/env.php';
cargarEnv($raiz . '/.env');

// Datos del usuario logueado, o null si no hay sesión válida
function usuarioActual(): ?array
{
    $token = $_COOKIE['pixela_token'] ?? null;
    if (!$token) {
        return null;
    }
    try {
        $p = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
    } catch (Throwable $e) {
        return null;
    }
    return ['id' => (int) $p->sub, 'nombre' => $p->nombre, 'email' => $p->email, 'rol' => $p->rol, 'exp' => (int) $p->exp];
}

function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Las páginas del panel no se guardan en caché (siempre datos frescos)
header('Cache-Control: no-store');
