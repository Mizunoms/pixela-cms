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
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalar Pixela CMS</title>
<style>
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px 16px;
           font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #0f0f1a; color: #f5f5fa; }
    .card { width: 100%; max-width: 520px; background: #181826; border: 1px solid #2c2c40; border-radius: 16px; padding: 32px; }
    h1 { margin: 0 0 4px; font-size: 24px; }
    h1 span { background: linear-gradient(90deg, #7c3aed, #2563eb); -webkit-background-clip: text; background-clip: text; color: transparent; }
    p.sub { margin: 0 0 24px; color: #a1a1b5; }
    h2 { font-size: 14px; text-transform: uppercase; letter-spacing: .05em; color: #a1a1b5; margin: 24px 0 10px; }
    ul.checks { list-style: none; padding: 0; margin: 0; }
    ul.checks li { display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid #2c2c40; font-size: 15px; }
    .si { color: #34d399; } .no { color: #f87171; font-weight: 600; }
    label { display: block; font-size: 13px; color: #a1a1b5; margin-bottom: 4px; }
    input[type=text], input[type=password] { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #2c2c40;
           background: #0f0f1a; color: #f5f5fa; font-size: 15px; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    details { margin-top: 8px; font-size: 14px; color: #a1a1b5; }
    details[open] .grid { margin-top: 12px; }
    button { width: 100%; margin-top: 24px; padding: 14px; border: 0; border-radius: 10px; font-size: 16px; font-weight: 600;
             color: #fff; background: linear-gradient(90deg, #7c3aed, #2563eb); cursor: pointer; }
    button:disabled { opacity: .4; cursor: not-allowed; }
    .alerta { padding: 14px 16px; border-radius: 10px; margin-top: 20px; font-size: 15px; line-height: 1.5; }
    .alerta.error { background: #3b1219; border: 1px solid #7f1d1d; color: #fecaca; }
    .alerta.ok    { background: #0b2e22; border: 1px solid #065f46; color: #a7f3d0; }
    .alerta.info  { background: #172554; border: 1px solid #1e3a8a; color: #bfdbfe; }
    .cred { background: #0f0f1a; border-radius: 8px; padding: 12px 14px; margin-top: 12px; font-family: ui-monospace, Consolas, monospace; font-size: 14px; }
    a { color: #93c5fd; }
    .confirmar { display: flex; gap: 8px; align-items: flex-start; margin-top: 12px; font-size: 14px; color: #fecaca; }
</style>
</head>
<body>
<main class="card">
    <h1>Instalar <span>Pixela CMS</span></h1>
    <p class="sub">Crea la base de datos y la configuración en un solo paso.</p>

<?php if ($estado === 'ok'): ?>
    <div class="alerta ok">
        <strong>¡Instalación completada!</strong><br>
        Se creó la base de datos <code>pixela_cms</code> y el archivo <code>.env</code>.
        <div class="cred">
            Usuario: admin@pixela.pe<br>
            Contraseña: Pixela2026!
        </div>
    </div>
    <h2>Siguiente paso</h2>
    <p>Comprueba que la API responde: <a href="<?= $e($urlApi) ?>" target="_blank"><?= $e($urlApi) ?></a><br>
       Debe mostrar <code>"bd":"conectada"</code>.</p>

<?php else: ?>
    <h2>1. Requisitos del servidor</h2>
    <ul class="checks">
        <?php foreach ($checks as $nombre => $ok): ?>
            <li><?= $e($nombre) ?> <span class="<?= $ok ? 'si' : 'no' ?>"><?= $ok ? '✔' : '✘ Falta' ?></span></li>
        <?php endforeach; ?>
    </ul>
    <?php if (!$serverOk): ?>
        <div class="alerta error">Corrige lo que está marcado con ✘ y recarga la página.</div>
    <?php endif; ?>

    <form method="post">
        <h2>2. Base de datos</h2>
        <p style="margin:0;font-size:15px;color:#a1a1b5">Con XAMPP no hay que cambiar nada: solo asegúrate de que <strong>Apache</strong> y <strong>MySQL</strong> estén encendidos.</p>
        <details>
            <summary>Cambiar datos de conexión (opcional)</summary>
            <div class="grid">
                <div><label>Servidor</label><input type="text" name="host" value="<?= $e($db['host']) ?>"></div>
                <div><label>Puerto</label><input type="text" name="port" value="<?= $e($db['port']) ?>"></div>
                <div><label>Usuario</label><input type="text" name="user" value="<?= $e($db['user']) ?>"></div>
                <div><label>Contraseña</label><input type="password" name="pass" value="<?= $e($db['pass']) ?>"></div>
            </div>
        </details>

        <?php if ($instalado): ?>
            <div class="alerta info">El sistema ya está instalado. <a href="<?= $e($urlApi) ?>" target="_blank">Ver la API</a></div>
            <label class="confirmar"><input type="checkbox" name="confirmar" value="si"> Quiero reinstalar y borrar los datos actuales</label>
        <?php endif; ?>

        <?php if ($estado === 'error'): ?>
            <div class="alerta error"><?= $e($mensaje) ?></div>
        <?php endif; ?>

        <button type="submit" <?= $serverOk ? '' : 'disabled' ?>><?= $instalado ? 'Reinstalar' : 'Instalar ahora' ?></button>
    </form>
<?php endif; ?>
</main>
</body>
</html>
