<?php
// =====================================================
// PIXELA CMS · Web pública
// Se arma en el servidor en cada visita leyendo la BD: colores, logo, menú y bloques.
// Por eso cualquier cambio hecho en el panel se ve al instante, sin tocar código.
// Rutas: /  -> página de inicio · /servicios -> página con slug "servicios"
// =====================================================

declare(strict_types=1);

$raiz = __DIR__;
$BASE = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

if (!file_exists($raiz . '/.env')) {
    header('Location: ' . $BASE . '/instalar.php');
    exit;
}

require $raiz . '/api/config/env.php';
cargarEnv($raiz . '/.env');
spl_autoload_register(function (string $clase) use ($raiz) {
    foreach (['config', 'core', 'services'] as $carpeta) {
        $archivo = "$raiz/api/$carpeta/$clase.php";
        if (file_exists($archivo)) {
            require $archivo;
            return;
        }
    }
});
require $raiz . '/public/funciones.php';

// ---------- ¿Qué página pidió el visitante? ----------
$ruta = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($BASE !== '' && str_starts_with($ruta, $BASE)) {
    $ruta = substr($ruta, strlen($BASE));
}
$slug = trim($ruta, '/');
if ($slug === 'index.php') {
    $slug = '';
}

try {
    $config = (new ConfigService())->obtener();
    $menu   = (new MenuService())->arbol(true);
    $paginas = new PaginaService();
    $pagina = null;
    $estado = 200;
    try {
        if ($slug === '') {
            $pagina = $paginas->publicaInicio();
        } elseif (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $pagina = $paginas->publicaPorSlug($slug);
        } else {
            throw new ApiException('Página no encontrada', 404);
        }
    } catch (ApiException $e) {
        if ($e->status() !== 404) {
            throw $e;
        }
        $estado = 404;
    }
} catch (Throwable $e) {
    // la BD no responde: página de error amigable
    http_response_code(503);
    $mensaje = env('APP_ENV') === 'production' ? '' : $e->getMessage();
    require $raiz . '/public/error.php';
    exit;
}

http_response_code($estado);
header('Cache-Control: no-store');   // siempre la versión más reciente de los colores y bloques

// Imágenes usadas en los bloques (se buscan todas juntas en una sola consulta)
$IMAGENES = (new MediaService())->variantesDe(idsDeImagenes($pagina['bloques'] ?? []));

require $raiz . '/public/plantilla.php';
