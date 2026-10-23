-- =====================================================
-- PIXELA CMS - Base de datos
-- Motor: MySQL 5.7+ / MariaDB 10.2+ (XAMPP)
-- Modelo en 3FN + bloques modulares en JSON
-- =====================================================

SET NAMES utf8mb4;

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
    color_primario   CHAR(7) NOT NULL DEFAULT '#8B5CF6',
    color_secundario CHAR(7) NOT NULL DEFAULT '#2563EB',
    color_acento     CHAR(7) NOT NULL DEFAULT '#C8FF3D',
    color_fondo      CHAR(7) NOT NULL DEFAULT '#0B0B14',
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
 '{"facebook": "", "instagram": "https://instagram.com/", "tiktok": "", "linkedin": "", "youtube": ""}');

INSERT INTO paginas (titulo, slug, meta_descripcion, es_inicio) VALUES
('Inicio',     'inicio',     'Estudio digital: webs, branding, redes y sistemas a medida', 1),
('Servicios',  'servicios',  'Todo lo que Pixela puede hacer por tu negocio', 0),
('Plantillas', 'plantillas', 'Webs listas por rubro para lanzar rápido', 0),
('Contacto',   'contacto',   'Cotiza tu proyecto con Pixela', 0);

INSERT INTO menu_items (id, parent_id, pagina_id, etiqueta, url_externa, orden) VALUES
(1, NULL, 1,    'Inicio',           NULL,              1),
(2, NULL, 2,    'Servicios',        NULL,              2),
(3, NULL, 3,    'Plantillas',       NULL,              3),
(4, NULL, 4,    'Contacto',         NULL,              4),
(5, 2,    NULL, 'Qué hacemos',      '/servicios#servicios', 1),
(6, 2,    NULL, 'Planes y precios', '/servicios#planes',    2);

-- Imágenes de ejemplo (capturas de las plantillas demo, ya en versiones 480/768/1280 WEBP)
INSERT INTO media (id, subido_por, nombre_original, alt_text, mime, ancho_original, alto_original) VALUES
(1, 1, 'barberia.png', 'Plantilla Corte Fino (barbería)', 'image/png', 1280, 960),
(2, 1, 'restaurante.png', 'Plantilla Sazón Criolla (restaurante)', 'image/png', 1280, 960),
(3, 1, 'hogar.png', 'Plantilla Casa en Orden (servicios del hogar)', 'image/png', 1280, 960),
(4, 1, 'consultorio.png', 'Plantilla Vida Sana (consultorio)', 'image/png', 1280, 960),
(5, 1, 'tienda.png', 'Plantilla Moda Urbana (tienda de ropa)', 'image/png', 1280, 960),
(6, 1, 'gimnasio.png', 'Plantilla Power Gym (gimnasio)', 'image/png', 1280, 960),
(7, 1, 'veterinaria.png', 'Plantilla Patitas Felices (veterinaria)', 'image/png', 1280, 960),
(8, 1, 'academia.png', 'Plantilla Aula Activa (academia)', 'image/png', 1280, 960),
(9, 1, 'proyecto-barberia.png', 'Web de Barbería El Corte', 'image/png', 1280, 800),
(10, 1, 'proyecto-polleria.png', 'Carta digital de Pollería Don Lucho', 'image/png', 1280, 800),
(11, 1, 'proyecto-cetpro.png', 'Web institucional de CETPRO Mangomarca', 'image/png', 1280, 800);

