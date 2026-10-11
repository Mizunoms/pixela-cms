-- =====================================================
-- PIXELA CMS - Base de datos
-- Motor: MySQL 5.7+ / MariaDB 10.2+ (XAMPP)
-- Modelo en 3FN + bloques modulares en JSON
-- =====================================================

CREATE DATABASE IF NOT EXISTS pixela_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pixela_cms;

-- Borra las tablas si ya existen (permite reimportar el script)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS mensajes_contacto, page_components, menu_items, paginas,
                     site_config, media_variantes, media, usuarios, roles;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------
-- 1. ROLES
-- -----------------------------------------------------
CREATE TABLE roles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(30)  NOT NULL UNIQUE,
    descripcion VARCHAR(150) NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 2. USUARIOS (login del panel)
-- -----------------------------------------------------
CREATE TABLE usuarios (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rol_id        INT UNSIGNED NOT NULL,
    nombre        VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activo        TINYINT(1)   NOT NULL DEFAULT 1,
    ultimo_login  DATETIME     NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 3. MEDIA (imagen original subida)
-- -----------------------------------------------------
CREATE TABLE media (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subido_por      INT UNSIGNED NULL,
    nombre_original VARCHAR(255) NOT NULL,
    alt_text        VARCHAR(255) NULL,
    mime            VARCHAR(50)  NOT NULL,
    ancho_original  INT UNSIGNED NOT NULL,
    alto_original   INT UNSIGNED NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_media_usuario FOREIGN KEY (subido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 4. MEDIA_VARIANTES (versiones responsive: 480/768/1280)
-- -----------------------------------------------------
CREATE TABLE media_variantes (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    media_id INT UNSIGNED NOT NULL,
    ancho    SMALLINT UNSIGNED NOT NULL,
    ruta     VARCHAR(255) NOT NULL,
    peso_kb  INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_media_ancho (media_id, ancho),
    CONSTRAINT fk_variantes_media FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 5. SITE_CONFIG (configuración global, una sola fila)
-- -----------------------------------------------------
CREATE TABLE site_config (
    id               TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    nombre_sitio     VARCHAR(100) NOT NULL,
    eslogan          VARCHAR(200) NULL,
    logo_id          INT UNSIGNED NULL,
    favicon_id       INT UNSIGNED NULL,
    color_primario   CHAR(7) NOT NULL DEFAULT '#7C3AED',
    color_secundario CHAR(7) NOT NULL DEFAULT '#2563EB',
    color_acento     CHAR(7) NOT NULL DEFAULT '#22D3EE',
    color_fondo      CHAR(7) NOT NULL DEFAULT '#0F0F1A',
    color_texto      CHAR(7) NOT NULL DEFAULT '#F5F5FA',
    whatsapp         VARCHAR(20)  NULL,
    email_contacto   VARCHAR(150) NULL,
    direccion        VARCHAR(200) NULL,
    redes_sociales   JSON NULL,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_una_fila CHECK (id = 1),
    CONSTRAINT fk_config_logo    FOREIGN KEY (logo_id)    REFERENCES media(id) ON DELETE SET NULL,
    CONSTRAINT fk_config_favicon FOREIGN KEY (favicon_id) REFERENCES media(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 6. PAGINAS
-- -----------------------------------------------------
CREATE TABLE paginas (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo           VARCHAR(120) NOT NULL,
    slug             VARCHAR(120) NOT NULL UNIQUE,
    meta_descripcion VARCHAR(255) NULL,
    es_inicio        TINYINT(1) NOT NULL DEFAULT 0,
    publicada        TINYINT(1) NOT NULL DEFAULT 1,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 7. MENU_ITEMS (con submenús y orden)
-- -----------------------------------------------------
CREATE TABLE menu_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id   INT UNSIGNED NULL,
    pagina_id   INT UNSIGNED NULL,
    etiqueta    VARCHAR(60)  NOT NULL,
    url_externa VARCHAR(255) NULL,
    orden       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    visible     TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_menu_padre  FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_menu_pagina FOREIGN KEY (pagina_id) REFERENCES paginas(id)    ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 8. PAGE_COMPONENTS (bloques modulares en JSON)
-- -----------------------------------------------------
CREATE TABLE page_components (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pagina_id  INT UNSIGNED NOT NULL,
    tipo       VARCHAR(40) NOT NULL,   -- hero, servicios, portafolio, plantillas, planes, proceso, testimonios, faq, contacto
    contenido  JSON NOT NULL,
    orden      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    visible    TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pagina_orden (pagina_id, orden),
    CONSTRAINT chk_contenido_json CHECK (JSON_VALID(contenido)),
    CONSTRAINT fk_componente_pagina FOREIGN KEY (pagina_id) REFERENCES paginas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 9. MENSAJES_CONTACTO
-- -----------------------------------------------------
CREATE TABLE mensajes_contacto (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(100) NOT NULL,
    email            VARCHAR(150) NOT NULL,
    telefono         VARCHAR(20)  NULL,
    servicio_interes VARCHAR(80)  NULL,
    mensaje          TEXT NOT NULL,
    leido            TINYINT(1) NOT NULL DEFAULT 0,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- =====================================================
-- DATOS INICIALES
-- =====================================================

INSERT INTO roles (nombre, descripcion) VALUES
('admin',  'Acceso total: configuración, usuarios y contenido'),
('editor', 'Solo puede editar páginas, bloques y medios');

-- Usuario admin de prueba -> email: admin@pixela.pe | clave: Pixela2026!  (CÁMBIALA)
INSERT INTO usuarios (rol_id, nombre, email, password_hash) VALUES
(1, 'Administrador', 'admin@pixela.pe', '$2y$10$bPq1G2IKQMFWfYus9nSMEuGIgxlhGIU6Y.vayUcxkciaByqhPU9ES');

INSERT INTO site_config (id, nombre_sitio, eslogan, whatsapp, email_contacto, direccion, redes_sociales) VALUES
(1, 'Pixela', 'Llevamos tu negocio a internet', '51999999999', 'contacto@pixela.pe', 'Lima, Perú',
 '{"facebook": "", "instagram": "", "tiktok": "", "linkedin": ""}');

INSERT INTO paginas (titulo, slug, meta_descripcion, es_inicio) VALUES
('Inicio',     'inicio',     'Estudio digital: webs, branding, redes y sistemas a medida', 1),
('Servicios',  'servicios',  'Todo lo que Pixela puede hacer por tu negocio', 0),
('Plantillas', 'plantillas', 'Webs listas por rubro para lanzar rápido', 0),
('Contacto',   'contacto',   'Cotiza tu proyecto con Pixela', 0);

INSERT INTO menu_items (parent_id, pagina_id, etiqueta, orden) VALUES
(NULL, 1, 'Inicio',     1),
(NULL, 2, 'Servicios',  2),
(NULL, 3, 'Plantillas', 3),
(NULL, 4, 'Contacto',   4);

-- Bloques de la página de inicio
INSERT INTO page_components (pagina_id, tipo, orden, contenido) VALUES
(1, 'hero', 1, '{
  "titulo": "Llevamos tu negocio a internet",
  "subtitulo": "Webs, branding, redes y sistemas a medida para negocios que quieren crecer.",
  "boton_texto": "Cotiza gratis",
  "boton_url": "/contacto",
  "imagen_id": null
}'),
(1, 'servicios', 2, '{
  "titulo": "¿Qué hacemos?",
  "items": [
    {"icono": "code",      "titulo": "Diseño web",          "descripcion": "Páginas rápidas, modernas y adaptadas a celular."},
    {"icono": "palette",   "titulo": "Branding",            "descripcion": "Logo, colores e identidad visual de tu marca."},
    {"icono": "share",     "titulo": "Redes sociales",      "descripcion": "Contenido y gestión de tus redes."},
    {"icono": "database",  "titulo": "Sistemas a medida",   "descripcion": "Inventarios, reservas y gestión interna."},
    {"icono": "server",    "titulo": "Hosting y dominio",   "descripcion": "Tu web siempre en línea con correo corporativo."},
    {"icono": "wrench",    "titulo": "Mantenimiento",       "descripcion": "Soporte y actualizaciones mensuales."}
  ]
}'),
(1, 'plantillas', 3, '{
  "titulo": "Plantillas listas por rubro",
  "items": [
    {"nombre": "Barbería",          "precio": 350, "imagen_id": null},
    {"nombre": "Restaurante",       "precio": 400, "imagen_id": null},
    {"nombre": "Servicios del hogar","precio": 350, "imagen_id": null}
  ]
}'),
(1, 'planes', 4, '{
  "titulo": "Paquetes",
  "items": [
    {"nombre": "Básico",   "precio": 499,  "destacado": false, "incluye": ["Landing page", "WhatsApp", "Dominio 1 año"]},
    {"nombre": "Estándar", "precio": 899,  "destacado": true,  "incluye": ["Web de 5 secciones", "Branding básico", "Hosting 1 año"]},
    {"nombre": "Premium",  "precio": 1499, "destacado": false, "incluye": ["Web + panel admin", "Branding completo", "Mantenimiento 3 meses"]}
  ]
}'),
(1, 'proceso', 5, '{
  "titulo": "¿Cómo trabajamos?",
  "pasos": ["Conversamos tu idea", "Diseñamos la propuesta", "Desarrollamos", "Lanzamos y te capacitamos"]
}'),
(1, 'testimonios', 6, '{
  "titulo": "Lo que dicen nuestros clientes",
  "items": [
    {"nombre": "Carlos R.", "negocio": "Barbería El Corte", "texto": "Ahora mis clientes reservan por WhatsApp desde la web."}
  ]
}'),
(1, 'faq', 7, '{
  "titulo": "Preguntas frecuentes",
  "items": [
    {"pregunta": "¿Cuánto demora mi web?", "respuesta": "Entre 1 y 3 semanas según el paquete."},
    {"pregunta": "¿Puedo editarla yo mismo?", "respuesta": "Sí, con el plan Premium tienes tu propio panel."}
  ]
}'),
(1, 'contacto', 8, '{
  "titulo": "Hablemos de tu proyecto",
  "mostrar_formulario": true,
  "mostrar_mapa": true
}');