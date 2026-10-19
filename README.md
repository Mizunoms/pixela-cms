# Pixela CMS

Sistema web autónomo y administrable para **Pixela**, un estudio digital (diseño web, branding, redes y sistemas a medida). Tiene dos partes que consumen una misma API:

- **Web pública:** se arma sola leyendo de la base de datos los colores, el logo, el menú y los bloques de contenido.
- **Panel admin:** permite cambiar todo eso sin tocar código.

## ▶ Cómo probarlo (3 pasos)

Solo se necesita **XAMPP** (con PHP 8 o superior). No hay que instalar nada más.

1. **Descargar:** botón verde **Code → Download ZIP**. Descomprimir y copiar la carpeta dentro de `C:\xampp\htdocs\`.
   > ⚠ Al descomprimir, Windows a veces crea una carpeta dentro de otra (`pixela-cms-main\pixela-cms-main`). Copia la de **adentro**, la que contiene `instalar.php`.
2. **Encender XAMPP:** abrir el panel de XAMPP y dar **Start** a **Apache** y **MySQL**.
3. **Instalar:** abrir en el navegador `http://localhost/NOMBRE-DE-LA-CARPETA/instalar.php` (por ejemplo `http://localhost/pixela-cms-main/instalar.php`) y pulsar **Instalar ahora**.

El instalador crea la base de datos y la configuración automáticamente.

**Usuario de prueba:** `admin@pixela.pe` / `Pixela2026!`

## Avance

| Semana | Hito | Estado |
|---|---|---|
| 1 | Cimientos: DER, servidor básico y endpoints de autenticación | ✅ Completado |
| 2 | Motor: CRUD de bloques, subida de imágenes, configuración de colores | ✅ Completado |
| 3 | Panel admin responsive | ⏳ Siguiente |
| 4 | Web pública con variables CSS dinámicas y QA responsive | Pendiente |

## Stack

La guía marca las tecnologías como opcionales. Se usa PHP + MySQL, cumpliendo los mismos requisitos:

| Requisito de la guía | Implementación en este proyecto |
|---|---|
| PostgreSQL con JSONB | MySQL / MariaDB con columna `JSON` (validada con `JSON_VALID`) |
| Tablas `site_config` y `page_components` | Incluidas, con el contenido de cada bloque en JSON |
| API REST con patrón controlador-servicio | PHP 8 con router propio: `controllers/` → `services/` |
| JWT en cookie httpOnly | `firebase/php-jwt` v7 + cookie `HttpOnly`, `SameSite=Strict` |
| Middleware de roles | `AuthMiddleware` (roles `admin` y `editor`) |
| `sharp` para imágenes | Librería GD de PHP (versiones 480 / 768 / 1280 px en webp) |

## Estructura

```
pixela-cms/
├── api/            API REST (punto de entrada: index.php)
│   ├── config/     conexión a BD y lectura del .env
│   ├── core/       router, respuestas JSON, validaciones y errores
│   ├── controllers/
│   ├── services/   lógica de negocio
│   └── middleware/ validación de JWT y roles
├── admin/          panel de control (semana 3)
├── public/         web pública (semana 4)
├── uploads/        imágenes procesadas (sin ejecución de PHP)
├── database/       script SQL (pixela_cms.sql)
├── docs/           DER, tabla de entidades y colección de Postman
├── vendor/         librería JWT (incluida, no requiere Composer)
├── instalar.php    instalador web
├── .env.example
└── composer.json
```

## Base de datos

9 tablas en 3FN. Lo estructural (usuarios, roles, páginas, menú, medios) va normalizado; solo el contenido variable de cada bloque va en JSON, lo que permite agregar tipos de bloque sin cambiar el esquema.

Diagrama: [`docs/DER_pixela_cms.pdf`](docs/DER_pixela_cms.pdf)

## Endpoints

Base: `http://localhost/NOMBRE-DE-LA-CARPETA/api` · Todas las respuestas son JSON: `{ "ok": true, "mensaje": "...", "data": ... }` o `{ "ok": false, "error": "..." }`.

**Roles:** `admin` puede todo; `editor` gestiona contenido (páginas, bloques, menú e imágenes) pero no la configuración global.

