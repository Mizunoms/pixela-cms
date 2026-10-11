<?php
// Lógica de autenticación: validar credenciales y generar el JWT

use Firebase\JWT\JWT;

class AuthService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    // Devuelve el usuario si las credenciales son correctas, o null
    public function validarCredenciales(string $email, string $password): ?array
    {
        $sql = 'SELECT u.id, u.nombre, u.email, u.password_hash, u.activo, r.nombre AS rol
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE u.email = ?
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if (!$usuario || !$usuario['activo'] || !password_verify($password, $usuario['password_hash'])) {
            return null;
        }

        $this->db->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')
                 ->execute([$usuario['id']]);

        unset($usuario['password_hash'], $usuario['activo']);
        $usuario['id'] = (int) $usuario['id'];
        return $usuario;
    }

    public function generarToken(array $usuario): string
    {
        $ahora = time();
        $payload = [
            'sub'    => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email'  => $usuario['email'],
            'rol'    => $usuario['rol'],
            'iat'    => $ahora,
            'exp'    => $ahora + $this->duracionSegundos(),
        ];
        return JWT::encode($payload, env('JWT_SECRET'), 'HS256');
    }

    public function duracionSegundos(): int
    {
        return (int) env('JWT_EXPIRA_HORAS', 8) * 3600;
    }
}
