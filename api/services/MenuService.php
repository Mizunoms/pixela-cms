<?php
// Menú de navegación con submenús (2 niveles) y orden.
// Cada ítem apunta a una página del CMS (pagina_id) o a un enlace (url_externa).

class MenuService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    // Árbol del menú: [{ id, etiqueta, url, hijos: [...] }]
    public function arbol(bool $soloVisibles): array
    {
        $sql = 'SELECT m.*, p.slug, p.es_inicio, p.publicada
                FROM menu_items m LEFT JOIN paginas p ON p.id = m.pagina_id
                ORDER BY m.orden, m.id';
        $items = $this->db->query($sql)->fetchAll();

        $nodos = [];
        foreach ($items as $i) {
            // en público se ocultan los ocultos y los que apuntan a páginas no publicadas
            $sinDestino = !$i['pagina_id'] && !$i['url_externa'];   // su página fue borrada
            if ($soloVisibles && (!$i['visible'] || $sinDestino || ($i['pagina_id'] && !$i['publicada']))) {
                continue;
            }
            $nodos[$i['id']] = $this->formatear($i, $soloVisibles);
        }

        $raiz = [];
        foreach ($nodos as $id => &$nodo) {
            $padre = $nodo['parent_id'];
            if ($padre === null) {
                $raiz[] = &$nodo;
            } elseif (isset($nodos[$padre])) {
                $nodos[$padre]['hijos'][] = &$nodo;
            }
            // un hijo cuyo padre está oculto tampoco se muestra
        }
        unset($nodo);
        return $raiz;
    }

    public function crear(array $d): array
    {
        $datos = $this->validar($d, null);
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(orden), 0) + 1 FROM menu_items WHERE parent_id <=> ?');
        $stmt->execute([$datos['parent_id']]);
        $datos['orden'] = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare('INSERT INTO menu_items (parent_id, pagina_id, etiqueta, url_externa, orden, visible) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$datos['parent_id'], $datos['pagina_id'], $datos['etiqueta'], $datos['url_externa'], $datos['orden'], $datos['visible']]);
        return $this->obtener((int) $this->db->lastInsertId());
    }

    public function actualizar(int $id, array $d): array
    {
        $actual = $this->buscar($id);
        if (!$d) {
            throw new ApiException('No se envió ningún campo para actualizar', 422);
        }
        // mezcla lo actual con lo que llega y valida el resultado completo
        $base = [
            'etiqueta'    => $actual['etiqueta'],
            'pagina_id'   => $actual['pagina_id'],
            'url_externa' => $actual['url_externa'],
            'parent_id'   => $actual['parent_id'],
            'visible'     => (int) $actual['visible'],
        ];
        // si cambia el destino, el otro tipo de destino se limpia
        if (array_key_exists('pagina_id', $d) && !array_key_exists('url_externa', $d)) $base['url_externa'] = null;
        if (array_key_exists('url_externa', $d) && !array_key_exists('pagina_id', $d)) $base['pagina_id'] = null;

        $datos = $this->validar(array_merge($base, $d), $id);
        $this->db->prepare('UPDATE menu_items SET parent_id = ?, pagina_id = ?, etiqueta = ?, url_externa = ?, visible = ? WHERE id = ?')
                 ->execute([$datos['parent_id'], $datos['pagina_id'], $datos['etiqueta'], $datos['url_externa'], $datos['visible'], $id]);
        return $this->obtener($id);
    }

    public function eliminar(int $id): void
    {
        $this->buscar($id);
        // los submenús se borran en cascada
        $this->db->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$id]);
    }

    // Reordena (y puede mover entre padres) todo el menú de una vez:
    // { "items": [ { "id": 1, "parent_id": null, "orden": 1 }, { "id": 5, "parent_id": 1, "orden": 1 }, ... ] }
    public function reordenar($items): array
    {
        if (!is_array($items) || !$items) {
            throw new ApiException("Envía 'items' con [{ id, parent_id, orden }]", 422);
        }
        $todos = array_map('intval', $this->db->query('SELECT id FROM menu_items')->fetchAll(PDO::FETCH_COLUMN));

        $nuevo = [];
        foreach ($items as $it) {
            if (!is_array($it)) {
                throw new ApiException("Cada ítem de 'items' debe ser { id, parent_id, orden }", 422);
            }
            $id = Validar::id($it['id'] ?? null, 'id');
            if (!in_array($id, $todos, true)) {
                throw new ApiException("No existe el ítem de menú $id", 422);
            }
            if (isset($nuevo[$id])) {
                throw new ApiException("El ítem $id está repetido", 422);
            }
            $nuevo[$id] = [
                'parent_id' => Validar::idOpcional($it['parent_id'] ?? null, 'parent_id'),
                'orden'     => Validar::id($it['orden'] ?? null, 'orden'),
            ];
        }
        if (count($nuevo) !== count($todos)) {
            throw new ApiException("'items' debe incluir los " . count($todos) . ' ítems del menú', 422);
        }
        foreach ($nuevo as $id => $n) {
            $padre = $n['parent_id'];
            if ($padre === null) {
                continue;
            }
            if ($padre === $id || !isset($nuevo[$padre])) {
                throw new ApiException("El padre del ítem $id no es válido", 422);
            }
            if ($nuevo[$padre]['parent_id'] !== null) {
                throw new ApiException('El menú admite solo 2 niveles (menú y submenú)', 422);
            }
        }

        $this->db->beginTransaction();
        $stmt = $this->db->prepare('UPDATE menu_items SET parent_id = ?, orden = ? WHERE id = ?');
        // primero los de nivel 1 para no romper la llave foránea
        uasort($nuevo, fn($a, $b) => ($a['parent_id'] !== null) <=> ($b['parent_id'] !== null));
        foreach ($nuevo as $id => $n) {
            $stmt->execute([$n['parent_id'], $n['orden'], $id]);
        }
        $this->db->commit();

        return $this->arbol(false);
    }

    // ---------- Internos ----------

    private function obtener(int $id): array
    {
        $stmt = $this->db->prepare('SELECT m.*, p.slug, p.es_inicio, p.publicada FROM menu_items m LEFT JOIN paginas p ON p.id = m.pagina_id WHERE m.id = ?');
        $stmt->execute([$id]);
        return $this->formatear($stmt->fetch(), false);
    }

    private function buscar(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM menu_items WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            throw new ApiException('Ítem de menú no encontrado', 404);
        }
        return $item;
    }

    private function validar(array $d, ?int $id): array
    {
        $etiqueta = Validar::texto($d['etiqueta'] ?? null, 'etiqueta', 60);
        $paginaId = Validar::idOpcional($d['pagina_id'] ?? null, 'pagina_id');
        $url      = isset($d['url_externa']) && $d['url_externa'] !== '' ? Validar::enlace($d['url_externa'], 'url_externa') : null;
        $parentId = Validar::idOpcional($d['parent_id'] ?? null, 'parent_id');
        $visible  = isset($d['visible']) ? Validar::booleano($d['visible'], 'visible') : 1;

        if (($paginaId === null) === ($url === null)) {
            throw new ApiException("Indica el destino con 'pagina_id' o con 'url_externa' (solo uno)", 422);
        }
        if ($paginaId !== null) {
            (new PaginaService())->buscar($paginaId);
        }
        if ($parentId !== null) {
            if ($parentId === $id) {
                throw new ApiException('Un ítem no puede ser su propio padre', 422);
            }
            $padre = $this->buscar($parentId);
            if ($padre['parent_id'] !== null) {
                throw new ApiException('El menú admite solo 2 niveles (menú y submenú)', 422);
            }
            if ($id !== null) {
                $stmt = $this->db->prepare('SELECT 1 FROM menu_items WHERE parent_id = ?');
                $stmt->execute([$id]);
                if ($stmt->fetchColumn()) {
                    throw new ApiException('Este ítem tiene submenús; no puede pasar a ser submenú', 422);
                }
            }
        }

        return ['etiqueta' => $etiqueta, 'pagina_id' => $paginaId, 'url_externa' => $url, 'parent_id' => $parentId, 'visible' => $visible];
    }

    private function formatear(array $i, bool $publico): array
    {
        if ($i['pagina_id']) {
            $url = $i['es_inicio'] ? '/' : '/' . $i['slug'];
        } else {
            $url = $i['url_externa'];
        }
        $nodo = [
            'id'        => (int) $i['id'],
            'etiqueta'  => $i['etiqueta'],
            'url'       => $url,
            'externo'   => (bool) preg_match('#^https?://#i', (string) $url),
            'parent_id' => $i['parent_id'] !== null ? (int) $i['parent_id'] : null,
            'orden'     => (int) $i['orden'],
            'hijos'     => [],
        ];
        if (!$publico) {
            $nodo['sin_destino'] = !$i['pagina_id'] && !$i['url_externa'];
            $nodo['pagina_id']   = $i['pagina_id'] !== null ? (int) $i['pagina_id'] : null;
            $nodo['url_externa'] = $i['url_externa'];
            $nodo['visible']     = (bool) $i['visible'];
        }
        return $nodo;
    }
}
