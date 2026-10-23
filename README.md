# Pixela CMS

Sistema web autónomo y administrable para **Pixela**, un estudio digital (diseño web, branding, redes y sistemas a medida). Tiene dos partes que usan la misma API:

- **Web pública:** se arma sola leyendo de la base de datos los colores, el logo, el menú y los bloques de contenido.
- **Panel admin:** permite cambiar todo eso sin tocar código. Los cambios se ven en la web al instante.

## ▶ Cómo probarlo (3 pasos)

Solo se necesita **XAMPP** (con PHP 8 o superior). No hay que instalar nada más.

1. **Descargar:** botón verde **Code → Download ZIP**. Descomprimir y copiar la carpeta dentro de `C:\xampp\htdocs\`.
   > ⚠ Al descomprimir, Windows a veces crea una carpeta dentro de otra (`pixela-cms-main\pixela-cms-main`). Copia la de **adentro**, la que contiene `instalar.php`.
2. **Encender XAMPP:** abrir el panel de XAMPP y dar **Start** a **Apache** y **MySQL**.
3. **Instalar:** abrir en el navegador `http://localhost/NOMBRE-DE-LA-CARPETA/instalar.php` (por ejemplo `http://localhost/pixela-cms-main/instalar.php`) y pulsar **Instalar ahora**.

El instalador crea la base de datos (con contenido de ejemplo) y la configuración automáticamente.

| Qué | Dirección |
|---|---|
| Web pública | `http://localhost/NOMBRE-DE-LA-CARPETA/` |
| Panel admin | `http://localhost/NOMBRE-DE-LA-CARPETA/admin/` |
| API | `http://localhost/NOMBRE-DE-LA-CARPETA/api/` |

**Usuario de prueba:** `admin@pixela.pe` / `Pixela2026!`

## Avance

| Semana | Hito | Estado |
|---|---|---|
| 1 | Cimientos: DER, servidor básico y endpoints de autenticación | ✅ Completado |
| 2 | Motor: CRUD de bloques, subida de imágenes, configuración de colores | ✅ Completado |
| 3 | Panel admin responsive: selector de colores, gestor de menú, editor de bloques | ✅ Completado |
| 4 | Web pública con variables CSS dinámicas, bloques adaptativos y QA responsive | ✅ Completado |

## Informes semanales

| Semana | Informe |
|---|---|
| 1 | [Cimientos](docs/informes/Informe_Semana_1_Cimientos.docx) |
| 2 | [El Motor](docs/informes/Informe_Semana_2_Motor.docx) |
| 3 | [El Panel](docs/informes/Informe_Semana_3_Panel.docx) |
| 4 | [La Vitrina y Cierre](docs/informes/Informe_Semana_4_Vitrina_y_Cierre.docx) |

## Stack

La guía marca las tecnologías como opcionales. Se usa PHP + MySQL, cumpliendo los mismos requisitos:

| Requisito de la guía | Implementación en este proyecto |
|---|---|
| PostgreSQL con JSONB | MySQL / MariaDB con columna `JSON` (validada con `JSON_VALID`) |
| Tablas `site_config` y `page_components` | Incluidas, con el contenido de cada bloque en JSON |
| API REST con patrón controlador-servicio | PHP 8 con router propio: `controllers/` → `services/` |
| JWT en cookie httpOnly | `firebase/php-jwt` v7 + cookie `HttpOnly`, `SameSite=Strict` |
| Middleware de roles | `AuthMiddleware` (roles `admin` y `editor`) |
| `sharp` para imágenes | Librería GD de PHP (versiones 480 / 768 / 1280 px en WEBP) |
| Componentes de servidor (Next.js) | La web pública se arma en el servidor con PHP en cada visita (`index.php` + `public/bloques/`) |
| Componentes de cliente (Next.js) | El panel consume la API con `fetch` y actualiza solo la sección que cambia, sin recargar la página |
| Tailwind con breakpoints | CSS propio *mobile-first* con breakpoints 640 / 768 / 1024 px (equivalentes a `sm` / `md` / `lg`) |

## Estructura

```
pixela-cms/
├── index.php        web pública (arma cada página desde la BD)
├── public/
│   ├── plantilla.php   cabecera, menú, pie y colores del panel
│   ├── bloques/        un archivo por tipo de bloque (hero, servicios, planes...)
│   └── assets/         CSS, JS e imágenes de la marca
├── admin/           panel de control
│   ├── login.php       inicio de sesión (pantalla dividida)
│   ├── index.php       panel protegido en el servidor (valida el JWT)
│   └── assets/         estilos y lógica del panel
├── api/             API REST (punto de entrada: api/index.php)
│   ├── config/  core/  controllers/  services/  middleware/
├── uploads/         imágenes procesadas (sin ejecución de PHP)
├── database/        script SQL (pixela_cms.sql)
├── docs/            DER, tabla de entidades y colección de Postman
├── vendor/          librería JWT (incluida, no requiere Composer)
├── instalar.php     instalador web
└── .env.example
```

## Cómo funciona la administrabilidad

1. En **Panel → Colores y logo** el admin elige los colores (HEX), el logo y los datos de contacto. Se guardan en `site_config`.
2. Al abrir la web, `index.php` lee `site_config` y escribe en el `<head>`:
   ```html
   <style>:root{--color-primario:#8B5CF6;--color-secundario:#2563EB;--color-acento:#C8FF3D;...}</style>
   ```
