<?php
// Mensajes que llegan desde el formulario de contacto de la web

class MensajeService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    public function crear(array $d): array
    {
        // campo trampa: los humanos no lo ven ni lo llenan; los bots sí
        if (!empty($d['website'])) {
            throw new ApiException('No se pudo enviar el mensaje', 400);
        }
        $nombre   = Validar::texto($d['nombre'] ?? null, 'nombre', 100);
        $email    = Validar::email($d['email'] ?? null, 'email');
        $telefono = Validar::texto($d['telefono'] ?? null, 'telefono', 20, false);
        $servicio = Validar::texto($d['servicio_interes'] ?? null, 'servicio_interes', 80, false);
        $mensaje  = Validar::texto($d['mensaje'] ?? null, 'mensaje', 2000);
        if (mb_strlen($mensaje) < 10) {
            throw new ApiException('El mensaje debe tener al menos 10 caracteres', 422);
        }
        if ($telefono !== null && !preg_match('/^[\d\s+\-()]{6,20}$/', $telefono)) {
            throw new ApiException('El teléfono solo puede tener números, espacios y +', 422);
        }

        $stmt = $this->db->prepare('INSERT INTO mensajes_contacto (nombre, email, telefono, servicio_interes, mensaje) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$nombre, $email, $telefono, $servicio, $mensaje]);
        return ['id' => (int) $this->db->lastInsertId()];
    }

    public function listar(): array
    {
        $filas = $this->db->query('SELECT * FROM mensajes_contacto ORDER BY leido, id DESC')->fetchAll();
        return array_map(fn($m) => [
            'id'               => (int) $m['id'],
            'nombre'           => $m['nombre'],
            'email'            => $m['email'],
            'telefono'         => $m['telefono'],
            'servicio_interes' => $m['servicio_interes'],
            'mensaje'          => $m['mensaje'],
            'leido'            => (bool) $m['leido'],
            'created_at'       => $m['created_at'],
        ], $filas);
    }

    public function marcar(int $id, $leido): void
    {
        $stmt = $this->db->prepare('UPDATE mensajes_contacto SET leido = ? WHERE id = ?');
        $stmt->execute([Validar::booleano($leido ?? true, 'leido'), $id]);
        if ($stmt->rowCount() === 0 && !$this->existe($id)) {
            throw new ApiException('Mensaje no encontrado', 404);
        }
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM mensajes_contacto WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            throw new ApiException('Mensaje no encontrado', 404);
        }
    }

    private function existe(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM mensajes_contacto WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }
}
