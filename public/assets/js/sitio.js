/* PIXELA CMS · Web pública: menú móvil, animaciones al hacer scroll y formulario de contacto */
'use strict';

const API = document.body.dataset.api;

// ---------- Menú en celular ----------
const btnMenu = document.getElementById('btnMenu');
const nav = document.getElementById('nav');
if (btnMenu) {
    btnMenu.addEventListener('click', () => {
        const abierto = nav.classList.toggle('abierto');
        btnMenu.setAttribute('aria-expanded', String(abierto));
    });
    // en celular, tocar un ítem con submenú lo despliega en vez de navegar
    document.querySelectorAll('.con-sub > a').forEach(a => a.addEventListener('click', (e) => {
        if (window.matchMedia('(max-width: 1024px)').matches) {
            e.preventDefault();
            a.parentElement.classList.toggle('abierto');
        }
    }));
    nav.querySelectorAll('.sub a, .nav > ul > li:not(.con-sub) > a').forEach(a => a.addEventListener('click', () => {
        nav.classList.remove('abierto');
        btnMenu.setAttribute('aria-expanded', 'false');
    }));
}

// ---------- Cabecera con sombra al bajar ----------
const cabecera = document.getElementById('cabecera');
window.addEventListener('scroll', () => cabecera.classList.toggle('con-scroll', window.scrollY > 20), { passive: true });

// ---------- ✦ Aparición de elementos al hacer scroll ----------
const elementos = document.querySelectorAll('.revelar');
if ('IntersectionObserver' in window) {
    const obs = new IntersectionObserver((entradas) => entradas.forEach(en => {
        if (en.isIntersecting) { en.target.classList.add('visible'); obs.unobserve(en.target); }
    }), { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    elementos.forEach(el => obs.observe(el));
} else {
    elementos.forEach(el => el.classList.add('visible'));
}

// ---------- Formulario de contacto -> POST /api/contacto ----------
const form = document.getElementById('formContacto');
if (form) {
    const aviso = document.getElementById('avisoContacto');
    const mostrar = (tipo, texto) => { aviso.className = 'aviso ' + tipo; aviso.textContent = texto; };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const datos = Object.fromEntries(new FormData(form).entries());
        if (!datos.nombre.trim() || !datos.email.trim() || datos.mensaje.trim().length < 10) {
            return mostrar('error', 'Completa tu nombre, tu email y un mensaje de al menos 10 caracteres.');
        }
        const boton = form.querySelector('button[type=submit]');
        boton.disabled = true;
        try {
            const r = await fetch(API + '/contacto', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(datos) });
            const json = await r.json();
            if (!r.ok) throw new Error(json.error || 'No se pudo enviar');
            mostrar('ok', '✔ ' + json.mensaje);
            form.reset();
        } catch (err) {
            mostrar('error', '✖ ' + (err.message === 'Failed to fetch' ? 'Sin conexión. Intenta de nuevo.' : err.message));
        }
        boton.disabled = false;
    });
}