### Público (lo consume la web)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/` | Estado de la API y la base de datos |
| GET | `/config` | Nombre, logo, colores HEX, `css_variables` listas para el front, contacto y redes |
| GET | `/menu` | Árbol del menú visible, con submenús |
| GET | `/inicio` | Página de inicio con sus bloques visibles en orden |
| GET | `/paginas/{slug}` | Cualquier página publicada con sus bloques visibles |

### Autenticación

| Método | Ruta | Acceso | Descripción |
|---|---|---|---|
| POST | `/auth/login` | Público | Inicia sesión; guarda el JWT en cookie httpOnly |
| POST | `/auth/logout` | Público | Cierra sesión |
| GET | `/auth/me` | Logueado | Datos del usuario actual |

### Panel admin

| Método | Ruta | Acceso | Descripción |
|---|---|---|---|
| PUT | `/admin/config` | Admin | Cambia colores, logo, nombre, WhatsApp, redes (parcial) |
| GET | `/admin/paginas` | Editor | Lista páginas con su cantidad de bloques |
| POST | `/admin/paginas` | Editor | Crea página (el slug se genera del título si no se envía) |
| GET | `/admin/paginas/{id}` | Editor | Página con todos sus bloques, incluidos los ocultos |
| PUT | `/admin/paginas/{id}` | Editor | Edita título, slug, publicada o la marca como inicio |
| DELETE | `/admin/paginas/{id}` | Editor | Borra la página y sus bloques (no permite borrar el inicio) |
| GET | `/admin/bloques/tipos` | Editor | Tipos de bloque disponibles |
| GET | `/admin/paginas/{id}/bloques` | Editor | Bloques de una página en orden |
| POST | `/admin/paginas/{id}/bloques` | Editor | Crea bloque `{ tipo, contenido: {...}, visible }` |
| PUT | `/admin/paginas/{id}/bloques/orden` | Editor | Reordena: `{ "ids": [3, 1, 2] }` |
| GET / PUT / DELETE | `/admin/bloques/{id}` | Editor | Ver, editar contenido/visibilidad o borrar un bloque |
| GET | `/admin/menu` | Editor | Menú completo, incluidos ocultos |
| POST | `/admin/menu` | Editor | Crea ítem: destino `pagina_id` o `url_externa`, `parent_id` para submenú |
| PUT | `/admin/menu/orden` | Editor | Reordena y mueve entre padres: `{ "items": [{ id, parent_id, orden }] }` |
| PUT / DELETE | `/admin/menu/{id}` | Editor | Edita o borra un ítem (borra también sus submenús) |
| GET | `/admin/media` | Editor | Biblioteca de imágenes con variantes, `url` y `srcset` |
| POST | `/admin/media` | Editor | Sube imagen (`multipart/form-data`, campo `imagen`) |
| PUT / DELETE | `/admin/media/{id}` | Editor | Edita el texto alternativo o borra la imagen y sus archivos |

### Imágenes

Al subir una imagen (JPG, PNG, WEBP o GIF, máximo 5 MB) se generan versiones de **480, 768 y 1280 px en WEBP** con GD, conservando la transparencia. Si la imagen es más chica, se guarda a su tamaño. La respuesta trae un `srcset` listo para `<img srcset="...">`.

### Probar con Postman

Importa [`docs/pixela-cms.postman_collection.json`](docs/pixela-cms.postman_collection.json) en Postman (**Import → archivo**). Ejecuta primero **1. Auth → Login admin**: Postman guarda la cookie y la usa en el resto. Trae 39 peticiones con pruebas automáticas (casos correctos y de error).

## Seguridad

- Contraseñas con `password_hash` (bcrypt).
- JWT firmado en cookie `HttpOnly` (no accesible desde JavaScript).
- Consultas con PDO y sentencias preparadas.
- Validación de todos los datos de entrada (colores HEX, slugs, URLs, tipos de bloque, JSON).
- Imágenes validadas por su contenido real (no por la extensión) y carpeta `uploads/` sin ejecución de PHP.
- `.env`, `vendor/`, `config/`, `services/` y demás carpetas internas bloqueadas por `.htaccess`.
- Credenciales fuera del repositorio (`.env` en `.gitignore`).
