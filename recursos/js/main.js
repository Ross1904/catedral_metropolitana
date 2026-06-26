const opcionesPreguntas = [
    "¿Nombre de tu primera mascota?",
    "¿Ciudad donde naciste?",
    "¿Nombre de tu escuela primaria?",
    "¿Tu color favorito de niño?",
    "¿Nombre de tu mejor amigo?",
    "¿Marca de tu primer teléfono?",
    "¿Tu comida favorita?",
    "¿Nombre de tu abuela materna?",
    "¿Año en que te graduaste?",
    "¿Nombre de tu calle favorita?"
];

let slideActual = 1; 

document.addEventListener('DOMContentLoaded', () => {
    generarPreguntasRegistro();
    
    const btnAccion = document.getElementById('btn-accion-registro');
    if(btnAccion) {
        btnAccion.addEventListener('click', manejarAvanceRegistro);
    }

    document.querySelectorAll('[data-vista]').forEach(enlace => {
        enlace.addEventListener('click', function(e) {
            e.preventDefault();
            const vistaDestino = this.getAttribute('data-vista');
            mostrarVista(vistaDestino);
        });
    });

    if (typeof vistaInicial !== 'undefined' && vistaInicial) {
        mostrarVista(vistaInicial);
    } else {
        mostrarVista('vista-login');
    }

    if (typeof mensajeSistema !== 'undefined' && mensajeSistema) {
        const tipo = (typeof tipoMensaje !== 'undefined') ? tipoMensaje : 'info';
        notificar(mensajeSistema, tipo);
    }
});

function manejarAvanceRegistro() {
    const btn = document.getElementById('btn-accion-registro');
    const form = document.getElementById('formulario-registro');

    const nombre = document.querySelector('input[name="reg-nombre"]').value;
    const usuario = document.querySelector('input[name="reg-usuario"]').value;
    const pass = document.getElementById('reg-contrasena').value;

    if(!nombre || !usuario || !pass) {
        notificar("Por favor complete nombre, usuario y contraseña.", "error");
        return;
    }
    if(pass.length < 6) {
        notificar("La contraseña es muy corta (mínimo 6).", "error");
        return;
    }

    const hiddenActual = document.getElementById(`hidden-pregunta-${slideActual}`);
    const respActual = document.querySelector(`input[name="respuesta${slideActual}"]`);

    if (!hiddenActual || !hiddenActual.value || !respActual.value.trim()) {
        notificar(`Responda la pregunta de seguridad #${slideActual}`, "error");
        return;
    }

    if (slideActual < 4) {
        slideActual++;
        irAlSlide(slideActual);
    } else {
        btn.innerText = "Registrando...";
        btn.disabled = true;
        btn.type = 'submit'; 
        form.submit();
    }
}

//MOTOR DEl SELECT

function generarPreguntasRegistro() {
    const area = document.getElementById('area-preguntas');
    if (!area) return;
    
    let slides = '<div class="contenedor-carrusel">';
    let puntos = '<div class="paginacion-puntos">';

    for (let i = 1; i <= 4; i++) {
        let activeClass = (i === 1) ? 'activo' : '';
        
        let optsLi = opcionesPreguntas.map(op => 
            `<li data-value="${op}" onclick="seleccionarOpcion(${i}, this)">${op}</li>`
        ).join('');
        
        slides += `
            <div class="slide-pregunta ${activeClass}" id="slide-${i}">
                <label class="etiqueta-pregunta">Pregunta ${i} de 4</label>
                
                <div class="select-personalizado" id="custom-select-${i}">
                    <input type="hidden" name="pregunta${i}" class="input-pregunta-oculto" id="hidden-pregunta-${i}" required>
                    
                    <div class="select-trigger" onclick="toggleSelect(${i}, event)">
                        <span id="texto-select-${i}">Seleccione una pregunta...</span>
                    </div>
                    
                    <ul class="select-opciones" id="lista-opciones-${i}">
                        ${optsLi}
                    </ul>
                </div>
                <input type="text" name="respuesta${i}" class="input-respuesta" placeholder="Escriba su respuesta aquí" autocomplete="off" required>
            </div>
        `;
        puntos += `<span class="punto ${activeClass}" id="punto-${i}"></span>`;
    }
    area.innerHTML = slides + '</div>' + puntos + '</div>';
    slideActual = 1;
    
    document.addEventListener('click', cerrarSelectsFuera);
}

