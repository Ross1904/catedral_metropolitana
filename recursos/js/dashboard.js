document.addEventListener('DOMContentLoaded', () => {
    flatpickr('input[type="date"].input-estilo-catedral', {
        locale: "es",
        dateFormat: "Y-m-d",
        disableMobile: "true",
        monthSelectorType: "static",
        
        onMonthChange: function(selectedDates, dateStr, instance) {
            animarDiasCalendario(instance);
        },
        onYearChange: function(selectedDates, dateStr, instance) {
            animarDiasCalendario(instance);
        }
    });

    function animarDiasCalendario(instance) {
        if (instance.daysContainer) {
            instance.daysContainer.classList.remove('animar-mes-catedral');
            void instance.daysContainer.offsetWidth; 
            instance.daysContainer.classList.add('animar-mes-catedral');
        }
    }

    //VALIDACIÓN DE FORMULARIOS

    document.querySelectorAll('form').forEach(form => {
        form.noValidate = true;

        form.addEventListener('submit', function(e) {
            
            const montos = this.querySelectorAll('input[type="number"][name="donacion-monto"]');
            montos.forEach(monto => {
                if (monto.hasAttribute('required')) {
                    if (Number(monto.value) <= 0) {
                        monto.setCustomValidity("El monto debe ser mayor a 0.");
                    } else {
                        monto.setCustomValidity("");
                    }
                }
            });

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
                input.setCustomValidity('');
                if (input.validity.valid) window.limpiarErrorPersonalizado(input);
            });
        });
    });

    document.addEventListener('input', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            if (typeof window.limpiarErrorPersonalizado === 'function') {
                window.limpiarErrorPersonalizado(e.target);
            }
        }
    });

    //FUNCIONES INYECTORAS
    window.mostrarErrorPersonalizado = function(input) {
        let grupo = input.closest('.grupo-entrada');
        if (!grupo) return;

        let textoAyuda = grupo.querySelector('.texto-ayuda-catedral');
        if (textoAyuda) textoAyuda.style.display = 'none';

        let errorMsg = grupo.querySelector('.mensaje-error-catedral');
        if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'mensaje-error-catedral animacion-aparecer-arriba';
            grupo.appendChild(errorMsg);
        }

        let esVacio = input.validity ? input.validity.valueMissing : input.value.trim() === '';
        let mensajeEspecial = input.validationMessage;

        errorMsg.innerHTML = ''; 

        let icono = document.createElement('i');
        icono.className = 'fas fa-exclamation-circle';
        icono.style.marginRight = '5px';

        let textoAviso = 'Formato incorrecto.';
        if (esVacio) {
            textoAviso = 'Este campo es obligatorio.';
        } else if (mensajeEspecial && input.type === 'number') {
            textoAviso = mensajeEspecial;
        }
        
        let nodoTexto = document.createTextNode(textoAviso);

        errorMsg.appendChild(icono);
        errorMsg.appendChild(nodoTexto);

        if (input.type === 'hidden' && input.closest('.select-personalizado-catedral')) {
            let trigger = input.closest('.select-personalizado-catedral').querySelector('.select-trigger-catedral');
            if (trigger) window.aplicarShakeYError(trigger);
        } else {
            window.aplicarShakeYError(input);
        }
    }

    window.aplicarShakeYError = function(elemento) {
        elemento.classList.add('input-error-catedral');
        elemento.classList.remove('animacion-shake');
        void elemento.offsetWidth; 
        elemento.classList.add('animacion-shake');
    }

    window.limpiarErrorPersonalizado = function(input) {
        let grupo = input.closest('.grupo-entrada');
        if (grupo) {
            let errorMsg = grupo.querySelector('.mensaje-error-catedral');
            if (errorMsg) errorMsg.remove(); 
            
            let textoAyuda = grupo.querySelector('.texto-ayuda-catedral');
            if (textoAyuda) textoAyuda.style.display = 'block';
        }

        if (input.type === 'hidden' && input.closest('.select-personalizado-catedral')) {
            let trigger = input.closest('.select-personalizado-catedral').querySelector('.select-trigger-catedral');
            if (trigger) trigger.classList.remove('input-error-catedral', 'animacion-shake');
        } else {
            input.classList.remove('input-error-catedral', 'animacion-shake');
        }
    }

    const barraLateral = document.getElementById('barra-lateral');
    const alternarTema = document.getElementById('alternar-tema');
    const btnColapsar = document.getElementById('btn-colapsar');
    const iconoColapsar = document.getElementById('icono-colapsar');
 
//CONTROL DEL MODO OSCURO / CLARO

const btnTema = document.getElementById('alternar-tema');
const iconoTema = document.getElementById('icono-tema'); 

