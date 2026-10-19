<?php
// Menú de navegación

class MenuController
{
    private MenuService $servicio;

    public function __construct()
    {
        $this->servicio = new MenuService();
    }

    // GET /menu  (público: solo ítems visibles)
    public function publico(array $params, $usuario): void
    {
        Response::ok($this->servicio->arbol(true));
    }

    // GET /admin/menu  (todos, incluidos los ocultos)
    public function listar(array $params, $usuario): void
    {
        Response::ok($this->servicio->arbol(false));
    }

    // POST /admin/menu  { etiqueta, pagina_id | url_externa, parent_id?, visible? }
    public function crear(array $params, $usuario): void
    {
        Response::creado($this->servicio->crear(Response::body()), 'Ítem de menú creado');
    }

    // PUT /admin/menu/orden  { items: [{ id, parent_id, orden }] }
    public function reordenar(array $params, $usuario): void
    {
        $body = Response::body();
        Response::ok($this->servicio->reordenar($body['items'] ?? null), 'Menú reordenado');
    }

    // PUT /admin/menu/{id}
    public function actualizar(array $params, $usuario): void
    {
        Response::ok($this->servicio->actualizar(Validar::idRuta($params), Response::body()), 'Ítem de menú actualizado');
    }

    // DELETE /admin/menu/{id}
    public function eliminar(array $params, $usuario): void
    {
        $this->servicio->eliminar(Validar::idRuta($params));
        Response::ok(null, 'Ítem de menú eliminado');
    }
}
