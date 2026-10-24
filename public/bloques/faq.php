<?php
// Bloque FAQ: acordeón de Bootstrap (collapse); la primera pregunta empieza abierta
$items = $c['items'] ?? [];
$idAcordeon = 'faq' . (int) $bloque['id'];
?>
<div class="container contenedor angosto">
    <?= encabezado($c, 'centrado') ?>
    <div class="accordion faq" id="<?= $idAcordeon ?>">
        <?php foreach ($items as $n => $p): $id = $idAcordeon . '-' . $n; ?>
        <div class="accordion-item revelar" style="--retraso:<?= $n * 60 ?>ms">
            <h3 class="accordion-header">
                <button class="accordion-button<?= $n === 0 ? '' : ' collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $id ?>" aria-expanded="<?= $n === 0 ? 'true' : 'false' ?>" aria-controls="<?= $id ?>">
                    <?= e($p['pregunta'] ?? '') ?><span class="mas" aria-hidden="true">+</span>
                </button>
            </h3>
            <div id="<?= $id ?>" class="accordion-collapse collapse<?= $n === 0 ? ' show' : '' ?>" data-bs-parent="#<?= $idAcordeon ?>">
                <div class="accordion-body"><?= e($p['respuesta'] ?? '') ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
