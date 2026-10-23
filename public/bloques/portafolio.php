<?php
// Bloque PORTAFOLIO: trabajos con imagen (o portada generada si aún no tiene foto)
$items = $c['items'] ?? [];
?>
<div class="contenedor">
    <?= encabezado($c) ?>
    <div class="rejilla rejilla-3">
        <?php foreach ($items as $n => $p):
            $foto = img($p['imagen_id'] ?? null, '(max-width: 700px) 90vw, 380px', '', $p['titulo'] ?? '');
            $tag = !empty($p['url']) ? 'a' : 'article'; ?>
        <<?= $tag ?> class="proyecto revelar" style="--retraso:<?= $n * 80 ?>ms" <?= $tag === 'a' ? 'href="' . e(enlace($p['url'])) . '" target="_blank" rel="noopener"' : '' ?>>
            <div class="proyecto-img" style="background:<?= pastel($n + 1) ?>">
                <?= $foto ?: '<span class="iniciales">' . e(mb_strtoupper(mb_substr($p['titulo'] ?? '?', 0, 2))) . '</span>' ?>
            </div>
            <div class="proyecto-info">
                <?php if (!empty($p['categoria'])): ?><span class="pill"><?= e($p['categoria']) ?></span><?php endif; ?>
                <h3><?= e($p['titulo'] ?? '') ?></h3>
            </div>
        </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
</div>
