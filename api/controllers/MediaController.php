<?php
// Biblioteca de imágenes

class MediaController
{
    private MediaService $servicio;

    public function __construct()
    {
        $this->servicio = new MediaService();
    }

    // GET /admin/media
    public function listar(array $params, $usuario): void
    {
        Response::ok($this->servicio->listar());
    }

    // POST /admin/media  (multipart/form-data: imagen = archivo, alt = texto opcional)
    public function subir(array $params, $usuario): void
    {
        $alt = Validar::texto($_POST['alt'] ?? null, 'alt', 255, false);
        Response::creado($this->servicio->subir($_FILES['imagen'] ?? null, $alt, $usuario['id']), 'Imagen subida');
    }

    // PUT /admin/media/{id}  { alt }
    public function actualizar(array $params, $usuario): void
    {
        $body = Response::body();
        $alt = Validar::texto($body['alt'] ?? null, 'alt', 255, false);
        Response::ok($this->servicio->actualizarAlt(Validar::idRuta($params), $alt), 'Imagen actualizada');
    }

    // DELETE /admin/media/{id}
    public function eliminar(array $params, $usuario): void
    {
        $this->servicio->eliminar(Validar::idRuta($params));
        Response::ok(null, 'Imagen eliminada');
    }
}