function actualizarIconoTema(esOscuro, animar = true) {
    if (iconoTema) {
        iconoTema.classList.remove('animar-giro-tema');
        
        void iconoTema.offsetWidth;

        if (esOscuro) {
            iconoTema.classList.remove('fa-moon');
            iconoTema.classList.add('fa-sun');
        } else {
            iconoTema.classList.remove('fa-sun');
            iconoTema.classList.add('fa-moon');
        }

        if (animar) {
            iconoTema.classList.add('animar-giro-tema');
        }
    }
}
 
 if (btnTema) {
    const claveTemaUsuario = 'tema_' + (typeof idUsuarioActual !== 'undefined' ? idUsuarioActual : 'generico');
    const temaGuardado = localStorage.getItem(claveTemaUsuario);
    
    if (!temaGuardado) {
        localStorage.setItem(claveTemaUsuario, 'claro');
        document.body.classList.remove('oscuro');
        btnTema.checked = false;
        actualizarIconoTema(false, false);
    } 
    else if (temaGuardado === 'oscuro') {
        document.body.classList.add('oscuro');
        btnTema.checked = true;
        actualizarIconoTema(true, false); 
    } 
    else {
        document.body.classList.remove('oscuro');
        btnTema.checked = false;
        actualizarIconoTema(false, false);
    }
 
    btnTema.addEventListener('change', function() {
        const estaOscuro = this.checked;
        if (estaOscuro) {
            document.body.classList.add('oscuro');
            localStorage.setItem(claveTemaUsuario, 'oscuro');
        } else {
            document.body.classList.remove('oscuro');
            localStorage.setItem(claveTemaUsuario, 'claro');
        }
        actualizarIconoTema(estaOscuro, true); 
    });
 
    if (iconoTema) {
        iconoTema.addEventListener('click', function() {
            btnTema.click(); 
        });
    }
}
  
    btnColapsar.addEventListener('click', () => {
        barraLateral.classList.toggle('colapsada');
        if(barraLateral.classList.contains('colapsada')) {
            iconoColapsar.classList.replace('fa-angle-double-left', 'fa-angle-double-right');
        } else {
            iconoColapsar.classList.replace('fa-angle-double-right', 'fa-angle-double-left');
        }
    });
    const itemsMenu = document.querySelectorAll('.item-menu[data-objetivo]');
    const modulos = document.querySelectorAll('.modulo');
    const mostrarModulo = (idModulo) => {
        itemsMenu.forEach(btn => btn.classList.remove('activo'));
        modulos.forEach(mod => mod.classList.remove('activo'));

        //CERRAR TODO AL CAMBIAR DE PANTALLA

        document.querySelectorAll('.modal-catedral.activo').forEach(modal => {
            cerrarModal(modal.id);
        });

        const itemTarget = document.querySelector(`.item-menu[data-objetivo="${idModulo}"]`);
        const moduloTarget = document.getElementById(idModulo);

        if (itemTarget) itemTarget.classList.add('activo');
        if (moduloTarget) moduloTarget.classList.add('activo');

        if (idModulo === 'mod-calendario') {
            mesActualCal = new Date().getMonth();
            anioActualCal = new Date().getFullYear();
            renderizarCalendario(mesActualCal, anioActualCal);
        }
    };

    if (typeof moduloActivo !== 'undefined' && moduloActivo) {
        mostrarModulo(moduloActivo);
    }

    itemsMenu.forEach(item => {
        item.addEventListener('click', () => {
            const objetivoId = item.getAttribute('data-objetivo');
            mostrarModulo(objetivoId);
        });
    });

    if(document.getElementById('cuadricula-dias')) {
        renderizarCalendario(mesActualCal, anioActualCal);
    }
});

//FUNCIONES PARA MODALES
function abrirModal(id) {
    document.getElementById(id).classList.add('activo');
}

function cerrarModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('activo');
        modal.querySelectorAll('.input-error-catedral, .animacion-shake').forEach(elemento => {
            elemento.classList.remove('input-error-catedral', 'animacion-shake');
        });
        modal.querySelectorAll('.mensaje-error-catedral').forEach(msg => msg.remove());
        modal.querySelectorAll('.texto-ayuda-catedral').forEach(ayuda => {
            ayuda.style.display = 'block';
        });

        const form = modal.querySelector('form');
        if (form && typeof limpiarFormulario === 'function') {
            limpiarFormulario(form);
        }

        if (id === 'modal-editar-preguntas') {
            modal.querySelectorAll('input[type="text"], input[type="hidden"]').forEach(inp => inp.value = '');
            modal.querySelectorAll('.select-trigger-catedral, .select-texto').forEach(trigger => {
                if (trigger.hasAttribute('data-texto-original')) {
                    trigger.textContent = trigger.getAttribute('data-texto-original');
                } else {
                    trigger.textContent = 'Seleccione una pregunta...';
                }
            });

            for (let i = 1; i <= 4; i++) {
                let paso = document.getElementById(`paso-pregunta-${i}`);
                if (paso) {
                    paso.style.display = (i === 1) ? 'block' : 'none';
                    paso.style.opacity = '1';
                }
            }
            let indicador = modal.querySelector('.progreso-preguntas');
            if (indicador) {
                indicador.innerHTML = `Paso <span id="paso-actual-num">1</span> de 4`;
            }
        }
    }
}

