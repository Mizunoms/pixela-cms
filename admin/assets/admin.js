/* =====================================================
   PIXELA CMS · Panel admin (JavaScript sin frameworks)
   - Navegación por secciones con #hash (sin recargar la página)
   - Todas las acciones consumen la API REST con fetch (cookie httpOnly)
   ===================================================== */
'use strict';

const CUERPO = document.body.dataset;
const API = CUERPO.api;
const SITIO = CUERPO.sitio;
const LOGO_PIXELA = CUERPO.logo;
const USUARIO = JSON.parse(CUERPO.usuario);
const ES_ADMIN = USUARIO.rol === 'admin';
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

// ---------- Utilidades ----------

// Escapa texto antes de meterlo en HTML (evita XSS)
function esc(v) {
    return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// Llamada a la API. Si la sesión expiró (401) vuelve al login.
async function api(ruta, { metodo = 'GET', datos, form } = {}) {
    const opciones = { method: metodo, credentials: 'same-origin', headers: {} };
    if (form) opciones.body = form;
    else if (datos !== undefined) { opciones.headers['Content-Type'] = 'application/json'; opciones.body = JSON.stringify(datos); }

    let r;
    try { r = await fetch(API + ruta, opciones); }
    catch { throw new Error('No hay conexión con la API'); }
    const json = await r.json().catch(() => ({ ok: false, error: 'Respuesta inválida del servidor' }));
    if (r.status === 401) { location.href = 'login.php?expirada=1'; throw new Error('Sesión expirada'); }
    if (!r.ok || !json.ok) throw new Error(json.error || 'Error ' + r.status);
    return json.data;
}

function toast(texto, tipo = 'ok') {
    const t = document.createElement('div');
    t.className = 'toast' + (tipo === 'error' ? ' error' : '');
    t.textContent = (tipo === 'error' ? '✖ ' : '✔ ') + texto;
    $('#toasts').appendChild(t);
    setTimeout(() => { t.classList.add('fuera'); setTimeout(() => t.remove(), 300); }, tipo === 'error' ? 4200 : 2600);
}

const ICONOS = {
    editar: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
    borrar: '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>',
    arriba: '<path d="m18 15-6-6-6 6"/>',
    abajo: '<path d="m6 9 6 6 6-6"/>',
    ver: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    bloques: '<rect x="3" y="3" width="18" height="7" rx="1"/><rect x="3" y="14" width="18" height="7" rx="1"/>',
    copiar: '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
    externo: '<path d="M7 17 17 7M7 7h10v10"/>',
    mas: '<path d="M12 5v14M5 12h14"/>',
    correo: '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
    check: '<path d="M20 6 9 17l-5-5"/>',
    paginas: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v5h5"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h10"/>',
    imagen: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/>',
    subir: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>',
};
const ico = (n) => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${ICONOS[n]}</svg>`;
const btnIco = (n, attrs = '', titulo = '') => `<button class="icono-btn" type="button" title="${esc(titulo)}" aria-label="${esc(titulo)}" ${attrs}>${ico(n)}</button>`;

// Confirmación en línea: el botón se convierte en "¿Seguro? Sí / No"
function confirmar(boton, alConfirmar) {
    const span = document.createElement('span');
    span.className = 'confirmar';
    span.innerHTML = '¿Seguro? <button class="si" type="button">Sí</button><button class="no" type="button">No</button>';
    boton.replaceWith(span);
    span.querySelector('.no').onclick = () => span.replaceWith(boton);
    span.querySelector('.si').onclick = async () => { span.textContent = 'Borrando…'; await alConfirmar(); };
}

// ✦ número que sube desde 0
function contar(el) {
    const fin = +el.dataset.n || 0;
    let v = 0;
    const paso = Math.max(1, Math.ceil(fin / 18));
    const t = setInterval(() => { v = Math.min(fin, v + paso); el.textContent = v; if (v >= fin) clearInterval(t); }, 35);
}

function fecha(txt) {
    const d = new Date(String(txt).replace(' ', 'T'));
    return isNaN(d) ? txt : d.toLocaleString('es-PE', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

// ---------- Drawer (formularios laterales) ----------
const drawer = {
    abrir({ etiqueta = 'EDITAR', titulo, sub = '', html, textoGuardar = 'GUARDAR →', alGuardar }) {
        $('#drawerEtiqueta').textContent = etiqueta;
        $('#drawerTitulo').textContent = titulo;
        $('#drawerSub').textContent = sub;
        $('#drawerCuerpo').innerHTML = html;
        const g = $('#drawerGuardar');
        g.textContent = textoGuardar;
        g.classList.toggle('oculto', !alGuardar);
        g.onclick = async () => {
            g.disabled = true;
            try { await alGuardar(); } catch (e) { toast(e.message, 'error'); }
            g.disabled = false;
        };
        $('#drawer').classList.add('on');
        $('#velo').classList.add('on');
        $('#drawer').setAttribute('aria-hidden', 'false');
        setTimeout(() => $('#drawerCuerpo input, #drawerCuerpo textarea, #drawerCuerpo select')?.focus(), 350);
    },
    cerrar() {
        $('#drawer').classList.remove('on');
        $('#velo').classList.remove('on');
        $('#drawer').setAttribute('aria-hidden', 'true');
    },
};
$('#drawerCerrar').onclick = $('#drawerCancelar').onclick = $('#velo').onclick = () => drawer.cerrar();
document.addEventListener('keydown', e => { if (e.key === 'Escape') { drawer.cerrar(); cerrarModal(); } });

// ---------- Selector de imágenes (modal) ----------
function cerrarModal() { $('#modal').classList.remove('on'); }
$('#modal').addEventListener('click', e => { if (e.target.id === 'modal') cerrarModal(); });

async function elegirImagen(alElegir) {
    const caja = $('#modalCaja');
    caja.innerHTML = '<div class="cargando"><span></span></div>';
    $('#modal').classList.add('on');
    const pintar = async () => {
        const medios = await api('/admin/media');
        caja.innerHTML = `
            <div class="fila"><h3>Elegir imagen</h3>
              <div class="acciones"><label class="btn chico">${ico('subir')} Subir nueva<input type="file" accept="image/*" hidden id="subirModal"></label>
              <button class="btn blanco chico" type="button" id="quitarImg">Sin imagen</button>
              <button class="cerrar" type="button" id="cerrarModal">✕</button></div></div>
            ${medios.length ? `<div class="galeria elegir">${medios.map(m => `
                <div class="foto" data-id="${m.id}"><div class="img"><img src="${esc(m.variantes[0]?.url)}" alt="${esc(m.alt)}" loading="lazy"></div>
                <div class="datos"><b>#${m.id} · ${esc(m.nombre_original)}</b><span class="mono">${m.ancho_original}×${m.alto_original}px</span></div></div>`).join('')}</div>`
            : '<div class="vacio" style="color:#555;border-color:#bbb"><b style="color:#111">Aún no hay imágenes</b>Sube la primera con el botón de arriba.</div>'}`;
        $('#cerrarModal').onclick = cerrarModal;
        $('#quitarImg').onclick = () => { alElegir(null); cerrarModal(); };
        $('#subirModal').onchange = async (e) => {
            if (!e.target.files[0]) return;
            try { const m = await subirArchivo(e.target.files[0]); toast('Imagen subida'); alElegir(m); cerrarModal(); }
            catch (err) { toast(err.message, 'error'); }
        };
        $$('.foto', caja).forEach(f => f.onclick = () => { alElegir(medios.find(m => m.id === +f.dataset.id)); cerrarModal(); });
    };
    try { await pintar(); } catch (e) { toast(e.message, 'error'); cerrarModal(); }
}

async function subirArchivo(archivo, alt = '') {
    const fd = new FormData();
    fd.append('imagen', archivo);
    if (alt) fd.append('alt', alt);
    return api('/admin/media', { metodo: 'POST', form: fd });
}

// =====================================================
//  Esquemas de los bloques: qué campos tiene cada tipo
//  (el contenido se guarda como JSON en page_components)
// =====================================================
const ICONOS_SERVICIO = { code: '</> Código', palette: '◐ Paleta', share: '⟲ Redes', database: '▤ Base de datos', server: '▣ Servidor', wrench: '⚙ Herramienta', camera: '◉ Cámara', chart: '▲ Gráfico', rocket: '➚ Cohete', heart: '♥ Corazón' };
const T = (nombre, etiqueta, extra = {}) => ({ nombre, etiqueta, tipo: 'texto', ...extra });
const ENCABEZADO = [T('etiqueta', 'Etiqueta (texto pequeño)'), T('titulo', 'Título')];

const BLOQUES = {
    hero: { nombre: 'Hero / Portada', desc: 'Título grande con botones e imagen', color: 'lila', campos: [
        T('etiqueta', 'Etiqueta'), T('titulo', 'Título'), T('resaltado', 'Palabra resaltada (va al final del título)'),
        T('subtitulo', 'Subtítulo', { tipo: 'area' }),
        { fila: [T('boton_texto', 'Texto del botón'), T('boton_url', 'Enlace del botón', { ayuda: '/contacto, #planes o https://' })] },
        { fila: [T('boton2_texto', 'Texto botón 2'), T('boton2_url', 'Enlace botón 2')] },
        { nombre: 'imagen_id', etiqueta: 'Imagen (si no eliges, se usa el logo)', tipo: 'imagen' }] },
    servicios: { nombre: 'Servicios', desc: 'Tarjetas con ícono, título y descripción', color: 'cielo', campos: [...ENCABEZADO,
        { nombre: 'items', etiqueta: 'Servicios', tipo: 'lista', item: 'Servicio', campos: [
            { nombre: 'icono', etiqueta: 'Ícono', tipo: 'select', opciones: ICONOS_SERVICIO }, T('titulo', 'Título'), T('descripcion', 'Descripción', { tipo: 'area' })] }] },
    portafolio: { nombre: 'Portafolio', desc: 'Trabajos realizados con imagen', color: 'rosa', campos: [...ENCABEZADO,
        { nombre: 'items', etiqueta: 'Proyectos', tipo: 'lista', item: 'Proyecto', campos: [
            T('titulo', 'Nombre del proyecto'), T('categoria', 'Categoría'), { nombre: 'imagen_id', etiqueta: 'Imagen', tipo: 'imagen' }, T('url', 'Enlace (opcional)')] }] },
    plantillas: { nombre: 'Plantillas', desc: 'Catálogo de plantillas con precio', color: 'durazno', campos: [...ENCABEZADO,
        { nombre: 'items', etiqueta: 'Plantillas', tipo: 'lista', item: 'Plantilla', campos: [
            T('nombre', 'Nombre'), T('rubro', 'Rubro'), { nombre: 'precio', etiqueta: 'Precio (S/)', tipo: 'numero' }, { nombre: 'imagen_id', etiqueta: 'Imagen', tipo: 'imagen' }, T('demo_url', 'Enlace de la demo (opcional)')] }] },
    planes: { nombre: 'Planes / Precios', desc: 'Paquetes con precio y lo que incluyen', color: 'lima', campos: [...ENCABEZADO,
        { nombre: 'items', etiqueta: 'Planes', tipo: 'lista', item: 'Plan', campos: [
            T('nombre', 'Nombre'), { nombre: 'precio', etiqueta: 'Precio (S/)', tipo: 'numero' }, { nombre: 'destacado', etiqueta: 'Plan destacado', tipo: 'booleano' },
            { nombre: 'incluye', etiqueta: 'Incluye (uno por línea)', tipo: 'lineas' }] }] },
    proceso: { nombre: 'Proceso', desc: 'Pasos numerados', color: 'cielo', campos: [...ENCABEZADO, { nombre: 'pasos', etiqueta: 'Pasos (uno por línea)', tipo: 'lineas' }] },
    testimonios: { nombre: 'Testimonios', desc: 'Opiniones de clientes', color: 'lila', campos: [...ENCABEZADO,
        { nombre: 'items', etiqueta: 'Testimonios', tipo: 'lista', item: 'Testimonio', campos: [T('nombre', 'Nombre'), T('negocio', 'Negocio'), T('texto', 'Testimonio', { tipo: 'area' })] }] },
    faq: { nombre: 'Preguntas frecuentes', desc: 'Acordeón de preguntas', color: 'durazno', campos: [...ENCABEZADO,
        { nombre: 'items', etiqueta: 'Preguntas', tipo: 'lista', item: 'Pregunta', campos: [T('pregunta', 'Pregunta'), T('respuesta', 'Respuesta', { tipo: 'area' })] }] },
    contacto: { nombre: 'Contacto', desc: 'Formulario, WhatsApp y mapa', color: 'rosa', campos: [...ENCABEZADO, T('texto', 'Texto', { tipo: 'area' }),
        { nombre: 'mostrar_formulario', etiqueta: 'Mostrar formulario', tipo: 'booleano' }, { nombre: 'mostrar_mapa', etiqueta: 'Mostrar mapa', tipo: 'booleano' }] },
    texto: { nombre: 'Texto', desc: 'Título y párrafos', color: 'gris', campos: [...ENCABEZADO, T('texto', 'Texto (cada línea es un párrafo)', { tipo: 'area' })] },
    galeria: { nombre: 'Galería', desc: 'Varias imágenes', color: 'cielo', campos: [...ENCABEZADO,
        { nombre: 'items', etiqueta: 'Imágenes', tipo: 'lista', item: 'Imagen', campos: [{ nombre: 'imagen_id', etiqueta: 'Imagen', tipo: 'imagen' }, T('alt', 'Descripción')] }] },
    cta: { nombre: 'Llamado a la acción', desc: 'Franja con botón destacado', color: 'lima', campos: [T('titulo', 'Título'), T('subtitulo', 'Subtítulo', { tipo: 'area' }),
        { fila: [T('boton_texto', 'Texto del botón'), T('boton_url', 'Enlace del botón')] }] },
};

// Contenido inicial al crear un bloque nuevo
function contenidoVacio(tipo) {
    const base = {};
    const llenar = (campos, obj) => campos.forEach(c => {
        if (c.fila) return llenar(c.fila, obj);
        obj[c.nombre] = c.tipo === 'lista' ? [] : c.tipo === 'lineas' ? [] : c.tipo === 'booleano' ? true : c.tipo === 'imagen' ? null : c.tipo === 'numero' ? 0 : '';
    });
    llenar(BLOQUES[tipo]?.campos || [], base);
    if (base.titulo !== undefined) base.titulo = 'Nuevo bloque ' + (BLOQUES[tipo]?.nombre || tipo);
    return base;
}

// ---------- Editor de formularios a partir del esquema ----------
const editor = {
    estado: null,       // copia del contenido que se está editando
    esquema: null,
    cacheImg: {},       // id -> url de miniatura

    pintar(cont) {
        cont.innerHTML = this.campos(this.esquema, this.estado, []);
        this.enlazar(cont);
    },

    campos(lista, obj, ruta) {
        return lista.map(c => {
            if (c.fila) return `<div class="dos-col">${this.campos(c.fila, obj, ruta)}</div>`;
            const r = [...ruta, c.nombre];
            const v = obj?.[c.nombre];
            const data = `data-ruta='${esc(JSON.stringify(r))}' data-tipo="${c.tipo}"`;
            const ayuda = c.ayuda ? `<div class="ayuda">${esc(c.ayuda)}</div>` : '';
            switch (c.tipo) {
                case 'area':
                    return `<div class="fila-campo"><label class="lbl">${esc(c.etiqueta)}</label><textarea class="campo" ${data}>${esc(v)}</textarea>${ayuda}</div>`;
                case 'numero':
                    return `<div class="fila-campo"><label class="lbl">${esc(c.etiqueta)}</label><input class="campo" type="number" min="0" step="1" value="${esc(v ?? 0)}" ${data}></div>`;
                case 'booleano':
                    return `<label class="switch"><b>${esc(c.etiqueta)}</b><input type="checkbox" ${v ? 'checked' : ''} ${data}><span class="tog"></span></label>`;
                case 'lineas':
                    return `<div class="fila-campo"><label class="lbl">${esc(c.etiqueta)}</label><textarea class="campo" ${data}>${esc((Array.isArray(v) ? v : []).join('\n'))}</textarea></div>`;
                case 'select':
                    return `<div class="fila-campo"><label class="lbl">${esc(c.etiqueta)}</label><select class="campo" ${data}>${Object.entries(c.opciones).map(([k, t]) => `<option value="${k}" ${k === v ? 'selected' : ''}>${esc(t)}</option>`).join('')}</select></div>`;
                case 'imagen': {
                    const url = v ? this.cacheImg[v] : '';
                    return `<div class="fila-campo"><label class="lbl">${esc(c.etiqueta)}</label><div class="selector-img">
                        <div class="miniatura" style="${url ? `background-image:url('${esc(url)}')` : ''}">${v ? (url ? '' : '#' + esc(v)) : 'SIN<br>IMAGEN'}</div>
                        <button class="btn blanco chico" type="button" data-elegir='${esc(JSON.stringify(r))}'>${ico('imagen')} ${v ? 'Cambiar' : 'Elegir'}</button></div></div>`;
                }
                case 'lista': {
                    const items = Array.isArray(v) ? v : [];
                    return `<div class="grupo-lista"><label class="lbl">${esc(c.etiqueta)} (${items.length})</label>
                        ${items.map((it, i) => `<div class="sub-item"><div class="sub-cab">${esc(c.item)} ${i + 1}<div class="btns">
                            <button class="icono-btn" type="button" title="Subir" data-mover='${esc(JSON.stringify(r))}' data-i="${i}" data-d="-1" ${i === 0 ? 'disabled' : ''}>${ico('arriba')}</button>
                            <button class="icono-btn" type="button" title="Bajar" data-mover='${esc(JSON.stringify(r))}' data-i="${i}" data-d="1" ${i === items.length - 1 ? 'disabled' : ''}>${ico('abajo')}</button>
                            <button class="icono-btn rojo" type="button" title="Quitar" data-quitar='${esc(JSON.stringify(r))}' data-i="${i}">${ico('borrar')}</button></div></div>
                            ${this.campos(c.campos, it, [...r, i])}</div>`).join('')}
                        <button class="btn chico" type="button" data-agregar='${esc(JSON.stringify(r))}' data-esquema="${esc(c.nombre)}">${ico('mas')} Agregar ${esc(c.item.toLowerCase())}</button></div>`;
                }
                default:
                    return `<div class="fila-campo"><label class="lbl">${esc(c.etiqueta)}</label><input class="campo" type="text" value="${esc(v)}" ${data}>${ayuda}</div>`;
            }
        }).join('');
    },

    enlazar(cont) {
        $$('[data-ruta]', cont).forEach(el => {
            const ev = el.type === 'checkbox' || el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(ev, () => {
                const ruta = JSON.parse(el.dataset.ruta);
                let valor = el.value;
                if (el.dataset.tipo === 'booleano') valor = el.checked;
                if (el.dataset.tipo === 'numero') valor = Number(el.value) || 0;
                if (el.dataset.tipo === 'lineas') valor = el.value.split('\n').map(s => s.trim()).filter(Boolean);
                this.poner(ruta, valor);
            });
        });
        $$('[data-agregar]', cont).forEach(b => b.onclick = () => {
            const ruta = JSON.parse(b.dataset.agregar);
            const lista = this.obtener(ruta) || [];
            const sub = this.buscarEsquema(this.esquema, b.dataset.esquema);
            const nuevo = {};
            (sub?.campos || []).forEach(c => nuevo[c.nombre] = c.tipo === 'booleano' ? false : c.tipo === 'imagen' ? null : c.tipo === 'numero' ? 0 : c.tipo === 'lineas' ? [] : c.tipo === 'select' ? Object.keys(c.opciones)[0] : '');
            this.poner(ruta, [...lista, nuevo]);
            this.pintar(cont);
        });
        $$('[data-quitar]', cont).forEach(b => b.onclick = () => {
            const ruta = JSON.parse(b.dataset.quitar);
            const lista = [...this.obtener(ruta)];
            lista.splice(+b.dataset.i, 1);
            this.poner(ruta, lista);
            this.pintar(cont);
        });
        $$('[data-mover]', cont).forEach(b => b.onclick = () => {
            const ruta = JSON.parse(b.dataset.mover);
            const lista = [...this.obtener(ruta)];
            const i = +b.dataset.i, j = i + +b.dataset.d;
            [lista[i], lista[j]] = [lista[j], lista[i]];
            this.poner(ruta, lista);
            this.pintar(cont);
        });
        $$('[data-elegir]', cont).forEach(b => b.onclick = () => elegirImagen(m => {
            const ruta = JSON.parse(b.dataset.elegir);
            if (m) this.cacheImg[m.id] = m.variantes[0]?.url;
            this.poner(ruta, m ? m.id : null);
            this.pintar(cont);
        }));
    },

    buscarEsquema(lista, nombre) {
        for (const c of lista) {
            if (c.fila) { const r = this.buscarEsquema(c.fila, nombre); if (r) return r; }
            if (c.nombre === nombre && c.tipo === 'lista') return c;
            if (c.campos) { const r = this.buscarEsquema(c.campos, nombre); if (r) return r; }
        }
        return null;
    },
    obtener(ruta) { return ruta.reduce((o, k) => (o == null ? undefined : o[k]), this.estado); },
    poner(ruta, valor) {
        let o = this.estado;
        ruta.slice(0, -1).forEach(k => { if (o[k] == null) o[k] = {}; o = o[k]; });
        o[ruta[ruta.length - 1]] = valor;
    },
};

// Busca miniaturas de las imágenes usadas en el contenido (para mostrarlas en el editor)
async function precargarImagenes() {
    try { (await api('/admin/media')).forEach(m => editor.cacheImg[m.id] = m.variantes[0]?.url); } catch { /* sin miniaturas */ }
}

// =====================================================
//  VISTAS
// =====================================================
const vista = $('#vista');

function cabecera(titulo, sub, acciones = '', migas = '') {
    return `<div class="cabeza"><div>${migas ? `<div class="migas">${migas}</div>` : ''}<h1>${titulo}</h1><p>${sub}</p></div><div class="acciones">${acciones}</div></div>`;
}

function cargando() { vista.innerHTML = '<div class="cargando"><span></span></div>'; }

function error(e) {
    vista.innerHTML = `<div class="contenido"><div class="vacio"><b>No se pudo cargar</b>${esc(e.message)}<br><br><button class="btn" onclick="router()">Reintentar</button></div></div>`;
}

// ---------- Dashboard ----------
async function vistaDashboard() {
    const [r, paginas, config] = await Promise.all([api('/admin/resumen'), api('/admin/paginas'), api('/config')]);
    actualizarContador(r.mensajes_sin_leer);
    const k = (color, icono, titulo, n, sub) => `<div class="kpi sticker" style="background:var(--${color})"><span class="ic">${ico(icono)}</span><div class="t">${titulo}</div><div class="n sube" data-n="${n}">0</div><div class="s">${sub}</div></div>`;
    const hora = new Date().getHours();
    const saludo = hora < 12 ? 'Buenos días' : hora < 19 ? 'Buenas tardes' : 'Buenas noches';

    vista.innerHTML = cabecera(`${saludo}, ${esc(USUARIO.nombre.split(' ')[0])} 👋`, '// todo en línea · los cambios se ven en la web al instante',
        `<a class="btn blanco" href="${SITIO}" target="_blank" rel="noopener">${ico('externo')} Ver sitio</a><button class="btn" type="button" id="nuevaPag">${ico('mas')} Nueva página</button>`) + `
        <div class="contenido">
            <div class="kpis">
                ${k('lila', 'paginas', 'Páginas', r.paginas, `${r.paginas_publicadas} publicadas`)}
                ${k('cielo', 'bloques', 'Bloques', r.bloques, `${r.bloques_visibles} visibles`)}
                ${k('lima', 'menu', 'Menú', r.menu, `${r.submenus} submenús`)}
                ${k('rosa', 'correo', 'Mensajes', r.mensajes, `${r.mensajes_sin_leer} sin leer`)}
            </div>
            <div class="rejilla-2">
                <div class="caja"><h2 class="caja-titulo">01 // Páginas <span class="pill">${paginas.length} REG.</span></h2>
                    <div class="tabla-scroll">${tablaPaginas(paginas, true)}</div></div>
                <div class="caja violeta"><h2 class="caja-titulo">02 // Colores del sitio <span class="vivo">EN VIVO</span></h2>
                    ${muestrasColores(config.colores)}
                    <div style="margin-top:14px">${previaSitio(config)}</div>
                    ${ES_ADMIN ? '<a class="btn blanco chico" href="#config" style="margin-top:14px">Cambiar colores y logo →</a>' : ''}</div>
            </div>
        </div>`;
    $$('.sube', vista).forEach(contar);
    $('#nuevaPag').onclick = () => formPagina();
    enlazarTablaPaginas();
}

function muestrasColores(c) {
    const n = { primario: 'Primario', secundario: 'Secund.', acento: 'Acento', fondo: 'Fondo', texto: 'Texto' };
    return `<div class="muestras">${Object.entries(n).map(([k, t]) => `<div class="muestra"><i style="background:${esc(c[k])}"></i><span>${t}</span><b>${esc(c[k])}</b></div>`).join('')}</div>`;
}

// Mini versión de la web con los colores actuales
function previaSitio(config, colores = config.colores) {
    const logo = config.logo?.variantes?.[0]?.url || LOGO_PIXELA;
    return `<div class="previa" id="previa" style="background:${esc(colores.fondo)};color:${esc(colores.texto)}">
        <div class="previa-nav" style="border-bottom:2px solid ${esc(colores.primario)}"><b><img src="${esc(logo)}" alt="">${esc(config.nombre_sitio)}</b>Inicio · Servicios · Contacto</div>
        <div class="previa-hero" style="background:radial-gradient(circle at 80% 30%, ${esc(colores.primario)}66, transparent 60%)">
            <h3>${esc(config.eslogan || config.nombre_sitio)}</h3><p>Así se ve tu web con estos colores</p>
            <span class="previa-btn" style="background:${esc(colores.acento)};box-shadow:3px 3px 0 ${esc(colores.primario)}">COTIZA GRATIS →</span></div>
        <div class="previa-cards"><div style="background:color-mix(in srgb, ${esc(colores.primario)} 45%, white)"></div><div style="background:color-mix(in srgb, ${esc(colores.secundario)} 45%, white)"></div><div style="background:color-mix(in srgb, ${esc(colores.acento)} 60%, white)"></div></div></div>`;
}

// ---------- Páginas ----------
function tablaPaginas(paginas, compacta = false) {
    if (!paginas.length) return '<div class="vacio"><b>Sin páginas</b>Crea la primera con “Nueva página”.</div>';
    return `<table><thead><tr><th>Título</th><th>Ruta</th><th>Bloques</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead><tbody>
        ${paginas.map(p => `<tr data-id="${p.id}">
            <td><b>${esc(p.titulo)}</b></td>
            <td><code>${p.es_inicio ? '/' : '/' + esc(p.slug)}</code></td>
            <td>${p.total_bloques}</td>
            <td>${p.es_inicio ? '<span class="pill durazno">INICIO</span>' : p.publicada ? '<span class="pill lima">PUBLICADA</span>' : '<span class="pill gris">BORRADOR</span>'}</td>
            <td><div class="btns">
                <a class="icono-btn" href="#pagina/${p.id}" title="Editar bloques">${ico('bloques')}</a>
                ${btnIco('editar', 'data-editar', 'Editar datos')}
                ${p.publicada ? `<a class="icono-btn" href="${SITIO}${p.es_inicio ? '' : esc(p.slug)}" target="_blank" rel="noopener" title="Ver en la web">${ico('ver')}</a>` : ''}
                ${!compacta && !p.es_inicio ? `<button class="icono-btn rojo" type="button" data-borrar title="Eliminar">${ico('borrar')}</button>` : ''}
            </div></td></tr>`).join('')}</tbody></table>`;
}

let cachePaginas = [];
function enlazarTablaPaginas() {
    $$('tr[data-id]', vista).forEach(tr => {
        const id = +tr.dataset.id;
        tr.querySelector('[data-editar]').onclick = async () => formPagina(await api('/admin/paginas/' + id));
        const b = tr.querySelector('[data-borrar]');
        if (b) b.onclick = () => confirmar(b, async () => {
            try { await api('/admin/paginas/' + id, { metodo: 'DELETE' }); toast('Página eliminada'); router(); }
            catch (e) { toast(e.message, 'error'); router(); }
        });
    });
}

async function vistaPaginas() {
    cachePaginas = await api('/admin/paginas');
    vista.innerHTML = cabecera('Páginas', '// cada página se arma con bloques que puedes ordenar, editar u ocultar',
        `<button class="btn" type="button" id="nuevaPag">${ico('mas')} Nueva página</button>`) + `
        <div class="contenido"><div class="caja"><h2 class="caja-titulo">Todas las páginas <span class="pill">${cachePaginas.length} REG.</span></h2>
        <div class="tabla-scroll">${tablaPaginas(cachePaginas)}</div></div></div>`;
    $('#nuevaPag').onclick = () => formPagina();
    enlazarTablaPaginas();
}

function formPagina(p = null) {
    const nueva = !p;
    drawer.abrir({
        etiqueta: nueva ? 'POST /admin/paginas' : `PUT /admin/paginas/${p.id}`,
        titulo: nueva ? 'Nueva página' : 'Editar página',
        sub: nueva ? 'Luego le agregas bloques' : `Ruta actual: /${p.es_inicio ? '' : p.slug}`,
        html: `
            <div class="fila-campo"><label class="lbl" for="pTitulo">Título</label><input class="campo" id="pTitulo" value="${esc(p?.titulo)}" placeholder="Ej. Portafolio"></div>
            <div class="fila-campo"><label class="lbl" for="pSlug">Slug (ruta)</label><input class="campo" id="pSlug" value="${esc(p?.slug)}" placeholder="se genera del título"><div class="ayuda">minúsculas, números y guiones · ej. mis-trabajos</div></div>
            <div class="fila-campo"><label class="lbl" for="pMeta">Descripción para Google</label><textarea class="campo" id="pMeta" maxlength="255">${esc(p?.meta_descripcion)}</textarea></div>
            <label class="switch"><div><b>Publicada</b><div class="ayuda">si la apagas, no se ve en la web</div></div><input type="checkbox" id="pPub" ${!p || p.publicada ? 'checked' : ''}><span class="tog"></span></label>
            <label class="switch"><div><b>Página de inicio</b><div class="ayuda">se muestra en la portada del sitio</div></div><input type="checkbox" id="pInicio" ${p?.es_inicio ? 'checked disabled' : ''}><span class="tog"></span></label>`,
        textoGuardar: nueva ? 'CREAR PÁGINA →' : 'GUARDAR →',
        alGuardar: async () => {
            const datos = { titulo: $('#pTitulo').value.trim(), meta_descripcion: $('#pMeta').value.trim(), publicada: $('#pPub').checked };
            const slug = $('#pSlug').value.trim();
            if (slug || !nueva) datos.slug = slug || p.slug;
            if ($('#pInicio').checked && !p?.es_inicio) datos.es_inicio = true;
            const r = nueva ? await api('/admin/paginas', { metodo: 'POST', datos }) : await api('/admin/paginas/' + p.id, { metodo: 'PUT', datos });
            drawer.cerrar();
            toast(nueva ? 'Página creada' : 'Página guardada');
            location.hash = nueva ? '#pagina/' + r.id : location.hash;
            if (!nueva) router();
        },
    });
    if (nueva) $('#pTitulo').addEventListener('input', e => {
        $('#pSlug').placeholder = e.target.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'se genera del título';
    });
}

// ---------- Bloques de una página ----------
function resumenBloque(b) {
    const c = b.contenido || {};
    const extra = Array.isArray(c.items) ? ` · ${c.items.length} elementos` : Array.isArray(c.pasos) ? ` · ${c.pasos.length} pasos` : '';
    return (c.titulo ? `${c.titulo}${c.resaltado ? ' ' + c.resaltado : ''}` : '(sin título)') + extra;
}

async function vistaPagina(id) {
    const [p] = await Promise.all([api('/admin/paginas/' + id), precargarImagenes()]);
    const bloques = p.bloques;
    vista.innerHTML = cabecera(esc(p.titulo), `// ${bloques.length} bloques · se muestran en este orden en la web`,
        `<a class="btn blanco" href="${SITIO}${p.es_inicio ? '' : esc(p.slug)}" target="_blank" rel="noopener">${ico('externo')} Ver página</a>
         <button class="btn" type="button" id="nuevoBloque">${ico('mas')} Agregar bloque</button>`,
        `<a href="#paginas">Páginas</a> / ${esc(p.titulo)}`) + `
        <div class="contenido"><div class="lista" id="listaBloques">
        ${bloques.length ? bloques.map((b, i) => `
            <div class="item ${b.visible ? '' : 'apagado'}" data-id="${b.id}">
                <span class="orden">${i + 1}</span>
                <span class="pill ${BLOQUES[b.tipo]?.color || 'gris'}">${esc(b.tipo.toUpperCase())}</span>
                <div class="info"><b>${esc(resumenBloque(b))}</b><span>${esc(BLOQUES[b.tipo]?.nombre || b.tipo)} · ${b.visible ? 'visible' : 'oculto'}</span></div>
                <div class="btns">
                    <label class="mini-tog" title="Visible en la web"><input type="checkbox" data-visible ${b.visible ? 'checked' : ''}><span class="tog"></span></label>
                    ${btnIco('arriba', `data-mover="-1" ${i === 0 ? 'disabled' : ''}`, 'Subir')}
                    ${btnIco('abajo', `data-mover="1" ${i === bloques.length - 1 ? 'disabled' : ''}`, 'Bajar')}
                    ${btnIco('editar', 'data-editar', 'Editar contenido')}
                    <button class="icono-btn rojo" type="button" data-borrar title="Eliminar">${ico('borrar')}</button>
                </div>
            </div>`).join('') : '<div class="vacio"><b>Esta página no tiene bloques</b>Agrega el primero con “Agregar bloque”.</div>'}
        </div></div>`;

    $('#nuevoBloque').onclick = () => elegirTipoBloque(p);
    const ids = bloques.map(b => b.id);
    $$('.item', vista).forEach((el, i) => {
        const b = bloques[i];
        el.querySelector('[data-visible]').onchange = async (e) => {
            try { await api('/admin/bloques/' + b.id, { metodo: 'PUT', datos: { visible: e.target.checked } }); toast(e.target.checked ? 'Bloque visible' : 'Bloque oculto'); router(); }
            catch (err) { toast(err.message, 'error'); }
        };
        $$('[data-mover]', el).forEach(btn => btn.onclick = async () => {
            const nuevo = [...ids];
            const j = i + +btn.dataset.mover;
            [nuevo[i], nuevo[j]] = [nuevo[j], nuevo[i]];
            try { await api(`/admin/paginas/${p.id}/bloques/orden`, { metodo: 'PUT', datos: { ids: nuevo } }); toast('Orden actualizado'); router(); }
            catch (err) { toast(err.message, 'error'); }
        });
        el.querySelector('[data-editar]').onclick = () => formBloque(p, b);
        const borrar = el.querySelector('[data-borrar]');
        borrar.onclick = () => confirmar(borrar, async () => {
            try { await api('/admin/bloques/' + b.id, { metodo: 'DELETE' }); toast('Bloque eliminado'); }
            catch (err) { toast(err.message, 'error'); }
            router();
        });
    });
}

