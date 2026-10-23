<?php
// Formulario de contacto (público) y bandeja de mensajes (admin)

class MensajeController
{
    private MensajeService $servicio;

    public function __construct()
    {
        $this->servicio = new MensajeService();
    }

    // POST /contacto  { nombre, email, telefono?, servicio_interes?, mensaje }
    public function crear(array $params, $usuario): void
    {
        Response::creado($this->servicio->crear(Response::body()), '¡Gracias! Te responderemos pronto.');
    }

    // GET /admin/mensajes
    public function listar(array $params, $usuario): void
    {
        Response::ok($this->servicio->listar());
    }

    // PUT /admin/mensajes/{id}  { leido: true|false }
    public function marcar(array $params, $usuario): void
    {
        $body = Response::body();
        $this->servicio->marcar(Validar::idRuta($params), $body['leido'] ?? true);
        Response::ok(null, 'Mensaje actualizado');
    }

    // DELETE /admin/mensajes/{id}
    public function eliminar(array $params, $usuario): void
    {
        $this->servicio->eliminar(Validar::idRuta($params));
        Response::ok(null, 'Mensaje eliminado');
    }
}
