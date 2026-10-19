<?php
// Bloques modulares de cada página (tabla page_components). El contenido de cada bloque va en JSON.

class ComponenteService
{
    // Tipos de bloque que el front sabe dibujar
    public const TIPOS = ['hero', 'servicios', 'portafolio', 'plantillas', 'planes', 'proceso',
                          'testimonios', 'faq', 'contacto', 'texto', 'galeria', 'cta'];
    private const MAX_JSON = 65535;   // 64 KB por bloque

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    public function listarPorPagina(int $paginaId, bool $soloVisibles = false): array
    {
        $sql = 'SELECT * FROM page_components WHERE pagina_id = ?' . ($soloVisibles ? ' AND visible = 1' : '') . ' ORDER BY orden, id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$paginaId]);
        return array_map(fn($c) => $this->formatear($c), $stmt->fetchAll());
    }

    public function obtener(int $id): array
    {
        return $this->formatear($this->buscar($id));
    }

    public function crear(int $paginaId, array $d): array
    {
        (new PaginaService())->buscar($paginaId);   // 404 si la página no existe

        $tipo      = $this->validarTipo($d['tipo'] ?? null);
        $contenido = $this->validarContenido($d['contenido'] ?? null);
        $visible   = isset($d['visible']) ? Validar::booleano($d['visible'], 'visible') : 1;

        // va al final de la página
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(orden), 0) + 1 FROM page_components WHERE pagina_id = ?');
        $stmt->execute([$paginaId]);
        $orden = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare('INSERT INTO page_components (pagina_id, tipo, contenido, orden, visible) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$paginaId, $tipo, $contenido, $orden, $visible]);

        return $this->obtener((int) $this->db->lastInsertId());
    }

    public function actualizar(int $id, array $d): array
    {
        $this->buscar($id);
        $cambios = [];
        if (array_key_exists('tipo', $d))      $cambios['tipo']      = $this->validarTipo($d['tipo']);
        if (array_key_exists('contenido', $d)) $cambios['contenido'] = $this->validarContenido($d['contenido']);
        if (array_key_exists('visible', $d))   $cambios['visible']   = Validar::booleano($d['visible'], 'visible');
        if (!$cambios) {
            throw new ApiException('Envía al menos uno de estos campos: tipo, contenido, visible', 422);
        }

        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($cambios)));
        $this->db->prepare("UPDATE page_components SET $set WHERE id = ?")->execute([...array_values($cambios), $id]);
        return $this->obtener($id);
    }

    public function eliminar(int $id): void
    {
        $bloque = $this->buscar($id);
        $this->db->prepare('DELETE FROM page_components WHERE id = ?')->execute([$id]);
        $this->compactarOrden((int) $bloque['pagina_id']);
    }

    // Recibe todos los ids de bloques de la página en el nuevo orden: { "ids": [5, 2, 8, ...] }
    public function reordenar(int $paginaId, $ids): array
    {
        (new PaginaService())->buscar($paginaId);
        if (!is_array($ids) || !$ids) {
            throw new ApiException("Envía 'ids' con la lista de bloques en el nuevo orden, ej. {\"ids\": [3, 1, 2]}", 422);
        }
        $ids = array_map(fn($v) => Validar::id($v, 'ids'), $ids);

        $actuales = array_column($this->listarPorPagina($paginaId), 'id');
        $a = $ids;
        $b = $actuales;
        sort($a);
        sort($b);
        if ($a !== $b) {
            throw new ApiException("'ids' debe contener exactamente los " . count($actuales) . ' bloques de la página, sin repetir', 422);
        }

        $this->db->beginTransaction();
        $stmt = $this->db->prepare('UPDATE page_components SET orden = ? WHERE id = ? AND pagina_id = ?');
        foreach ($ids as $i => $id) {
            $stmt->execute([$i + 1, $id, $paginaId]);
        }
        $this->db->commit();

        return $this->listarPorPagina($paginaId);
    }

    // ---------- Internos ----------

    private function buscar(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM page_components WHERE id = ?');
        $stmt->execute([$id]);
        $bloque = $stmt->fetch();
        if (!$bloque) {
            throw new ApiException('Bloque no encontrado', 404);
        }
        return $bloque;
    }

    private function validarTipo($tipo): string
    {
        if (!is_string($tipo) || !in_array($tipo, self::TIPOS, true)) {
            throw new ApiException("'tipo' no válido. Usa uno de: " . implode(', ', self::TIPOS), 422);
        }
        return $tipo;
    }

    // contenido = objeto JSON ({...}); se guarda como texto JSON
    private function validarContenido($contenido): string
    {
        if (!is_array($contenido) || ($contenido !== [] && array_is_list($contenido))) {
            throw new ApiException("'contenido' debe ser un objeto JSON, ej. {\"titulo\": \"Hola\"}", 422);
        }
        $json = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($json) > self::MAX_JSON) {
            throw new ApiException("'contenido' es demasiado grande (máximo 64 KB)", 422);
        }
        return $json === '[]' ? '{}' : $json;
    }

    private function compactarOrden(int $paginaId): void
    {
        $ids = array_column($this->listarPorPagina($paginaId), 'id');
        $stmt = $this->db->prepare('UPDATE page_components SET orden = ? WHERE id = ?');
        foreach ($ids as $i => $id) {
            $stmt->execute([$i + 1, $id]);
        }
    }

    private function formatear(array $c): array
    {
        return [
            'id'         => (int) $c['id'],
            'pagina_id'  => (int) $c['pagina_id'],
            'tipo'       => $c['tipo'],
            'orden'      => (int) $c['orden'],
            'visible'    => (bool) $c['visible'],
            'contenido'  => json_decode($c['contenido'], true) ?: (object) [],
            'updated_at' => $c['updated_at'],
        ];
    }
}