function elegirTipoBloque(p) {
    drawer.abrir({
        etiqueta: `POST /admin/paginas/${p.id}/bloques`, titulo: 'Agregar bloque', sub: 'Elige qué tipo de sección quieres agregar',
        html: `<div class="tipos">${Object.entries(BLOQUES).map(([k, b]) => `<button type="button" class="tipo sticker" style="background:var(--${b.color})" data-tipo="${k}"><b>${esc(b.nombre)}</b><span>${esc(b.desc)}</span></button>`).join('')}</div>`,
    });
    $$('[data-tipo]', $('#drawerCuerpo')).forEach(b => b.onclick = async () => {
        try {
            const nuevo = await api(`/admin/paginas/${p.id}/bloques`, { metodo: 'POST', datos: { tipo: b.dataset.tipo, contenido: contenidoVacio(b.dataset.tipo) } });
            toast('Bloque agregado al final');
            formBloque(p, nuevo);
            vistaPagina(p.id).catch(error);
        } catch (e) { toast(e.message, 'error'); }
    });
}

function formBloque(p, b) {
    const def = BLOQUES[b.tipo] || { nombre: b.tipo, campos: [] };
    editor.estado = structuredClone(b.contenido && !Array.isArray(b.contenido) ? b.contenido : {});
    editor.esquema = def.campos;
    let pestana = 'contenido';

    drawer.abrir({
        etiqueta: `PUT /admin/bloques/${b.id}`, titulo: 'Editar bloque',
        sub: `${p.titulo} · tipo ${b.tipo} · orden ${b.orden}`,
        html: `<div class="tabs"><button type="button" class="on" data-p="contenido">Contenido</button><button type="button" data-p="json">JSON</button></div><div id="editorCampos"></div>`,
        textoGuardar: 'GUARDAR CAMBIOS →',
        alGuardar: async () => {
            if (pestana === 'json') leerJson();
            await api('/admin/bloques/' + b.id, { metodo: 'PUT', datos: { contenido: editor.estado } });
            drawer.cerrar();
            toast(`Bloque “${def.nombre}” guardado`);
            router();
        },
    });
    const cont = $('#editorCampos');
    const leerJson = () => {
        try {
            const obj = JSON.parse($('#jsonBloque').value);
            if (!obj || typeof obj !== 'object' || Array.isArray(obj)) throw new Error();
            editor.estado = obj;
        } catch { throw new Error('El JSON no es válido: revisa comas y comillas'); }
    };
    const mostrar = () => {
        if (pestana === 'json') cont.innerHTML = `<textarea class="campo json" id="jsonBloque" spellcheck="false">${esc(JSON.stringify(editor.estado, null, 2))}</textarea><div class="ayuda">Es lo que se guarda en la columna JSON “contenido”.</div>`;
        else editor.pintar(cont);
    };
    $$('.tabs button', $('#drawerCuerpo')).forEach(t => t.onclick = () => {
        if (t.dataset.p === pestana) return;
        try { if (pestana === 'json') leerJson(); } catch (e) { return toast(e.message, 'error'); }
        pestana = t.dataset.p;
        $$('.tabs button', $('#drawerCuerpo')).forEach(x => x.classList.toggle('on', x === t));
        mostrar();
    });
    mostrar();
}

