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

// Errores: los controlados (ApiException) devuelven su mensaje y código;
// los no controlados, un 500 genérico (sin exponer detalles en producción)
set_exception_handler(function (Throwable $e) {
    if ($e instanceof ApiException) {
        Response::error($e->getMessage(), $e->status());
    }
    $mensaje = env('APP_ENV') === 'production' ? 'Error interno del servidor' : $e->getMessage();
    Response::error($mensaje, 500);
});

$router = new Router();

// Quién puede hacer qué
$logueado = AuthMiddleware::autenticado();
$editor   = AuthMiddleware::rol(['admin', 'editor']);   // contenido: páginas, bloques, menú, imágenes
$admin    = AuthMiddleware::rol(['admin']);             // configuración global del sitio

// ---------- Rutas públicas (las usa la web) ----------
$router->get('/', [HealthController::class, 'estado']);
$router->get('/config',          [ConfigController::class, 'obtener']);
$router->get('/menu',            [MenuController::class, 'publico']);
$router->get('/inicio',          [PaginaController::class, 'inicio']);
$router->get('/paginas/{slug}',  [PaginaController::class, 'publica']);

// ---------- Autenticación ----------
$router->post('/auth/login',  [AuthController::class, 'login']);
$router->post('/auth/logout', [AuthController::class, 'logout']);
$router->get('/auth/me',      [AuthController::class, 'me'], [$logueado]);

// ---------- Panel admin ----------
$router->get('/admin/ping', [HealthController::class, 'adminPing'], [$admin]);

// Configuración global (colores, logo, contacto)
$router->put('/admin/config', [ConfigController::class, 'actualizar'], [$admin]);

// Páginas
$router->get('/admin/paginas',         [PaginaController::class, 'listar'],     [$editor]);
$router->post('/admin/paginas',        [PaginaController::class, 'crear'],      [$editor]);
$router->get('/admin/paginas/{id}',    [PaginaController::class, 'obtener'],    [$editor]);
$router->put('/admin/paginas/{id}',    [PaginaController::class, 'actualizar'], [$editor]);
$router->delete('/admin/paginas/{id}', [PaginaController::class, 'eliminar'],   [$editor]);

// Bloques de cada página (contenido en JSON)
$router->get('/admin/bloques/tipos',               [ComponenteController::class, 'tipos'],      [$editor]);
$router->get('/admin/paginas/{id}/bloques',        [ComponenteController::class, 'listar'],     [$editor]);
$router->post('/admin/paginas/{id}/bloques',       [ComponenteController::class, 'crear'],      [$editor]);
$router->put('/admin/paginas/{id}/bloques/orden',  [ComponenteController::class, 'reordenar'],  [$editor]);
$router->get('/admin/bloques/{id}',                [ComponenteController::class, 'obtener'],    [$editor]);
$router->put('/admin/bloques/{id}',                [ComponenteController::class, 'actualizar'], [$editor]);
$router->delete('/admin/bloques/{id}',             [ComponenteController::class, 'eliminar'],   [$editor]);

// Menú (la ruta /orden va antes que /{id})
$router->get('/admin/menu',         [MenuController::class, 'listar'],     [$editor]);
$router->post('/admin/menu',        [MenuController::class, 'crear'],      [$editor]);
$router->put('/admin/menu/orden',   [MenuController::class, 'reordenar'],  [$editor]);
$router->put('/admin/menu/{id}',    [MenuController::class, 'actualizar'], [$editor]);
$router->delete('/admin/menu/{id}', [MenuController::class, 'eliminar'],   [$editor]);

// Imágenes
$router->get('/admin/media',         [MediaController::class, 'listar'],     [$editor]);
$router->post('/admin/media',        [MediaController::class, 'subir'],      [$editor]);
$router->put('/admin/media/{id}',    [MediaController::class, 'actualizar'], [$editor]);
$router->delete('/admin/media/{id}', [MediaController::class, 'eliminar'],   [$editor]);

$router->ejecutar($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
