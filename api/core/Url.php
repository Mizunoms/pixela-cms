<?php
// Arma URLs públicas a partir de la carpeta del proyecto (funciona con cualquier nombre de carpeta)

class Url
{
    // Ej: /pixela-cms  (la API vive en /pixela-cms/api/index.php)
    public static function base(): string
    {
        $api = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        return rtrim(str_replace('\\', '/', dirname($api)), '/');
    }

    // Ej: uploads/2026/10/abc-480.webp  ->  /pixela-cms/uploads/2026/10/abc-480.webp
    public static function archivo(string $rutaRelativa): string
    {
        return self::base() . '/' . ltrim($rutaRelativa, '/');
    }

    // Carpeta raíz del proyecto en disco
    public static function raiz(): string
    {
        return dirname(__DIR__, 2);
    }
}
