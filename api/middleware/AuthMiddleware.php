<?php
// Verifica el JWT de la cookie httpOnly y el rol del usuario

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    public const COOKIE = 'pixela_token';

    // Solo exige estar logueado (cualquier rol)
    public static function autenticado(): callable
    {
        return fn() => self::validarToken();
    }

    // Exige estar logueado y tener uno de los roles indicados
    public static function rol(array $rolesPermitidos): callable
    {
        return function () use ($rolesPermitidos) {
            $usuario = self::validarToken();
            if (!in_array($usuario['rol'], $rolesPermitidos, true)) {
                Response::error('No tienes permisos para esta acción', 403);
            }
            return $usuario;
        };
    }

    private static function validarToken(): array
    {
        $token = $_COOKIE[self::COOKIE] ?? null;
        if (!$token) {
            Response::error('No autenticado', 401);
        }

        try {
            $payload = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
        } catch (Throwable $e) {
            Response::error('Sesión inválida o expirada', 401);
        }

        return [
            'id'     => (int) $payload->sub,
            'nombre' => $payload->nombre,
            'email'  => $payload->email,
            'rol'    => $payload->rol,
        ];
    }
}
