<?php
// =====================================================
// PIXELA CMS - Instalador
// Abre en el navegador: http://localhost/<carpeta>/instalar.php
// Crea el archivo .env y la base de datos con datos de ejemplo.
// =====================================================

declare(strict_types=1);

$raiz     = __DIR__;
$rutaEnv  = $raiz . '/.env';
$rutaSql  = $raiz . '/database/pixela_cms.sql';
$base     = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$urlApi   = $base . '/api/';

// ---------- Datos de conexión (los de XAMPP por defecto) ----------
$db = [
    'host' => $_POST['host'] ?? '127.0.0.1',
    'port' => $_POST['port'] ?? '3306',
    'user' => $_POST['user'] ?? 'root',
    'pass' => $_POST['pass'] ?? '',
    'name' => 'pixela_cms',
];

// ---------- Comprobaciones del servidor ----------
$checks = [
    'PHP 8.0 o superior'          => version_compare(PHP_VERSION, '8.0.0', '>='),
    'Extensión PDO MySQL'         => extension_loaded('pdo_mysql'),
    'Extensión GD (imágenes)'     => extension_loaded('gd'),
    'Librería JWT (vendor/)'      => file_exists($raiz . '/vendor/autoload.php'),
    'Script SQL encontrado'       => file_exists($rutaSql),
    'Carpeta con permiso de escritura' => is_writable($raiz),
];
// GD es recomendada (se usa al subir imágenes), no bloquea la instalación
$serverOk = !in_array(false, array_diff_key($checks, ['Extensión GD (imágenes)' => 0]), true);

