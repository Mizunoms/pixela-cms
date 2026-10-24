<?php
// Bloque SERVICIOS: tarjetas tipo sticker con ícono
$items = $c['items'] ?? [];
?>
<div class="container contenedor">
    <?= encabezado($c) ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
        <?php foreach ($items as $n => $s): ?>
        <div class="col"><article class="sticker tarjeta h-100 revelar" style="background:<?= pastel($n) ?>;--retraso:<?= $n * 70 ?>ms">
            <span class="icono-caja"><?= icono((string) ($s['icono'] ?? 'code')) ?></span>
            <h3><?= e($s['titulo'] ?? '') ?></h3>
            <p><?= e($s['descripcion'] ?? '') ?></p>
        </article></div>
        <?php endforeach; ?>
    </div>
</div>
