<?php
// Bloque SERVICIOS: tarjetas tipo sticker con ícono
$items = $c['items'] ?? [];
?>
<div class="contenedor">
    <?= encabezado($c) ?>
    <div class="rejilla rejilla-3">
        <?php foreach ($items as $n => $s): ?>
        <article class="sticker tarjeta revelar" style="background:<?= pastel($n) ?>;--retraso:<?= $n * 70 ?>ms">
            <span class="icono-caja"><?= icono((string) ($s['icono'] ?? 'code')) ?></span>
            <h3><?= e($s['titulo'] ?? '') ?></h3>
            <p><?= e($s['descripcion'] ?? '') ?></p>
        </article>
        <?php endforeach; ?>
    </div>
</div>
