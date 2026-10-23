<?php
// Subida de imágenes: valida el archivo y genera versiones responsive (480/768/1280 px) con GD.

class MediaService
{
    private const ANCHOS      = [480, 768, 1280];
    private const MAX_BYTES   = 5 * 1024 * 1024;   // 5 MB
    private const MAX_LADO    = 6000;              // evita imágenes gigantes que agoten la memoria
    private const TIPOS       = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const CALIDAD     = 82;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    public function listar(): array
    {
        $medios = $this->db->query('SELECT * FROM media ORDER BY id DESC')->fetchAll();
        return array_map(fn($m) => $this->formatear($m), $medios);
    }

    public function obtener(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM media WHERE id = ?');
        $stmt->execute([$id]);
        $medio = $stmt->fetch();
        return $medio ? $this->formatear($medio) : null;
    }

    public function existe(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM media WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    public function subir(?array $archivo, ?string $alt, int $usuarioId): array
    {
        if (!extension_loaded('gd')) {
            throw new ApiException('La extensión GD de PHP no está activada en el servidor (necesaria para procesar imágenes)', 500);
        }
        $this->validarSubida($archivo);

        // el tipo se detecta leyendo el contenido real del archivo (no la extensión)
        $info = @getimagesize($archivo['tmp_name']);
        $mime = $info['mime'] ?? '';
        if (!$info || !in_array($mime, self::TIPOS, true)) {
            throw new ApiException('Formato no permitido. Usa JPG, PNG, WEBP o GIF', 415);
        }
        if ($info[0] < 1 || $info[1] < 1) {
            throw new ApiException('El archivo no es una imagen válida', 415);
        }
        [$ancho, $alto] = $info;
        if ($ancho > self::MAX_LADO || $alto > self::MAX_LADO) {
            throw new ApiException('La imagen es demasiado grande (máximo ' . self::MAX_LADO . ' px por lado)', 422);
        }

        $origen = @imagecreatefromstring(file_get_contents($archivo['tmp_name']));
        if (!$origen) {
            throw new ApiException('No se pudo leer la imagen', 415);
        }

        // Anchos a generar: los que sean menores al original; si es muy chica, una sola versión a su tamaño
        $anchos = array_values(array_filter(self::ANCHOS, fn($w) => $w < $ancho));
        if (count($anchos) < count(self::ANCHOS)) {
            $anchos[] = min($ancho, max(self::ANCHOS));
        }
        $anchos = array_values(array_unique($anchos));

        $usarWebp = function_exists('imagewebp');
        $extension = $usarWebp ? 'webp' : 'jpg';
        $carpetaRel = 'uploads/' . date('Y/m');
        $carpetaAbs = Url::raiz() . '/' . $carpetaRel;
        if (!is_dir($carpetaAbs) && !mkdir($carpetaAbs, 0755, true)) {
            throw new ApiException('No se pudo crear la carpeta de subidas', 500);
        }
        $nombreBase = bin2hex(random_bytes(8));

        $creados = [];
        $variantes = [];
        try {
            foreach ($anchos as $w) {
                $h = (int) round($alto * $w / $ancho);
                $lienzo = imagecreatetruecolor($w, $h);
                // conserva la transparencia de PNG/WEBP/GIF
                imagealphablending($lienzo, false);
                imagesavealpha($lienzo, true);
                imagefill($lienzo, 0, 0, imagecolorallocatealpha($lienzo, 0, 0, 0, 127));
                imagecopyresampled($lienzo, $origen, 0, 0, 0, 0, $w, $h, $ancho, $alto);

                $rutaRel = "$carpetaRel/$nombreBase-$w.$extension";
                $rutaAbs = Url::raiz() . '/' . $rutaRel;
                $ok = $usarWebp ? imagewebp($lienzo, $rutaAbs, self::CALIDAD) : imagejpeg($lienzo, $rutaAbs, self::CALIDAD);
                imagedestroy($lienzo);
                if (!$ok) {
                    throw new ApiException('No se pudo guardar la imagen procesada', 500);
                }
                $creados[] = $rutaAbs;
                $variantes[] = ['ancho' => $w, 'ruta' => $rutaRel, 'peso_kb' => max(1, (int) ceil(filesize($rutaAbs) / 1024))];
            }
            imagedestroy($origen);

            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                'INSERT INTO media (subido_por, nombre_original, alt_text, mime, ancho_original, alto_original) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$usuarioId, mb_substr(basename($archivo['name']), 0, 255), $alt, $mime, $ancho, $alto]);
            $mediaId = (int) $this->db->lastInsertId();

            $stmt = $this->db->prepare('INSERT INTO media_variantes (media_id, ancho, ruta, peso_kb) VALUES (?, ?, ?, ?)');
            foreach ($variantes as $v) {
                $stmt->execute([$mediaId, $v['ancho'], $v['ruta'], $v['peso_kb']]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            foreach ($creados as $f) {
                @unlink($f);
            }
            throw $e;
        }

        return $this->obtener($mediaId);
    }

    public function actualizarAlt(int $id, ?string $alt): array
    {
        if (!$this->existe($id)) {
            throw new ApiException('Imagen no encontrada', 404);
        }
        $this->db->prepare('UPDATE media SET alt_text = ? WHERE id = ?')->execute([$alt, $id]);
        return $this->obtener($id);
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare('SELECT ruta FROM media_variantes WHERE media_id = ?');
        $stmt->execute([$id]);
        $rutas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $borrado = $this->db->prepare('DELETE FROM media WHERE id = ?');
        $borrado->execute([$id]);
        if ($borrado->rowCount() === 0) {
            throw new ApiException('Imagen no encontrada', 404);
        }
        // las variantes en BD se borran en cascada; aquí se borran los archivos
        foreach ($rutas as $ruta) {
            if (str_starts_with($ruta, 'uploads/')) {   // las imágenes de ejemplo (public/assets) se quedan en el repo
                @unlink(Url::raiz() . '/' . $ruta);
            }
        }
    }

    // Variantes de varios medios a la vez (para config y bloques)
    public function variantesDe(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT m.id, m.alt_text, v.ancho, v.ruta FROM media m
                                    JOIN media_variantes v ON v.media_id = m.id
                                    WHERE m.id IN ($marcas) ORDER BY v.ancho");
        $stmt->execute($ids);
        $res = [];
        foreach ($stmt->fetchAll() as $f) {
            $res[$f['id']]['id'] = (int) $f['id'];
            $res[$f['id']]['alt'] = $f['alt_text'];
            $res[$f['id']]['variantes'][] = ['ancho' => (int) $f['ancho'], 'url' => Url::archivo($f['ruta'])];
        }
        foreach ($res as &$m) {
            $m['url'] = end($m['variantes'])['url'];   // la más grande
            $m['srcset'] = implode(', ', array_map(fn($v) => "{$v['url']} {$v['ancho']}w", $m['variantes']));
        }
        return $res;
    }

    private function formatear(array $m): array
    {
        $variantes = $this->variantesDe([$m['id']])[$m['id']] ?? ['variantes' => [], 'url' => null, 'srcset' => ''];
        return [
            'id'              => (int) $m['id'],
            'nombre_original' => $m['nombre_original'],
            'alt'             => $m['alt_text'],
            'mime'            => $m['mime'],
            'ancho_original'  => (int) $m['ancho_original'],
            'alto_original'   => (int) $m['alto_original'],
            'url'             => $variantes['url'],
            'srcset'          => $variantes['srcset'],
            'variantes'       => $variantes['variantes'],
            'created_at'      => $m['created_at'],
        ];
    }

    private function validarSubida(?array $archivo): void
    {
        if (!$archivo || !isset($archivo['error'])) {
            throw new ApiException("Envía la imagen en el campo 'imagen' (multipart/form-data)", 422);
        }
        if (is_array($archivo['error'])) {
            throw new ApiException('Sube una sola imagen por petición', 422);
        }
        switch ($archivo['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new ApiException('La imagen supera el tamaño máximo permitido por el servidor', 413);
            case UPLOAD_ERR_NO_FILE:
                throw new ApiException("Envía la imagen en el campo 'imagen'", 422);
            default:
                throw new ApiException('Error al recibir el archivo (código ' . $archivo['error'] . ')', 400);
        }
        if ($archivo['size'] > self::MAX_BYTES) {
            throw new ApiException('La imagen pesa más de 5 MB', 413);
        }
        if (!is_uploaded_file($archivo['tmp_name'])) {
            throw new ApiException('Archivo no válido', 400);
        }
    }
}
