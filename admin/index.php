<?php
// Panel de control. La sesión se valida aquí, en el servidor: sin JWT válido no se muestra nada.
require __DIR__ . '/_bootstrap.php';

$usuario = usuarioActual();
if (!$usuario) {
    header('Location: login.php');
    exit;
}
$esAdmin = $usuario['rol'] === 'admin';
$duracion = (int) env('JWT_EXPIRA_HORAS', 8) * 3600;

// Íconos (Lucide) usados en el menú lateral
$ico = [
    'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
    'paginas'   => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v5h5"/>',
    'menu'      => '<path d="M4 6h16M4 12h16M4 18h10"/>',
    'media'     => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/>',
    'mensajes'  => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
    'config'    => '<circle cx="13.5" cy="6.5" r="1.5"/><circle cx="17.5" cy="10.5" r="1.5"/><circle cx="8.5" cy="7.5" r="1.5"/><circle cx="6.5" cy="12.5" r="1.5"/><path d="M12 2a10 10 0 0 0 0 20c1 0 1.5-.8 1.5-1.5 0-.4-.2-.8-.4-1.1-.3-.3-.4-.7-.4-1.1 0-.8.7-1.5 1.5-1.5H16a6 6 0 0 0 6-6c0-4.4-4.5-8.8-10-8.8Z"/>',
    'sitio'     => '<path d="M7 17 17 7M7 7h10v10"/>',
];
$svg = fn(string $k) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $ico[$k] . '</svg>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel · Pixela CMS</title>
<link rel="icon" type="image/png" href="<?= e($BASE) ?>/public/assets/img/pixela-logo-128.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<!-- Framework: Bootstrap 5.3 -->
<link rel="stylesheet" href="<?= e($BASE) ?>/public/assets/lib/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body data-api="<?= e($BASE) ?>/api"
      data-sitio="<?= e($BASE) ?>/"
      data-logo="<?= e($BASE) ?>/public/assets/img/pixela-logo-128.png"
      data-usuario='<?= e(json_encode(['nombre' => $usuario['nombre'], 'rol' => $usuario['rol']], JSON_UNESCAPED_UNICODE)) ?>'
      data-exp="<?= $usuario['exp'] ?>" data-duracion="<?= $duracion ?>">
<div class="estrellas"></div>
<div class="nebula" style="width:560px;height:560px;background:var(--violeta);top:-240px;right:-160px"></div>

<div class="app">
    <!-- Menú lateral: offcanvas de Bootstrap (fijo en escritorio, deslizable en celular) -->
    <aside class="side offcanvas-lg offcanvas-start" id="side" tabindex="-1" aria-label="Menú del panel">
        <a class="marca" href="#dashboard"><img src="<?= e($BASE) ?>/public/assets/img/pixela-logo-128.png" alt="Pixela">PIXELA <span class="acento">CMS</span></a>
        <nav class="nav flex-column" id="nav">
            <a class="nav-link" href="#dashboard" data-vista="dashboard"><?= $svg('dashboard') ?>Dashboard</a>
            <div class="grupo">CONTENIDO</div>
            <a class="nav-link" href="#paginas" data-vista="paginas"><?= $svg('paginas') ?>Páginas y bloques</a>
            <a class="nav-link" href="#menu" data-vista="menu"><?= $svg('menu') ?>Menú</a>
            <a class="nav-link" href="#media" data-vista="media"><?= $svg('media') ?>Imágenes</a>
            <a class="nav-link" href="#mensajes" data-vista="mensajes"><?= $svg('mensajes') ?>Mensajes <span class="contador oculto" id="contMensajes">0</span></a>
            <div class="grupo">SITIO</div>
            <?php if ($esAdmin): ?>
            <a class="nav-link" href="#config" data-vista="config"><?= $svg('config') ?>Colores y logo</a>
            <?php endif; ?>
            <a class="nav-link" href="<?= e($BASE) ?>/" target="_blank" rel="noopener"><?= $svg('sitio') ?>Ver sitio web</a>
        </nav>
        <div class="yo">
            <div class="yo-fila">
                <div class="av"><?= e(mb_strtoupper(mb_substr($usuario['nombre'], 0, 1))) ?></div>
                <div style="min-width:0"><div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($usuario['nombre']) ?></div><small>● <?= e($usuario['rol']) ?></small></div>
            </div>
            <div class="sesion">Sesión: <b id="tiempoSesion">--:--:--</b><div class="barra-sesion"><span id="barraSesion" style="width:100%"></span></div></div>
            <button class="btn blanco chico" id="btnSalir" type="button">⏻ Cerrar sesión</button>
        </div>
    </aside>

    <div class="main">
        <div class="topbar">
            <a class="marca" href="#dashboard"><img src="<?= e($BASE) ?>/public/assets/img/pixela-logo-128.png" alt="">PIXELA <span class="acento">CMS</span></a>
            <button class="icono-btn" id="btnMenu" type="button" aria-label="Abrir menú" data-bs-toggle="offcanvas" data-bs-target="#side" aria-controls="side"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        </div>
        <div class="cinta"><div id="cinta"></div></div>
        <div id="vista"><div class="cargando"><span></span></div></div>
    </div>
</div>

<!-- Drawer reutilizable (formularios): offcanvas de Bootstrap a la derecha -->
<aside class="drawer offcanvas offcanvas-end" id="drawer" tabindex="-1" aria-labelledby="drawerTitulo">
    <div class="drawer-cab">
        <div class="fila"><span class="pill lima" id="drawerEtiqueta">EDITAR</span><button class="cerrar" id="drawerCerrar" type="button" data-bs-dismiss="offcanvas" aria-label="Cerrar">✕</button></div>
        <h2 id="drawerTitulo">Editar</h2>
        <div class="sub" id="drawerSub"></div>
    </div>
    <div class="drawer-cuerpo" id="drawerCuerpo"></div>
    <div class="drawer-pie">
        <button class="btn blanco" id="drawerCancelar" type="button" data-bs-dismiss="offcanvas">Cancelar</button>
        <button class="btn osc" id="drawerGuardar" type="button">GUARDAR →</button>
    </div>
</aside>

<!-- Selector de imágenes: modal de Bootstrap -->
<div class="modal fade" id="modal" tabindex="-1" aria-label="Elegir imagen">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-caja" id="modalCaja"></div>
    </div>
</div>
<!-- Avisos: toasts de Bootstrap -->
<div class="toast-container toasts" id="toasts"></div>

<script src="<?= e($BASE) ?>/public/assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/admin.js"></script>
</body>
</html>
