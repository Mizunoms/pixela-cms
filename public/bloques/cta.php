<?php
// Bloque CTA: franja de llamado a la acción con el color de acento
$destino = $c['boton_url'] ?? '';
?>
<div class="container contenedor">
    <div class="cta-caja d-flex flex-wrap justify-content-between align-items-center gap-4 revelar">
        <div>
            <h2><?= e($c['titulo'] ?? '') ?></h2>
            <?php if (!empty($c['subtitulo'])): ?><p><?= e($c['subtitulo']) ?></p><?php endif; ?>
        </div>
        <?php if (!empty($c['boton_texto'])): ?>
        <a class="btn oscuro grande" href="<?= e(enlace($destino)) ?>" <?= esExterno($destino) ? 'target="_blank" rel="noopener"' : '' ?>><?= e($c['boton_texto']) ?> <?= icono('flecha') ?></a>
        <?php endif; ?>
    </div>
</div>
