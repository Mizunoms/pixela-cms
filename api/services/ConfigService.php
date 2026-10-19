<?php
// Configuración global del sitio (tabla site_config, una sola fila): nombre, logo, colores y contacto.

class ConfigService
{
    private const COLORES = ['color_primario', 'color_secundario', 'color_acento', 'color_fondo', 'color_texto'];
    private const REDES   = ['facebook', 'instagram', 'tiktok', 'linkedin', 'youtube'];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    public function obtener(): array
    {
        $c = $this->db->query('SELECT * FROM site_config WHERE id = 1')->fetch();
        if (!$c) {
            throw new ApiException('La configuración del sitio no existe. Ejecuta el instalador.', 500);
        }

        $medios = (new MediaService())->variantesDe([$c['logo_id'], $c['favicon_id']]);
        $colores = [];
        foreach (self::COLORES as $campo) {
            $colores[substr($campo, 6)] = $c[$campo];   // color_primario -> primario
        }

        return [
            'nombre_sitio'   => $c['nombre_sitio'],
            'eslogan'        => $c['eslogan'],
            'logo'           => $c['logo_id'] ? ($medios[$c['logo_id']] ?? null) : null,
            'favicon'        => $c['favicon_id'] ? ($medios[$c['favicon_id']] ?? null) : null,
            'colores'        => $colores,
            // listo para pegar en <style> del front: :root { --color-primario: ... }
            'css_variables'  => ':root{' . implode('', array_map(fn($k, $v) => "--color-$k:$v;", array_keys($colores), $colores)) . '}',
            'whatsapp'       => $c['whatsapp'],
            'email_contacto' => $c['email_contacto'],
            'direccion'      => $c['direccion'],
            'redes_sociales' => json_decode($c['redes_sociales'] ?? '{}', true) ?: (object) [],
            'updated_at'     => $c['updated_at'],
        ];
    }

    // Actualización parcial: solo cambia los campos que llegan
    public function actualizar(array $d): array
    {
        $cambios = [];

        if (array_key_exists('nombre_sitio', $d))   $cambios['nombre_sitio']   = Validar::texto($d['nombre_sitio'], 'nombre_sitio', 100);
        if (array_key_exists('eslogan', $d))        $cambios['eslogan']        = Validar::texto($d['eslogan'], 'eslogan', 200, false);
        if (array_key_exists('direccion', $d))      $cambios['direccion']      = Validar::texto($d['direccion'], 'direccion', 200, false);
        if (array_key_exists('email_contacto', $d)) $cambios['email_contacto'] = Validar::email($d['email_contacto'], 'email_contacto', false);

        if (array_key_exists('whatsapp', $d)) {
            $wa = preg_replace('/[\s+\-()]/', '', (string) ($d['whatsapp'] ?? ''));
            if ($wa !== '' && !preg_match('/^\d{9,15}$/', $wa)) {
                throw new ApiException("El 'whatsapp' debe tener de 9 a 15 dígitos con código de país (ej. 51987654321)", 422);
            }
            $cambios['whatsapp'] = $wa ?: null;
        }

        // colores: acepta { "colores": { "primario": "#..." } } o los campos sueltos color_primario
        $colores = is_array($d['colores'] ?? null) ? $d['colores'] : [];
        foreach (self::COLORES as $campo) {
            $corto = substr($campo, 6);
            $valor = $colores[$corto] ?? $d[$campo] ?? null;
            if ($valor !== null) {
                $cambios[$campo] = Validar::hex($valor, $campo);
            }
        }

        $media = new MediaService();
        foreach (['logo_id', 'favicon_id'] as $campo) {
            if (array_key_exists($campo, $d)) {
                $id = Validar::idOpcional($d[$campo], $campo);
                if ($id !== null && !$media->existe($id)) {
                    throw new ApiException("No existe una imagen con id $id para '$campo'", 422);
                }
                $cambios[$campo] = $id;
            }
        }

        if (array_key_exists('redes_sociales', $d)) {
            if (!is_array($d['redes_sociales'])) {
                throw new ApiException("'redes_sociales' debe ser un objeto, ej. {\"instagram\": \"https://...\"}", 422);
            }
            $redes = [];
            foreach (self::REDES as $red) {
                $url = trim((string) ($d['redes_sociales'][$red] ?? ''));
                if ($url !== '' && !(filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url))) {
                    throw new ApiException("La URL de '$red' no es válida (debe empezar con https://)", 422);
                }
                $redes[$red] = $url;
            }
            $cambios['redes_sociales'] = json_encode($redes, JSON_UNESCAPED_SLASHES);
        }

        if (!$cambios) {
            throw new ApiException('No se envió ningún campo para actualizar', 422);
        }

        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($cambios)));
        $this->db->prepare("UPDATE site_config SET $set WHERE id = 1")->execute(array_values($cambios));

        return $this->obtener();
    }
}
