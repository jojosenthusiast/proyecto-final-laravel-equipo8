'use strict';

const enlacesErrores = document.querySelectorAll('.resumen-errores a');
for (const enlace of enlacesErrores) {
    const campo = document.getElementById(enlace.hash.slice(1));
    if (!campo?.matches('input, select, textarea')) {
        enlace.replaceWith(enlace.textContent);
        continue;
    }

    // Algunos formularios existentes muestran el mensaje sin atributos en el campo.
    campo.setAttribute('aria-invalid', 'true');
    const error = campo.closest('.campo')?.querySelector('.error-campo');
    if (error && !campo.hasAttribute('aria-describedby')) {
        error.id ||= `${campo.id}-error`;
        campo.setAttribute('aria-describedby', error.id);
    }

    enlace.addEventListener('click', (evento) => {
        evento.preventDefault();
        campo.focus();
    });
}
document.querySelector('main [aria-invalid="true"]')?.focus({ preventScroll: true });

for (const boton of document.querySelectorAll('[data-cerrar-alerta]')) {
    boton.hidden = false;
    boton.addEventListener('click', () => {
        boton.closest('.alerta').remove();
        const titulo = document.querySelector('main h1');
        if (titulo) {
            titulo.tabIndex = -1;
            titulo.focus();
        }
    });
}

const dialogo = document.getElementById('confirmar-eliminacion');
if (typeof dialogo?.showModal === 'function') {
    let formularioPendiente;
    let botonOrigen;

    // Captura el envío antes del onsubmit nativo, que sigue disponible si este archivo no carga.
    document.addEventListener('submit', (evento) => {
        const formulario = evento.target;
        if (!formulario.matches('form[data-confirmar]') || evento.defaultPrevented) {
            return;
        }

        evento.preventDefault();
        evento.stopImmediatePropagation();
        if (dialogo.open) {
            return;
        }

        formularioPendiente = formulario;
        botonOrigen = evento.submitter;
        dialogo.returnValue = 'cancelar';
        document.getElementById('mensaje-confirmacion').textContent = formulario.dataset.confirmar;
        dialogo.showModal();
    }, { capture: true });

    dialogo.addEventListener('close', () => {
        if (dialogo.returnValue === 'eliminar' && formularioPendiente?.isConnected) {
            HTMLFormElement.prototype.submit.call(formularioPendiente);
        } else {
            botonOrigen?.focus();
        }
        formularioPendiente = undefined;
        botonOrigen = undefined;
    });
}

for (const campo of document.querySelectorAll('input[type="file"][data-vista-previa]')) {
    const imagen = document.getElementById(campo.dataset.vistaPrevia);
    if (!(imagen instanceof HTMLImageElement)) {
        continue;
    }

    const fuenteOriginal = imagen.getAttribute('src');
    const aviso = document.createElement('p');
    aviso.className = 'aviso-imagen';
    aviso.id = `${campo.id}-limites`;
    aviso.setAttribute('aria-live', 'polite');
    const instrucciones = 'JPG, PNG o WEBP. Máximo 2 MB.';
    aviso.textContent = instrucciones;
    campo.before(aviso);
    campo.setAttribute('aria-describedby', [campo.getAttribute('aria-describedby'), aviso.id].filter(Boolean).join(' '));

    let urlTemporal;
    campo.addEventListener('change', () => {
        if (urlTemporal) {
            URL.revokeObjectURL(urlTemporal);
            urlTemporal = undefined;
        }
        imagen.setAttribute('src', fuenteOriginal);
        aviso.textContent = instrucciones;
        aviso.classList.remove('aviso-imagen-error');

        const archivo = campo.files[0];
        if (!archivo) {
            return;
        }

        if (archivo.size > 2 * 1024 * 1024 || !/\.(jpe?g|png|webp)$/i.test(archivo.name)) {
            aviso.textContent = 'La foto debe ser JPG, PNG o WEBP y pesar como máximo 2 MB.';
            aviso.classList.add('aviso-imagen-error');
            return;
        }

        urlTemporal = URL.createObjectURL(archivo);
        imagen.src = urlTemporal;
    });
}

const botonMenu = document.querySelector('.boton-menu');
const menu = document.getElementById('menu-principal');
if (botonMenu && menu) {
    const pantallaEstrecha = window.matchMedia('(max-width: 639px)');
    const actualizarMenu = (abierto) => {
        botonMenu.hidden = !pantallaEstrecha.matches;
        botonMenu.setAttribute('aria-expanded', String(abierto));
        menu.hidden = pantallaEstrecha.matches && !abierto;
    };

    actualizarMenu(false);
    pantallaEstrecha.addEventListener('change', () => actualizarMenu(false));
    botonMenu.addEventListener('click', () => {
        actualizarMenu(botonMenu.getAttribute('aria-expanded') === 'false');
    });
    menu.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && pantallaEstrecha.matches) {
            actualizarMenu(false);
            botonMenu.focus();
        }
    });
}

// Conserva el orden de los listados cuando los encabezados pasan a etiquetas de tarjeta.
for (const tabla of document.querySelectorAll('.tabla')) {
    const enlaces = tabla.querySelectorAll('thead th a');
    if (!enlaces.length) {
        continue;
    }

    const opciones = document.createElement('details');
    opciones.className = 'orden-tabla-movil';
    const titulo = document.createElement('summary');
    titulo.textContent = 'Ordenar resultados';
    const lista = document.createElement('ul');
    for (const enlace of enlaces) {
        const opcion = document.createElement('li');
        opcion.append(enlace.cloneNode(true));
        lista.append(opcion);
    }
    opciones.append(titulo, lista);
    tabla.before(opciones);
    tabla.dataset.ordenMovil = '';
}

for (const boton of document.querySelectorAll('[data-imprimir]')) {
    boton.hidden = false;
    boton.addEventListener('click', () => window.print());
}
