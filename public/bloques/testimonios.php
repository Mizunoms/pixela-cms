<?php
// Bloque TESTIMONIOS: opiniones en tarjetas ligeramente giradas (efecto sticker pegado)
$items = $c['items'] ?? [];
?>
<div class="container contenedor">
    <?= encabezado($c) ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($items as $n => $t): ?>
        <div class="col"><figure class="testimonio sticker h-100 revelar" style="background:<?= pastel($n + 2) ?>;--giro:<?= [-1.2, .8, -.6][$n % 3] ?>deg;--retraso:<?= $n * 80 ?>ms">
            <span class="comillas">“</span>
            <blockquote><?= e($t['texto'] ?? '') ?></blockquote>
            <figcaption><span class="avatar"><?= e(mb_strtoupper(mb_substr($t['nombre'] ?? '?', 0, 1))) ?></span><span><b><?= e($t['nombre'] ?? '') ?></b><small><?= e($t['negocio'] ?? '') ?></small></span></figcaption>
        </figure></div>
        <?php endforeach; ?>
    </div>
</div>