// ---------- Menú ----------
async function vistaMenu() {
    const [arbol, paginas] = await Promise.all([api('/admin/menu'), api('/admin/paginas')]);
    cachePaginas = paginas;
    const fila = (it, i, hermanos, hijo) => `
        <div class="item ${hijo ? 'hijo' : ''} ${it.visible && !it.sin_destino ? '' : 'apagado'}" data-id="${it.id}">
            <span class="orden">${i + 1}</span>
            <div class="info"><b>${esc(it.etiqueta)}${it.sin_destino ? ' ⚠' : ''}</b><span>${it.sin_destino ? 'Sin destino: su página fue borrada' : (it.pagina_id ? 'Página → ' : 'Enlace → ') + esc(it.url)}</span></div>
            <div class="btns">
                ${it.externo ? '<span class="pill cielo">EXTERNO</span>' : ''}
                <label class="mini-tog" title="Visible"><input type="checkbox" data-visible ${it.visible ? 'checked' : ''}><span class="tog"></span></label>
                ${btnIco('arriba', `data-mover="-1" ${i === 0 ? 'disabled' : ''}`, 'Subir')}
                ${btnIco('abajo', `data-mover="1" ${i === hermanos.length - 1 ? 'disabled' : ''}`, 'Bajar')}
                ${btnIco('editar', 'data-editar', 'Editar')}
                <button class="icono-btn rojo" type="button" data-borrar title="Eliminar">${ico('borrar')}</button>
            </div></div>`;
    vista.innerHTML = cabecera('Menú', '// así se ve la navegación de tu web · máximo 2 niveles',
        `<button class="btn" type="button" id="nuevoItem">${ico('mas')} Nuevo ítem</button>`) + `
        <div class="contenido"><div class="lista">
        ${arbol.length ? arbol.map((it, i) => fila(it, i, arbol, false) + it.hijos.map((h, j) => fila(h, j, it.hijos, true)).join('')).join('') : '<div class="vacio"><b>El menú está vacío</b>Agrega el primer ítem.</div>'}
        </div></div>`;

    const plano = [];
    arbol.forEach(it => { plano.push(it); it.hijos.forEach(h => plano.push(h)); });
    $('#nuevoItem').onclick = () => formMenu(null, arbol);

    $$('.item', vista).forEach(el => {
        const it = plano.find(x => x.id === +el.dataset.id);
        const hermanos = it.parent_id ? arbol.find(a => a.id === it.parent_id).hijos : arbol;
        el.querySelector('[data-visible]').onchange = async (e) => {
            try { await api('/admin/menu/' + it.id, { metodo: 'PUT', datos: { visible: e.target.checked } }); toast('Menú actualizado'); router(); }
            catch (err) { toast(err.message, 'error'); router(); }
        };
        $$('[data-mover]', el).forEach(b => b.onclick = async () => {
            const lista = [...hermanos];
            const i = lista.indexOf(it), j = i + +b.dataset.mover;
            [lista[i], lista[j]] = [lista[j], lista[i]];
            const pos = new Map(lista.map((x, k) => [x.id, k + 1]));
            const items = plano.map(x => ({ id: x.id, parent_id: x.parent_id, orden: pos.get(x.id) ?? x.orden }));
            try { await api('/admin/menu/orden', { metodo: 'PUT', datos: { items } }); toast('Orden actualizado'); router(); }
            catch (err) { toast(err.message, 'error'); }
        });
        el.querySelector('[data-editar]').onclick = () => formMenu(it, arbol);
        const borrar = el.querySelector('[data-borrar]');
        borrar.onclick = () => confirmar(borrar, async () => {
            try { await api('/admin/menu/' + it.id, { metodo: 'DELETE' }); toast('Ítem eliminado'); }
            catch (err) { toast(err.message, 'error'); }
            router();
        });
    });
}

