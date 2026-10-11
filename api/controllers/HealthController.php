<?php
// Endpoints de prueba: estado de la API y verificación de rol admin

class HealthController
{
    // GET /  -> comprueba que la API y la BD respondan
    public function estado(array $params, $usuario): void
    {
        Database::conectar()->query('SELECT 1');
        Response::ok(['api' => 'Pixela CMS', 'version' => '1.0', 'bd' => 'conectada']);
    }

    // GET /admin/ping  -> solo admin
    public function adminPing(array $params, $usuario): void
    {
        Response::ok(['usuario' => $usuario['nombre']], 'Acceso de admin confirmado');
    }
}
