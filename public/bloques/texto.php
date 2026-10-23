<?php
// Bloque TEXTO: título y párrafos (cada línea del panel es un párrafo)
?>
<div class="contenedor angosto">
    <?= encabezado($c) ?>
    <div class="texto-largo revelar"><?= parrafos($c['texto'] ?? '') ?></div>
</div>
