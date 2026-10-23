<?php
// Bloque PLANTILLAS: catálogo con precio y botón directo a WhatsApp
$items = $c['items'] ?? [];
?>
<div class="contenedor">
    <?= encabezado($c) ?>
    <div class="rejilla rejilla-4">
        <?php foreach ($items as $n => $p):
            $foto = img($p['imagen_id'] ?? null, '(max-width: 700px) 90vw, 300px', '', $p['nombre'] ?? '');
            $pedido = $config['whatsapp'] ? whatsapp($config['whatsapp'], 'Hola, me interesa la plantilla "' . ($p['nombre'] ?? '') . '"') : enlace('/contacto'); ?>
        <article class="plantilla sticker revelar" style="--retraso:<?= $n * 70 ?>ms">
            <div class="navegador"><i></i><i></i><i></i></div>
            <div class="plantilla-img" style="background:<?= pastel($n) ?>">
                <?= $foto ?: '<span class="iniciales">' . e(mb_strtoupper(mb_substr($p['rubro'] ?? $p['nombre'] ?? '?', 0, 1))) . '</span>' ?>
            </div>
            <div class="plantilla-info">
                <?php if (!empty($p['rubro'])): ?><span class="pill"><?= e($p['rubro']) ?></span><?php endif; ?>
                <h3><?= e($p['nombre'] ?? '') ?></h3>
                <div class="precio-fila">
                    <b class="precio">S/ <?= e(number_format((float) ($p['precio'] ?? 0), 0)) ?></b>
                    <a class="btn chico" href="<?= e($pedido) ?>" target="_blank" rel="noopener">La quiero</a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</div>