function conectar(array $db, bool $conBase): PDO
{
    $dsn = "mysql:host={$db['host']};port={$db['port']};charset=utf8mb4" . ($conBase ? ";dbname={$db['name']}" : '');
    return new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function yaInstalado(array $db): bool
{
    try {
        $pdo = conectar($db, true);
        return (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

// ---------- Instalación ----------
$estado  = null;   // 'ok' | 'error'
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $serverOk) {
    $instalado = yaInstalado($db);
    $confirmo  = ($_POST['confirmar'] ?? '') === 'si';

    if ($instalado && !$confirmo) {
        $estado  = 'error';
        $mensaje = 'El sistema ya está instalado. Para reinstalar marca la casilla de confirmación (se borrarán los datos actuales).';
    } else {
        try {
            // 1. Importar la base de datos
            $pdo = conectar($db, false);
            $pdo->exec(file_get_contents($rutaSql));

            // 2. Crear el .env con una clave JWT aleatoria
            $plantilla = file_get_contents($raiz . '/.env.example');
            $reemplazos = [
                '/^DB_HOST=.*$/m'    => 'DB_HOST=' . $db['host'],
                '/^DB_PORT=.*$/m'    => 'DB_PORT=' . $db['port'],
                '/^DB_USER=.*$/m'    => 'DB_USER=' . $db['user'],
                '/^DB_PASS=.*$/m'    => 'DB_PASS=' . $db['pass'],
                '/^JWT_SECRET=.*$/m' => 'JWT_SECRET=' . bin2hex(random_bytes(32)),
            ];
            $contenido = preg_replace(array_keys($reemplazos), array_values($reemplazos), $plantilla);
            if (file_put_contents($rutaEnv, $contenido) === false) {
                throw new RuntimeException('No se pudo crear el archivo .env');
            }

            $estado = 'ok';
        } catch (PDOException $e) {
            $estado  = 'error';
            $mensaje = str_contains($e->getMessage(), '2002')
                ? 'No se pudo conectar a MySQL. ¿Está encendido MySQL en el panel de XAMPP?'
                : 'Error de base de datos: ' . $e->getMessage();
        } catch (Throwable $e) {
            $estado  = 'error';
            $mensaje = $e->getMessage();
        }
    }
}

$instalado = $estado !== 'ok' && $serverOk && yaInstalado($db);
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<?php
// ---------- Datos solo para la vista ----------
$totalChecks = count($checks);
$okChecks    = count(array_filter($checks));
$iconos = [
    'PHP 8.0 o superior'               => '<path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/>',
    'Extensión PDO MySQL'              => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>',
    'Extensión GD (imágenes)'          => '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>',
    'Librería JWT (vendor/)'           => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/>',
    'Script SQL encontrado'            => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>',
    'Carpeta con permiso de escritura' => '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>',
];
$svg = fn(string $paths, string $clase = 'ico') =>
    '<svg class="' . $clase . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalar Pixela CMS</title>
<link rel="icon" type="image/png" href="public/assets/img/pixela-logo-128.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
    :root {
        --bg-0: #090A0F; --bg-1: #0D0E15; --bg-2: #121520;
        --cian: #00F0FF; --violeta: #8A2BE2; --verde: #00FF66; --ambar: #FFB020; --rojo: #FF3B6B;
        --texto: #E8ECFF; --tenue: #8B91B5; --linea: rgba(138, 160, 255, .14);
        --sans: "Space Grotesk", system-ui, "Segoe UI", sans-serif;
        --mono: "JetBrains Mono", ui-monospace, Consolas, monospace;
    }
    * { box-sizing: border-box; }
    html, body { margin: 0; min-height: 100%; }
    body {
        min-height: 100vh; display: grid; place-items: center; padding: 40px 16px;
        font-family: var(--sans); color: var(--texto); background: var(--bg-0); overflow-x: hidden;
    }

    /* ✦ ALARAKO: fondo galáctico animado
       Capas (de atrás hacia adelante): núcleo + brazos de galaxia girando, nebulosas a la deriva,
       3 capas de estrellas a distinta velocidad (profundidad) y estrellas fugaces.
       --mx / --my (−1 a 1) las mueve el mouse; cada capa se desplaza según su --p (parallax). */
    .galaxia { --mx: 0; --my: 0; position: fixed; inset: 0; z-index: -1; overflow: hidden; pointer-events: none;
        background: radial-gradient(ellipse at 50% 120%, #1a0f3a 0%, var(--bg-0) 60%); }
    .capa { position: absolute; inset: 0; --p: 10px;
        translate: calc(var(--mx) * var(--p)) calc(var(--my) * var(--p)); transition: translate 1.2s cubic-bezier(.2,.8,.2,1); }

    /* galaxia espiral: dos discos de gradiente cónico girando en sentidos opuestos = efecto remolino */
    .espiral, .espiral::before { position: absolute; left: 50%; top: 50%; width: 150vmax; aspect-ratio: 1; translate: -50% -50%; border-radius: 50%; }
    .espiral {
        background: conic-gradient(from 0deg, transparent 0deg, rgba(138,43,226,.38) 35deg, transparent 95deg,
                    rgba(0,240,255,.20) 170deg, transparent 230deg, rgba(160,80,255,.32) 290deg, transparent 350deg);
        -webkit-mask: radial-gradient(circle, #000 0%, rgba(0,0,0,.55) 22%, transparent 58%);
                mask: radial-gradient(circle, #000 0%, rgba(0,0,0,.55) 22%, transparent 58%);
        filter: blur(28px); opacity: .75; animation: gira-galaxia 90s linear infinite; }
    .espiral::before { content: "";
        background: conic-gradient(from 120deg, transparent 0deg, rgba(0,119,255,.30) 50deg, transparent 120deg,
                    rgba(138,43,226,.30) 200deg, transparent 260deg);
        -webkit-mask: radial-gradient(circle, #000 0%, transparent 45%); mask: radial-gradient(circle, #000 0%, transparent 45%);
        animation: gira-galaxia 60s linear infinite reverse; }
    .nucleo { position: absolute; left: 50%; top: 50%; width: 46vmax; aspect-ratio: 1; translate: -50% -50%; border-radius: 50%;
        background: radial-gradient(circle, rgba(170,120,255,.28) 0%, rgba(90,60,200,.12) 35%, transparent 65%);
        animation: pulso-nucleo 8s ease-in-out infinite; }

    /* estrellas: 3 capas (lejos → cerca) que se desplazan a distinta velocidad */
    .estrellas { position: absolute; inset: -50%; background-repeat: repeat; }
    .estrellas.e1 { background-image:
            radial-gradient(1px 1px at 20px 30px, #fff, transparent), radial-gradient(1px 1px at 120px 80px, #9ff, transparent),
            radial-gradient(1px 1px at 200px 160px, #fff, transparent), radial-gradient(1px 1px at 60px 190px, #c9b8ff, transparent),
            radial-gradient(1px 1px at 260px 40px, #fff, transparent), radial-gradient(1px 1px at 170px 120px, #fff, transparent);
        background-size: 280px 210px; opacity: .5; animation: deriva 160s linear infinite; }
    .estrellas.e2 { background-image:
            radial-gradient(1.5px 1.5px at 90px 50px, #fff, transparent), radial-gradient(1.5px 1.5px at 330px 210px, #00F0FF, transparent),
            radial-gradient(1.5px 1.5px at 420px 120px, #b98cff, transparent), radial-gradient(1px 1px at 240px 280px, #fff, transparent);
        background-size: 480px 320px; opacity: .8; animation: deriva 90s linear infinite, titila 4s ease-in-out infinite; }
    .estrellas.e3 { background-image:
            radial-gradient(2.5px 2.5px at 150px 200px, #fff, transparent), radial-gradient(2px 2px at 560px 90px, #c4a6ff, transparent),
            radial-gradient(2.5px 2.5px at 380px 420px, #8ff7ff, transparent);
        background-size: 700px 520px; opacity: .9; animation: deriva 55s linear infinite, titila 6s 2s ease-in-out infinite; }

    /* nebulosas que viajan */
    .nebulosa { position: absolute; border-radius: 50%; filter: blur(90px); opacity: .45; animation: viaja 26s ease-in-out infinite alternate; }
    .nebulosa.n1 { width: 560px; height: 560px; background: var(--violeta); top: -180px; left: -160px; }
    .nebulosa.n2 { width: 460px; height: 460px; background: #0077ff; bottom: -180px; right: -140px; animation-delay: -9s; animation-duration: 32s; opacity: .35; }
    .nebulosa.n3 { width: 300px; height: 300px; background: var(--cian); top: 35%; right: 12%; opacity: .14; animation-delay: -16s; animation-duration: 22s; }
    .nebulosa.n4 { width: 340px; height: 340px; background: #c026d3; bottom: 10%; left: 8%; opacity: .16; animation-delay: -4s; animation-duration: 28s; }

    /* estrellas fugaces */
    .fugaz { position: absolute; width: 160px; height: 2px; border-radius: 2px; opacity: 0; rotate: -32deg;
        background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,.9) 70%, #fff);
        filter: drop-shadow(0 0 6px #9ff); animation: fugaz 11s linear infinite; }
    .fugaz.f1 { top: 12%; left: 78%; animation-delay: 1.5s; }
    .fugaz.f2 { top: 4%;  left: 40%; animation-delay: 5.5s; animation-duration: 13s; }
    .fugaz.f3 { top: 30%; left: 95%; animation-delay: 9s;  animation-duration: 15s; width: 110px; }
    @keyframes gira-galaxia { to { rotate: 360deg; } }
    @keyframes pulso-nucleo { 50% { scale: 1.12; opacity: .8; } }
    @keyframes viaja { 0% { transform: translate(0, 0) scale(1); } 50% { transform: translate(90px, 60px) scale(1.15); } 100% { transform: translate(-40px, 110px) scale(.95); } }
    @keyframes fugaz { 0% { opacity: 0; transform: translateX(0); } 2% { opacity: 1; } 9% { opacity: 0; transform: translateX(-620px); } 100% { opacity: 0; transform: translateX(-620px); } }

    /* ✦ ALARAKO: tarjeta de cristal con borde de gradiente que gira (conic-gradient + máscara) */
    .card { position: relative; isolation: isolate; overflow: hidden; width: 100%; max-width: 560px; padding: 36px 32px 32px; border-radius: 22px;
        background: var(--bg-1);
        box-shadow: 0 0 0 1px rgba(255,255,255,.04) inset, 0 30px 80px -20px rgba(0,0,0,.8), 0 0 60px -10px rgba(138,43,226,.45);
        animation: aparece .7s cubic-bezier(.2,.8,.2,1) both; }
    /* ✦ ALARAKO: la imagen de marca Pixela vive dentro de la tarjeta.
       Capas: imagen que deriva y gira lento + halo de nebulosa giratorio + polvo de estrellas + pixeles flotantes.
       --px / --py las mueve el mouse (parallax). */
    .card { --px: 0px; --py: 0px; }
    .card-galaxia { position: absolute; inset: 0; z-index: -1; pointer-events: none; overflow: hidden; }
    .card-galaxia > * { position: absolute; }
    .cg-imagen { inset: -8%;
        background: url("public/assets/img/pixela-fondo-tarjeta.webp") center / cover no-repeat;
        translate: var(--px) var(--py); transition: translate .6s cubic-bezier(.2,.8,.2,1);
        animation: deriva-imagen 38s ease-in-out infinite alternate; }
    .cg-halo { left: 50%; top: 52%; width: 150%; aspect-ratio: 1; translate: -50% -50%;
        background: conic-gradient(from 0deg, transparent 0deg, rgba(138,43,226,.35) 70deg, transparent 140deg, rgba(0,240,255,.22) 220deg, transparent 290deg);
        filter: blur(40px); mix-blend-mode: screen; animation: gira-halo 26s linear infinite; }
    .cg-polvo, .cg-polvo::after { inset: 0 0 -100% 0; background-repeat: repeat; }
    .cg-polvo { background-image:
            radial-gradient(1px 1px at 30px 40px, #fff, transparent),
            radial-gradient(1px 1px at 140px 110px, #b9f6ff, transparent),
            radial-gradient(1.5px 1.5px at 210px 20px, #fff, transparent),
            radial-gradient(1px 1px at 90px 170px, #d8c7ff, transparent);
        background-size: 240px 200px; opacity: .7; animation: sube 40s linear infinite, titila 3.5s ease-in-out infinite; }
    .cg-polvo::after { content: ""; position: absolute; background-image:
            radial-gradient(2px 2px at 60px 90px, #00F0FF, transparent),
            radial-gradient(1.5px 1.5px at 250px 260px, #fff, transparent),
            radial-gradient(2px 2px at 180px 330px, #b98cff, transparent);
        background-size: 320px 380px; animation: sube 24s linear infinite, titila 5s 1s ease-in-out infinite; }
    .cg-pixel { width: 7px; height: 7px; border-radius: 1px; background: linear-gradient(135deg, #b18cff, #6a7dff);
        box-shadow: 0 0 10px rgba(138,43,226,.9); opacity: 0; animation: flota-pixel 9s ease-in-out infinite; }
    .cg-pixel:nth-child(4) { left: 18%; top: 78%; animation-delay: 0s; }
    .cg-pixel:nth-child(5) { left: 74%; top: 64%; width: 5px; height: 5px; animation-delay: 2.2s; background: #00F0FF; box-shadow: 0 0 10px #00F0FF; }
    .cg-pixel:nth-child(6) { left: 40%; top: 88%; width: 9px; height: 9px; animation-delay: 4.1s; }
    .cg-pixel:nth-child(7) { left: 86%; top: 30%; width: 6px; height: 6px; animation-delay: 6s; }
    .cg-pixel:nth-child(8) { left: 10%; top: 40%; width: 5px; height: 5px; animation-delay: 7.4s; background: #00F0FF; box-shadow: 0 0 10px #00F0FF; }
    /* velo oscuro para que el texto se lea */
    .cg-velo { inset: 0; background: linear-gradient(180deg, rgba(9,10,15,.72) 0%, rgba(9,10,15,.42) 40%, rgba(9,10,15,.55) 70%, rgba(9,10,15,.35) 88%, rgba(9,10,15,.7) 100%); }
    @keyframes deriva-imagen { 0% { transform: scale(1.02) rotate(-1.2deg) translate(-1.5%, 1%); } 100% { transform: scale(1.12) rotate(1.5deg) translate(1.5%, -1.5%); } }
    @keyframes gira-halo { to { rotate: 360deg; } }
    @keyframes sube { to { transform: translateY(-50%); } }
    @keyframes flota-pixel { 0% { opacity: 0; transform: translateY(0) rotate(0); } 20% { opacity: .9; } 80% { opacity: .6; } 100% { opacity: 0; transform: translateY(-140px) rotate(90deg); } }
    .card::before { content: ""; position: absolute; inset: 0; padding: 1px; border-radius: inherit; pointer-events: none;
        background: conic-gradient(from var(--giro), transparent 0deg, var(--cian) 60deg, transparent 120deg, var(--violeta) 220deg, transparent 300deg);
        -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0); -webkit-mask-composite: xor;
        mask: linear-gradient(#000 0 0) content-box exclude, linear-gradient(#000 0 0); animation: gira 8s linear infinite; }
    @property --giro { syntax: "<angle>"; inherits: false; initial-value: 0deg; }

    /* Encabezado */
    .top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 26px; }
    .marca { display: flex; align-items: center; gap: 14px; min-width: 0; }
    /* ✦ ALARAKO: logo con glow pulsante */
    .logo { flex: none; width: 54px; height: 54px; border-radius: 50%; display: block;
        box-shadow: 0 0 22px rgba(138,43,226,.6); animation: late 3.2s ease-in-out infinite; }
    .marca h1 { margin: 0; font-size: 21px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .marca h1 span { background: linear-gradient(90deg, var(--cian), #8f7bff); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .marca p { margin: 3px 0 0; font: 12px var(--mono); color: var(--tenue); letter-spacing: .08em; }
    /* ✦ ALARAKO: badge de estado con punto que emite ondas */
    .badge { flex: none; display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px;
        font: 600 11px var(--mono); letter-spacing: .12em; text-transform: uppercase;
        color: var(--verde); background: rgba(0,255,102,.08); border: 1px solid rgba(0,255,102,.3); }
    .badge i { width: 7px; height: 7px; border-radius: 50%; background: currentColor; box-shadow: 0 0 10px currentColor; animation: onda 1.8s ease-out infinite; }
    .badge.ambar { color: var(--ambar); background: rgba(255,176,32,.08); border-color: rgba(255,176,32,.3); }
    .badge.rojo  { color: var(--rojo);  background: rgba(255,59,107,.08); border-color: rgba(255,59,107,.3); }

    /* Secciones */
    .paso { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 26px 0 12px; }
    .paso h2 { margin: 0; font: 600 12px var(--mono); letter-spacing: .18em; text-transform: uppercase; color: var(--tenue); }
    .paso h2 b { color: var(--cian); font-weight: 600; text-shadow: 0 0 12px rgba(0,240,255,.6); }
    .contador { flex: none; white-space: nowrap; font: 600 12px var(--mono); color: var(--verde); }
    /* ✦ ALARAKO: barra de progreso de requisitos con brillo que recorre */
    .barra { position: relative; height: 4px; border-radius: 4px; background: rgba(255,255,255,.06); overflow: hidden; margin-bottom: 14px; }
    .barra span { position: absolute; inset: 0 auto 0 0; border-radius: inherit; background: linear-gradient(90deg, var(--violeta), var(--cian));
        box-shadow: 0 0 12px var(--cian); animation: llena 1.1s .3s cubic-bezier(.2,.8,.2,1) both; }
    .barra span::after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, transparent, rgba(255,255,255,.8), transparent);
        width: 40%; animation: barrido 2.4s 1.4s ease-in-out infinite; }

    ul.checks { list-style: none; padding: 0; margin: 0; display: grid; gap: 8px; }
    ul.checks li { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 12px; font-size: 14.5px;
        background: rgba(13,14,21,.62); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(6px); border: 1px solid var(--linea); transition: border-color .25s, background .25s, transform .25s;
        animation: entra .5s cubic-bezier(.2,.8,.2,1) both; }
    ul.checks li:hover { border-color: rgba(0,240,255,.4); background: rgba(0,240,255,.04); transform: translateX(3px); }
    <?php for ($i = 1; $i <= $totalChecks; $i++): ?>ul.checks li:nth-child(<?= $i ?>) { animation-delay: <?= 0.15 + $i * 0.07 ?>s; }
    <?php endfor; ?>
    .ico { width: 18px; height: 18px; flex: none; color: var(--tenue); }
    ul.checks li .nombre { flex: 1; min-width: 0; }
    .estado { flex: none; display: inline-flex; align-items: center; gap: 6px; font: 600 11px var(--mono); letter-spacing: .08em; text-transform: uppercase; }
    .estado.si    { color: var(--verde); text-shadow: 0 0 10px rgba(0,255,102,.7); }
    .estado.aviso { color: var(--ambar); text-shadow: 0 0 10px rgba(255,176,32,.6); }
    .estado.no    { color: var(--rojo);  text-shadow: 0 0 10px rgba(255,59,107,.7); }
    .estado svg { width: 15px; height: 15px; }

    .nota { margin: 0; font-size: 14.5px; line-height: 1.6; color: var(--tenue); }
    .nota strong { color: var(--texto); }

    /* Datos de conexión */
    details { margin-top: 12px; border: 1px dashed var(--linea); border-radius: 12px; padding: 10px 14px; transition: border-color .25s; }
    details[open] { border-color: rgba(0,240,255,.35); }
    summary { cursor: pointer; font: 12px var(--mono); letter-spacing: .06em; color: var(--tenue); list-style: none; display: flex; align-items: center; gap: 8px; }
    summary::-webkit-details-marker { display: none; }
    summary::before { content: "›"; color: var(--cian); font-size: 16px; transition: transform .25s; }
    details[open] summary::before { transform: rotate(90deg); }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
    label { display: block; font: 11px var(--mono); letter-spacing: .1em; text-transform: uppercase; color: var(--tenue); margin-bottom: 6px; }
    /* ✦ ALARAKO: inputs que se encienden en cian al enfocar */
    input[type=text], input[type=password] { width: 100%; padding: 11px 12px; border-radius: 10px; font: 14px var(--mono); color: var(--texto);
        background: rgba(9,10,15,.7); border: 1px solid var(--linea); outline: none; transition: border-color .2s, box-shadow .2s; }
    input[type=text]:focus, input[type=password]:focus { border-color: var(--cian); box-shadow: 0 0 0 3px rgba(0,240,255,.15), 0 0 18px rgba(0,240,255,.25); }

    /* Alertas con borde lateral de neón */
    .alerta { position: relative; display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px 14px 18px; border-radius: 12px; margin-top: 18px;
        font-size: 14.5px; line-height: 1.55; background: rgba(255,255,255,.03); border: 1px solid var(--linea); overflow: hidden; }
    .alerta::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: currentColor; box-shadow: 0 0 14px currentColor; }
    .alerta svg { width: 18px; height: 18px; flex: none; margin-top: 2px; }
    .alerta .txt { color: var(--texto); }
    .alerta.error { color: var(--rojo); background: rgba(255,59,107,.06); }
    .alerta.info  { color: var(--cian); background: rgba(0,240,255,.05); }
    a { color: var(--cian); text-decoration: none; border-bottom: 1px solid rgba(0,240,255,.4); transition: text-shadow .2s, border-color .2s; }
    a:hover { text-shadow: 0 0 10px rgba(0,240,255,.8); border-color: var(--cian); }

    .confirmar { display: flex; gap: 10px; align-items: center; margin-top: 14px; font: 13px var(--mono); text-transform: none; letter-spacing: 0; color: #ff9db5; cursor: pointer; }
    .confirmar input { width: 16px; height: 16px; accent-color: var(--rojo); }

    /* ✦ ALARAKO: botón con gradiente, destello que cruza y glow al hover */
    button { position: relative; overflow: hidden; width: 100%; margin-top: 26px; padding: 15px 18px; border: 0; border-radius: 14px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 10px;
        font: 700 15px var(--sans); letter-spacing: .14em; text-transform: uppercase; color: #fff;
        background: linear-gradient(100deg, var(--violeta), #3b5bff 55%, var(--cian));
        box-shadow: 0 10px 30px -10px rgba(138,43,226,.8), 0 0 0 1px rgba(255,255,255,.12) inset;
        transition: transform .2s, box-shadow .3s, filter .3s; }
    button::after { content: ""; position: absolute; top: 0; bottom: 0; left: -60%; width: 40%; transform: skewX(-20deg);
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.45), transparent); animation: destello 3.5s ease-in-out infinite; }
    button:hover { transform: translateY(-2px); filter: brightness(1.1); box-shadow: 0 16px 40px -10px rgba(0,240,255,.7), 0 0 30px rgba(138,43,226,.6); }
    button:active { transform: translateY(0); }
    button svg { width: 18px; height: 18px; transition: transform .25s; }
    button:hover svg { transform: translateX(4px); }
    button.peligro { background: linear-gradient(100deg, #7a1238, var(--rojo)); box-shadow: 0 10px 30px -10px rgba(255,59,107,.7); }
    button:disabled { opacity: .35; cursor: not-allowed; transform: none; filter: grayscale(.6); }
    button:disabled::after { display: none; }

    /* Pantalla de éxito */
    .exito { text-align: center; padding: 8px 0 4px; }
    /* ✦ ALARAKO: check central con anillos de energía expandiéndose */
    .orbe { position: relative; width: 92px; height: 92px; margin: 6px auto 22px; display: grid; place-items: center; border-radius: 50%;
        color: var(--verde); background: radial-gradient(circle, rgba(0,255,102,.18), rgba(0,255,102,.02) 70%);
        border: 1px solid rgba(0,255,102,.45); box-shadow: 0 0 40px rgba(0,255,102,.35); }
    .orbe::before, .orbe::after { content: ""; position: absolute; inset: -1px; border-radius: 50%; border: 1px solid var(--verde); animation: anillo 2.4s ease-out infinite; }
    .orbe::after { animation-delay: 1.2s; }
    .orbe svg { width: 40px; height: 40px; filter: drop-shadow(0 0 8px var(--verde)); }
    .exito h2 { margin: 0 0 8px; font-size: 22px; letter-spacing: .06em; }
    .exito p { margin: 0 auto; max-width: 400px; color: var(--tenue); line-height: 1.6; font-size: 14.5px; }
    code { font-family: var(--mono); font-size: .9em; color: var(--cian); background: rgba(0,240,255,.08); padding: 1px 6px; border-radius: 6px; }
    .cred { margin: 22px 0 0; text-align: left; border-radius: 14px; overflow: hidden; border: 1px solid var(--linea); }
    .cred .fila { display: flex; justify-content: space-between; gap: 12px; padding: 12px 16px; font: 13px var(--mono); }
    .cred .fila + .fila { border-top: 1px solid var(--linea); }
    .cred .fila span:first-child { color: var(--tenue); text-transform: uppercase; letter-spacing: .1em; font-size: 11px; align-self: center; }
    .cred .fila span:last-child { color: var(--texto); overflow-wrap: anywhere; text-align: right; }
    .acciones { display: grid; gap: 10px; margin-top: 22px; }
    .acciones a.boton { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 14px; border-radius: 14px; border: 1px solid rgba(0,240,255,.45);
        font: 700 14px var(--sans); letter-spacing: .12em; text-transform: uppercase; color: var(--cian); background: rgba(0,240,255,.06); transition: box-shadow .25s, background .25s; }
    .acciones a.boton:hover { background: rgba(0,240,255,.12); box-shadow: 0 0 26px rgba(0,240,255,.35); text-shadow: none; }
    .acciones a.boton svg { width: 18px; height: 18px; }

    .pie { margin-top: 26px; padding-top: 16px; border-top: 1px solid var(--linea); display: flex; justify-content: space-between; gap: 10px;
        font: 11px var(--mono); letter-spacing: .1em; text-transform: uppercase; color: #8b91b5; }

    /* Animaciones */
    @keyframes deriva  { to { transform: translate3d(300px, 220px, 0); } }
    @keyframes titila  { 50% { opacity: .35; } }
    @keyframes flota   { to { transform: translate(60px, 40px) scale(1.12); } }
    @keyframes gira    { to { --giro: 360deg; } }
    @keyframes aparece { from { opacity: 0; transform: translateY(24px) scale(.98); filter: blur(6px); } }
    @keyframes entra   { from { opacity: 0; transform: translateX(-10px); } }
    @keyframes late    { 50% { box-shadow: 0 0 34px rgba(0,240,255,.55); } }
    @keyframes onda    { 0% { box-shadow: 0 0 0 0 currentColor; } 100% { box-shadow: 0 0 0 9px transparent; } }
    @keyframes llena   { from { width: 0; } }
    @keyframes barrido { from { transform: translateX(-120%); } to { transform: translateX(320%); } }
    @keyframes destello{ 0%, 60% { left: -60%; } 100% { left: 130%; } }
    @keyframes anillo  { from { transform: scale(1); opacity: .8; } to { transform: scale(1.7); opacity: 0; } }

    @media (max-width: 480px) {
        .card { padding: 28px 20px 24px; border-radius: 18px; }
        .top { flex-wrap: wrap; }
        .grid { grid-template-columns: 1fr; }
        .marca h1 { font-size: 18px; }
    }
    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after { animation: none !important; transition: none !important; }
    }
</style>
</head>
<body>
<div class="galaxia" aria-hidden="true">
    <div class="capa" style="--p: 8px"><div class="espiral"></div><div class="nucleo"></div></div>
    <div class="capa" style="--p: 14px"><div class="nebulosa n1"></div><div class="nebulosa n2"></div><div class="nebulosa n3"></div><div class="nebulosa n4"></div></div>
    <div class="capa" style="--p: 6px"><div class="estrellas e1"></div></div>
    <div class="capa" style="--p: 16px"><div class="estrellas e2"></div></div>
    <div class="capa" style="--p: 30px"><div class="estrellas e3"></div></div>
    <div class="capa" style="--p: 20px"><i class="fugaz f1"></i><i class="fugaz f2"></i><i class="fugaz f3"></i></div>
</div>

<main class="card">
    <div class="card-galaxia" aria-hidden="true">
        <div class="cg-imagen"></div>
        <div class="cg-halo"></div>
        <div class="cg-polvo"></div>
        <i class="cg-pixel"></i><i class="cg-pixel"></i><i class="cg-pixel"></i><i class="cg-pixel"></i><i class="cg-pixel"></i>
        <div class="cg-velo"></div>
    </div>
    <header class="top">
        <div class="marca">
            <img class="logo" src="public/assets/img/pixela-logo-128.png" srcset="public/assets/img/pixela-logo-256.png 2x" width="54" height="54" alt="Logo de Pixela">
            <div>
                <h1>Pixela <span>CMS</span></h1>
                <p>// asistente de instalación</p>
            </div>
        </div>
        <?php if ($estado === 'ok' || $instalado): ?>
            <span class="badge"><i></i>Online</span>
        <?php elseif (!$serverOk || $estado === 'error'): ?>
            <span class="badge rojo"><i></i>Revisar</span>
        <?php else: ?>
            <span class="badge ambar"><i></i>Listo</span>
        <?php endif; ?>
    </header>

<?php if ($estado === 'ok'): ?>
    <section class="exito">
        <div class="orbe"><?= $svg('<path d="M20 6 9 17l-5-5"/>', '') ?></div>
        <h2>Instalación completada</h2>
        <p>Se creó la base de datos <code>pixela_cms</code> y el archivo <code>.env</code> con una clave segura.</p>

        <div class="cred">
            <div class="fila"><span>Usuario</span><span>admin@pixela.pe</span></div>
            <div class="fila"><span>Contraseña</span><span>Pixela2026!</span></div>
        </div>

        <div class="acciones">
            <a class="boton" href="<?= $e($base) ?>/admin/" target="_blank">
                <?= $svg('<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>', '') ?>
                Entrar al panel admin
            </a>
            <a class="boton" href="<?= $e($base) ?>/" target="_blank">
                <?= $svg('<path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/><circle cx="12" cy="12" r="10"/>', '') ?>
                Ver la web pública
            </a>
            <a class="boton" href="<?= $e($urlApi) ?>" target="_blank">
                <?= $svg('<path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/>', '') ?>
                Probar la API
            </a>
        </div>
    </section>

<?php else: ?>
    <div class="paso">
        <h2><b>01</b> // Requisitos del servidor</h2>
        <span class="contador"><?= $okChecks ?>/<?= $totalChecks ?> OK</span>
    </div>
    <div class="barra"><span style="width: <?= round($okChecks / $totalChecks * 100) ?>%"></span></div>
    <ul class="checks">
        <?php foreach ($checks as $nombre => $ok): ?>
            <li>
                <?= $svg($iconos[$nombre] ?? '<circle cx="12" cy="12" r="10"/>') ?>
                <span class="nombre"><?= $e($nombre) ?></span>
                <?php if ($ok): ?>
                    <span class="estado si"><?= $svg('<path d="M20 6 9 17l-5-5"/>', '') ?>OK</span>
                <?php elseif ($nombre === 'Extensión GD (imágenes)'): ?>
                    <span class="estado aviso" title="Se necesita para subir imágenes desde el panel"><?= $svg('<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>', '') ?>Recomendada</span>
                <?php else: ?>
                    <span class="estado no"><?= $svg('<path d="M18 6 6 18"/><path d="m6 6 12 12"/>', '') ?>Falta</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if (!$serverOk): ?>
        <div class="alerta error"><?= $svg('<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>', '') ?><span class="txt">Corrige lo que está marcado como <strong>Falta</strong> y recarga la página.</span></div>
    <?php endif; ?>

    <form method="post">
        <div class="paso"><h2><b>02</b> // Base de datos</h2></div>
        <p class="nota">Con XAMPP no hay que cambiar nada: solo asegúrate de que <strong>Apache</strong> y <strong>MySQL</strong> estén encendidos.</p>
        <details>
            <summary>Cambiar datos de conexión (opcional)</summary>
            <div class="grid">
                <div><label for="host">Servidor</label><input id="host" type="text" name="host" value="<?= $e($db['host']) ?>"></div>
                <div><label for="port">Puerto</label><input id="port" type="text" name="port" value="<?= $e($db['port']) ?>"></div>
                <div><label for="user">Usuario</label><input id="user" type="text" name="user" value="<?= $e($db['user']) ?>"></div>
                <div><label for="pass">Contraseña</label><input id="pass" type="password" name="pass" value="<?= $e($db['pass']) ?>"></div>
            </div>
        </details>

        <?php if ($instalado): ?>
            <div class="alerta info"><?= $svg('<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>', '') ?><span class="txt">El sistema ya está instalado. <a href="<?= $e($base) ?>/admin/" target="_blank">Panel admin</a> · <a href="<?= $e($base) ?>/" target="_blank">Web</a> · <a href="<?= $e($urlApi) ?>" target="_blank">API</a></span></div>
            <label class="confirmar"><input type="checkbox" name="confirmar" value="si"> Quiero reinstalar y borrar los datos actuales</label>
        <?php endif; ?>

        <?php if ($estado === 'error'): ?>
            <div class="alerta error"><?= $svg('<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>', '') ?><span class="txt"><?= $e($mensaje) ?></span></div>
        <?php endif; ?>

        <button type="submit" class="<?= $instalado ? 'peligro' : '' ?>" <?= $serverOk ? '' : 'disabled' ?>>
            <?= $instalado ? 'Reinstalar' : 'Instalar ahora' ?>
            <?= $svg('<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>', '') ?>
        </button>
    </form>
<?php endif; ?>

    <footer class="pie"><span>Pixela CMS v1.0</span><span>PHP <?= $e(PHP_VERSION) ?></span></footer>
</main>
<script>
    // ✦ ALARAKO: parallax — la tarjeta y las capas del fondo se desplazan siguiendo el mouse
    (function () {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        var card = document.querySelector('.card');
        var galaxia = document.querySelector('.galaxia');
        document.addEventListener('pointermove', function (ev) {
            var nx = ev.clientX / window.innerWidth - 0.5;   // −0.5 a 0.5
            var ny = ev.clientY / window.innerHeight - 0.5;
            card.style.setProperty('--px', (nx * -18).toFixed(1) + 'px');
            card.style.setProperty('--py', (ny * -18).toFixed(1) + 'px');
            galaxia.style.setProperty('--mx', (nx * -2).toFixed(3));  // fondo de la página: cada capa según su --p
            galaxia.style.setProperty('--my', (ny * -2).toFixed(3));
        });
    })();
</script>
</body>
</html>
