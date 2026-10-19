<?php
// Bloques modulares de las páginas (hero, servicios, testimonios...)

class ComponenteController
{
    private ComponenteService $servicio;

    public function __construct()
    {
        $this->servicio = new ComponenteService();
    }

    // GET /admin/bloques/tipos  -> tipos de bloque disponibles
    public function tipos(array $params, $usuario): void
    {
        Response::ok(ComponenteService::TIPOS);
    }

    // GET /admin/paginas/{id}/bloques
    public function listar(array $params, $usuario): void
    {
        $paginaId = Validar::idRuta($params);
        (new PaginaService())->buscar($paginaId);
        Response::ok($this->servicio->listarPorPagina($paginaId));
    }

    // POST /admin/paginas/{id}/bloques  { tipo, contenido: {...}, visible? }
    public function crear(array $params, $usuario): void
    {
        Response::creado($this->servicio->crear(Validar::idRuta($params), Response::body()), 'Bloque creado');
    }

    // PUT /admin/paginas/{id}/bloques/orden  { ids: [3, 1, 2] }
    public function reordenar(array $params, $usuario): void
    {
        $body = Response::body();
        Response::ok($this->servicio->reordenar(Validar::idRuta($params), $body['ids'] ?? null), 'Orden actualizado');
    }

    // GET /admin/bloques/{id}
    public function obtener(array $params, $usuario): void
    {
        Response::ok($this->servicio->obtener(Validar::idRuta($params)));
    }

    // PUT /admin/bloques/{id}  { tipo?, contenido?, visible? }
    public function actualizar(array $params, $usuario): void
    {
        Response::ok($this->servicio->actualizar(Validar::idRuta($params), Response::body()), 'Bloque actualizado');
    }

    // DELETE /admin/bloques/{id}
    public function eliminar(array $params, $usuario): void
    {
        $this->servicio->eliminar(Validar::idRuta($params));
        Response::ok(null, 'Bloque eliminado');
    }
}