function limpiarFormulario(form) {
    if (!form) return;
    form.reset();
    form.querySelectorAll('.select-texto').forEach(span => {
        if(span.hasAttribute('data-texto-original')) {
            span.textContent = span.getAttribute('data-texto-original');
        }
    });

    const secMonetaria = form.querySelector('[id^="seccion-monetaria-"]');
    const secBienes = form.querySelector('[id^="seccion-bienes-"]');
    const contRef = form.querySelector('[id^="contenedor-referencia-"]');
    
    if (secMonetaria) secMonetaria.style.display = 'block';
    if (secBienes) secBienes.style.display = 'none';
    if (contRef) contRef.style.display = 'none';

    const inputDonante = form.querySelector('input[name="donacion-donante"]');
    const inputRef = form.querySelector('input[name="donacion-referencia"]');
    const inputDesc = form.querySelector('input[name="donacion-descripcion"]');
    const inputMonto = form.querySelector('input[name="donacion-monto"]');

    if (inputDonante) {
        inputDonante.removeAttribute('required');
        inputDonante.placeholder = "Ej. Familia Pérez";
        let ayuda = inputDonante.nextElementSibling;
        if (ayuda && ayuda.classList.contains('texto-ayuda-catedral')) {
            ayuda.textContent = "Si se deja en blanco, se registrará como 'Anónimo'.";
            ayuda.style.color = "inherit";
        }
    }
    if (inputRef) {
        inputRef.removeAttribute('required');
        inputRef.placeholder = "";
    }
    if (inputDesc) {
        inputDesc.removeAttribute('required');
        inputDesc.placeholder = "Ej. Arroz, Cemento, Sillas";
    }
    if (inputMonto) {
        inputMonto.setAttribute('required', 'required'); 
    }

    const contOtro = form.querySelector('[id^="contenedor-otro-"]');
    if (contOtro) {
        contOtro.style.display = 'none';
        const inputOtro = contOtro.querySelector('input[name="agenda-tipo-otro"]');
        if (inputOtro) inputOtro.removeAttribute('required');
    }
    
    form.querySelectorAll('input').forEach(input => {
        input.style.borderColor = '';
        input.style.boxShadow = '';
    });
}

function abrirModalEditarActividad(id, nombre, categoria, dias, hora, lugar) {
    document.getElementById('edit-act-id').value = id;
    document.getElementById('edit-act-nombre').value = nombre;
    document.getElementById('edit-act-dias').value = dias;
    document.getElementById('edit-act-hora').value = hora;
    document.getElementById('edit-act-lugar').value = lugar;
    
    const inputOculto = document.getElementById('edit-act-categoria-hidden');
    const textoVisible = document.querySelector('#edit-act-categoria-trigger .select-texto');
    
    inputOculto.value = categoria;
    
    const nombresCategorias = {
        'Formación': 'Formación Pastoral', 
        'Grupo Devocional': 'Grupo Devocional', 
        'Reunión': 'Reunión General'
    };
    textoVisible.textContent = nombresCategorias[categoria] || 'Seleccione una categoría...';

    abrirModal('modal-editar-actividad');
}

function confirmarEliminacion(id, nombre) {
    document.getElementById('input-eliminar-id').value = id;
    document.getElementById('nombre-eliminar-display').textContent = '«' + nombre + '»';
    
    abrirModal('modal-confirmar-eliminacion');
}

function confirmarEliminarAgenda() {
    let id = document.getElementById('edit-agenda-id').value;
    let titulo = document.getElementById('edit-agenda-titulo').value;
    
    document.getElementById('input-eliminar-agenda-id').value = id;
    document.getElementById('nombre-eliminar-agenda-display').innerText = titulo;
    
    cerrarModal('modal-editar-agenda');
    abrirModal('modal-confirmar-eliminar-agenda');
}

document.addEventListener('DOMContentLoaded', () => {
    const toast = document.getElementById('toast-notificacion');
    if (toast) {
        setTimeout(() => {
            toast.style.animation = 'salirToast 0.5s forwards';
            setTimeout(() => { toast.remove(); }, 500);
        }, 6000);
    }
});

function abrirModalEditarDonacion(id, tipo, monto, donante, metodo, descripcion, cantidad, referencia, fecha) {
    document.getElementById('edit-donacion-id').value = id;
    document.getElementById('edit-donacion-donante').value = donante === "Anónimo" ? "" : donante;
    document.getElementById('edit-donacion-fecha').value = fecha;

    const liOpcion = document.querySelector(`#modal-editar-donacion li[onclick*="'${tipo}'"]`);
    if (liOpcion) {
        seleccionarOpcionDonacion(liOpcion, tipo, 'edit-donacion-tipo-hidden', 'edit-tipo-texto', 'edit');
    }

    if (tipo === 'Monetaria') {
        document.getElementById('edit-monto').value = monto;
        document.getElementById('edit-referencia').value = referencia === "N/A" ? "" : referencia;
        
        const liMetodo = document.querySelector(`#modal-editar-donacion li[onclick*="'${metodo}'"]`);
        if(liMetodo) {
            seleccionarMetodoPago(liMetodo, metodo, 'edit-metodo-hidden', 'edit');
        }
    } else {
        document.getElementById('edit-descripcion').value = descripcion;
        document.getElementById('edit-cantidad').value = cantidad === "N/A" ? "" : cantidad;
    }

    abrirModal('modal-editar-donacion');
}

function confirmarEliminarDonacion(id, donante) {
    document.getElementById('input-eliminar-donacion-id').value = id;
    document.getElementById('nombre-eliminar-donacion-display').textContent = 'la donación de «' + donante + '»';
    abrirModal('modal-confirmar-eliminar-donacion');
}

//PANEL DE NOTIFICACIONES

function togglePanelActividad() {
    document.getElementById('panel-actividad').classList.toggle('abierto');
}

function toggleDetalleActividad(elementoClickeado) {
    elementoClickeado.classList.toggle('expandido');
    
    if (elementoClickeado.classList.contains('no-visto')) {
        elementoClickeado.classList.remove('no-visto');
        
        let idNotif = elementoClickeado.getAttribute('data-id');
        let formData = new FormData();
        formData.append('accion', 'marcar-notificacion-vista');
        formData.append('id', idNotif);
        
        fetch('../php/controlador.php', { method: 'POST', body: formData });
        
        actualizarBadgeNotificaciones();
    }
}

