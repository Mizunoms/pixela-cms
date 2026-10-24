<?php
// Login del panel (pantalla dividida). Si ya hay sesión válida, entra directo al panel.
require __DIR__ . '/_bootstrap.php';

if (usuarioActual()) {
    header('Location: ./');
    exit;
}
$aviso = isset($_GET['expirada']) ? 'Tu sesión expiró. Vuelve a ingresar.' : (isset($_GET['salio']) ? 'Cerraste sesión correctamente.' : '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión · Pixela CMS</title>
<link rel="icon" type="image/png" href="<?= e($BASE) ?>/public/assets/img/pixela-logo-128.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<!-- Framework: Bootstrap 5.3 -->
<link rel="stylesheet" href="<?= e($BASE) ?>/public/assets/lib/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<main class="login container-fluid p-0">
  <div class="row g-0 min-vh-100">
    <section class="col-12 col-lg-6 login-izq">
        <div class="estrellas" style="position:absolute;opacity:.55"></div>
        <div class="marca"><img src="<?= e($BASE) ?>/public/assets/img/pixela-logo-128.png" alt="Pixela">PIXELA <span class="acento">CMS</span></div>
        <div class="sello">✦ PANEL<br>ADMIN<br>v1.0 ✦</div>
        <h1>Tu web,<br>bajo tu <span class="resalte">control</span></h1>
        <div class="pasos">
            <div><b>01</b>Inicias sesión con tu cuenta</div>
            <div><b>02</b>La API te entrega un token JWT seguro</div>
            <div><b>03</b>Cambias colores, menú y bloques sin tocar código</div>
        </div>
        <div class="login-pie">Pixela CMS · PHP + MySQL · Proyecto final</div>
    </section>

    <section class="col-12 col-lg-6 login-der">
        <form class="form-login" id="formLogin" novalidate>
            <span class="pill lima">● ACCESO SEGURO</span>
            <h2>Iniciar sesión</h2>
            <p>Ingresa con tu cuenta de administrador o editor</p>

            <div id="alerta" class="alerta <?= $aviso ? 'info' : 'oculto' ?>"><?= e($aviso) ?></div>

            <div class="fila-campo">
                <label class="lbl" for="email">Email</label>
                <input class="form-control campo" id="email" name="email" type="email" autocomplete="username" placeholder="admin@pixela.pe" required>
            </div>
            <div class="fila-campo">
                <label class="lbl" for="password">Contraseña</label>
                <div class="con-boton">
                    <input class="form-control campo" id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" required>
                    <button type="button" class="ver-clave" id="verClave">VER</button>
                </div>
            </div>
            <button class="btn osc" type="submit" id="btnEntrar">ENTRAR AL PANEL →</button>
            <div class="nota">La sesión dura <?= (int) env('JWT_EXPIRA_HORAS', 8) ?> horas · token en cookie httpOnly</div>
        </form>
    </section>
  </div>
</main>

<script>
const API = <?= json_encode($BASE . '/api') ?>;
const form = document.getElementById('formLogin');
const alerta = document.getElementById('alerta');
const btn = document.getElementById('btnEntrar');

function mostrar(tipo, texto) {
    alerta.className = 'alerta ' + tipo;
    alerta.textContent = texto;
}

document.getElementById('verClave').onclick = (e) => {
    const p = document.getElementById('password');
    p.type = p.type === 'password' ? 'text' : 'password';
    e.target.textContent = p.type === 'password' ? 'VER' : 'OCULTAR';
};

form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const email = form.email.value.trim();
    const password = form.password.value;
    if (!email || !password) return mostrar('error', 'Escribe tu email y tu contraseña.');

    btn.disabled = true;
    btn.textContent = 'VERIFICANDO…';
    try {
        const r = await fetch(API + '/auth/login', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        const datos = await r.json();
        if (!r.ok) throw new Error(datos.error || 'No se pudo iniciar sesión');
        mostrar('ok', '✔ Bienvenido, ' + datos.data.nombre + '. Entrando…');
        setTimeout(() => location.href = './', 500);
    } catch (e) {
        mostrar('error', e.message === 'Failed to fetch' ? 'No hay conexión con la API. ¿Apache está encendido?' : e.message);
        btn.disabled = false;
        btn.textContent = 'ENTRAR AL PANEL →';
    }
});
</script>
</body>
</html>
