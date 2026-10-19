<?php
// Páginas del sitio (inicio, servicios, plantillas, contacto...) y sus bloques.

class PaginaService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    // ---------- Público ----------

    // Página publicada con sus bloques visibles, lista para que el front la dibuje
    public function publicaPorSlug(string $slug): array
    {
        $stmt = $this->db->prepare('SELECT * FROM paginas WHERE slug = ? AND publicada = 1');
        $stmt->execute([$slug]);
        $pagina = $stmt->fetch();
        if (!$pagina) {
            throw new ApiException('Página no encontrada', 404);
        }
        return $this->conBloques($pagina, true);
    }

    public function publicaInicio(): array
    {
        $pagina = $this->db->query('SELECT * FROM paginas WHERE es_inicio = 1 AND publicada = 1 LIMIT 1')->fetch();
        if (!$pagina) {
            throw new ApiException('No hay una página de inicio publicada', 404);
        }
        return $this->conBloques($pagina, true);
    }

    // ---------- Admin ----------

    public function listar(): array
    {
        $sql = 'SELECT p.*, COUNT(c.id) AS total_bloques
                FROM paginas p LEFT JOIN page_components c ON c.pagina_id = p.id
                GROUP BY p.id ORDER BY p.es_inicio DESC, p.id';
        return array_map(function ($p) {
            $f = $this->formatear($p);
            $f['total_bloques'] = (int) $p['total_bloques'];
            return $f;
        }, $this->db->query($sql)->fetchAll());
    }

    public function obtener(int $id): array
    {
        return $this->conBloques($this->buscar($id), false);
    }

    public function crear(array $d): array
    {
        $titulo = Validar::texto($d['titulo'] ?? null, 'titulo', 120);
        $slug   = Validar::slug($d['slug'] ?? $this->slugDe($titulo));
        $meta   = Validar::texto($d['meta_descripcion'] ?? null, 'meta_descripcion', 255, false);
        $publicada = isset($d['publicada']) ? Validar::booleano($d['publicada'], 'publicada') : 1;
        $esInicio  = isset($d['es_inicio']) ? Validar::booleano($d['es_inicio'], 'es_inicio') : 0;

        $this->validarSlugLibre($slug);

        $this->db->beginTransaction();
        if ($esInicio) {
            $this->db->exec('UPDATE paginas SET es_inicio = 0');
        }
        $stmt = $this->db->prepare('INSERT INTO paginas (titulo, slug, meta_descripcion, es_inicio, publicada) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$titulo, $slug, $meta, $esInicio, $publicada]);
        $id = (int) $this->db->lastInsertId();
        $this->db->commit();

        return $this->obtener($id);
    }

    public function actualizar(int $id, array $d): array
    {
        $actual = $this->buscar($id);
        $cambios = [];

        if (array_key_exists('titulo', $d))           $cambios['titulo'] = Validar::texto($d['titulo'], 'titulo', 120);
        if (array_key_exists('meta_descripcion', $d)) $cambios['meta_descripcion'] = Validar::texto($d['meta_descripcion'], 'meta_descripcion', 255, false);
        if (array_key_exists('publicada', $d))        $cambios['publicada'] = Validar::booleano($d['publicada'], 'publicada');
        if (array_key_exists('slug', $d)) {
            $cambios['slug'] = Validar::slug($d['slug']);
            $this->validarSlugLibre($cambios['slug'], $id);
        }
        $esInicio = array_key_exists('es_inicio', $d) ? Validar::booleano($d['es_inicio'], 'es_inicio') : null;

        if ((int) $actual['es_inicio'] === 1 && ($esInicio === 0 || ($cambios['publicada'] ?? 1) === 0)) {
            throw new ApiException('La página de inicio debe seguir publicada. Marca otra página como inicio primero.', 409);
        }
        if (!$cambios && $esInicio === null) {
            throw new ApiException('No se envió ningún campo para actualizar', 422);
        }

        $this->db->beginTransaction();
        if ($esInicio === 1) {
            $this->db->exec('UPDATE paginas SET es_inicio = 0');
            $cambios['es_inicio'] = 1;
            $cambios['publicada'] = 1;
        }
        if ($cambios) {
            $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($cambios)));
            $this->db->prepare("UPDATE paginas SET $set WHERE id = ?")->execute([...array_values($cambios), $id]);
        }
        $this->db->commit();

        return $this->obtener($id);
    }

    public function eliminar(int $id): void
    {
        $pagina = $this->buscar($id);
        if ((int) $pagina['es_inicio'] === 1) {
            throw new ApiException('No puedes borrar la página de inicio. Marca otra página como inicio primero.', 409);
        }
        // los bloques se borran en cascada; los ítems del menú que apuntaban aquí quedan sin página
        $this->db->prepare('DELETE FROM paginas WHERE id = ?')->execute([$id]);
    }

    // ---------- Internos ----------

    public function buscar(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM paginas WHERE id = ?');
        $stmt->execute([$id]);
        $pagina = $stmt->fetch();
        if (!$pagina) {
            throw new ApiException('Página no encontrada', 404);
        }
        return $pagina;
    }

    private function conBloques(array $pagina, bool $soloVisibles): array
    {
        $resultado = $this->formatear($pagina);
        $resultado['bloques'] = (new ComponenteService())->listarPorPagina((int) $pagina['id'], $soloVisibles);
        return $resultado;
    }

    private function formatear(array $p): array
    {
        return [
            'id'               => (int) $p['id'],
            'titulo'           => $p['titulo'],
            'slug'             => $p['slug'],
            'meta_descripcion' => $p['meta_descripcion'],
            'es_inicio'        => (bool) $p['es_inicio'],
            'publicada'        => (bool) $p['publicada'],
            'created_at'       => $p['created_at'],
            'updated_at'       => $p['updated_at'],
        ];
    }

    private function validarSlugLibre(string $slug, int $excepto = 0): void
    {
        $stmt = $this->db->prepare('SELECT 1 FROM paginas WHERE slug = ? AND id <> ?');
        $stmt->execute([$slug, $excepto]);
        if ($stmt->fetchColumn()) {
            throw new ApiException("Ya existe una página con el slug '$slug'", 409);
        }
    }

    // "Nuestros Servicios" -> "nuestros-servicios"
    private function slugDe(string $texto): string
    {
        $t = strtr(mb_strtolower($texto), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        return trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-');
    }
}
