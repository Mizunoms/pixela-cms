<?php
// Páginas del sitio

class PaginaController
{
    private PaginaService $servicio;

    public function __construct()
    {
        $this->servicio = new PaginaService();
    }

    // GET /inicio  (público)
    public function inicio(array $params, $usuario): void
    {
        Response::ok($this->servicio->publicaInicio());
    }

    // GET /paginas/{slug}  (público)
    public function publica(array $params, $usuario): void
    {
        Response::ok($this->servicio->publicaPorSlug((string) $params['slug']));
    }

    // GET /admin/paginas
    public function listar(array $params, $usuario): void
    {
        Response::ok($this->servicio->listar());
    }

    // GET /admin/paginas/{id}
    public function obtener(array $params, $usuario): void
    {
        Response::ok($this->servicio->obtener(Validar::idRuta($params)));
    }

    // POST /admin/paginas  { titulo, slug?, meta_descripcion?, publicada?, es_inicio? }
    public function crear(array $params, $usuario): void
    {
        Response::creado($this->servicio->crear(Response::body()), 'Página creada');
    }

    // PUT /admin/paginas/{id}
    public function actualizar(array $params, $usuario): void
    {
        Response::ok($this->servicio->actualizar(Validar::idRuta($params), Response::body()), 'Página actualizada');
    }

    // DELETE /admin/paginas/{id}
    public function eliminar(array $params, $usuario): void
    {
        $this->servicio->eliminar(Validar::idRuta($params));
        Response::ok(null, 'Página eliminada');
    }
}