function formMenu(it, arbol) {
    const nuevo = !it;
    const conPagina = nuevo ? true : !!it.pagina_id || it.sin_destino;
    const padres = arbol.filter(a => !it || a.id !== it.id);
    drawer.abrir({
        etiqueta: nuevo ? 'POST /admin/menu' : `PUT /admin/menu/${it.id}`,
        titulo: nuevo ? 'Nuevo ítem' : 'Editar ítem', sub: 'Elige a dónde lleva este enlace',
        html: `
            <div class="fila-campo"><label class="lbl" for="mEtiqueta">Texto del enlace</label><input class="campo" id="mEtiqueta" value="${esc(it?.etiqueta)}" maxlength="60" placeholder="Ej. Portafolio"></div>
            <div class="tabs"><button type="button" data-d="pagina" class="${conPagina ? 'on' : ''}">Página del sitio</button><button type="button" data-d="url" class="${conPagina ? '' : 'on'}">Enlace / ancla</button></div>
            <div class="fila-campo ${conPagina ? '' : 'oculto'}" id="mPaginaFila"><label class="lbl" for="mPagina">Página</label>
                <select class="campo" id="mPagina">${cachePaginas.map(p => `<option value="${p.id}" ${it?.pagina_id === p.id ? 'selected' : ''}>${esc(p.titulo)} (/${p.es_inicio ? '' : esc(p.slug)})</option>`).join('')}</select></div>
            <div class="fila-campo ${conPagina ? 'oculto' : ''}" id="mUrlFila"><label class="lbl" for="mUrl">Enlace</label><input class="campo" id="mUrl" value="${esc(it?.url_externa)}" placeholder="https://instagram.com/... · /contacto · #planes"></div>
            <div class="fila-campo"><label class="lbl" for="mPadre">Ubicación</label>
                <select class="campo" id="mPadre" ${it?.hijos?.length ? 'disabled' : ''}><option value="">Menú principal</option>${padres.map(a => `<option value="${a.id}" ${it?.parent_id === a.id ? 'selected' : ''}>Submenú de “${esc(a.etiqueta)}”</option>`).join('')}</select>
                ${it?.hijos?.length ? '<div class="ayuda">Tiene submenús, por eso queda en el menú principal.</div>' : ''}</div>
            <label class="switch"><b>Visible en la web</b><input type="checkbox" id="mVisible" ${!it || it.visible ? 'checked' : ''}><span class="tog"></span></label>`,
        textoGuardar: nuevo ? 'CREAR →' : 'GUARDAR →',
        alGuardar: async () => {
            const usaPagina = $('#mPaginaFila').classList.contains('oculto') === false;
            const datos = { etiqueta: $('#mEtiqueta').value.trim(), visible: $('#mVisible').checked, parent_id: $('#mPadre').value || null };
            if (usaPagina) { datos.pagina_id = +$('#mPagina').value; datos.url_externa = null; }
            else { datos.url_externa = $('#mUrl').value.trim(); datos.pagina_id = null; }
            if (nuevo) await api('/admin/menu', { metodo: 'POST', datos });
            else await api('/admin/menu/' + it.id, { metodo: 'PUT', datos });
            drawer.cerrar();
            toast(nuevo ? 'Ítem creado' : 'Ítem guardado');
            router();
        },
    });
    $$('.tabs button', $('#drawerCuerpo')).forEach(t => t.onclick = () => {
        $$('.tabs button', $('#drawerCuerpo')).forEach(x => x.classList.toggle('on', x === t));
        $('#mPaginaFila').classList.toggle('oculto', t.dataset.d !== 'pagina');
        $('#mUrlFila').classList.toggle('oculto', t.dataset.d === 'pagina');
    });
}