INSERT INTO media_variantes (media_id, ancho, ruta, peso_kb) VALUES
(1, 480, 'public/assets/img/demo/barberia-480.webp', 11),
(1, 768, 'public/assets/img/demo/barberia-768.webp', 18),
(1, 1280, 'public/assets/img/demo/barberia-1280.webp', 32),
(2, 480, 'public/assets/img/demo/restaurante-480.webp', 11),
(2, 768, 'public/assets/img/demo/restaurante-768.webp', 22),
(2, 1280, 'public/assets/img/demo/restaurante-1280.webp', 40),
(3, 480, 'public/assets/img/demo/hogar-480.webp', 13),
(3, 768, 'public/assets/img/demo/hogar-768.webp', 26),
(3, 1280, 'public/assets/img/demo/hogar-1280.webp', 46),
(4, 480, 'public/assets/img/demo/consultorio-480.webp', 13),
(4, 768, 'public/assets/img/demo/consultorio-768.webp', 24),
(4, 1280, 'public/assets/img/demo/consultorio-1280.webp', 43),
(5, 480, 'public/assets/img/demo/tienda-480.webp', 12),
(5, 768, 'public/assets/img/demo/tienda-768.webp', 20),
(5, 1280, 'public/assets/img/demo/tienda-1280.webp', 34),
(6, 480, 'public/assets/img/demo/gimnasio-480.webp', 11),
(6, 768, 'public/assets/img/demo/gimnasio-768.webp', 19),
(6, 1280, 'public/assets/img/demo/gimnasio-1280.webp', 32),
(7, 480, 'public/assets/img/demo/veterinaria-480.webp', 12),
(7, 768, 'public/assets/img/demo/veterinaria-768.webp', 24),
(7, 1280, 'public/assets/img/demo/veterinaria-1280.webp', 42),
(8, 480, 'public/assets/img/demo/academia-480.webp', 15),
(8, 768, 'public/assets/img/demo/academia-768.webp', 28),
(8, 1280, 'public/assets/img/demo/academia-1280.webp', 48),
(9, 480, 'public/assets/img/demo/proyecto-barberia-480.webp', 9),
(9, 768, 'public/assets/img/demo/proyecto-barberia-768.webp', 16),
(9, 1280, 'public/assets/img/demo/proyecto-barberia-1280.webp', 28),
(10, 480, 'public/assets/img/demo/proyecto-polleria-480.webp', 10),
(10, 768, 'public/assets/img/demo/proyecto-polleria-768.webp', 16),
(10, 1280, 'public/assets/img/demo/proyecto-polleria-1280.webp', 28),
(11, 480, 'public/assets/img/demo/proyecto-cetpro-480.webp', 12),
(11, 768, 'public/assets/img/demo/proyecto-cetpro-768.webp', 23),
(11, 1280, 'public/assets/img/demo/proyecto-cetpro-1280.webp', 41);

