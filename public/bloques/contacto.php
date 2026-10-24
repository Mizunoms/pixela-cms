<?php
// Bloque CONTACTO: formulario (se guarda en la BD vía POST /api/contacto), datos y mapa
?>
<div class="container contenedor">
    <?= encabezado($c) ?>
    <?php if (!empty($c['texto'])): ?><div class="sec-intro"><?= parrafos($c['texto']) ?></div><?php endif; ?>
    <div class="row g-4 align-items-start">
        <?php if (!empty($c['mostrar_formulario'])): ?>
        <div class="col-12 col-lg-7"><form class="formulario sticker revelar" id="formContacto" novalidate>
            <div class="aviso oculto" id="avisoContacto" role="status"></div>
            <div class="row g-3">
                <div class="col-12 col-sm-6"><label class="form-label" for="cNombre">Nombre</label><input class="form-control" id="cNombre" name="nombre" required maxlength="100" autocomplete="name" placeholder="Tu nombre"></div>
                <div class="col-12 col-sm-6"><label class="form-label" for="cEmail">Email</label><input class="form-control" id="cEmail" name="email" type="email" required maxlength="150" autocomplete="email" placeholder="tu@correo.com"></div>
                <div class="col-12 col-sm-6"><label class="form-label" for="cTel">Teléfono <small>(opcional)</small></label><input class="form-control" id="cTel" name="telefono" maxlength="20" autocomplete="tel" placeholder="987 654 321"></div>
                <div class="col-12 col-sm-6"><label class="form-label" for="cServicio">¿Qué necesitas?</label>
                    <select class="form-select" id="cServicio" name="servicio_interes">
                        <option value="">Elige una opción</option>
                        <option>Página web</option><option>Branding</option><option>Redes sociales</option><option>Sistema a medida</option><option>Otro</option>
                    </select>
                </div>
                <div class="col-12"><label class="form-label" for="cMensaje">Mensaje</label><textarea class="form-control" id="cMensaje" name="mensaje" required minlength="10" maxlength="2000" rows="5" placeholder="Cuéntanos sobre tu negocio y lo que buscas"></textarea></div>
            </div>
            <!-- campo trampa para bots: las personas no lo ven -->
            <label class="trampa" aria-hidden="true">Web<input name="website" tabindex="-1" autocomplete="off"></label>
            <button class="btn oscuro align-self-start" type="submit">Enviar mensaje <?= icono('flecha') ?></button>
        </form></div>
        <?php endif; ?>
        <div class="col-12 col-lg-5"><div class="contacto-datos revelar">
            <?php if ($config['whatsapp']): ?>
            <a class="dato sticker" style="background:var(--pastel-3)" href="<?= e(whatsapp($config['whatsapp'], 'Hola, quiero cotizar un proyecto')) ?>" target="_blank" rel="noopener"><?= icono('whatsapp') ?><span><small>WhatsApp</small>+<?= e($config['whatsapp']) ?></span></a>
            <?php endif; ?>
            <?php if ($config['email_contacto']): ?>
            <a class="dato sticker" style="background:var(--pastel-2)" href="mailto:<?= e($config['email_contacto']) ?>"><?= icono('mail') ?><span><small>Email</small><?= e($config['email_contacto']) ?></span></a>
            <?php endif; ?>
            <?php if ($config['direccion']): ?>
            <div class="dato sticker" style="background:var(--pastel-1)"><?= icono('pin') ?><span><small>Dirección</small><?= e($config['direccion']) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($c['mostrar_mapa']) && $config['direccion']): ?>
            <div class="mapa"><iframe title="Mapa: <?= e($config['direccion']) ?>" src="https://www.google.com/maps?q=<?= e(rawurlencode($config['direccion'])) ?>&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>
            <?php endif; ?>
        </div></div>
    </div>
</div>