// ---------- Imágenes ----------
async function vistaMedia() {
    const medios = await api('/admin/media');
    vista.innerHTML = cabecera('Imágenes', '// al subir una imagen se crean versiones de 480, 768 y 1280 px en WEBP') + `
        <div class="contenido">
            <label class="zona-subida" id="zona">${ico('subir')}<b>Arrastra imágenes aquí o haz clic</b><span>JPG, PNG, WEBP o GIF · máximo 5 MB cada una</span>
                <input type="file" accept="image/*" multiple hidden id="archivos"></label>
            ${medios.length ? `<div class="galeria">${medios.map(m => `
                <div class="foto" data-id="${m.id}">
                    <div class="img"><img src="${esc(m.variantes[0]?.url)}" srcset="${esc(m.srcset)}" sizes="220px" alt="${esc(m.alt)}" loading="lazy"></div>
                    <div class="datos"><b>#${m.id} · ${esc(m.nombre_original)}</b>
                        <span class="mono">${m.ancho_original}×${m.alto_original}px · ${m.variantes.map(v => v.ancho).join(' / ')}</span>
                        <input class="campo" data-alt value="${esc(m.alt)}" placeholder="Texto alternativo" maxlength="255">
                        <div class="btns">${btnIco('copiar', 'data-copiar', 'Copiar URL')}<a class="icono-btn" href="${esc(m.url)}" target="_blank" rel="noopener" title="Ver">${ico('ver')}</a>
                        <button class="icono-btn rojo" type="button" data-borrar title="Eliminar">${ico('borrar')}</button></div></div>
                </div>`).join('')}</div>` : '<div class="vacio"><b>Aún no hay imágenes</b>Sube la primera en la zona de arriba.</div>'}
        </div>`;

    const zona = $('#zona');
    const subirVarios = async (archivos) => {
        let ok = 0;
        for (const f of archivos) {
            try { await subirArchivo(f); ok++; } catch (e) { toast(`${f.name}: ${e.message}`, 'error'); }
        }
        if (ok) toast(ok === 1 ? 'Imagen subida' : `${ok} imágenes subidas`);
        router();
    };
    $('#archivos').onchange = e => subirVarios([...e.target.files]);
    ['dragenter', 'dragover'].forEach(ev => zona.addEventListener(ev, e => { e.preventDefault(); zona.classList.add('encima'); }));
    ['dragleave', 'drop'].forEach(ev => zona.addEventListener(ev, e => { e.preventDefault(); zona.classList.remove('encima'); }));
    zona.addEventListener('drop', e => subirVarios([...e.dataTransfer.files]));

    $$('.foto', vista).forEach(f => {
        const m = medios.find(x => x.id === +f.dataset.id);
        f.querySelector('[data-alt]').onchange = async (e) => {
            try { await api('/admin/media/' + m.id, { metodo: 'PUT', datos: { alt: e.target.value } }); toast('Texto alternativo guardado'); }
            catch (err) { toast(err.message, 'error'); }
        };
        f.querySelector('[data-copiar]').onclick = async () => {
            try { await navigator.clipboard.writeText(location.origin + m.url); toast('URL copiada'); } catch { toast('No se pudo copiar', 'error'); }
        };
        const b = f.querySelector('[data-borrar]');
        b.onclick = () => confirmar(b, async () => {
            try { await api('/admin/media/' + m.id, { metodo: 'DELETE' }); toast('Imagen eliminada'); }
            catch (err) { toast(err.message, 'error'); }
            router();
        });
    });
}