function toggleSelect(id, event) {
    if(event) event.stopPropagation();
    for(let i=1; i<=4; i++) {
        if(i !== id) {
            const otro = document.getElementById(`custom-select-${i}`);
            if(otro) otro.classList.remove('abierto');
        }
    }
    const contenedor = document.getElementById(`custom-select-${id}`);
    if(contenedor) contenedor.classList.toggle('abierto');
}

function seleccionarOpcion(idLista, elementoLi) {
    if(elementoLi.classList.contains('deshabilitado')) return;

    const valor = elementoLi.getAttribute('data-value');
    const spanTexto = document.getElementById(`texto-select-${idLista}`);
    spanTexto.innerText = valor;
    spanTexto.style.color = "var(--primario-oscuro)";
    const inputOculto = document.getElementById(`hidden-pregunta-${idLista}`);
    inputOculto.value = valor;
    
    document.getElementById(`custom-select-${idLista}`).classList.remove('abierto');
    
    bloquearPreguntasRepetidas();
}

function cerrarSelectsFuera(e) {
    if (!e.target.closest('.select-personalizado')) {
        document.querySelectorAll('.select-personalizado').forEach(sel => {
            sel.classList.remove('abierto');
        });
    }
}

function bloquearPreguntasRepetidas() {
    const inputsOcultos = document.querySelectorAll('.input-pregunta-oculto');
    const seleccionadas = Array.from(inputsOcultos)
        .map(input => input.value)
        .filter(val => val !== ""); 

    for(let i=1; i<=4; i++) {
        const inputActual = document.getElementById(`hidden-pregunta-${i}`);
        if(!inputActual) continue;
        
        const valorActual = inputActual.value;
        const opciones = document.querySelectorAll(`#lista-opciones-${i} li`);
        
        opciones.forEach(opcion => {
            const valorOpcion = opcion.getAttribute('data-value');
            if (seleccionadas.includes(valorOpcion) && valorActual !== valorOpcion) {
                opcion.classList.add('deshabilitado');
            } else {
                opcion.classList.remove('deshabilitado');
            }
        });
    }
}

function irAlSlide(n) {
    for (let i = 1; i <= 4; i++) {
        const s = document.getElementById(`slide-${i}`);
        if(s) s.classList.remove('activo');
        const p = document.getElementById(`punto-${i}`);
        if(p) p.classList.remove('activo');
    }
    
    document.getElementById(`slide-${n}`).classList.add('activo');
    document.getElementById(`punto-${n}`).classList.add('activo');

    const btn = document.getElementById('btn-accion-registro');
    if(btn) {
        if (n === 4) {
            btn.innerText = "Registrar";
            btn.classList.remove('boton-secundario');
            btn.classList.add('boton-primario');
        } else {
            btn.innerText = "Siguiente";
            btn.classList.add('boton-secundario');
            btn.classList.remove('boton-primario');
        }
    }
}

function mostrarVista(id) {
    document.querySelectorAll('.vista-centrada, .vista-scroll').forEach(v => {
        v.classList.remove('activa');
    });

    document.querySelectorAll('.mensaje-error-catedral').forEach(msg => msg.remove());
    document.querySelectorAll('.input-error-catedral, .animacion-shake').forEach(el => {
        el.classList.remove('input-error-catedral', 'animacion-shake');
    });

    const panelLateral = document.querySelector('.panel-lateral');
    if(panelLateral) {
        if(id === 'vista-recuperar-2' || id === 'vista-recuperar-3' || id === 'vista-registro') {
            panelLateral.classList.add('oculto');
        } else {
            panelLateral.classList.remove('oculto');
        }
    }

    const destino = document.getElementById(id);
    if(destino) {

        void destino.offsetWidth;
        destino.classList.add('activa');

        //LIMPIEZA AUTOMÁTICA
        const formularios = destino.querySelectorAll('form');
        formularios.forEach(formulario => {
            formulario.reset();
        });

        if(id === 'vista-registro') {
            generarPreguntasRegistro();
            slideActual = 1;
            irAlSlide(1);
        }
    }
}

function notificar(msg, tipo = 'info') {
    const toast = document.getElementById('notificacion');
    if(!toast) return;
    toast.innerText = msg;
    toast.style.borderLeftColor = (tipo === 'error') ? 'var(--error)' : (tipo === 'exito' ? 'var(--exito)' : 'var(--catedral-ocre)');
    toast.classList.add('visible');
    setTimeout(() => toast.classList.remove('visible'), 3000);
}

