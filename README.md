# Pixela CMS

Sistema web autónomo y administrable para **Pixela**, un estudio digital (diseño web, branding, redes y sistemas a medida). Tiene dos partes que usan la misma API:

- **Web pública:** se arma sola leyendo de la base de datos los colores, el logo, el menú y los bloques de contenido.
- **Panel admin:** permite cambiar todo eso sin tocar código. Los cambios se ven en la web al instante.

---

## 📦 Instalación paso a paso

Para probar el proyecto basta con instalar **XAMPP**. No hace falta Composer, Node ni importar nada a mano.

### Requisitos

- **XAMPP** con **PHP 8.0 o superior** (se descarga gratis en [apachefriends.org](https://www.apachefriends.org/es/index.html)).
- Un navegador (Chrome, Edge, Opera, Firefox).

### Paso 1. Descargar el proyecto

En esta página de GitHub, pulsa el botón verde **Code → Download ZIP**.

### Paso 2. Copiar la carpeta en XAMPP

1. Descomprime el ZIP.
2. Copia la carpeta del proyecto dentro de `C:\xampp\htdocs\`.

> ⚠ **Ojo:** al descomprimir, Windows a veces crea una carpeta dentro de otra (`pixela-cms-main\pixela-cms-main`). Copia la de **adentro**, la que contiene el archivo `instalar.php`.

Debe quedar así:

```
C:\xampp\htdocs\pixela-cms-main\
    ├── admin\
    ├── api\
    ├── instalar.php
    └── ...
```

La carpeta puede llamarse como quieras (`pixela-cms`, `pixela-cms-main`, etc.): el sistema se adapta solo.

### Paso 3. Encender XAMPP

Abre el **XAMPP Control Panel** y pulsa **Start** en **Apache** y en **MySQL**. Los dos deben quedar en verde.

### Paso 4. Ejecutar el instalador

Abre en el navegador (cambia el nombre si tu carpeta se llama distinto):

```
http://localhost/pixela-cms-main/instalar.php
```

El instalador revisa que todo esté bien. Pulsa **Instalar ahora**. Crea la base de datos con contenido de ejemplo y el archivo de configuración `.env` con una clave segura.

### Paso 5. ¡Listo!

| Qué | Dirección |
|---|---|
| 🌐 Web pública | `http://localhost/pixela-cms-main/` |
| 🔐 Panel admin | `http://localhost/pixela-cms-main/admin/` (o el botón **Ingresar** de la web) |
| ⚙ API | `http://localhost/pixela-cms-main/api/` |

**Usuario de prueba:**

| Email | Contraseña |
|---|---|
| `admin@pixela.pe` | `Pixela2026!` |

### Si algo falla

| Problema | Solución |
|---|---|
| Sale **Not Found** | La carpeta quedó una dentro de otra. Revisa el Paso 2: `instalar.php` debe estar directamente dentro de la carpeta. |
| **Error de conexión** a la base de datos | MySQL está apagado. Enciéndelo en XAMPP y vuelve a abrir `instalar.php`. |
| Mi MySQL **tiene contraseña** | En el instalador abre **"Cambiar datos de conexión"** y escribe tu usuario y contraseña. |
| El instalador avisa que falta **GD** | Igual se instala, pero para subir imágenes hay que activarla: en XAMPP, pulsa **Config → PHP (php.ini)**, busca `;extension=gd`, borra el `;` del inicio, guarda y reinicia Apache. |
| Los cambios no se ven en la web | Recarga sin caché con **Ctrl + F5**. |
| Quiero **empezar de cero** | Abre `instalar.php`, marca **"Quiero reinstalar y borrar los datos actuales"** e instala de nuevo. |

> **Para quien comparte el proyecto:** manda siempre el ZIP de GitHub. Si comprimes tu propia carpeta de `htdocs`, borra antes el archivo `.env`. Si no lo borras, el sistema creerá que ya está instalado y en la otra PC no encontrará la base de datos.

---

## 🧭 Guía del panel de administración

### Entrar al panel

En la web pública pulsa **Ingresar** (arriba a la derecha; en el celular está dentro del menú ☰) o el enlace **Acceso al panel** del pie de página. Escribe el email y la contraseña de prueba.

Al entrar verás la **guía rápida "¿Por dónde empiezo?"** con 4 pasos y un botón para ir a cada uno. La sesión dura **8 horas**. Abajo del menú lateral ves cuánto tiempo te queda. Al terminar, pulsa **Cerrar sesión**.

### Las secciones del panel

| Sección | Para qué sirve |
|---|---|
| **Dashboard** | Resumen: cuántas páginas, bloques, ítems de menú y mensajes sin leer hay, más una vista previa de los colores actuales. |
| **Páginas y bloques** | Crear páginas y armar su contenido con bloques. |
| **Menú** | Ordenar el menú de la web y crear submenús. |
| **Imágenes** | Subir fotos para usarlas en los bloques, el logo o el favicon. |
| **Mensajes** | Leer lo que llega desde el formulario de contacto. |
| **Colores y logo** | Cambiar la identidad visual completa de la web (solo el rol **admin**). |

### 🎨 Colores y logo

Cada uno de los 5 colores tiene un selector, un campo HEX (por ejemplo `#8B5CF6`) y debajo del nombre dice en qué parte de la web se usa. A la derecha hay una **vista previa en vivo**, y con **Guardar cambios** se aplica en toda la web.

| Color | Valor original | Dónde se usa en la web |
|---|---|---|
| **Primario** | `#8B5CF6` (morado) | Sombra de los botones y tarjetas, contorno de la cabecera, brillo del logo, resaltado de los títulos, tarjetas de planes y preguntas frecuentes, nebulosa del fondo. |
| **Secundario** | `#2563EB` (azul) | Segunda nebulosa del fondo y tonos pastel de algunas tarjetas. |
| **Acento** | `#C8FF3D` (verde lima) | Botones principales, cinta animada, opción activa del menú, plan destacado, íconos ✔ y títulos del pie de página. |
| **Fondo** | `#0B0B14` (casi negro) | Color de fondo de toda la web. |
| **Texto** | `#F5F5FA` (blanco) | Letras, bordes de las tarjetas y el ícono del menú. |

Los tonos pastel de las tarjetas se calculan solos a partir del primario, el secundario y el acento, así que también cambian con la paleta.

> 💡 El **fondo** y el **texto** deben contrastar (uno claro y el otro oscuro) para que todo se lea bien. Si algo no te gusta, el botón **Restaurar Pixela** devuelve los colores originales; luego guarda.

En la misma sección también se cambian el **nombre del sitio**, el **eslogan**, el **logo**, el **favicon** (el icono de la pestaña), el **WhatsApp**, el **email**, la **dirección** y las **redes sociales**. El WhatsApp se usa en el botón flotante y en los botones "Cotiza gratis" y "La quiero".

### 📄 Páginas y bloques

Cada página de la web está formada por **bloques** que se apilan en orden (portada, servicios, precios, etc.).

1. En **Páginas y bloques** pulsa **Nueva página**, o el botón **Contenido** de una página existente.
2. Dentro de la página pulsa **Agregar bloque** y elige el tipo.
3. Completa el formulario que aparece en el panel lateral y pulsa **Guardar cambios**. En los campos de enlace aparece una lista con las páginas del sitio para elegir.

Botones de cada bloque:

| Botón | Qué hace |
|---|---|
| Interruptor | Muestra u oculta el bloque en la web, sin borrarlo. |
| ↑ / ↓ | Sube o baja el bloque en la página. |
| ✏ Editar | Abre el formulario del bloque. La pestaña **Avanzado (JSON)** es solo para usuarios avanzados. |
| 🗑 Eliminar | Borra el bloque (pide confirmación). |

Tipos de bloque disponibles:

| Bloque | Qué muestra |
|---|---|
| **Portada** | Lo primero que se ve: título grande, texto, botones e imagen. |
| **Servicios** | Tarjetas con icono, título y descripción. |
| **Portafolio** | Trabajos realizados con imagen. |
| **Plantillas** | Catálogo con precio, imagen y botones "Ver demo" y "La quiero". |
| **Planes / Precios** | Paquetes con precio y lo que incluyen. Uno se puede marcar como destacado. |
| **Proceso** | Pasos numerados. |
| **Testimonios** | Opiniones de clientes. |
| **Preguntas frecuentes** | Acordeón de preguntas y respuestas. |
| **Contacto** | Formulario, WhatsApp y mapa. |
| **Texto** | Título y párrafos libres. |
| **Galería** | Varias imágenes en cuadrícula. |
| **CTA** | Franja final con un llamado a la acción y un botón. |

En la lista de páginas también puedes **publicar u ocultar** una página entera. La página de Inicio no se puede borrar ni ocultar.

### 🧩 Menú

- **Nuevo ítem:** elige si apunta a una **página** del sistema o a un **enlace** (por ejemplo `/servicios#planes` o una URL externa).
- **Submenú:** en "Ubicación" elige **Submenú de "…"** para que quede dentro de otro ítem.
- **Ordenar:** usa ↑ y ↓.
- **Ocultar:** apaga el interruptor y el ítem desaparece del menú de la web.

Si borras una página que estaba en el menú, su ítem queda marcado con ⚠ y la web lo oculta hasta que lo corrijas.

### 🖼 Imágenes

Arrastra las fotos al recuadro o haz clic para elegirlas (JPG, PNG, WEBP o GIF, máximo 5 MB). El sistema crea versiones de 480, 768 y 1280 px para que la web cargue rápido en el celular. A cada imagen se le puede poner un **texto alternativo**.

Después, en cualquier bloque con campo "Imagen" (o en Colores y logo), pulsa **Elegir** y selecciona la foto de la biblioteca.

### ✉ Mensajes

Aquí llega lo que la gente envía desde el formulario de **Contacto**. Puedes marcar cada mensaje como leído o borrarlo. El Dashboard muestra cuántos faltan leer.

### 👥 Roles

| Rol | Puede |
|---|---|
| **admin** | Todo, incluida la sección Colores y logo. |
| **editor** | Gestionar páginas, bloques, menú, imágenes y mensajes, pero **no** los colores ni el logo. |

La instalación trae el usuario **admin**. El rol editor ya está listo en el sistema y en la API.

---

## 🧪 Plantillas de ejemplo

La página **Plantillas** trae 8 plantillas por rubro, cada una con una **demo funcionando**: barbería, restaurante, servicios del hogar, consultorio, tienda de ropa, gimnasio, veterinaria y academia. Las demos están en `public/demos/` y se abren con el botón **Ver demo**.

---

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

**Framework de frontend:** Bootstrap 5.3, incluido en `public/assets/lib/bootstrap/` (funciona sin internet). Se usa en la web pública y en el panel:

| Componente de Bootstrap | Dónde se usa |
|---|---|
| Grilla (`container`, `row`, `col-*`, `row-cols-*`, `g-*`) | Todas las secciones de la web, el dashboard, la configuración y el login |
| Navbar + Collapse + Dropdown | Menú de la web con submenús y botón ☰ en celular |
| Accordion | Preguntas frecuentes |
| Formularios (`form-control`, `form-select`, `form-label`) | Formulario de contacto y formularios del panel |
| Offcanvas | Menú lateral del panel en celular y panel lateral de edición |
| Modal | Selector de imágenes |
| Toast | Avisos del panel (guardado, errores) |
| Table + `table-responsive` | Tablas del panel con scroll interno |
| Utilidades (`d-flex`, `gap-*`, `order-*`, `h-100`…) | Alineaciones y orden en celular |


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
| Tailwind (framework CSS) con breakpoints | **Bootstrap 5.3** (framework CSS/JS) con sus breakpoints `sm` / `md` / `lg` / `xl`, más una hoja de estilos propia con el diseño de Pixela |

## Estructura

```
pixela-cms/
├── index.php        web pública (arma cada página desde la BD)
├── public/
│   ├── plantilla.php   cabecera, menú, pie y colores del panel
│   ├── bloques/        un archivo por tipo de bloque (hero, servicios, planes...)
│   ├── demos/          demos de las plantillas por rubro
│   └── assets/         CSS, JS, imágenes y lib/bootstrap (framework)
├── admin/           panel de control
│   ├── login.php       inicio de sesión (pantalla dividida)
│   ├── index.php       panel protegido en el servidor (valida el JWT)
│   └── assets/         estilos y lógica del panel
├── api/             API REST (punto de entrada: api/index.php)
│   ├── config/  core/  controllers/  services/  middleware/
├── uploads/         imágenes subidas desde el panel (sin ejecución de PHP)
├── database/        script SQL (pixela_cms.sql)
├── docs/            DER, tabla de entidades, colección de Postman e informes
├── vendor/          librería JWT (incluida, no requiere Composer)
├── instalar.php     instalador web
└── .env.example     plantilla de variables de entorno
```

## Cómo funciona la administrabilidad

1. En **Panel → Colores y logo** el admin elige los colores (HEX), el logo y los datos de contacto. Se guardan en `site_config`.
2. Al abrir la web, `index.php` lee `site_config` y escribe en el `<head>`:
   ```html
   <style>:root{--color-primario:#8B5CF6;--color-secundario:#2563EB;--color-acento:#C8FF3D;...}</style>
   ```
3. Todo el CSS de la web usa esas variables (`var(--color-primario)`), incluso los tonos pastel de las tarjetas, que se calculan con `color-mix()`. Por eso, al cambiar un color en el panel, cambia toda la web sin tocar código.
4. Lo mismo pasa con el **menú** (`menu_items`, con orden y submenús) y con los **bloques** de cada página (`page_components`, con su contenido en JSON, orden y visibilidad).

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
