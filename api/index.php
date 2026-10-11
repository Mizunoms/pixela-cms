<?php
// Punto de entrada único de la API

declare(strict_types=1);

$raiz = dirname(__DIR__);

require $raiz . '/vendor/autoload.php';
require __DIR__ . '/config/env.php';
cargarEnv($raiz . '/.env');

// Autocarga de clases propias (controllers, services, middleware, core, config)
spl_autoload_register(function (string $clase) {
    foreach (['config', 'core', 'controllers', 'services', 'middleware'] as $carpeta) {
        $archivo = __DIR__ . "/$carpeta/$clase.php";
        if (file_exists($archivo)) {
            require $archivo;
            return;
        }
    }
});

// Errores no controlados -> JSON genérico (sin exponer detalles en producción)
set_exception_handler(function (Throwable $e) {
    $mensaje = env('APP_ENV') === 'production' ? 'Error interno del servidor' : $e->getMessage();
    Response::error($mensaje, 500);
});

$router = new Router();

// ---------- Rutas ----------
$router->get('/', [HealthController::class, 'estado']);

// Autenticación
$router->post('/auth/login',  [AuthController::class, 'login']);
$router->post('/auth/logout', [AuthController::class, 'logout']);
$router->get('/auth/me',      [AuthController::class, 'me'], [AuthMiddleware::autenticado()]);

// Ejemplo de ruta solo para admin (se usará en semana 2)
$router->get('/admin/ping', [HealthController::class, 'adminPing'], [AuthMiddleware::rol(['admin'])]);

$router->ejecutar($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
