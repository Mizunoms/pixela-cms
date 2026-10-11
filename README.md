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
| 2 | Motor: CRUD de bloques, subida de imágenes, configuración de colores | ⏳ En desarrollo |
| 3 | Panel admin responsive | Pendiente |
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
│   ├── core/       router y respuestas JSON
│   ├── controllers/
│   ├── services/   lógica de negocio
│   └── middleware/ validación de JWT y roles
├── admin/          panel de control (semana 3)
├── public/         web pública (semana 4)
├── uploads/        imágenes procesadas
├── database/       script SQL (pixela_cms.sql)
├── docs/           DER y tabla de entidades
├── vendor/         librería JWT (incluida, no requiere Composer)
├── instalar.php    instalador web
├── .env.example
└── composer.json
```

## Base de datos

9 tablas en 3FN. Lo estructural (usuarios, roles, páginas, menú, medios) va normalizado; solo el contenido variable de cada bloque va en JSON, lo que permite agregar tipos de bloque sin cambiar el esquema.

Diagrama: [`docs/DER_pixela_cms.pdf`](docs/DER_pixela_cms.pdf)

## Endpoints

Base: `http://localhost/NOMBRE-DE-LA-CARPETA/api`

| Método | Ruta | Acceso | Descripción |
|---|---|---|---|
| GET | `/` | Público | Estado de la API y la base de datos |
| POST | `/auth/login` | Público | Inicia sesión; guarda el JWT en cookie httpOnly |
| POST | `/auth/logout` | Público | Cierra sesión |
| GET | `/auth/me` | Logueado | Datos del usuario actual |
| GET | `/admin/ping` | Solo admin | Prueba de protección por rol |

Ejemplo de login:

```json
POST /auth/login
{ "email": "admin@pixela.pe", "password": "Pixela2026!" }
```

## Seguridad

- Contraseñas con `password_hash` (bcrypt).
- JWT firmado en cookie `HttpOnly` (no accesible desde JavaScript).
- Consultas con PDO y sentencias preparadas.
- `.env`, `vendor/`, `config/`, `services/` y demás carpetas internas bloqueadas por `.htaccess`.
- Credenciales fuera del repositorio (`.env` en `.gitignore`).
