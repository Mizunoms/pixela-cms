<?php
// Bloque GALERÍA: imágenes en mosaico
$items = array_values(array_filter($c['items'] ?? [], fn($i) => !empty($i['imagen_id'])));
?>
<div class="container contenedor">
    <?= encabezado($c) ?>
    <?php if ($items): ?>
    <div class="galeria">
        <?php foreach ($items as $n => $f): ?>
        <figure class="revelar" style="--retraso:<?= $n * 60 ?>ms"><?= img($f['imagen_id'], '(max-width: 700px) 90vw, 400px', '', $f['alt'] ?? '') ?></figure>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
