<?php
// Error controlado de la API: lleva el mensaje para el cliente y el código HTTP.
// Los servicios lo lanzan y index.php lo convierte en respuesta JSON.

class ApiException extends RuntimeException
{
    public function __construct(string $mensaje, private int $status = 400)
    {
        parent::__construct($mensaje);
    }

    public function status(): int
    {
        return $this->status;
    }
}