3. Todo el CSS de la web usa esas variables (`var(--color-primario)`), incluso los tonos pastel de las tarjetas, que se calculan con `color-mix()`. Por eso, al cambiar un color en el panel, cambia toda la web sin tocar código.
4. Lo mismo pasa con el **menú** (`menu_items`, con orden y submenús) y con los **bloques** de cada página (`page_components`, con su contenido en JSON, orden y visibilidad).

## Panel admin

- **Login** en pantalla dividida, con el token JWT guardado en una cookie httpOnly.
- **Dashboard** con totales, páginas y vista previa de los colores.
- **Páginas y bloques:** crear, publicar, reordenar (↑ ↓), ocultar y editar cada bloque con un formulario según su tipo o directamente en JSON.
- **Menú:** ítems hacia páginas o enlaces, submenús, orden y visibilidad.
- **Imágenes:** subir arrastrando, texto alternativo y versiones responsive.
- **Mensajes** del formulario de contacto.
- **Colores y logo** (solo admin), con vista previa en vivo.
- **Responsive:** el menú lateral se colapsa en celular y las tablas tienen scroll interno.

## Base de datos

9 tablas en 3FN. Lo estructural (usuarios, roles, páginas, menú, medios) va normalizado; solo el contenido variable de cada bloque va en JSON, lo que permite agregar tipos de bloque sin cambiar el esquema.

Diagrama: [`docs/DER_pixela_cms.pdf`](docs/DER_pixela_cms.pdf)

## Endpoints

Base: `http://localhost/NOMBRE-DE-LA-CARPETA/api` · Todas las respuestas son JSON: `{ "ok": true, "mensaje": "...", "data": ... }` o `{ "ok": false, "error": "..." }`.

**Roles:** `admin` puede todo; `editor` gestiona el contenido (páginas, bloques, menú, imágenes y mensajes), pero no la configuración global.

### Público (lo consume la web)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/` | Estado de la API y la base de datos |
| GET | `/config` | Nombre, logo, colores HEX, `css_variables`, contacto y redes |
| GET | `/menu` | Árbol del menú visible, con submenús |
| GET | `/inicio` | Página de inicio con sus bloques visibles en orden |
| GET | `/paginas/{slug}` | Cualquier página publicada con sus bloques visibles |
| POST | `/contacto` | Formulario de contacto (guarda el mensaje) |

### Autenticación

| Método | Ruta | Acceso | Descripción |
|---|---|---|---|
| POST | `/auth/login` | Público | Inicia sesión; guarda el JWT en cookie httpOnly |
| POST | `/auth/logout` | Público | Cierra sesión |
| GET | `/auth/me` | Logueado | Datos del usuario actual |

### Panel admin

| Método | Ruta | Acceso | Descripción |
|---|---|---|---|
| GET | `/admin/resumen` | Editor | Totales para el dashboard |
| PUT | `/admin/config` | Admin | Cambia colores, logo, nombre, WhatsApp y redes (parcial) |
| GET / POST | `/admin/paginas` | Editor | Lista o crea páginas |
| GET / PUT / DELETE | `/admin/paginas/{id}` | Editor | Ver (con bloques ocultos), editar o borrar una página |
| GET | `/admin/bloques/tipos` | Editor | Tipos de bloque disponibles |
| GET / POST | `/admin/paginas/{id}/bloques` | Editor | Lista o crea bloques `{ tipo, contenido: {...}, visible }` |
| PUT | `/admin/paginas/{id}/bloques/orden` | Editor | Reordena: `{ "ids": [3, 1, 2] }` |
| GET / PUT / DELETE | `/admin/bloques/{id}` | Editor | Ver, editar o borrar un bloque |
| GET / POST | `/admin/menu` | Editor | Menú completo o nuevo ítem |
| PUT | `/admin/menu/orden` | Editor | Reordena y mueve entre padres |
| PUT / DELETE | `/admin/menu/{id}` | Editor | Edita o borra un ítem |
| GET / POST | `/admin/media` | Editor | Biblioteca o subida de imágenes (`multipart/form-data`, campo `imagen`) |
| PUT / DELETE | `/admin/media/{id}` | Editor | Texto alternativo o borrado |
| GET | `/admin/mensajes` | Editor | Mensajes del formulario |
| PUT / DELETE | `/admin/mensajes/{id}` | Editor | Marcar leído o borrar |

### Probar con Postman

Importa [`docs/pixela-cms.postman_collection.json`](docs/pixela-cms.postman_collection.json) en Postman (**Import → archivo**). Ejecuta primero **1. Auth → Login admin**: Postman guarda la cookie y la usa en el resto. Trae 45 peticiones con pruebas automáticas (casos correctos y de error).

## Seguridad

- Contraseñas con `password_hash` (bcrypt).
- JWT firmado en cookie `HttpOnly` y `SameSite=Strict` (no accesible desde JavaScript).
- **Doble protección del panel:** `admin/index.php` valida el JWT en el servidor antes de mostrar nada, y cada endpoint de la API lo vuelve a validar junto con el rol.
- Consultas con PDO y sentencias preparadas.
- Validación de todos los datos de entrada (colores HEX, slugs, URLs, tipos de bloque, JSON, emails).
- Todo lo que se escribe en el panel se escapa antes de mostrarse (protección contra XSS).
- Imágenes validadas por su contenido real (no por la extensión), y la carpeta `uploads/` no ejecuta PHP.
- Archivos internos (`.env`, `vendor/`, `database/`, `api/config/`, `public/*.php`, `admin/_*.php`) bloqueados por `.htaccess`.
- El formulario de contacto tiene un campo trampa contra bots.
