<?php
// Bloque FAQ: acordeón con <details> (funciona aunque JavaScript esté apagado)
$items = $c['items'] ?? [];
?>
<div class="contenedor angosto">
    <?= encabezado($c, 'centrado') ?>
    <div class="faq">
        <?php foreach ($items as $n => $p): ?>
        <details class="revelar" style="--retraso:<?= $n * 60 ?>ms" <?= $n === 0 ? 'open' : '' ?>>
            <summary><?= e($p['pregunta'] ?? '') ?><span class="mas">+</span></summary>
            <p><?= e($p['respuesta'] ?? '') ?></p>
        </details>
        <?php endforeach; ?>
    </div>
</div>
