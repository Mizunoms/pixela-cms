<?php
// Endpoints de autenticación: login, logout y usuario actual

class AuthController
{
    private AuthService $servicio;

    public function __construct()
    {
        $this->servicio = new AuthService();
    }

    // POST /auth/login  { "email": "...", "password": "..." }
    public function login(array $params, $usuario): void
    {
        $datos = Response::body();
        $email = trim($datos['email'] ?? '');
        $password = $datos['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            Response::error('Email y contraseña son obligatorios', 422);
        }

        $usuario = $this->servicio->validarCredenciales($email, $password);
        if (!$usuario) {
            Response::error('Credenciales incorrectas', 401);
        }

        $token = $this->servicio->generarToken($usuario);
        $this->ponerCookie($token, time() + $this->servicio->duracionSegundos());

        Response::ok($usuario, 'Sesión iniciada');
    }

    // POST /auth/logout
    public function logout(array $params, $usuario): void
    {
        $this->ponerCookie('', time() - 3600);
        Response::ok(null, 'Sesión cerrada');
    }

    // GET /auth/me  (requiere estar logueado)
    public function me(array $params, $usuario): void
    {
        Response::ok($usuario);
    }

    private function ponerCookie(string $valor, int $expira): void
    {
        setcookie(AuthMiddleware::COOKIE, $valor, [
            'expires'  => $expira,
            'path'     => '/',
            'httponly' => true,                                  // JS no puede leerla
            'secure'   => env('APP_ENV') === 'production',       // solo HTTPS en producción
            'samesite' => 'Strict',
        ]);
    }
}
