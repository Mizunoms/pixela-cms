<?php
// Estructura de la web: <head> con los colores del panel, cabecera con menú, bloques y pie.
// Variables que llegan desde index.php: $config, $menu, $pagina (o null si es 404), $BASE

$logo = $config['logo']['url'] ?? ($BASE . '/public/assets/img/pixela-logo-256.png');
$logoSrcset = $config['logo']['srcset'] ?? '';
$favicon = $config['favicon']['variantes'][0]['url'] ?? ($config['logo']['variantes'][0]['url'] ?? $BASE . '/public/assets/img/pixela-logo-128.png');
$nombre = $config['nombre_sitio'];
$titulo = $pagina ? ($pagina['es_inicio'] ? "$nombre · " . ($config['eslogan'] ?: $pagina['titulo']) : $pagina['titulo'] . " · $nombre") : "Página no encontrada · $nombre";
$descripcion = $pagina['meta_descripcion'] ?? $config['eslogan'];
$rutaActual = '/' . $slug;
$wa = $config['whatsapp'] ? whatsapp($config['whatsapp'], "Hola $nombre, quiero información") : '';

// Palabras de la cinta: títulos de los servicios de la página (o del sitio)
$cinta = [];
foreach (($pagina['bloques'] ?? []) as $b) {
    if ($b['tipo'] === 'servicios') {
        $cinta = array_column($b['contenido']['items'] ?? [], 'titulo');
        break;
    }
}
if (!$cinta) {
    $cinta = [$nombre, $config['eslogan'] ?: 'Bienvenido'];
}

