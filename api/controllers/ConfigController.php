<?php
// Configuración global del sitio: colores, logo, nombre y contacto

class ConfigController
{
    private ConfigService $servicio;

    public function __construct()
    {
        $this->servicio = new ConfigService();
    }

    // GET /config  (público: la web lo usa para pintar colores y logo)
    public function obtener(array $params, $usuario): void
    {
        Response::ok($this->servicio->obtener());
    }

    // PUT /admin/config  (solo admin)
    public function actualizar(array $params, $usuario): void
    {
        Response::ok($this->servicio->actualizar(Response::body()), 'Configuración actualizada');
    }
}
