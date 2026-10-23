<?php
// Funciones de ayuda para dibujar la web pública

// Escapa texto para HTML (evita XSS con lo que se escribe en el panel)
function e($texto): string
{
    return htmlspecialchars((string) ($texto ?? ''), ENT_QUOTES, 'UTF-8');
}

// Enlaces del CMS: "/contacto" -> /pixela-cms/contacto · "#planes" y "https://..." se dejan igual
function enlace(?string $url): string
{
    global $BASE;
    $url = trim((string) $url);
    if ($url === '') {
        return '#';
    }
    if (preg_match('#^https?://#i', $url) || str_starts_with($url, '#') || str_starts_with($url, 'mailto:')) {
        return $url;
    }
    return $BASE . '/' . ltrim($url, '/');
}

function esExterno(?string $url): bool
{
    return (bool) preg_match('#^https?://#i', (string) $url);
}

// Busca todos los "imagen_id" dentro del JSON de los bloques
function idsDeImagenes(array $bloques): array
{
    $ids = [];
    $recorrer = function ($valor) use (&$recorrer, &$ids) {
        if (!is_array($valor)) {
            return;
        }
        foreach ($valor as $clave => $v) {
            if ($clave === 'imagen_id' && $v) {
                $ids[] = (int) $v;
            }
            $recorrer($v);
        }
    };
    foreach ($bloques as $b) {
        $recorrer($b['contenido']);
    }
    return $ids;
}

// <img> responsive con las versiones 480/768/1280 generadas al subir
function img($id, string $sizes = '100vw', string $clase = '', string $alt = ''): string
{
    global $IMAGENES;
    $m = $id ? ($IMAGENES[(int) $id] ?? null) : null;
    if (!$m) {
        return '';
    }
    return '<img class="' . e($clase) . '" src="' . e($m['url']) . '" srcset="' . e($m['srcset']) . '" sizes="' . e($sizes) . '" alt="' . e($alt ?: ($m['alt'] ?? '')) . '" loading="lazy" decoding="async">';
}

// Link de WhatsApp con mensaje listo
function whatsapp(string $numero, string $texto = ''): string
{
    $numero = preg_replace('/\D/', '', $numero);
    return 'https://wa.me/' . $numero . ($texto !== '' ? '?text=' . rawurlencode($texto) : '');
}

// Colores pastel tipo sticker: se calculan a partir de los colores del panel (cambian si cambias la paleta)
function pastel(int $i): string
{
    return ['var(--pastel-1)', 'var(--pastel-2)', 'var(--pastel-3)', 'var(--pastel-4)'][$i % 4];
}

// Texto con saltos de línea -> párrafos
function parrafos(?string $texto): string
{
    $lineas = array_filter(array_map('trim', explode("\n", (string) $texto)));
    return implode('', array_map(fn($l) => '<p>' . e($l) . '</p>', $lineas));
}

// Íconos (Lucide, licencia ISC) para servicios y redes
function icono(string $nombre): string
{
    $i = [
        'code'     => '<path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/>',
        'palette'  => '<circle cx="13.5" cy="6.5" r="1.5"/><circle cx="17.5" cy="10.5" r="1.5"/><circle cx="8.5" cy="7.5" r="1.5"/><circle cx="6.5" cy="12.5" r="1.5"/><path d="M12 2a10 10 0 0 0 0 20c1 0 1.5-.8 1.5-1.5 0-.4-.2-.8-.4-1.1-.3-.3-.4-.7-.4-1.1 0-.8.7-1.5 1.5-1.5H16a6 6 0 0 0 6-6c0-4.4-4.5-8.8-10-8.8Z"/>',
        'share'    => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>',
        'server'   => '<rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><path d="M6 6h.01M6 18h.01"/>',
        'wrench'   => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9Z"/>',
        'camera'   => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z"/><circle cx="12" cy="13" r="3"/>',
        'chart'    => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>',
        'rocket'   => '<path d="M4.5 16.5c-1.5 1.3-2 5-2 5s3.7-.5 5-2c.7-.8.7-2.1-.1-2.9a2.2 2.2 0 0 0-2.9-.1Z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.9A12.9 12.9 0 0 1 22 2c0 2.7-.8 7.5-6 11a22.4 22.4 0 0 1-4 2Z"/>',
        'heart'    => '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7Z"/>',
        'check'    => '<path d="M20 6 9 17l-5-5"/>',
        'usuario'  => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'ojo'      => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'flecha'   => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'mail'     => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'pin'      => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'whatsapp' => '<path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/><path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/>',
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3Z"/>',
        'instagram'=> '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
        'tiktok'   => '<path d="M16 3a5 5 0 0 0 5 5v3a8 8 0 0 1-5-1.7V15a6 6 0 1 1-6-6v3a3 3 0 1 0 3 3V3Z"/>',
        'linkedin' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6Z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
        'youtube'  => '<path d="M2.5 17a24 24 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.6 49.6 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24 24 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.6 49.6 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($i[$nombre] ?? $i['code']) . '</svg>';
}

// Encabezado común de cada sección: etiqueta tipo sticker + título
function encabezado(array $c, string $clase = ''): string
{
    $h = '';
    if (!empty($c['etiqueta'])) {
        $h .= '<span class="etiqueta">✦ ' . e($c['etiqueta']) . '</span>';
    }
    if (!empty($c['titulo'])) {
        $h .= '<h2>' . e($c['titulo']) . '</h2>';
    }
    return $h ? '<header class="sec-cabeza ' . e($clase) . '">' . $h . '</header>' : '';
}