// ¿Este enlace del menú es la página actual?
$activo = function (array $item) use ($rutaActual): bool {
    $u = strtok((string) $item['url'], '#');
    return $u !== '' && $u !== false && !$item['externo'] && ($u === $rutaActual || ($u === '/' && $rutaActual === '/inicio'));
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?></title>
<meta name="description" content="<?= e($descripcion) ?>">
<meta property="og:title" content="<?= e($titulo) ?>">
<meta property="og:description" content="<?= e($descripcion) ?>">
<meta name="theme-color" content="<?= e($config['colores']['fondo']) ?>">
<link rel="icon" href="<?= e($favicon) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<!-- Framework CSS: Bootstrap 5.3 (grilla, navbar, collapse, dropdown, acordeón y formularios) -->
<link rel="stylesheet" href="<?= e($BASE) ?>/public/assets/lib/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= e($BASE) ?>/public/assets/css/sitio.css">
<!-- ✦ Colores del panel (tabla site_config) convertidos en variables CSS -->
<style><?= $config['css_variables'] /* solo contiene valores HEX ya validados por la API */ ?></style>
</head>
<body data-api="<?= e($BASE) ?>/api">
<div class="cielo" aria-hidden="true"><div class="estrellas"></div><div class="nebula n1"></div><div class="nebula n2"></div></div>

<header class="cabecera navbar navbar-expand-lg" id="cabecera">
    <a class="marca navbar-brand" href="<?= e($BASE) ?>/" aria-label="<?= e($nombre) ?> - inicio">
        <img src="<?= e($logo) ?>" <?= $logoSrcset ? 'srcset="' . e($logoSrcset) . '" sizes="44px"' : '' ?> alt="" width="44" height="44">
        <span><?= e($nombre) ?></span>
    </a>
    <button class="btn-menu navbar-toggler" id="btnMenu" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Abrir menú"><span></span><span></span><span></span></button>
    <nav class="nav-principal collapse navbar-collapse" id="nav" aria-label="Principal">
        <ul class="navbar-nav">
        <?php foreach ($menu as $item): ?>
            <li class="nav-item<?= $item['hijos'] ? ' dropdown' : '' ?>">
                <?php if ($item['hijos']): ?>
                <a href="<?= e(enlace($item['url'])) ?>" class="nav-link dropdown-toggle <?= $activo($item) ? 'activo' : '' ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= e($item['etiqueta']) ?></a>
                <ul class="dropdown-menu sub">
                    <?php foreach ($item['hijos'] as $h): ?>
                    <li><a class="dropdown-item" href="<?= e(enlace($h['url'])) ?>" <?= $h['externo'] ? 'target="_blank" rel="noopener"' : '' ?>><?= e($h['etiqueta']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <a href="<?= e(enlace($item['url'])) ?>" class="nav-link <?= $activo($item) ? 'activo' : '' ?>" <?= $item['externo'] ? 'target="_blank" rel="noopener"' : '' ?>><?= e($item['etiqueta']) ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>
        <div class="nav-acciones">
            <a class="acceso" href="<?= e($BASE) ?>/admin/" title="Iniciar sesión en el panel"><?= icono('usuario') ?><span>Ingresar</span></a>
            <?php if ($wa): ?><a class="btn" href="<?= e($wa) ?>" target="_blank" rel="noopener">Cotiza gratis <?= icono('flecha') ?></a><?php endif; ?>
        </div>
    </nav>
</header>

<main id="contenido">
<?php if (!$pagina): ?>
    <section class="seccion error404">
        <div class="container contenedor">
            <span class="sticker-404">404</span>
            <h1>Esta página se perdió en el espacio</h1>
            <p>La dirección <code><?= e($rutaActual) ?></code> no existe o todavía no está publicada.</p>
            <a class="btn" href="<?= e($BASE) ?>/">Volver al inicio <?= icono('flecha') ?></a>
        </div>
    </section>
<?php else:
    $vistos = [];
    foreach ($pagina['bloques'] as $i => $bloque):
        $tipo = $bloque['tipo'];
        $c = is_array($bloque['contenido']) ? $bloque['contenido'] : [];
        $archivo = __DIR__ . '/bloques/' . $tipo . '.php';
        if (!is_file($archivo)) {
            continue;   // tipo desconocido: se ignora sin romper la página
        }
        // el primer bloque de cada tipo tiene id para poder enlazarlo (/servicios#planes)
        $ancla = isset($vistos[$tipo]) ? '' : ' id="' . e($tipo) . '"';
        $vistos[$tipo] = true;
        echo '<section class="seccion bloque-' . e($tipo) . '"' . $ancla . ' data-bloque="' . (int) $bloque['id'] . '">';
        require $archivo;
        echo '</section>';
        if ($i === 0 && $tipo === 'hero'): ?>
            <div class="cinta" aria-hidden="true"><div><?php for ($r = 0; $r < 6; $r++) foreach ($cinta as $p) echo e(mb_strtoupper($p)) . ' ✦ '; ?></div></div>
        <?php endif;
    endforeach;
endif; ?>
</main>

<footer class="pie">
    <div class="container contenedor">
        <div class="row g-4 g-lg-5">
            <div class="col-12 col-lg-5">
                <a class="marca" href="<?= e($BASE) ?>/"><img src="<?= e($logo) ?>" alt="" width="40" height="40"><span><?= e($nombre) ?></span></a>
                <p><?= e($config['eslogan']) ?></p>
                <div class="redes">
                    <?php foreach ($config['redes_sociales'] as $red => $url): if (!$url) continue; ?>
                    <a href="<?= e($url) ?>" target="_blank" rel="noopener" aria-label="<?= e($red) ?>"><?= icono($red) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-12 col-sm-5 col-lg-3">
                <h3>Navegación</h3>
                <ul><?php foreach ($menu as $item): ?><li><a href="<?= e(enlace($item['url'])) ?>"><?= e($item['etiqueta']) ?></a></li><?php endforeach; ?></ul>
            </div>
            <div class="col-12 col-sm-7 col-lg-4">
                <h3>Contacto</h3>
                <ul class="datos">
                    <?php if ($config['whatsapp']): ?><li><?= icono('whatsapp') ?><a href="<?= e($wa) ?>" target="_blank" rel="noopener">+<?= e($config['whatsapp']) ?></a></li><?php endif; ?>
                    <?php if ($config['email_contacto']): ?><li><?= icono('mail') ?><a href="mailto:<?= e($config['email_contacto']) ?>"><?= e($config['email_contacto']) ?></a></li><?php endif; ?>
                    <?php if ($config['direccion']): ?><li><?= icono('pin') ?><?= e($config['direccion']) ?></li><?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="pie-final d-flex flex-wrap justify-content-between gap-3"><span>© <?= date('Y') ?> <?= e($nombre) ?></span><span class="mono"><a href="<?= e($BASE) ?>/admin/">Acceso al panel</a> · Hecho con Pixela CMS ✦</span></div>
    </div>
</footer>

<?php if ($wa): ?>
<a class="wa-flotante" href="<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-3.3-.8-2.8-1.1-4.5-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .6l-.4.6-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l2 .9c.3.1.5.2.5.3.1.2.1.7-.1 1.2Z"/></svg></a>
<?php endif; ?>

<!-- Framework JS: Bootstrap 5.3 (navbar, dropdown, collapse y acordeón) -->
<script src="<?= e($BASE) ?>/public/assets/lib/bootstrap/js/bootstrap.bundle.min.js" defer></script>
<script src="<?= e($BASE) ?>/public/assets/js/sitio.js" defer></script>
</body>
</html>