function eliminarNotificacion(event, idNotif, boton) {
    event.stopPropagation();
    
    let itemActividad = boton.closest('.item-actividad');
    itemActividad.style.opacity = '0';
    
    setTimeout(() => {
        let eraNoVisto = itemActividad.classList.contains('no-visto');
        itemActividad.remove();
        
        let formData = new FormData();
        formData.append('accion', 'eliminar-notificacion');
        formData.append('id', idNotif);
        
        fetch('../php/controlador.php', { method: 'POST', body: formData });
        
        if (eraNoVisto) actualizarBadgeNotificaciones();
    }, 300);
}

function actualizarBadgeNotificaciones() {
    let badge = document.querySelector('.badge-notificacion');
    let punto = document.querySelector('.item-actividad.expandido .punto-nuevo');
    if (punto) punto.remove();
    
    if (badge) {
        let actual = parseInt(badge.textContent);
        if (actual > 1) {
            badge.textContent = actual - 1; 
        } else {
            badge.remove();
        }
    }
}

//ACCIONES MASIVAS DE NOTIFICACIONES

function marcarTodasVistas() {
    const itemsNoVistos = document.querySelectorAll('.item-actividad.no-visto');
    if (itemsNoVistos.length === 0) return;

    itemsNoVistos.forEach(item => {
        item.classList.remove('no-visto');
        const punto = item.querySelector('.punto-nuevo');
        if (punto) punto.remove();
    });

    const badge = document.querySelector('.badge-notificacion');
    if (badge) badge.remove();

    let formData = new FormData();
    formData.append('accion', 'marcar-todas-notificaciones-vistas');
    fetch('../php/controlador.php', { method: 'POST', body: formData });
}

function confirmarEliminarTodasNotif() {
    abrirModal('modal-confirmar-vaciar-historial');
}

function eliminarTodasNotificaciones() {
    cerrarModal('modal-confirmar-vaciar-historial');

    const panelCuerpo = document.querySelector('.panel-lateral-cuerpo');
    if (panelCuerpo) {
        panelCuerpo.innerHTML = `
            <div style="text-align: center; padding: 30px; opacity: 0.6;">
                <i class="fas fa-bed" style="font-size: 3rem; margin-bottom: 10px;"></i>
                <p>El sistema está en paz. No hay actividad reciente.</p>
            </div>
        `;
    }

    const badge = document.querySelector('.badge-notificacion');
    if (badge) badge.remove();
    
    const barraBotones = document.getElementById('botones-accion-notificaciones');
    if (barraBotones) barraBotones.style.display = 'none';

    let formData = new FormData();
    formData.append('accion', 'eliminar-todas-notificaciones');
    fetch('../php/controlador.php', { method: 'POST', body: formData });
}

//PREGUNTAS DE SEGURIDAD

function navegarPregunta(pasoActual, pasoDestino) {
    if (pasoDestino > pasoActual && pasoActual <= 4) {
        let inputRespuesta = document.querySelector(`#paso-pregunta-${pasoActual} input[type="text"]`);
        let inputPregunta = document.getElementById(`edit-preg-${pasoActual}-hidden`);
        let pasoValido = true;
        
        if (!inputPregunta.value.trim()) {
            if (window.mostrarErrorPersonalizado) window.mostrarErrorPersonalizado(inputPregunta);
            pasoValido = false;
        } else {
            if (window.limpiarErrorPersonalizado) window.limpiarErrorPersonalizado(inputPregunta);
        }
        
        if (!inputRespuesta.value.trim()) {
            if (window.mostrarErrorPersonalizado) window.mostrarErrorPersonalizado(inputRespuesta);
            pasoValido = false;
        } else {
            if (window.limpiarErrorPersonalizado) window.limpiarErrorPersonalizado(inputRespuesta);
        }
        
        if (!pasoValido) return;
    }

    let divActual = document.getElementById(`paso-pregunta-${pasoActual}`);
    let divDestino = document.getElementById(`paso-pregunta-${pasoDestino}`);
    
    divActual.style.opacity = '0';
    
    setTimeout(() => {
        divActual.style.display = 'none';
        divDestino.style.display = 'block';
        void divDestino.offsetWidth; 
        divDestino.style.opacity = '1';
        divDestino.style.transition = 'opacity 0.3s ease';
        
        let indicador = document.querySelector('.progreso-preguntas');
        if (pasoDestino <= 4) {
            indicador.innerHTML = `Paso <span id="paso-actual-num">${pasoDestino}</span> de 4`;
        } else {
            indicador.innerHTML = `Confirmación Final`;
        }
    }, 200);
}

function resetearModalPreguntas() {
    for(let i = 1; i <= 5; i++) {
        let paso = document.getElementById(`paso-pregunta-${i}`);
        if(paso) {
            paso.style.display = (i === 1) ? 'block' : 'none';
            paso.style.opacity = '1';
        }
    }
    document.querySelector('.progreso-preguntas').innerHTML = `Paso <span id="paso-actual-num">1</span> de 4`;
    document.querySelectorAll('#form-preguntas-seguridad input[type="text"]').forEach(input => input.value = '');
    document.querySelector('#form-preguntas-seguridad input[type="password"]').value = '';
}