// ---------- Mensajes ----------
function actualizarContador(n) {
    const c = $('#contMensajes');
    c.textContent = n;
    c.classList.toggle('oculto', !n);
}

async function vistaMensajes() {
    const lista = await api('/admin/mensajes');
    const sinLeer = lista.filter(m => !m.leido).length;
    actualizarContador(sinLeer);
    vista.innerHTML = cabecera('Mensajes', `// lo que envían desde el formulario de contacto · ${sinLeer} sin leer`) + `
        <div class="contenido"><div class="lista">
        ${lista.length ? lista.map(m => `
            <div class="mensaje ${m.leido ? '' : 'nuevo'}" data-id="${m.id}">
                <div class="cab"><b>${esc(m.nombre)}</b>${m.leido ? '<span class="pill gris">LEÍDO</span>' : '<span class="pill rosa">NUEVO</span>'}
                    ${m.servicio_interes ? `<span class="pill cielo">${esc(m.servicio_interes)}</span>` : ''}<span class="mono">${esc(fecha(m.created_at))}</span></div>
                <div class="mono" style="font-size:12.5px;color:var(--tn);margin-bottom:8px">${esc(m.email)}${m.telefono ? ' · ' + esc(m.telefono) : ''}</div>
                <p>${esc(m.mensaje)}</p>
                <div class="btns">
                    <a class="btn chico" href="mailto:${esc(m.email)}?subject=${encodeURIComponent('Re: tu consulta a Pixela')}">${ico('correo')} Responder</a>
                    <button class="btn blanco chico" type="button" data-leido>${m.leido ? 'Marcar como nuevo' : ico('check') + ' Marcar leído'}</button>
                    <button class="icono-btn rojo" type="button" data-borrar title="Eliminar">${ico('borrar')}</button>
                </div></div>`).join('') : '<div class="vacio"><b>No hay mensajes todavía</b>Cuando alguien escriba desde la web, aparecerá aquí.</div>'}
        </div></div>`;
    $$('.mensaje', vista).forEach(el => {
        const m = lista.find(x => x.id === +el.dataset.id);
        el.querySelector('[data-leido]').onclick = async () => {
            try { await api('/admin/mensajes/' + m.id, { metodo: 'PUT', datos: { leido: !m.leido } }); router(); }
            catch (err) { toast(err.message, 'error'); }
        };
        const b = el.querySelector('[data-borrar]');
        b.onclick = () => confirmar(b, async () => {
            try { await api('/admin/mensajes/' + m.id, { metodo: 'DELETE' }); toast('Mensaje eliminado'); }
            catch (err) { toast(err.message, 'error'); }
            router();
        });
    });
}