-- Bloques de cada página (el contenido de cada bloque va en JSON)
INSERT INTO page_components (pagina_id, tipo, orden, contenido) VALUES
(1, 'hero', 1, '{
  "etiqueta": "Estudio digital · Lima",
  "titulo": "Llevamos tu negocio a",
  "resaltado": "internet",
  "subtitulo": "Webs, branding, redes y sistemas a medida para negocios que quieren crecer.",
  "boton_texto": "Cotiza gratis",
  "boton_url": "/contacto",
  "boton2_texto": "Ver plantillas",
  "boton2_url": "/plantillas",
  "imagen_id": null
}'),
(1, 'servicios', 2, '{
  "etiqueta": "Servicios",
  "titulo": "¿Qué hacemos?",
  "items": [
    {
      "icono": "code",
      "titulo": "Diseño web",
      "descripcion": "Páginas rápidas, modernas y adaptadas a celular."
    },
    {
      "icono": "palette",
      "titulo": "Branding",
      "descripcion": "Logo, colores e identidad visual de tu marca."
    },
    {
      "icono": "share",
      "titulo": "Redes sociales",
      "descripcion": "Contenido y gestión mensual de tus redes."
    },
    {
      "icono": "database",
      "titulo": "Sistemas a medida",
      "descripcion": "Inventarios, reservas y gestión interna."
    },
    {
      "icono": "server",
      "titulo": "Hosting y dominio",
      "descripcion": "Tu web siempre en línea con correo corporativo."
    },
    {
      "icono": "wrench",
      "titulo": "Mantenimiento",
      "descripcion": "Soporte, copias de seguridad y actualizaciones."
    }
  ]
}'),
(1, 'portafolio', 3, '{
  "etiqueta": "Portafolio",
  "titulo": "Trabajos recientes",
  "items": [
    {
      "titulo": "Barbería El Corte",
      "imagen_id": 9,
      "categoria": "Web + reservas",
      "url": ""
    },
    {
      "titulo": "Pollería Don Lucho",
      "imagen_id": 10,
      "categoria": "Carta digital QR",
      "url": ""
    },
    {
      "titulo": "CETPRO Mangomarca",
      "imagen_id": 11,
      "categoria": "Web institucional",
      "url": ""
    }
  ]
}'),
(1, 'proceso', 4, '{
  "etiqueta": "Así trabajamos",
  "titulo": "De la idea a internet en 4 pasos",
  "pasos": [
    "Conversamos tu idea",
    "Diseñamos la propuesta",
    "Desarrollamos y probamos",
    "Lanzamos y te capacitamos"
  ]
}'),
(1, 'planes', 5, '{
  "etiqueta": "Paquetes",
  "titulo": "Elige tu plan",
  "items": [
    {
      "nombre": "Básico",
      "precio": 499,
      "destacado": false,
      "incluye": [
        "Landing page de 1 sección",
        "Botón de WhatsApp",
        "Dominio .com 1 año"
      ]
    },
    {
      "nombre": "Estándar",
      "precio": 899,
      "destacado": true,
      "incluye": [
        "Web de 5 secciones",
        "Branding básico",
        "Hosting 1 año",
        "Formulario de contacto"
      ]
    },
    {
      "nombre": "Premium",
      "precio": 1499,
      "destacado": false,
      "incluye": [
        "Web + panel administrable",
        "Branding completo",
        "Mantenimiento 3 meses",
        "Capacitación"
      ]
    }
  ]
}'),
(1, 'testimonios', 6, '{
  "etiqueta": "Clientes",
  "titulo": "Lo que dicen de nosotros",
  "items": [
    {
      "nombre": "Carlos R.",
      "negocio": "Barbería El Corte",
      "texto": "Ahora mis clientes reservan por WhatsApp desde la web. Se nota en la caja."
    },
    {
      "nombre": "Lucía M.",
      "negocio": "Pastelería Dulce Lu",
      "texto": "Me armaron el logo y la web en dos semanas. Todo muy claro y rápido."
    },
    {
      "nombre": "Jorge P.",
      "negocio": "Taller JP Motors",
      "texto": "El panel es facilísimo. Cambio precios y fotos yo mismo sin llamar a nadie."
    }
  ]
}'),
(1, 'faq', 7, '{
  "etiqueta": "Dudas",
  "titulo": "Preguntas frecuentes",
  "items": [
    {
      "pregunta": "¿Cuánto demora mi web?",
      "respuesta": "Entre 1 y 3 semanas, según el paquete que elijas."
    },
    {
      "pregunta": "¿Puedo editarla yo mismo?",
      "respuesta": "Sí. Con el plan Premium tienes tu propio panel para cambiar textos, colores y fotos."
    },
    {
      "pregunta": "¿Incluye dominio y hosting?",
      "respuesta": "Los planes Estándar y Premium incluyen 1 año de hosting y dominio .com."
    },
    {
      "pregunta": "¿Cómo pago?",
      "respuesta": "50% al iniciar y 50% al entregar. Aceptamos transferencia, Yape y Plin."
    }
  ]
}'),
(1, 'cta', 8, '{
  "titulo": "¿Listo para llevar tu negocio a internet?",
  "subtitulo": "Cuéntanos tu idea y te enviamos una propuesta gratis en 24 horas.",
  "boton_texto": "Cotizar ahora",
  "boton_url": "/contacto"
}'),
(2, 'hero', 1, '{
  "etiqueta": "Servicios",
  "titulo": "Todo lo que tu negocio",
  "resaltado": "necesita",
  "subtitulo": "Desde el logo hasta el sistema de reservas: un solo equipo para todo lo digital.",
  "boton_texto": "Ver planes",
  "boton_url": "#planes",
  "boton2_texto": "",
  "boton2_url": "",
  "imagen_id": null
}'),
(2, 'servicios', 2, '{
  "etiqueta": "Qué incluye",
  "titulo": "Nuestros servicios",
  "items": [
    {
      "icono": "code",
      "titulo": "Diseño web",
      "descripcion": "Páginas rápidas, modernas y adaptadas a celular."
    },
    {
      "icono": "palette",
      "titulo": "Branding",
      "descripcion": "Logo, colores e identidad visual de tu marca."
    },
    {
      "icono": "share",
      "titulo": "Redes sociales",
      "descripcion": "Contenido y gestión mensual de tus redes."
    },
    {
      "icono": "database",
      "titulo": "Sistemas a medida",
      "descripcion": "Inventarios, reservas y gestión interna."
    },
    {
      "icono": "server",
      "titulo": "Hosting y dominio",
      "descripcion": "Tu web siempre en línea con correo corporativo."
    },
    {
      "icono": "wrench",
      "titulo": "Mantenimiento",
      "descripcion": "Soporte, copias de seguridad y actualizaciones."
    }
  ]
}'),
(2, 'proceso', 3, '{
  "etiqueta": "Así trabajamos",
  "titulo": "De la idea a internet en 4 pasos",
  "pasos": [
    "Conversamos tu idea",
    "Diseñamos la propuesta",
    "Desarrollamos y probamos",
    "Lanzamos y te capacitamos"
  ]
}'),
(2, 'planes', 4, '{
  "etiqueta": "Paquetes",
  "titulo": "Elige tu plan",
  "items": [
    {
      "nombre": "Básico",
      "precio": 499,
      "destacado": false,
      "incluye": [
        "Landing page de 1 sección",
        "Botón de WhatsApp",
        "Dominio .com 1 año"
      ]
    },
    {
      "nombre": "Estándar",
      "precio": 899,
      "destacado": true,
      "incluye": [
        "Web de 5 secciones",
        "Branding básico",
        "Hosting 1 año",
        "Formulario de contacto"
      ]
    },
    {
      "nombre": "Premium",
      "precio": 1499,
      "destacado": false,
      "incluye": [
        "Web + panel administrable",
        "Branding completo",
        "Mantenimiento 3 meses",
        "Capacitación"
      ]
    }
  ]
}'),
(2, 'cta', 5, '{
  "titulo": "¿Listo para llevar tu negocio a internet?",
  "subtitulo": "Cuéntanos tu idea y te enviamos una propuesta gratis en 24 horas.",
  "boton_texto": "Cotizar ahora",
  "boton_url": "/contacto"
}'),
(3, 'hero', 1, '{
  "etiqueta": "Plantillas",
  "titulo": "Tu web lista en",
  "resaltado": "días",
  "subtitulo": "Elige una plantilla diseñada para tu rubro y la adaptamos a tu marca.",
  "boton_texto": "Quiero una",
  "boton_url": "/contacto",
  "boton2_texto": "",
  "boton2_url": "",
  "imagen_id": null
}'),
(3, 'plantillas', 2, '{
  "etiqueta": "Listas para usar",
  "titulo": "Plantillas por rubro",
  "items": [
    {
      "nombre": "Corte Fino",
      "rubro": "Barbería",
      "precio": 350,
      "imagen_id": 1,
      "demo_url": "/public/demos/barberia.html"
    },
    {
      "nombre": "Sazón Criolla",
      "rubro": "Restaurante",
      "precio": 400,
      "imagen_id": 2,
      "demo_url": "/public/demos/restaurante.html"
    },
    {
      "nombre": "Casa en Orden",
      "rubro": "Servicios del hogar",
      "precio": 350,
      "imagen_id": 3,
      "demo_url": "/public/demos/hogar.html"
    },
    {
      "nombre": "Vida Sana",
      "rubro": "Consultorio",
      "precio": 420,
      "imagen_id": 4,
      "demo_url": "/public/demos/consultorio.html"
    },
    {
      "nombre": "Moda Urbana",
      "rubro": "Tienda de ropa",
      "precio": 450,
      "imagen_id": 5,
      "demo_url": "/public/demos/tienda.html"
    },
    {
      "nombre": "Power Gym",
      "rubro": "Gimnasio",
      "precio": 400,
      "imagen_id": 6,
      "demo_url": "/public/demos/gimnasio.html"
    },
    {
      "nombre": "Patitas Felices",
      "rubro": "Veterinaria",
      "precio": 380,
      "imagen_id": 7,
      "demo_url": "/public/demos/veterinaria.html"
    },
    {
      "nombre": "Aula Activa",
      "rubro": "Academia",
      "precio": 420,
      "imagen_id": 8,
      "demo_url": "/public/demos/academia.html"
    }
  ]
}'),
(3, 'texto', 3, '{
  "etiqueta": "¿Cómo funciona?",
  "titulo": "Plantilla + tu marca",
  "texto": "Cada plantilla ya viene con las secciones que tu rubro necesita.\\nNosotros cambiamos colores, textos, fotos y logo para que se vea 100% tuya, y la publicamos con tu dominio."
}'),
(3, 'cta', 4, '{
  "titulo": "¿Listo para llevar tu negocio a internet?",
  "subtitulo": "Cuéntanos tu idea y te enviamos una propuesta gratis en 24 horas.",
  "boton_texto": "Cotizar ahora",
  "boton_url": "/contacto"
}'),
(4, 'hero', 1, '{
  "etiqueta": "Contacto",
  "titulo": "Hablemos de tu",
  "resaltado": "proyecto",
  "subtitulo": "Escríbenos por el formulario o por WhatsApp. Respondemos en menos de 24 horas.",
  "boton_texto": "",
  "boton_url": "",
  "boton2_texto": "",
  "boton2_url": "",
  "imagen_id": null
}'),
(4, 'contacto', 2, '{
  "etiqueta": "Escríbenos",
  "titulo": "Cuéntanos qué necesitas",
  "texto": "Déjanos tus datos y te enviamos una propuesta sin costo.",
  "mostrar_formulario": true,
  "mostrar_mapa": true
}'),
(4, 'faq', 3, '{
  "etiqueta": "Dudas",
  "titulo": "Preguntas frecuentes",
  "items": [
    {
      "pregunta": "¿Cuánto demora mi web?",
      "respuesta": "Entre 1 y 3 semanas, según el paquete que elijas."
    },
    {
      "pregunta": "¿Puedo editarla yo mismo?",
      "respuesta": "Sí. Con el plan Premium tienes tu propio panel para cambiar textos, colores y fotos."
    },
    {
      "pregunta": "¿Incluye dominio y hosting?",
      "respuesta": "Los planes Estándar y Premium incluyen 1 año de hosting y dominio .com."
    },
    {
      "pregunta": "¿Cómo pago?",
      "respuesta": "50% al iniciar y 50% al entregar. Aceptamos transferencia, Yape y Plin."
    }
  ]
}');