const fRec = document.getElementById('formulario-recuperar-3');
if(fRec) {
    fRec.addEventListener('submit', function(e) {
        const p1 = document.getElementById('nueva-contrasena').value;
        const p2 = document.getElementById('confirmar-contrasena').value;
        if(p1 !== p2) { e.preventDefault(); notificar('No coinciden', 'error'); return; }
        if(p1.length < 6) { e.preventDefault(); notificar('Mínimo 6 caracteres', 'error'); }
    });
}

/* PREGUNTAS DE SEGURIDAD */

function actualizarPreguntasDisponibles() {
    const seleccionadas = [];
    
    document.querySelectorAll('.select-trigger').forEach(trigger => {
        let texto = trigger.textContent.trim().toLowerCase(); 
        
        if (texto && !texto.includes('seleccione')) {
            seleccionadas.push(texto);
        }
    });

    document.querySelectorAll('.select-personalizado').forEach(contenedor => {
        const trigger = contenedor.querySelector('.select-trigger');
        let textoActual = trigger ? trigger.textContent.trim().toLowerCase() : '';
        
        const opciones = contenedor.querySelectorAll('.select-opciones li');
        
        opciones.forEach(opcion => {
            let textoOpcion = opcion.textContent.trim().toLowerCase();
            
            if (seleccionadas.includes(textoOpcion) && textoOpcion !== textoActual) {
                opcion.style.display = 'none';
            } else {
                opcion.style.display = 'block'; // Se muestra libremente
            }
        });
    });
}

document.addEventListener('click', function(e) {
    if (e.target.matches('.select-opciones li')) {
        setTimeout(actualizarPreguntasDisponibles, 50);
    }
    
    if (e.target.closest('.select-trigger')) {
        actualizarPreguntasDisponibles();
    }
});

//VALIDACIÓN DE FORMULARIOS (LOGIN/REGISTRO)

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach(form => {
        form.noValidate = true;

        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault(); 
                let primerInvalido = null;

                this.querySelectorAll('input, select, textarea').forEach(input => {
                    if (!input.validity.valid) {
                        if (!primerInvalido) primerInvalido = input;
                        window.mostrarErrorPersonalizado(input);
                    } else {
                        window.limpiarErrorPersonalizado(input);
                    }
                });

                if (primerInvalido && primerInvalido.type !== 'hidden') {
                    primerInvalido.focus();
                }
            }
        });

        form.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('input', () => {
                if (input.validity.valid) window.limpiarErrorPersonalizado(input);
            });
        });
    });
});

document.addEventListener('input', function(e) {
    if (e.target.tagName === 'INPUT') {
        if (typeof window.limpiarErrorPersonalizado === 'function') {
            window.limpiarErrorPersonalizado(e.target);
        }
    }
});

window.mostrarErrorPersonalizado = function(input) {
    let grupo = input.parentElement;
    let errorMsg = grupo.querySelector('.mensaje-error-catedral');
    
    if (!errorMsg) {
        errorMsg = document.createElement('div');
        errorMsg.className = 'mensaje-error-catedral animacion-aparecer-arriba';
        input.insertAdjacentElement('afterend', errorMsg);
    }

    let esVacio = input.validity ? input.validity.valueMissing : input.value.trim() === '';

    errorMsg.innerHTML = ''; 
    let icono = document.createElement('i');
    icono.className = 'fas fa-exclamation-circle';
    icono.style.marginRight = '5px';
    
    let textoAviso = esVacio ? 'Este campo es obligatorio.' : 'Formato incorrecto.';
    let nodoTexto = document.createTextNode(textoAviso);
    
    errorMsg.appendChild(icono);
    errorMsg.appendChild(nodoTexto);

    window.aplicarShakeYError(input);
}

window.aplicarShakeYError = function(elemento) {
    elemento.classList.add('input-error-catedral');
    elemento.classList.remove('animacion-shake');
    void elemento.offsetWidth; 
    elemento.classList.add('animacion-shake');
}

window.limpiarErrorPersonalizado = function(input) {
    let grupo = input.parentElement;
    if (grupo) {
        let errorMsg = grupo.querySelector('.mensaje-error-catedral');
        if (errorMsg) errorMsg.remove(); 
    }
    input.classList.remove('input-error-catedral', 'animacion-shake');
}

//VER/OCULTAR CONTRASEÑA
function toggleclave(idInput, iconoElegido) {
    const input = document.getElementById(idInput);
    if (!input) return;
    if (input.type === "password") {
        input.type = "text";
        iconoElegido.classList.remove("fa-eye");
        iconoElegido.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        iconoElegido.classList.remove("fa-eye-slash");
        iconoElegido.classList.add("fa-eye");
    }
}