// ---------- Configuración: colores y logo (solo admin) ----------
const COLORES_PIXELA = { primario: '#8B5CF6', secundario: '#2563EB', acento: '#C8FF3D', fondo: '#0B0B14', texto: '#F5F5FA' };

async function vistaConfig() {
    if (!ES_ADMIN) {
        vista.innerHTML = `<div class="contenido"><div class="vacio"><b>Solo el administrador puede cambiar los colores y el logo</b>Tu rol es editor.</div></div>`;
        return;
    }
    const c = await api('/config');
    const colores = { ...c.colores };
    const logo = { id: c.logo?.id || null, url: c.logo?.variantes?.[0]?.url || null };
    const favicon = { id: c.favicon?.id || null, url: c.favicon?.variantes?.[0]?.url || null };
    const nombres = { primario: 'Primario', secundario: 'Secundario', acento: 'Acento', fondo: 'Fondo', texto: 'Texto' };
    const redes = ['facebook', 'instagram', 'tiktok', 'linkedin', 'youtube'];
    const selImg = (k, o) => `<div class="selector-img"><div class="miniatura" id="mini-${k}" style="${o.url ? `background-image:url('${esc(o.url)}')` : ''}">${o.url ? '' : 'SIN<br>IMAGEN'}</div>
        <button class="btn blanco chico" type="button" id="elegir-${k}">${ico('imagen')} Elegir</button></div>`;

    vista.innerHTML = cabecera('Colores y logo', '// cambia la identidad de toda la web sin tocar código',
        `<a class="btn blanco" href="${SITIO}" target="_blank" rel="noopener">${ico('externo')} Ver sitio</a><button class="btn" type="button" id="guardarConfig">GUARDAR CAMBIOS →</button>`) + `
        <div class="contenido">
            <div class="rejilla-2">
                <div class="caja"><h2 class="caja-titulo">01 // Paleta de colores <button class="btn blanco chico" type="button" id="restaurar">Restaurar Pixela</button></h2>
                    <div class="colores-form">${Object.entries(nombres).map(([k, t]) => `
                        <div class="color-campo"><span>${t}</span><input type="color" data-color="${k}" value="${esc(colores[k])}" aria-label="${t}">
                        <input type="text" data-hex="${k}" value="${esc(colores[k])}" maxlength="7" aria-label="${t} en HEX"></div>`).join('')}</div>
                    <p class="mono" style="font-size:12px;color:var(--tn);margin:14px 0 0">Se guardan en HEX en site_config y la web los usa como variables CSS (--color-primario, etc.).</p>
                </div>
                <div class="caja violeta"><h2 class="caja-titulo">02 // Vista previa <span class="vivo">EN VIVO</span></h2><div id="previaCont"></div></div>
            </div>
            <div class="rejilla-2">
                <div class="caja"><h2 class="caja-titulo">03 // Identidad</h2>
                    <div class="dos-col">
                        <div class="fila-campo"><label class="lbl" for="cNombre">Nombre del sitio</label><input class="campo" id="cNombre" value="${esc(c.nombre_sitio)}" maxlength="100"></div>
                        <div class="fila-campo"><label class="lbl" for="cEslogan">Eslogan</label><input class="campo" id="cEslogan" value="${esc(c.eslogan)}" maxlength="200"></div>
                    </div>
                    <div class="dos-col">
                        <div class="fila-campo"><label class="lbl">Logo</label>${selImg('logo', logo)}</div>
                        <div class="fila-campo"><label class="lbl">Favicon</label>${selImg('favicon', favicon)}</div>
                    </div>
                </div>
                <div class="caja"><h2 class="caja-titulo">04 // Contacto y redes</h2>
                    <div class="dos-col">
                        <div class="fila-campo"><label class="lbl" for="cWa">WhatsApp</label><input class="campo" id="cWa" value="${esc(c.whatsapp)}" placeholder="51987654321"></div>
                        <div class="fila-campo"><label class="lbl" for="cEmail">Email</label><input class="campo" id="cEmail" value="${esc(c.email_contacto)}"></div>
                    </div>
                    <div class="fila-campo"><label class="lbl" for="cDir">Dirección (también se usa para el mapa)</label><input class="campo" id="cDir" value="${esc(c.direccion)}"></div>
                    <div class="dos-col">${redes.map(r => `<div class="fila-campo"><label class="lbl" for="red-${r}">${r}</label><input class="campo" id="red-${r}" value="${esc(c.redes_sociales?.[r] || '')}" placeholder="https://"></div>`).join('')}</div>
                </div>
            </div>
        </div>`;

    const repintar = () => {
        const cfg = { ...c, nombre_sitio: $('#cNombre').value, eslogan: $('#cEslogan').value, logo: logo.url ? { variantes: [{ url: logo.url }] } : null };
        $('#previaCont').innerHTML = previaSitio(cfg, colores) + muestrasColores(colores).replace('class="muestras"', 'class="muestras" style="margin-top:14px"');
    };
    const ponerColor = (k, v) => {
        colores[k] = v.toUpperCase();
        $(`[data-color="${k}"]`).value = v;
        $(`[data-hex="${k}"]`).value = v.toUpperCase();
        repintar();
    };
    $$('[data-color]').forEach(i => i.oninput = () => ponerColor(i.dataset.color, i.value));
    $$('[data-hex]').forEach(i => i.oninput = () => { if (/^#[0-9a-f]{6}$/i.test(i.value)) ponerColor(i.dataset.hex, i.value); });
    $('#cNombre').oninput = $('#cEslogan').oninput = repintar;
    $('#restaurar').onclick = () => Object.entries(COLORES_PIXELA).forEach(([k, v]) => ponerColor(k, v));
    [['logo', logo], ['favicon', favicon]].forEach(([k, o]) => $('#elegir-' + k).onclick = () => elegirImagen(m => {
        o.id = m ? m.id : null;
        o.url = m ? m.variantes[0]?.url : null;
        const mini = $('#mini-' + k);
        mini.style.backgroundImage = o.url ? `url('${o.url}')` : '';
        mini.innerHTML = o.url ? '' : 'SIN<br>IMAGEN';
        repintar();
    }));
    repintar();

    $('#guardarConfig').onclick = async (e) => {
        e.target.disabled = true;
        try {
            const red = {};
            redes.forEach(r => red[r] = $('#red-' + r).value.trim());
            await api('/admin/config', { metodo: 'PUT', datos: {
                nombre_sitio: $('#cNombre').value, eslogan: $('#cEslogan').value, colores,
                logo_id: logo.id, favicon_id: favicon.id,
                whatsapp: $('#cWa').value, email_contacto: $('#cEmail').value, direccion: $('#cDir').value, redes_sociales: red,
            } });
            toast('Configuración guardada · la web ya cambió');
        } catch (err) { toast(err.message, 'error'); }
        e.target.disabled = false;
    };
}

// =====================================================
//  Router por #hash
// =====================================================
const VISTAS = { dashboard: vistaDashboard, paginas: vistaPaginas, pagina: vistaPagina, menu: vistaMenu, media: vistaMedia, mensajes: vistaMensajes, config: vistaConfig };
const CINTA = { dashboard: 'DASHBOARD', paginas: 'PÁGINAS', pagina: 'BLOQUES', menu: 'MENÚ', media: 'IMÁGENES', mensajes: 'MENSAJES', config: 'COLORES' };

async function router() {
    const [nombre, param] = (location.hash.slice(1) || 'dashboard').split('/');
    const fn = VISTAS[nombre] || vistaDashboard;
    const activa = nombre === 'pagina' ? 'paginas' : (VISTAS[nombre] ? nombre : 'dashboard');
    $$('#nav a[data-vista]').forEach(a => a.classList.toggle('activo', a.dataset.vista === activa));
    $('#cinta').textContent = Array(14).fill(`${CINTA[nombre] || 'DASHBOARD'} ✦ PIXELA CMS ✦ JSON ✦ JWT ✦ `).join('');
    cerrarMenuMovil();
    cargando();
    try { await fn(param); } catch (e) { if (e.message !== 'Sesión expirada') error(e); }
    window.scrollTo({ top: 0 });
}
window.addEventListener('hashchange', router);

// ---------- Menú lateral en celular ----------
function cerrarMenuMovil() { $('#side').classList.remove('abierto'); $('#veloSide').classList.remove('on'); }
$('#btnMenu').onclick = () => { $('#side').classList.add('abierto'); $('#veloSide').classList.add('on'); };
$('#veloSide').onclick = cerrarMenuMovil;

// ---------- Sesión: cuenta regresiva del JWT y cierre ----------
const EXP = +CUERPO.exp * 1000;
const DURACION = +CUERPO.duracion * 1000;
function relojSesion() {
    const resta = EXP - Date.now();
    if (resta <= 0) { location.href = 'login.php?expirada=1'; return; }
    const h = Math.floor(resta / 3600000), m = Math.floor(resta % 3600000 / 60000), s = Math.floor(resta % 60000 / 1000);
    $('#tiempoSesion').textContent = `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    $('#barraSesion').style.width = Math.max(0, Math.min(100, resta / DURACION * 100)) + '%';
}
setInterval(relojSesion, 1000);
relojSesion();

$('#btnSalir').onclick = async () => {
    try { await fetch(API + '/auth/logout', { method: 'POST', credentials: 'same-origin' }); } catch { /* igual salimos */ }
    location.href = 'login.php?salio=1';
};

// Contador de mensajes nuevos en el menú
api('/admin/resumen').then(r => actualizarContador(r.mensajes_sin_leer)).catch(() => {});
router();
