<?php
// Se muestra si la base de datos no responde (por ejemplo, MySQL apagado en XAMPP)
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sitio en mantenimiento</title>
<style>
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 20px; background: #0B0B14; color: #F5F5FA; font-family: system-ui, "Segoe UI", sans-serif; }
    .caja { max-width: 520px; background: #fff; color: #0B0B12; border: 3px solid #0B0B12; border-radius: 20px; box-shadow: 8px 8px 0 #C8FF3D; padding: 32px; }
    h1 { margin: 0 0 10px; font-size: 30px; }
    p { line-height: 1.6; margin: 0 0 10px; }
    code { background: #F3F0E8; padding: 2px 6px; border-radius: 6px; font-size: 13px; }
</style>
</head>
<body>
<div class="caja">
    <h1>Volvemos en un momento</h1>
    <p>El sitio no pudo conectarse a la base de datos. Si eres el administrador, revisa que <b>MySQL</b> esté encendido en XAMPP.</p>
    <?php if (!empty($mensaje)): ?><p><code><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></code></p><?php endif; ?>
</div>
</body>
</html>
