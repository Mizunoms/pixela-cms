<?php
// Números del dashboard del panel

class ResumenController
{
    // GET /admin/resumen
    public function obtener(array $params, $usuario): void
    {
        $db = Database::conectar();
        $n = fn(string $sql) => (int) $db->query($sql)->fetchColumn();

        Response::ok([
            'paginas'           => $n('SELECT COUNT(*) FROM paginas'),
            'paginas_publicadas'=> $n('SELECT COUNT(*) FROM paginas WHERE publicada = 1'),
            'bloques'           => $n('SELECT COUNT(*) FROM page_components'),
            'bloques_visibles'  => $n('SELECT COUNT(*) FROM page_components WHERE visible = 1'),
            'menu'              => $n('SELECT COUNT(*) FROM menu_items'),
            'submenus'          => $n('SELECT COUNT(*) FROM menu_items WHERE parent_id IS NOT NULL'),
            'imagenes'          => $n('SELECT COUNT(*) FROM media'),
            'variantes'         => $n('SELECT COUNT(*) FROM media_variantes'),
            'mensajes'          => $n('SELECT COUNT(*) FROM mensajes_contacto'),
            'mensajes_sin_leer' => $n('SELECT COUNT(*) FROM mensajes_contacto WHERE leido = 0'),
        ]);
    }
}
