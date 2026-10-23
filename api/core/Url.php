<?php
// Arma URLs públicas a partir de la carpeta del proyecto (funciona con cualquier nombre de carpeta)

class Url
{
    // Ej: /pixela-cms  — sirve tanto si se llama desde /api/index.php, /admin/... o el index.php de la raíz
    public static function base(): string
    {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if (in_array(basename($dir), ['api', 'admin'], true)) {
            $dir = dirname($dir);
        }
        return rtrim(str_replace('\\', '/', $dir), '/');
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
