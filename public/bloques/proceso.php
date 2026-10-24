<?php
// Bloque PROCESO: pasos numerados unidos por una línea
$pasos = $c['pasos'] ?? [];
?>
<div class="container contenedor">
    <?= encabezado($c) ?>
    <ol class="pasos row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
        <?php foreach ($pasos as $n => $paso): ?>
        <li class="col revelar" style="--retraso:<?= $n * 100 ?>ms"><span class="num" style="background:<?= pastel($n) ?>"><?= str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT) ?></span><p><?= e($paso) ?></p></li>
        <?php endforeach; ?>
    </ol>
</div>