//CONTROLADOR DE PREGUNTAS DE SEGURIDAD

function activarMenuSeguridad(event, gatillo, numActiva) {
    event.stopPropagation();

    let contenedor = gatillo.closest('.select-personalizado-catedral');
    let ul = contenedor.querySelector('.select-opciones-catedral');

    document.querySelectorAll('.select-personalizado-catedral').forEach(select => {
        if(select !== contenedor) select.classList.remove('abierto');
    });

    let seleccionadas = [];
    for(let i = 1; i <= 4; i++) {
        if(i !== numActiva) {
            let inputOculto = document.getElementById(`edit-preg-${i}-hidden`);
            if(inputOculto && inputOculto.value.trim() !== "") {
                seleccionadas.push(inputOculto.value.trim());
            }
        }
    }

    let opciones = ul.querySelectorAll('li');
    opciones.forEach(li => {
        let valorExacto = li.getAttribute('data-valor');
        
        if (valorExacto && seleccionadas.includes(valorExacto.trim())) {
            li.style.display = 'none'; 
        } else {
            li.style.display = 'block'; 
        }
    });

    contenedor.classList.toggle('abierto');
}

function seleccionarPreguntaSeguridad(event, elementoLi, valor, numActiva) {
    event.stopPropagation();
    
    let contenedor = elementoLi.closest('.select-personalizado-catedral');
    let textoVisible = contenedor.querySelector('.select-texto');
    let inputOculto = document.getElementById(`edit-preg-${numActiva}-hidden`);
    
    textoVisible.innerText = valor;
    inputOculto.value = valor;
    
    contenedor.classList.remove('abierto');
}

//MOTOR DEL CALENDARIO LITÚRGICO

let mesActualCal = new Date().getMonth();
let anioActualCal = new Date().getFullYear();

function renderizarCalendario(mes, anio) {
    const cuadricula = document.getElementById('cuadricula-dias');
    const mesAnioDisplay = document.getElementById('mes-anio-display');
    if(!cuadricula || !mesAnioDisplay) return;

    cuadricula.innerHTML = '';
    
    const primerDia = new Date(anio, mes, 1).getDay();
    const diasEnMes = new Date(anio, mes + 1, 0).getDate();
    const mesesNombres = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    
    mesAnioDisplay.innerText = mesesNombres[mes] + ' ' + anio;

    for (let i = 0; i < primerDia; i++) {
        let diaVacio = document.createElement('div');
        diaVacio.className = 'dia-vacio';
        cuadricula.appendChild(diaVacio);
    }

    for (let dia = 1; dia <= diasEnMes; dia++) {
        let celdaDia = document.createElement('div');
        celdaDia.className = 'dia-calendario';
        
        let mesStr = String(mes + 1).padStart(2, '0');
        let diaStr = String(dia).padStart(2, '0');
        let fechaCelda = `${anio}-${mesStr}-${diaStr}`;

        let numeroDia = document.createElement('span');
        numeroDia.className = 'numero-dia';
        numeroDia.innerText = dia;
        
        let hoy = new Date();
        if (dia === hoy.getDate() && mes === hoy.getMonth() && anio === hoy.getFullYear()) {
            celdaDia.classList.add('hoy');
        }
        celdaDia.appendChild(numeroDia);

        let eventosDelDia = eventosAgendaDB.filter(evento => evento.fecha_hora_inicio.startsWith(fechaCelda));
        
        if (eventosDelDia.length > 0) {
            let indicador = document.createElement('div');
            indicador.className = 'indicador-triangulo-evento';
            
            celdaDia.appendChild(indicador);
            celdaDia.classList.add('dia-con-eventos');
            
            celdaDia.onclick = () => abrirDetalleDia(fechaCelda);
        } else {
            celdaDia.onclick = () => abrirDetalleDia(fechaCelda);
        }

        cuadricula.appendChild(celdaDia);
    }

    //ANIMACIÓN DEL CALENDARIO
    const cuadriculaAnimada = document.getElementById('cuadricula-dias');
    const tituloMesAnimado = document.getElementById('mes-anio-display');
    
    if (cuadriculaAnimada && tituloMesAnimado) {
        cuadriculaAnimada.classList.remove('animacion-calendario');
        tituloMesAnimado.classList.remove('animacion-calendario');
        
        void cuadriculaAnimada.offsetWidth;
        
        cuadriculaAnimada.classList.add('animacion-calendario');
        tituloMesAnimado.classList.add('animacion-calendario');
    }
}

document.getElementById('btn-mes-anterior')?.addEventListener('click', () => {
    mesActualCal--;
    if (mesActualCal < 0) { mesActualCal = 11; anioActualCal--; }
    renderizarCalendario(mesActualCal, anioActualCal);
});

document.getElementById('btn-mes-siguiente')?.addEventListener('click', () => {
    mesActualCal++;
    if (mesActualCal > 11) { mesActualCal = 0; anioActualCal++; }
    renderizarCalendario(mesActualCal, anioActualCal);
});

//CONTROL DE MODALES DEL CALENDARIO
function formatearFechaElegante(fechaSQL) {
    let partes = fechaSQL.split('-');
    let fecha = new Date(partes[0], partes[1] - 1, partes[2]);
    return fecha.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
}

