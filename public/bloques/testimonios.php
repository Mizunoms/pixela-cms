<?php
// Bloque TESTIMONIOS: opiniones en tarjetas ligeramente giradas (efecto sticker pegado)
$items = $c['items'] ?? [];
?>
<div class="contenedor">
    <?= encabezado($c) ?>
    <div class="rejilla rejilla-3">
        <?php foreach ($items as $n => $t): ?>
        <figure class="testimonio sticker revelar" style="background:<?= pastel($n + 2) ?>;--giro:<?= [-1.2, .8, -.6][$n % 3] ?>deg;--retraso:<?= $n * 80 ?>ms">
            <span class="comillas">“</span>
            <blockquote><?= e($t['texto'] ?? '') ?></blockquote>
            <figcaption><span class="avatar"><?= e(mb_strtoupper(mb_substr($t['nombre'] ?? '?', 0, 1))) ?></span><span><b><?= e($t['nombre'] ?? '') ?></b><small><?= e($t['negocio'] ?? '') ?></small></span></figcaption>
        </figure>
        <?php endforeach; ?>
    </div>
</div>
