<?php
// Bloque PLANES: paquetes con precio; el destacado se resalta con el color de acento
$items = $c['items'] ?? [];
?>
<div class="container contenedor">
    <?= encabezado($c, 'centrado') ?>
    <div class="row row-cols-1 row-cols-lg-3 g-4 align-items-center justify-content-center planes">
        <?php foreach ($items as $n => $p):
            $pedido = $config['whatsapp'] ? whatsapp($config['whatsapp'], 'Hola, quiero el plan ' . ($p['nombre'] ?? '')) : enlace('/contacto'); ?>
        <div class="col"><article class="plan revelar <?= !empty($p['destacado']) ? 'destacado' : '' ?>" style="--retraso:<?= $n * 90 ?>ms">
            <?php if (!empty($p['destacado'])): ?><span class="sello">★ MÁS ELEGIDO</span><?php endif; ?>
            <h3><?= e($p['nombre'] ?? '') ?></h3>
            <div class="precio-grande"><small>S/</small><?= e(number_format((float) ($p['precio'] ?? 0), 0)) ?></div>
            <ul>
                <?php foreach (($p['incluye'] ?? []) as $linea): ?>
                <li><?= icono('check') ?><?= e($linea) ?></li>
                <?php endforeach; ?>
            </ul>
            <a class="btn <?= !empty($p['destacado']) ? 'oscuro' : 'claro' ?>" href="<?= e($pedido) ?>" target="_blank" rel="noopener">Elegir plan <?= icono('flecha') ?></a>
        </article></div>
        <?php endforeach; ?>
    </div>
</div>
