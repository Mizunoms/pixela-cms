<?php
// Router simple: método + ruta -> controlador, con middlewares opcionales

class Router
{
    private array $rutas = [];

    public function get(string $ruta, array $accion, array $middlewares = []): void
    {
        $this->agregar('GET', $ruta, $accion, $middlewares);
    }

    public function post(string $ruta, array $accion, array $middlewares = []): void
    {
        $this->agregar('POST', $ruta, $accion, $middlewares);
    }

    public function put(string $ruta, array $accion, array $middlewares = []): void
    {
        $this->agregar('PUT', $ruta, $accion, $middlewares);
    }

    public function delete(string $ruta, array $accion, array $middlewares = []): void
    {
        $this->agregar('DELETE', $ruta, $accion, $middlewares);
    }

    private function agregar(string $metodo, string $ruta, array $accion, array $middlewares): void
    {
        // Convierte /paginas/{id} en una expresión regular
        $ruta = rtrim($ruta, '/') ?: '/';
        $patron = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $ruta) . '$#';
        $this->rutas[] = compact('metodo', 'patron', 'accion', 'middlewares');
    }

    public function ejecutar(string $metodo, string $uri): void
    {
        // Base detectada sola (ej. /pixela-cms/api o /pixela-cms-main/api)
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $ruta = rawurldecode(parse_url($uri, PHP_URL_PATH));
        if (str_starts_with($ruta, $base)) {
            $ruta = substr($ruta, strlen($base));
        }
        $ruta = rtrim($ruta, '/') ?: '/';

        foreach ($this->rutas as $r) {
            if ($r['metodo'] !== $metodo || !preg_match($r['patron'], $ruta, $coincidencias)) {
                continue;
            }

            // Ejecuta middlewares; el resultado (ej. usuario autenticado) se pasa al controlador
            $usuario = null;
            foreach ($r['middlewares'] as $middleware) {
                $usuario = $middleware();
            }

            $params = array_filter($coincidencias, 'is_string', ARRAY_FILTER_USE_KEY);
            [$clase, $metodoControlador] = $r['accion'];
            (new $clase())->$metodoControlador($params, $usuario);
            return;
        }

        Response::error('Ruta no encontrada', 404);
    }
}
