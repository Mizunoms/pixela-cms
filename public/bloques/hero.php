<?php
// Bloque HERO: portada con título, palabra resaltada, botones e imagen (o el logo flotando)
$imagenHero = img($c['imagen_id'] ?? null, '(max-width: 900px) 90vw, 480px', 'hero-img');
?>
<div class="contenedor hero">
    <div class="hero-texto revelar">
        <?php if (!empty($c['etiqueta'])): ?><span class="etiqueta">✦ <?= e($c['etiqueta']) ?></span><?php endif; ?>
        <h1><?= e($c['titulo'] ?? '') ?><?php if (!empty($c['resaltado'])): ?> <span class="resalte"><?= e($c['resaltado']) ?></span><?php endif; ?></h1>
        <?php if (!empty($c['subtitulo'])): ?><p class="hero-sub"><?= e($c['subtitulo']) ?></p><?php endif; ?>
        <div class="botones">
            <?php if (!empty($c['boton_texto'])): ?><a class="btn" href="<?= e(enlace($c['boton_url'] ?? '')) ?>" <?= esExterno($c['boton_url'] ?? '') ? 'target="_blank" rel="noopener"' : '' ?>><?= e($c['boton_texto']) ?> <?= icono('flecha') ?></a><?php endif; ?>
            <?php if (!empty($c['boton2_texto'])): ?><a class="btn claro" href="<?= e(enlace($c['boton2_url'] ?? '')) ?>"><?= e($c['boton2_texto']) ?></a><?php endif; ?>
        </div>
    </div>
    <div class="hero-visual revelar" aria-hidden="<?= $imagenHero ? 'false' : 'true' ?>">
        <?php if ($imagenHero): ?>
            <div class="marco-img"><?= $imagenHero ?></div>
        <?php else: ?>
            <div class="orbe">
                <div class="anillo"></div>
                <img src="<?= e($logo) ?>" alt="">
                <span class="chip" style="background:var(--pastel-2);left:-4%;top:14%"><?= icono('code') ?></span>
                <span class="chip" style="background:var(--pastel-4);right:-2%;top:44%;animation-delay:-1.5s"><?= icono('palette') ?></span>
                <span class="chip" style="background:var(--pastel-3);left:8%;bottom:6%;animation-delay:-3s"><?= icono('rocket') ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>