function abrirDetalleDia(fechaFormateada) {
    let eventosDelDia = eventosAgendaDB.filter(e => e.fecha_hora_inicio.startsWith(fechaFormateada));
    const contenedorEventos = document.getElementById('lista-eventos-dia');
    
    const opcionesFecha = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const fechaObj = new Date(fechaFormateada + 'T00:00:00');
    document.getElementById('titulo-detalle-dia').textContent = "Agenda: " + fechaObj.toLocaleDateString('es-ES', opcionesFecha);

    contenedorEventos.innerHTML = '';

    if (eventosDelDia.length === 0) {
        if (typeof esSecretarioJS !== 'undefined' && !esSecretarioJS) {
            // VISTA CIUDADANO
            contenedorEventos.innerHTML = `
                <div style="text-align: center; padding: 40px 20px;">
                    <i class="far fa-calendar-times" style="font-size: 4rem; color: var(--acento-dorado); opacity: 0.6; margin-bottom: 20px;"></i>
                    <h4 class="texto-azul-adaptable" style="font-size: 1.4rem; margin-bottom: 10px;">Día sin actividades</h4>
                    <p style="opacity: 0.8; margin: 0; line-height: 1.5;">La agenda parroquial se encuentra libre. No hay eventos, misas especiales ni sacramentos programados para esta fecha.</p>
                </div>
            `;
        } else {
            // VISTA SECRETARIO/ADMIN
            contenedorEventos.innerHTML = `
                <div style="text-align: center; padding: 30px 20px; opacity: 0.8;">
                    <i class="far fa-calendar-plus" style="font-size: 3.5rem; color: var(--acento-dorado); margin-bottom: 15px;"></i>
                    <p style="margin: 0; font-size: 1.1rem;">El día está libre.<br>Utiliza el botón inferior para agendar un nuevo evento.</p>
                </div>
            `;
        }
    } 
    else {
        eventosDelDia.forEach(evento => {
            let horaInicio = (evento.fecha_hora_inicio.split(' ')[1] || evento.fecha_hora_inicio).substring(0,5);
            let horaFin = (evento.fecha_hora_fin.split(' ')[1] || evento.fecha_hora_fin).substring(0,5);
            let htmlEvento = `
                <div class="tarjeta-evento-agenda" style="background: var(--fondo-panel); padding: 20px; border-radius: 12px; margin-bottom: 20px; border-left: 5px solid var(--acento-dorado); box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: left;">
                    
                    <h4 class="texto-azul-adaptable" style="margin: 0 0 15px 0; font-size: 1.25rem;">
                        <i class="fas fa-bookmark" style="color: var(--acento-dorado); margin-right: 8px;"></i> ${evento.titulo_actividad}
                    </h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 10px;">
                        <p style="margin: 0; font-size: 0.95rem;"><i class="fas fa-tag" style="color: #8AB4F8; width: 20px;"></i> <strong>Tipo:</strong> ${evento.tipo_actividad}</p>
                        <p style="margin: 0; font-size: 0.95rem;"><i class="fas fa-info-circle" style="color: #6b9071; width: 20px;"></i> <strong>Estado:</strong> ${evento.estado}</p>
                        <p style="margin: 0; font-size: 0.95rem;"><i class="fas fa-clock" style="color: var(--acento-dorado); width: 20px;"></i> <strong>Inicio:</strong> ${horaInicio}</p>
                        <p style="margin: 0; font-size: 0.95rem;"><i class="fas fa-hourglass-end" style="color: var(--acento-dorado); width: 20px;"></i> <strong>Fin:</strong> ${horaFin}</p>
                    </div>
            `;

            if (typeof esSecretarioJS !== 'undefined' && esSecretarioJS) {
                const titulo = evento.titulo_actividad.replace(/'/g, "\\'");
                const tipo = evento.tipo_actividad.replace(/'/g, "\\'");
                htmlEvento += `
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed rgba(198, 156, 109, 0.3); text-align: right;">
                        <button class="boton-accion-tarjeta btn-editar texto-azul-adaptable" onclick="abrirEdicionAgenda(${evento.id}, '${titulo}', '${tipo}', '${evento.estado}', '${evento.fecha_hora_inicio}', '${evento.fecha_hora_fin}')" style="background: rgba(198, 156, 109, 0.1); border: 1px solid var(--acento-dorado); padding: 8px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s ease;">
                            <i class="fas fa-pen"></i> Gestionar Evento
                        </button>
                    </div>
                `;
            }

            htmlEvento += `</div>`;
            contenedorEventos.innerHTML += htmlEvento;
        });
    }

    const btnNuevoEvento = document.getElementById('btn-nuevo-evento-dia');
    if (btnNuevoEvento) {
        btnNuevoEvento.onclick = () => {
            cerrarModal('modal-detalle-dia');
            document.getElementById('crear-agenda-fecha').value = fechaFormateada;
            document.getElementById('texto-fecha-seleccionada').innerText = `Fecha seleccionada: ${fechaObj.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' })}`;
            abrirModal('modal-agenda');
        };
    }

    abrirModal('modal-detalle-dia');
}

function abrirEdicionAgenda(id, titulo, tipo, estado, fechaInicioCompleta, fechaFinCompleta) {
    cerrarModal('modal-detalle-dia');
    
    let fecha = fechaInicioCompleta.split(' ')[0];
    let horaInicio = fechaInicioCompleta.includes(' ') ? fechaInicioCompleta.split(' ')[1].substring(0,5) : '';
    let horaFin = fechaFinCompleta.includes(' ') ? fechaFinCompleta.split(' ')[1].substring(0,5) : '';

    document.getElementById('edit-agenda-id').value = id;
    document.getElementById('edit-agenda-fecha').value = fecha;
    document.getElementById('edit-agenda-titulo').value = titulo;
    document.getElementById('edit-agenda-hora-inicio').value = horaInicio;
    document.getElementById('edit-agenda-hora-fin').value = horaFin;
    
    const esTipoEstandar = ['Boda', 'Comunión', 'Bautizo', 'Misa Especial', 'Mantenimiento'].includes(tipo);
    document.getElementById('edit-agenda-tipo-hidden').value = esTipoEstandar ? tipo : 'Otro';
    document.querySelector('#edit-agenda-tipo-trigger .select-texto').textContent = esTipoEstandar ? tipo : 'Otro (Especificar)';
    
    const contenedorOtroEdit = document.getElementById('contenedor-otro-editar');
    const inputOtroEdit = document.getElementById('input-otro-editar');
    
    if (!esTipoEstandar) {
        contenedorOtroEdit.style.display = 'block';
        inputOtroEdit.value = tipo; // Si el tipo estaba vacío en la BD, aquí se ponía en blanco
        
        // Hacemos que sea requerido, pero avisamos al usuario para que no se tranque
        inputOtroEdit.setAttribute('required', 'required'); 
    } else {
        contenedorOtroEdit.style.display = 'none';
        inputOtroEdit.value = '';
        inputOtroEdit.removeAttribute('required');
    }

    document.getElementById('edit-agenda-estado-hidden').value = estado;
    document.querySelector('#edit-agenda-estado-trigger .select-texto').innerText = estado;
    
    document.getElementById('input-eliminar-agenda-id').value = id;
    document.getElementById('nombre-eliminar-agenda-display').innerText = titulo;

    abrirModal('modal-editar-agenda');
}

function actualizarBadgeNotificaciones() {
    let badge = document.querySelector('.badge-notificacion');
    let punto = document.querySelector('.item-actividad.expandido .punto-nuevo');
    if (punto) punto.remove();
    
    if (badge) {
        let actual = parseInt(badge.textContent);
        if (actual > 1) {
            badge.textContent = actual - 1;
        } else {
            badge.remove();
        }
    }
}

//MOTOR DE LOS SELECTORES PERSONALIZADOS 

function toggleSelectCatedral(elemento) {
    if (window.event) window.event.stopPropagation();

    const contenedor = elemento.closest('.select-personalizado-catedral');
    
    if (contenedor.classList.contains('abierto')) {
        contenedor.classList.remove('abierto');
        return;
    }

    document.querySelectorAll('.select-personalizado-catedral.abierto').forEach(select => {
        select.classList.remove('abierto');
    });

    contenedor.classList.add('abierto');
}

function seleccionarOpcionCatedral(elementoLi, valor, texto, idInputOculto) {
    if (window.event) window.event.stopPropagation();

    const contenedor = elementoLi.closest('.select-personalizado-catedral');
    const inputOculto = document.getElementById(idInputOculto);
    const textoTrigger = contenedor.querySelector('.select-texto');

    if(inputOculto) {
        inputOculto.value = valor;
        if(typeof window.limpiarErrorPersonalizado === 'function') window.limpiarErrorPersonalizado(inputOculto);
    }
    if(textoTrigger) textoTrigger.textContent = texto;

    contenedor.classList.remove('abierto');
}

function seleccionarOpcionDonacion(elementoLi, valor, idInputOculto, idTextoTrigger, prefijo) {
    seleccionarOpcionCatedral(elementoLi, valor, elementoLi.textContent, idInputOculto);
    
    const seccionMonetaria = document.getElementById(`seccion-monetaria-${prefijo}`);
    const seccionBienes = document.getElementById(`seccion-bienes-${prefijo}`);
    const inputMonto = document.getElementById(prefijo === 'crear' ? 'crear-monto' : 'edit-monto');
    const inputDescripcion = document.getElementById(prefijo === 'crear' ? 'crear-descripcion' : 'edit-descripcion');

    if (valor === 'Monetaria') {
        if(seccionMonetaria) seccionMonetaria.style.display = 'block';
        if(seccionBienes) seccionBienes.style.display = 'none';

        if(inputMonto) inputMonto.setAttribute('required', 'required');
        if(inputDescripcion) inputDescripcion.removeAttribute('required');
        
        if(inputDescripcion) inputDescripcion.placeholder = "Ej. Arroz, Cemento, Sillas";
    } else {
        if(seccionMonetaria) seccionMonetaria.style.display = 'none';
        if(seccionBienes) seccionBienes.style.display = 'block';

        if(inputDescripcion) {
            inputDescripcion.setAttribute('required', 'required');
            inputDescripcion.placeholder = "Obligatorio (Ej. Arroz, Cemento, Sillas)";
        }
        if(inputMonto) inputMonto.removeAttribute('required');
    }
}

function seleccionarMetodoPago(elementoLi, valor, idInputOculto, prefijo) {
    seleccionarOpcionCatedral(elementoLi, valor, valor, idInputOculto);

    let inputDonante = document.getElementById(prefijo === 'crear' ? 'crear-donante' : 'edit-donacion-donante');
    let inputReferencia = document.getElementById(prefijo === 'crear' ? 'crear-referencia' : 'edit-referencia');
    let contenedorReferencia = document.getElementById(prefijo === 'crear' ? 'contenedor-referencia-crear' : 'contenedor-referencia-edit');
    let textoAyudaDonante = inputDonante.nextElementSibling; 
    
    if (valor === 'Pago Móvil' || valor === 'Transferencia') {
        inputDonante.setAttribute('required', 'required');
        inputReferencia.setAttribute('required', 'required');
        
        if (contenedorReferencia) contenedorReferencia.style.display = 'block';
        
        inputDonante.placeholder = "Obligatorio para pagos electrónicos";
        inputReferencia.placeholder = "Obligatorio (Ej. 123456789)";
        
        if(textoAyudaDonante) {
            textoAyudaDonante.textContent = `Requerido para conciliar la ${valor} en el banco.`;
            textoAyudaDonante.style.color = "var(--acento-rojo)";
        }
    } 
    else {
        inputDonante.removeAttribute('required');
        inputReferencia.removeAttribute('required');
        
        if (contenedorReferencia) contenedorReferencia.style.display = 'none';
        inputReferencia.value = ''; 
        
        inputDonante.placeholder = "Ej. Familia Pérez";
        
        if(textoAyudaDonante) {
            textoAyudaDonante.textContent = "Si se deja en blanco, se registrará como 'Anónimo'.";
            textoAyudaDonante.style.color = "inherit";
        }
    }
}

function seleccionarOpcionAgenda(elementoLi, valor, texto, idInputOculto, idContenedorOtro) {
    seleccionarOpcionCatedral(elementoLi, valor, texto, idInputOculto);
    
    const contenedorOtro = document.getElementById(idContenedorOtro);

    if (valor === 'Otro') {
        if(contenedorOtro) {
            contenedorOtro.style.display = 'block';
            const inputOtro = contenedorOtro.querySelector('input');
            if(inputOtro) inputOtro.setAttribute('required', 'required'); // <-- LO HACE OBLIGATORIO
        }
    } else {
        if(contenedorOtro) {
            contenedorOtro.style.display = 'none';
            const inputOtro = contenedorOtro.querySelector('input');
            if(inputOtro) {
                inputOtro.value = '';
                inputOtro.removeAttribute('required'); // <-- LE QUITA LO OBLIGATORIO
            }
        }
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.select-personalizado-catedral')) {
        document.querySelectorAll('.select-personalizado-catedral.abierto').forEach(select => {
            select.classList.remove('abierto');
        });
    }
});

function forzarGuardadoAgenda() {
    let form = document.getElementById('form-editar-agenda-bd');
    
    if (form) {

        let hIni = document.getElementById('edit-agenda-hora-inicio');
        if (hIni.value && hIni.value.length > 5) hIni.value = hIni.value.substring(0, 5);
        
        let hFin = document.getElementById('edit-agenda-hora-fin');
        if (hFin.value && hFin.value.length > 5) hFin.value = hFin.value.substring(0, 5);
        
        if (form.checkValidity()) {
            form.submit();
        } else {
            form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }
    }
}

//MÓDULO ADMINISTRADOR: GESTIÓN DE USUARIOS

function abrirModalEditarUsuarioAdmin(id, nombre, usuario, rolId) {
    document.getElementById('admin-edit-user-id').value = id;
    document.getElementById('admin-edit-user-nombre').value = nombre;
    document.getElementById('admin-edit-user-login').value = usuario;

    const inputRolOculto = document.getElementById('admin-edit-user-rol-hidden');
    const triggerTexto = document.querySelector('#admin-edit-user-rol-trigger .select-texto');

    inputRolOculto.value = rolId;
    
    if (rolId == 3) {
        triggerTexto.textContent = 'Administrador';
    } else if (rolId == 2) {
        triggerTexto.textContent = 'Secretario';
    } else {
        triggerTexto.textContent = 'Ciudadano';
    }

    abrirModal('modal-admin-editar-usuario');
}

function confirmarEliminarUsuarioAdmin(id, usuario) {
    document.getElementById('admin-delete-user-id').value = id;
    document.getElementById('admin-delete-user-name').textContent = usuario;
    abrirModal('modal-admin-eliminar-usuario');
}

//GRÁFICAS DINÁMICAS (CHART.JS)

document.addEventListener('DOMContentLoaded', () => {
    const colorDorado = '#C69C6D';
    const colorOscuro = '#1a242f';
    const colorCeleste = '#8AB4F8';
    const colorVerde = '#6b9071';
    const colorRojo = '#e63946';

    const ctxActividades = document.getElementById('graficaActividades');
    if (ctxActividades && typeof graficaActNombres !== 'undefined') {
        new Chart(ctxActividades, {
            type: 'bar',
            data: {
                labels: graficaActNombres,
                datasets: [{
                    label: 'Total Registradas',
                    data: graficaActValores,
                    backgroundColor: colorDorado,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { 
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    } 
                }
            }
        });
    }

    const ctxDonaciones = document.getElementById('graficaDonaciones');
    if (ctxDonaciones && typeof graficaDonNombres !== 'undefined') {
        new Chart(ctxDonaciones, {
            type: 'doughnut',
            data: {
                labels: graficaDonNombres,
                datasets: [{
                    data: graficaDonValores, 
                    backgroundColor: ['#6b9071', '#C69C6D', '#4285F4', '#555555', '#e63946'],
                    borderWidth: 2,
                    borderColor: document.body.classList.contains('oscuro') ? '#1a242f' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
