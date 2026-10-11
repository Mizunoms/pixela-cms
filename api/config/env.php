<?php
// Carga las variables del archivo .env (raíz del proyecto)

function cargarEnv(string $ruta): void
{
    if (!file_exists($ruta)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Falta el archivo .env. Abre instalar.php en el navegador para configurar el sistema.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = explode('=', $linea, 2);
        $_ENV[trim($clave)] = trim($valor);
    }
}

function env(string $clave, $defecto = null)
{
    return $_ENV[$clave] ?? $defecto;
}
