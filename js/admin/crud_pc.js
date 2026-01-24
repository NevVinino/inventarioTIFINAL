document.addEventListener("DOMContentLoaded", function () {
    // --- elementos principales ---
    const modal = document.getElementById("modalActivo");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formActivo");

    // --- inputs del formulario ---
    const fechaCompraInput = document.getElementById("fechaCompra");
    const garantiaInput = document.getElementById("garantia");
    const precioInput = document.getElementById("precioCompra");
    const antiguedadInput = document.getElementById("antiguedad");
    const estadoGarantiaInput = document.getElementById("estadoGarantia");

    // --- labels visuales ---
    const labelAntiguedad = document.getElementById("antiguedadLegible") || null;
    const labelGarantia = document.getElementById("estadoGarantiaLabel") || null;

    // --- observaciones UI ---
    const btnToggleObs = document.getElementById("toggleObservaciones");
    const contenedorObs = document.getElementById("contenedorObservaciones");

    // --- configuración de slots --- ACTUALIZADO para PC (más slots que laptop)
    const slotsData = {
        cpu: 1,
        ram: 4,
        almacenamiento: 2,
        tarjeta_video: 1  // NUEVO: PC tiene tarjeta de video por defecto
    };

    // --- configuración de filtro de tipos de componentes ---
    let tipoFiltroActual = 'todos'; // 'todos', 'generico', 'detallado'

    // --- configuración de componentes (solo para RAM y Almacenamiento) ---
    const componentesSeleccionados = {
        RAM: new Set(),
        Almacenamiento: new Set()
    };

    // --- debug helper ---
    function debugTabla() {
        const tabla = document.getElementById("tablaPCs");
        if (!tabla) {
            console.error("No se encontró la tabla de PCs en el DOM");
            return;
        }

        const filas = tabla.querySelectorAll("tbody tr");
        console.log(`Tabla encontrada. Número de filas: ${filas.length}`);
        
        // Verificar si hay filas con mensaje de "no se encontraron PCs"
        const mensajeNoEncontrado = tabla.querySelector("tbody tr td[colspan]");
        if (mensajeNoEncontrado) {
            console.log("Mensaje encontrado: ", mensajeNoEncontrado.textContent);
        }
    }

    // --- utilidades ---
    function safeSetText(el, text) {
        if (!el) return;
        el.textContent = text;
    }

    // --- toggle observaciones ---
    function configurarToggleObservaciones() {
        const btnToggle = document.getElementById("toggleObservaciones");
        const contenedor = document.getElementById("contenedorObservaciones");
        
        if (btnToggle && contenedor) {
            btnToggle.replaceWith(btnToggle.cloneNode(true));
            const nuevoBtn = document.getElementById("toggleObservaciones");
            
            nuevoBtn.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (contenedor.style.display === "none" || contenedor.style.display === "") {
                    contenedor.style.display = "block";
                    nuevoBtn.textContent = "Ocultar";
                } else {
                    contenedor.style.display = "none";
                    nuevoBtn.textContent = "Mostrar";
                }
            });
        }
    }

    // --- configuración de filtro de tipos de componentes ---
    function configurarFiltroTipoComponente() {
        const btnToggle = document.getElementById('toggleTipoComponente');
        const estadoFiltro = document.getElementById('estadoFiltro');
        
        if (!btnToggle || !estadoFiltro) {
            console.error('Elementos de filtro no encontrados');
            return;
        }
        
        btnToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log("=== CAMBIANDO FILTRO DE COMPONENTES PC ===");
            console.log("Filtro anterior:", tipoFiltroActual);
            
            switch(tipoFiltroActual) {
                case 'todos':
                    tipoFiltroActual = 'generico';
                    btnToggle.textContent = 'Solo Genéricos';
                    btnToggle.className = 'btn-toggle-tipo filtro-generico';
                    estadoFiltro.textContent = '(Solo componentes genéricos)';
                    break;
                case 'generico':
                    tipoFiltroActual = 'detallado';
                    btnToggle.textContent = 'Solo Detallados';
                    btnToggle.className = 'btn-toggle-tipo filtro-detallado';
                    estadoFiltro.textContent = '(Solo componentes detallados)';
                    break;
                case 'detallado':
                    tipoFiltroActual = 'todos';
                    btnToggle.textContent = 'Mostrar Todos';
                    btnToggle.className = 'btn-toggle-tipo';
                    estadoFiltro.textContent = '(Genéricos y Detallados)';
                    break;
            }
            
            console.log("Nuevo filtro:", tipoFiltroActual);
            btnToggle.setAttribute('data-tipo', tipoFiltroActual);
            
            // Aplicar filtro a todos los selects de slots existentes
            aplicarFiltroASlots();
            
            console.log(`=== FILTRO CAMBIADO A: ${tipoFiltroActual} ===`);
        });
    }

    // --- event listeners para campos de slots - ACTUALIZADO para tarjeta de video ---
    function configurarEventListenersSlots() {
        ['slots_cpu', 'slots_ram', 'slots_almacenamiento', 'slots_tarjeta_video'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                // Remover listener anterior si existe
                element.removeEventListener('change', actualizarVistaSlots);
                // Añadir nuevo listener
                element.addEventListener('change', actualizarVistaSlots);
                console.log(`Event listener configurado para ${id} PC`);
            } else {
                console.error(`Elemento ${id} no encontrado para PC`);
            }
        });
    }

    // --- función global para recopilar datos de slots --- ACTUALIZADA para múltiples CPUs
    window.recopilarDatosSlots = function() {
        const datos = {
            cpu: null,
            cpus: [], // NUEVO: array para múltiples CPUs
            rams: [],
            almacenamientos: [],
            tarjetas_video: []
        };
        
        // CORREGIDO: Recopilar TODOS los CPUs en lugar de solo el primero
        document.querySelectorAll('select[data-tipo="cpu"]').forEach(slot => {
            if (slot.value) {
                datos.cpus.push(slot.value);
            }
        });
        
        // Mantener compatibilidad hacia atrás: si solo hay un CPU, asignarlo a cpu
        if (datos.cpus.length > 0) {
            datos.cpu = datos.cpus[0]; // Para validación
        }
        
        document.querySelectorAll('select[data-tipo="ram"]').forEach(slot => {
            if (slot.value) {
                datos.rams.push(slot.value);
            }
        });
        
        document.querySelectorAll('select[data-tipo="almacenamiento"]').forEach(slot => {
            if (slot.value) {
                datos.almacenamientos.push(slot.value);
            }
        });
        
        document.querySelectorAll('select[data-tipo="tarjeta_video"]').forEach(slot => {
            if (slot.value) {
                datos.tarjetas_video.push(slot.value);
            }
        });
        
        console.log("📊 Datos recopilados de slots PC:", datos);
        return datos;
    };

    // --- función para actualizar hidden input ---
    function actualizarHiddenInput(tipo) {
        if (tipo === 'CPU') return;
        
        const hiddenInput = document.getElementById(`${tipo.toLowerCase()}sHidden`);
        if (hiddenInput) {
            const valores = Array.from(componentesSeleccionados[tipo]);
            hiddenInput.value = valores.join(',');
        }
    }

    // --- funciones para guardar y restaurar valores de slots - CORREGIDAS para múltiples CPUs ---
    function guardarValoresSlots() {
        const valores = {
            cpu: {},
            ram: {},
            almacenamiento: {},
            tarjeta_video: {}
        };
        
        // CORREGIDO: Guardar TODOS los valores de CPU correctamente
        document.querySelectorAll('select[data-tipo="cpu"]').forEach(select => {
            const slot = select.getAttribute('data-slot');
            if (slot !== null && select.value && select.value !== '') {
                const valorData = {
                    valor: select.value,
                    texto: select.options[select.selectedIndex]?.text || '',
                    tipo: select.options[select.selectedIndex]?.getAttribute('data-tipo') || ''
                };
                valores.cpu[slot] = valorData;
                seleccionesPermanentes.cpu[slot] = valorData; // Guardar permanentemente
                console.log(`💾 CPU PC guardado: slot ${slot} = ${select.value}`);
            }
        });
        
        document.querySelectorAll('select[data-tipo="ram"]').forEach(select => {
            const slot = select.getAttribute('data-slot');
            if (slot !== null && select.value && select.value !== '') {
                const valorData = {
                    valor: select.value,
                    texto: select.options[select.selectedIndex]?.text || '',
                    tipo: select.options[select.selectedIndex]?.getAttribute('data-tipo') || ''
                };
                valores.ram[slot] = valorData;
                seleccionesPermanentes.ram[slot] = valorData; // Guardar permanentemente
            }
        });
        
        document.querySelectorAll('select[data-tipo="almacenamiento"]').forEach(select => {
            const slot = select.getAttribute('data-slot');
            if (slot !== null && select.value && select.value !== '') {
                const valorData = {
                    valor: select.value,
                    texto: select.options[select.selectedIndex]?.text || '',
                    tipo: select.options[select.selectedIndex]?.getAttribute('data-tipo') || ''
                };
                valores.almacenamiento[slot] = valorData;
                seleccionesPermanentes.almacenamiento[slot] = valorData; // Guardar permanentemente
            }
        });
        
        // NUEVO: Guardar valores de tarjetas de video
        document.querySelectorAll('select[data-tipo="tarjeta_video"]').forEach(select => {
            const slot = select.getAttribute('data-slot');
            if (slot !== null && select.value && select.value !== '') {
                const valorData = {
                    valor: select.value,
                    texto: select.options[select.selectedIndex]?.text || '',
                    tipo: select.options[select.selectedIndex]?.getAttribute('data-tipo') || ''
                };
                valores.tarjeta_video[slot] = valorData;
                seleccionesPermanentes.tarjeta_video[slot] = valorData; // Guardar permanentemente
            }
        });
        
        console.log("📥 Valores guardados PC (temporales):", valores);
        console.log("💾 Selecciones permanentes PC actualizadas:", seleccionesPermanentes);
        return valores;
    }

    function restaurarValoresSlots(valores) {
        // Usar selecciones permanentes como fuente principal
        const fuenteDatos = seleccionesPermanentes;
        
        console.log("🔄 Restaurando slots PC desde selecciones permanentes:", fuenteDatos);
        
        // Restaurar CPU
        Object.keys(fuenteDatos.cpu || {}).forEach(slot => {
            const select = document.querySelector(`select[data-tipo="cpu"][data-slot="${slot}"]`);
            if (select && fuenteDatos.cpu[slot]) {
                const valorBuscado = fuenteDatos.cpu[slot].valor;
                const tipoComponente = fuenteDatos.cpu[slot].tipo;
                
                const debeEstarVisible = tipoFiltroActual === 'todos' || tipoFiltroActual === tipoComponente;
                
                if (debeEstarVisible) {
                    const optionExists = Array.from(select.options).some(option => option.value === valorBuscado);
                    if (optionExists) {
                        select.value = valorBuscado;
                        console.log(`✅ CPU PC slot ${slot} restaurado:`, valorBuscado);
                    }
                } else {
                    // Mantener selección oculta
                    const optionTemp = document.createElement('option');
                    optionTemp.value = valorBuscado;
                    optionTemp.textContent = fuenteDatos.cpu[slot].texto + ' (Oculto en filtro actual)';
                    optionTemp.setAttribute('data-tipo', tipoComponente);
                    optionTemp.style.fontStyle = 'italic';
                    optionTemp.style.opacity = '0.7';
                    select.appendChild(optionTemp);
                    select.value = valorBuscado;
                    console.log(`🔒 CPU PC slot ${slot} mantenido (oculto):`, valorBuscado);
                }
            }
        });
        
        // Restaurar RAM
        Object.keys(fuenteDatos.ram || {}).forEach(slot => {
            const select = document.querySelector(`select[data-tipo="ram"][data-slot="${slot}"]`);
            if (select && fuenteDatos.ram[slot]) {
                const valorBuscado = fuenteDatos.ram[slot].valor;
                const tipoComponente = fuenteDatos.ram[slot].tipo;
                
                const debeEstarVisible = tipoFiltroActual === 'todos' || tipoFiltroActual === tipoComponente;
                
                if (debeEstarVisible) {
                    const optionExists = Array.from(select.options).some(option => option.value === valorBuscado);
                    if (optionExists) {
                        select.value = valorBuscado;
                        console.log(`✅ RAM PC slot ${slot} restaurado:`, valorBuscado);
                    }
                } else {
                    // Mantener selección oculta
                    const optionTemp = document.createElement('option');
                    optionTemp.value = valorBuscado;
                    optionTemp.textContent = fuenteDatos.ram[slot].texto + ' (Oculto en filtro actual)';
                    optionTemp.setAttribute('data-tipo', tipoComponente);
                    optionTemp.style.fontStyle = 'italic';
                    optionTemp.style.opacity = '0.7';
                    select.appendChild(optionTemp);
                    select.value = valorBuscado;
                    console.log(`🔒 RAM PC slot ${slot} mantenido (oculto):`, valorBuscado);
                }
            }
        });
        
        // Restaurar Almacenamiento
        Object.keys(fuenteDatos.almacenamiento || {}).forEach(slot => {
            const select = document.querySelector(`select[data-tipo="almacenamiento"][data-slot="${slot}"]`);
            if (select && fuenteDatos.almacenamiento[slot]) {
                const valorBuscado = fuenteDatos.almacenamiento[slot].valor;
                const tipoComponente = fuenteDatos.almacenamiento[slot].tipo;
                
                const debeEstarVisible = tipoFiltroActual === 'todos' || tipoFiltroActual === tipoComponente;
                
                if (debeEstarVisible) {
                    const optionExists = Array.from(select.options).some(option => option.value === valorBuscado);
                    if (optionExists) {
                        select.value = valorBuscado;
                        console.log(`✅ Almacenamiento PC slot ${slot} restaurado:`, valorBuscado);
                    }
                } else {
                    // Mantener selección oculta
                    const optionTemp = document.createElement('option');
                    optionTemp.value = valorBuscado;
                    optionTemp.textContent = fuenteDatos.almacenamiento[slot].texto + ' (Oculto en filtro actual)';
                    optionTemp.setAttribute('data-tipo', tipoComponente);
                    optionTemp.style.fontStyle = 'italic';
                    optionTemp.style.opacity = '0.7';
                    select.appendChild(optionTemp);
                    select.value = valorBuscado;
                    console.log(`🔒 Almacenamiento PC slot ${slot} mantenido (oculto):`, valorBuscado);
                }
            }
        });
        
        // NUEVO: Restaurar Tarjetas de Video
        Object.keys(fuenteDatos.tarjeta_video || {}).forEach(slot => {
            const select = document.querySelector(`select[data-tipo="tarjeta_video"][data-slot="${slot}"]`);
            if (select && fuenteDatos.tarjeta_video[slot]) {
                const valorBuscado = fuenteDatos.tarjeta_video[slot].valor;
                const tipoComponente = fuenteDatos.tarjeta_video[slot].tipo;
                
                const debeEstarVisible = tipoFiltroActual === 'todos' || tipoFiltroActual === tipoComponente;
                
                if (debeEstarVisible) {
                    const optionExists = Array.from(select.options).some(option => option.value === valorBuscado);
                    if (optionExists) {
                        select.value = valorBuscado;
                        console.log(`✅ Tarjeta de Video PC slot ${slot} restaurado:`, valorBuscado);
                    }
                } else {
                    // Mantener selección oculta
                    const optionTemp = document.createElement('option');
                    optionTemp.value = valorBuscado;
                    optionTemp.textContent = fuenteDatos.tarjeta_video[slot].texto + ' (Oculto en filtro actual)';
                    optionTemp.setAttribute('data-tipo', tipoComponente);
                    optionTemp.style.fontStyle = 'italic';
                    optionTemp.style.opacity = '0.7';
                    select.appendChild(optionTemp);
                    select.value = valorBuscado;
                    console.log(`🔒 Tarjeta de Video PC slot ${slot} mantenido (oculto):`, valorBuscado);
                }
            }
        });
    }

    // --- NUEVO: Sistema de preservación permanente de selecciones - ACTUALIZADO ---
    let seleccionesPermanentes = {
        cpu: {},
        ram: {},
        almacenamiento: {},
        tarjeta_video: {} // NUEVO
    };

    // --- NUEVA función para limpiar selecciones permanentes - ACTUALIZADA ---
    function limpiarSeleccionesPermanentes() {
        seleccionesPermanentes = {
            cpu: {},
            ram: {},
            almacenamiento: {},
            tarjeta_video: {} // NUEVO
        };
        console.log("🧹 Selecciones permanentes PC limpiadas");
    }

    // --- NUEVA función para eliminar una selección específica ---
    function eliminarSeleccionPermanente(tipo, slot) {
        if (seleccionesPermanentes[tipo] && seleccionesPermanentes[tipo][slot]) {
            delete seleccionesPermanentes[tipo][slot];
            console.log(`🗑️ Eliminada selección permanente PC ${tipo} slot ${slot}`);
        }
    }

    // --- funciones de slots - MEJORADAS para manejar cambios de selección ---
    function generarSlotsHTML(tipo, cantidad) {
        const container = document.getElementById(`slots-${tipo}-container`);
        if (!container) {
            console.error(`Container slots-${tipo}-container no encontrado para PC`);
            return;
        }
        
        console.log(`🏗️ Generando ${cantidad} slots de ${tipo} para PC`);
        
        // Si la cantidad es 0, limpiar el contenedor y ocultar
        if (cantidad <= 0) {
            container.innerHTML = '';
            container.style.display = 'none';
            console.log(`❌ No se generaron slots de ${tipo} para PC (cantidad = ${cantidad})`);
            return;
        }
        
        // Mostrar el contenedor si hay slots
        container.style.display = 'block';
        container.innerHTML = `<h6>Slots de ${tipo.toUpperCase()} (${cantidad})</h6>`;
        
        for (let i = 0; i < cantidad; i++) {
            const slotDiv = document.createElement('div');
            slotDiv.className = 'slot-item';
            slotDiv.innerHTML = `
                <label>Slot ${i + 1}:</label>
                <select name="slot_${tipo}_${i}" id="slot_${tipo}_${i}" class="slot-select" data-tipo="${tipo}" data-slot="${i}">
                    <option value="">Libre</option>
                </select>
            `;
            container.appendChild(slotDiv);
        }
        
        // Llenar opciones según el tipo y filtro actual
        const selects = container.querySelectorAll('.slot-select');
        selects.forEach(select => {
            llenarOpcionesSlotConFiltro(tipo, select);
            
            // NUEVO: Agregar listener para detectar cambios y actualizar selecciones permanentes
            select.addEventListener('change', function() {
                const slot = this.getAttribute('data-slot');
                const tipoSlot = this.getAttribute('data-tipo');
                
                if (this.value && this.value !== '') {
                    // Guardar nueva selección
                    const valorData = {
                        valor: this.value,
                        texto: this.options[this.selectedIndex]?.text || '',
                        tipo: this.options[this.selectedIndex]?.getAttribute('data-tipo') || ''
                    };
                    seleccionesPermanentes[tipoSlot][slot] = valorData;
                    console.log(`💾 Nueva selección PC guardada: ${tipoSlot} slot ${slot} = ${this.value}`);
                } else {
                    // Eliminar selección
                    eliminarSeleccionPermanente(tipoSlot, slot);
                }
            });
        });
        
        console.log(`✅ ${cantidad} slots de ${tipo} para PC generados correctamente`);
    }

    // --- abrir modal "Nuevo" - ACTUALIZADO para PC ---
    if (btnNuevo) {
        btnNuevo.addEventListener("click", function () {
            if (!modal) return;

            // Limpiar selecciones permanentes al abrir nuevo modal
            limpiarSeleccionesPermanentes();

            const modalTitle = document.getElementById("modal-title");
            if (modalTitle) modalTitle.textContent = "Registrar PC";
            const accionField = document.getElementById("accion");
            if (accionField) accionField.value = "crear";

            if (form) form.reset();
            modal.querySelectorAll("input, select, textarea").forEach(el => el.disabled = false);

            componentesSeleccionados.RAM.clear();
            componentesSeleccionados.Almacenamiento.clear();

            ['RAM', 'Almacenamiento'].forEach(tipo => {
                const contenedor = document.getElementById(`${tipo.toLowerCase()}Seleccionados`);
                if (contenedor) contenedor.innerHTML = '';
                const hiddenInput = document.getElementById(`${tipo.toLowerCase()}sHidden`);
                if (hiddenInput) hiddenInput.value = '';
            });

            safeSetText(labelAntiguedad, "(No calculado)");
            safeSetText(labelGarantia, "(No calculado)");

            const contenedorObs = document.getElementById("contenedorObservaciones");
            const btnToggleObs = document.getElementById("toggleObservaciones");
            if (btnToggleObs) btnToggleObs.textContent = "Mostrar";
            if (contenedorObs) contenedorObs.style.display = "none";

            calcularAntiguedad();
            calcularEstadoGarantia();

            // Configurar slots por defecto para PC (diferentes de laptop)
            document.getElementById('slots_cpu').value = 1;
            document.getElementById('slots_ram').value = 4; // PC tiene más RAM slots
            document.getElementById('slots_almacenamiento').value = 2; // PC tiene más almacenamiento slots
            document.getElementById('slots_tarjeta_video').value = 0; // CORREGIDO: PC inicia con 0 tarjetas de video por defecto
            
            // Resetear filtro de componentes
            tipoFiltroActual = 'todos';
            const btnToggle = document.getElementById('toggleTipoComponente');
            const estadoFiltro = document.getElementById('estadoFiltro');
            if (btnToggle) {
                btnToggle.textContent = 'Mostrar Todos';
                btnToggle.className = 'btn-toggle-tipo';
                btnToggle.setAttribute('data-tipo', 'todos');
            }
            if (estadoFiltro) {
                estadoFiltro.textContent = '(Genéricos y Detallados)';
            }
            
            // Configurar vista de slots
            actualizarVistaSlots();
            
            // IMPORTANTE: Configurar event listeners DESPUÉS de actualizar slots
            setTimeout(() => {
                configurarEventListenersSlots();
                configurarToggleObservaciones();
            }, 150);

            modal.style.display = "block";
        });
    }

    // --- cerrar modal ---
    if (spanClose && modal) {
        // Cerrar modal con clic en la X o con tecla Escape
        spanClose.addEventListener("click", () => modal.style.display = "none");
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && modal.style.display === "block") {
                modal.style.display = "none";
            }
        });
    }

    // --- buscador ---
    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaPCs tbody tr");
    if (buscador) {
        buscador.addEventListener("input", function () {
            const valor = buscador.value.toLowerCase();
            filas.forEach(function (fila) {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(valor) ? "" : "none";
            });
        });
    }

    // --- Modal de visualización ---
    const modalView = document.getElementById('modalVisualizacion');
    const spanCloseView = document.querySelector('.close-view');

    document.querySelectorAll(".btn-ver").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modalView) return;

            // Limpiar todos los campos antes de llenar
            document.querySelectorAll('#modalVisualizacion .detalle-item span').forEach(span => {
                span.textContent = 'No especificado';
                if (span.id === 'view-estado') {
                    span.removeAttribute('data-estado');
                }
            });
            
            document.querySelectorAll('#modalVisualizacion .detalle-item div#view-qr').forEach(div => {
                div.innerHTML = '';
            });
            
            // Limpiar observaciones
            const observacionesDiv = document.getElementById('view-observaciones');
            if (observacionesDiv) {
                observacionesDiv.textContent = 'Sin observaciones';
            }
            
            const downloadBtn = document.getElementById('download-qr');
            if (downloadBtn) {
                downloadBtn.style.display = 'none';
            }
            
            // Función auxiliar para formatear fechas
            function formatearFecha(fecha) {
                if (!fecha || fecha === '') return 'No especificado';
                try {
                    // CORREGIDO: Agregar 'T00:00:00' para evitar problemas de zona horaria
                    const fechaString = fecha.includes('T') ? fecha : fecha + 'T00:00:00';
                    const fechaObj = new Date(fechaString);
                    if (isNaN(fechaObj.getTime())) return 'Fecha inválida';
                    
                    // CORREGIDO: Usar toLocaleDateString con UTC para evitar desfase de zona horaria
                    return fechaObj.toLocaleDateString('es-ES', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        timeZone: 'UTC'
                    });
                } catch (e) {
                    return 'Fecha inválida';
                }
            }
            
            // Función auxiliar para formatear precios
            function formatearPrecio(precio) {
                if (!precio || precio === '' || precio === '0') return 'No especificado';
                try {
                    const precioNum = parseFloat(precio);
                    if (isNaN(precioNum)) return 'Precio inválido';
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD',
                        minimumFractionDigits: 2
                    }).format(precioNum);
                } catch (e) {
                    return '$ ' + precio;
                }
            }
            
            // Función auxiliar para formatear antigüedad
            function formatearAntiguedad(antiguedadDias) {
                if (!antiguedadDias || antiguedadDias === '' || antiguedadDias === '0') {
                    return 'No calculado';
                }
                
                const dias = parseInt(antiguedadDias);
                if (isNaN(dias)) return 'No calculado';
                
                const años = Math.floor(dias / 365);
                const restoDias = dias % 365;
                const meses = Math.floor(restoDias / 30);
                const diasFinales = restoDias % 30;
                
                const partes = [];
                if (años > 0) partes.push(`${años} ${años === 1 ? "año" : "años"}`);
                if (meses > 0) partes.push(`${meses} ${meses === 1 ? "mes" : "meses"}`);
                if (diasFinales > 0 || partes.length === 0) partes.push(`${diasFinales} ${diasFinales === 1 ? "día" : "días"}`);
                
                return partes.join(", ");
            }
            
            // NUEVA función para procesar información de slots y remover IDs (igual que laptops)
            function procesarInfoSlots(infoSlots) {
                if (!infoSlots || infoSlots === 'No especificado' || infoSlots.trim() === '') {
                    return 'No especificado';
                }
                
                // Dividir por comas para procesar cada slot individualmente
                const slots = infoSlots.split(', ');
                const slotsLimpios = slots.map(slot => {
                    // Buscar patrón "Slot [número]: [información]" y reemplazar solo el número por texto genérico
                    // Mantener toda la información después de los dos puntos
                    const match = slot.match(/^Slot\s+\d+:\s*(.+)$/);
                    if (match) {
                        return 'Slot: ' + match[1]; // match[1] contiene todo después de los dos puntos
                    }
                    return slot; // Si no coincide con el patrón, devolver tal como está
                });
                
                return slotsLimpios.join(', ');
            }
            
            // Llenar campos básicos con validación y formato
            for (let attr in this.dataset) {
                const element = document.getElementById('view-' + attr);
                if (element) {
                    let valor = this.dataset[attr] || 'No especificado';
                    
                    // Aplicar formato específico según el tipo de campo
                    switch(attr) {
                        case 'fechacompra':
                        case 'garantia':
                            valor = formatearFecha(valor);
                            break;
                        case 'preciocompra':
                            valor = formatearPrecio(valor);
                            break;
                        case 'antiguedad':
                            valor = formatearAntiguedad(valor);
                            break;
                        case 'estadogarantia':
                            // Aplicar clase CSS según el estado
                            if (valor.toLowerCase() === 'vigente') {
                                element.className = 'estado-garantia-vigente';
                            } else if (valor.toLowerCase() === 'no vigente') {
                                element.className = 'estado-garantia-no-vigente';
                            } else {
                                element.className = 'estado-garantia-sin';
                            }
                            break;
                        case 'empresa':
                            // Mostrar empresa o "No asignado" si está vacío
                            if (!valor || valor === 'No especificado' || valor.trim() === '') {
                                valor = 'No asignado';
                            }
                            break;
                        case 'asistente':
                            // Mostrar el nombre del asistente TI que registró el activo
                            if (!valor || valor === 'No especificado' || valor.trim() === '') {
                                valor = 'Usuario no identificado';
                            }
                            break;
                        case 'qr': {
                            const qrPath = this.dataset[attr];
                            const qrLink = (() => {
                                const linkAttr = this.dataset.qrLink;
                                if (linkAttr && linkAttr.trim() !== '') return linkAttr;
                                const idActivo = this.dataset.id;
                                if (!idActivo) return null;
                                const host = window.location.host || 'localhost:8000';
                                const protocol = window.location.protocol === 'http:' || window.location.protocol === 'https:' ? window.location.protocol : 'http:';
                                const base = host.includes('localhost') || host.includes('127.0.0.1') ? 'http://localhost:8000' : `${protocol}//${host}`;
                                return `${base}/php/views/user/detalle_activo_publico.php?id=${idActivo}`;
                            })();

                            if (qrPath) {
                                const img = document.createElement('img');
                                img.src = `../../${qrPath}`;
                                img.alt = 'QR Code';
                                img.className = 'qr-image';

                                element.innerHTML = '';
                                if (qrLink) {
                                    const anchor = document.createElement('a');
                                    anchor.href = qrLink;
                                    anchor.target = '_blank';
                                    anchor.rel = 'noopener noreferrer';
                                    anchor.appendChild(img);
                                    element.appendChild(anchor);
                                } else {
                                    element.appendChild(img);
                                }
                            } else {
                                element.textContent = 'No especificado';
                            }

                            if (downloadBtn && qrPath) {
                                downloadBtn.style.display = 'block';
                                downloadBtn.href = "../../" + qrPath;
                                downloadBtn.download = qrPath.split('/').pop();
                            }
                            continue; // Skip setting textContent for QR
                        }
                        case 'cpu':
                        case 'ram':
                        case 'almacenamiento':
                        case 'tarjeta_video':
                            // APLICAR procesamiento para remover IDs de slots
                            const infoSlotsProcesada = procesarInfoSlots(this.dataset[attr]);
                            const componentes = infoSlotsProcesada.split(', ');
                            
                            if (componentes.length > 0 && componentes[0] !== '' && componentes[0] !== 'No especificado') {
                                if (componentes.length > 1) {
                                    const ul = document.createElement('ul');
                                    ul.className = 'componentes-lista';
                                    
                                    componentes.forEach(componente => {
                                        const li = document.createElement('li');
                                        li.textContent = componente;
                                        ul.appendChild(li);
                                    });
                                    
                                    element.innerHTML = '';
                                    element.appendChild(ul);
                                } else {
                                    element.textContent = componentes[0];
                                }
                            } else {
                                element.textContent = 'No especificado';
                            }
                            continue; // Skip setting textContent for components
                        case 'estado':
                            element.textContent = valor;
                            element.setAttribute('data-estado', valor);
                            
                            // Aplicar clase CSS según el estado
                            element.className = 'estado-celda';
                            if (valor.toLowerCase() === 'disponible') {
                                element.classList.add('estado-disponible');
                            } else if (valor.toLowerCase() === 'asignado') {
                                element.classList.add('estado-asignado');
                            } else if (valor.toLowerCase() === 'malogrado') {
                                element.classList.add('estado-malogrado');
                            } else if (valor.toLowerCase() === 'almacen') {
                                element.classList.add('estado-almacen');
                            }
                            continue;
                        case 'observaciones':
                            const observacionesElement = document.getElementById('view-observaciones');
                            if (observacionesElement) {
                                if (valor && valor !== 'No especificado' && valor.trim() !== '') {
                                    observacionesElement.textContent = valor;
                                    observacionesElement.className = 'observaciones-texto con-contenido';
                                } else {
                                    observacionesElement.textContent = 'Sin observaciones';
                                    observacionesElement.className = 'observaciones-texto sin-contenido';
                                }
                            }
                            continue;
                        case 'link':
                            // Para links, mostrar un enlace acortado con opciones de clic y copia
                            if (valor && valor !== 'No especificado' && valor.trim() !== '') {
                                try {
                                    // Verificar si es una URL válida
                                    const url = new URL(valor.startsWith('http') ? valor : 'https://' + valor);
                                    
                                    // Crear contenedor para el link con opciones
                                    const linkContainer = document.createElement('div');
                                    linkContainer.className = 'link-container';
                                    
                                    // Crear enlace clickeable
                                    const linkElement = document.createElement('a');
                                    linkElement.href = url.href;
                                    linkElement.target = '_blank';
                                    linkElement.rel = 'noopener noreferrer';
                                    linkElement.textContent = '🔗 Abrir enlace';
                                    linkElement.className = 'link-abrir';
                                    
                                    // Crear botón para copiar
                                    const copyButton = document.createElement('button');
                                    copyButton.type = 'button';
                                    copyButton.textContent = '📋 Copiar link';
                                    copyButton.className = 'btn-copiar-link';
                                    copyButton.onclick = function() {
                                        navigator.clipboard.writeText(url.href).then(() => {
                                            // Feedback visual temporal
                                            const originalText = copyButton.textContent;
                                            copyButton.textContent = '✅ Copiado';
                                            copyButton.style.backgroundColor = '#4CAF50';
                                            
                                            setTimeout(() => {
                                                copyButton.textContent = originalText;
                                                copyButton.style.backgroundColor = '';
                                            }, 2000);
                                        }).catch(err => {
                                            console.error('Error copiando al portapapeles:', err);
                                            alert('No se pudo copiar el enlace');
                                        });
                                    };
                                    
                                    // Mostrar URL completa en tooltip
                                    linkContainer.title = `URL completa: ${url.href}`;
                                    
                                    linkContainer.appendChild(linkElement);
                                    linkContainer.appendChild(copyButton);
                                    
                                    element.innerHTML = '';
                                    element.appendChild(linkContainer);
                                } catch (e) {
                                    // Si no es una URL válida, mostrar como texto con opción de copia
                                    const textContainer = document.createElement('div');
                                    textContainer.className = 'link-container';
                                    
                                    const textSpan = document.createElement('span');
                                    textSpan.textContent = valor;
                                    textSpan.className = 'link-texto';
                                    
                                    const copyButton = document.createElement('button');
                                    copyButton.type = 'button';
                                    copyButton.textContent = '📋 Copiar';
                                    copyButton.className = 'btn-copiar-link';
                                    copyButton.onclick = function() {
                                        navigator.clipboard.writeText(valor).then(() => {
                                            const originalText = copyButton.textContent;
                                            copyButton.textContent = '✅ Copiado';
                                            copyButton.style.backgroundColor = '#4CAF50';
                                            
                                            setTimeout(() => {
                                                copyButton.textContent = originalText;
                                                copyButton.style.backgroundColor = '';
                                            }, 2000);
                                        }).catch(err => {
                                            console.error('Error copiando al portapapeles:', err);
                                            alert('No se pudo copiar el texto');
                                        });
                                    };
                                    
                                    textContainer.appendChild(textSpan);
                                    textContainer.appendChild(copyButton);
                                    
                                    element.innerHTML = '';
                                    element.appendChild(textContainer);
                                }
                            } else {
                                element.textContent = 'No especificado';
                            }
                            continue;
                        default:
                            // Para campos de texto simples, aplicar capitalización si es apropiado
                            if (valor !== 'No especificado' && typeof valor === 'string') {
                                // Capitalizar primera letra para ciertos campos
                                if (['nombreequipo', 'modelo', 'marca'].includes(attr)) {
                                    valor = valor.charAt(0).toUpperCase() + valor.slice(1);
                                }
                            }
                            break;
                    }
                    
                    element.textContent = valor;
                }
            }

            modalView.style.display = 'block';
        });
    });

    if (spanCloseView) {
        spanCloseView.addEventListener('click', function () {
            if (modalView) modalView.style.display = 'none';
        });
    }

    // --- funciones auxiliares ---
    function cargarComponentes(tipo, datos) {
        if (!datos || tipo === 'CPU') return;
        
        const contenedor = document.getElementById(`${tipo.toLowerCase()}Seleccionados`);
        
        datos.split('||').forEach(item => {
            const [id, descripcion] = item.split('::');
            if (!id || !descripcion) return;
            
            componentesSeleccionados[tipo].add(id);
            
            const div = document.createElement('div');
            div.className = 'componente-tag';
            div.dataset.id = id;
            div.textContent = descripcion;
            
            const btnEliminar = document.createElement('button');
            btnEliminar.type = 'button';
            btnEliminar.textContent = 'X';
            btnEliminar.onclick = () => {
                componentesSeleccionados[tipo].delete(id);
                div.remove();
                actualizarHiddenInput(tipo);
            };
            
            div.appendChild(btnEliminar);
            contenedor.appendChild(div);
        });
        
        actualizarHiddenInput(tipo);
    }
    
    function verificarAsignacion(id_activo) {
        fetch(`../controllers/procesar_pc.php?verificar_asignacion=1&id_activo=${id_activo}`)
            .then(response => response.json())
            .then(data => {
                if (data.asignado) {
                    alert("Esta PC está asignada actualmente. Algunas opciones de edición pueden estar limitadas.");
                }
            })
            .catch(error => console.error('Error verificando asignación PC:', error));
    }

    function cargarSlotsExistentes(id_activo) {
        fetch(`../controllers/procesar_pc.php?obtener_slots=1&id_activo=${id_activo}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('slots_cpu').value = data.slots.cpu_count || 1;
                    document.getElementById('slots_ram').value = data.slots.ram_count || 4; // PC default
                    document.getElementById('slots_almacenamiento').value = data.slots.almacenamiento_count || 2; // PC default
                    document.getElementById('slots_tarjeta_video').value = data.slots.tarjeta_video_count || 1; // PC default
                    
                    const cpuSlots = parseInt(document.getElementById('slots_cpu').value) || 1;
                    const ramSlots = parseInt(document.getElementById('slots_ram').value) || 4;
                    const almacenamientoSlots = parseInt(document.getElementById('slots_almacenamiento').value) || 2;
                    const tarjetaVideoSlots = parseInt(document.getElementById('slots_tarjeta_video').value) || 1; // NUEVO
                    
                    slotsData.cpu = cpuSlots;
                    slotsData.ram = ramSlots;
                    slotsData.almacenamiento = almacenamientoSlots;
                    slotsData.tarjeta_video = tarjetaVideoSlots; // NUEVO
                    
                    const container = document.getElementById('slots-container');
                    if (container) {
                        container.style.display = 'block';
                        
                        generarSlotsHTML('cpu', cpuSlots);
                        generarSlotsHTML('ram', ramSlots);
                        generarSlotsHTML('almacenamiento', almacenamientoSlots);
                        generarSlotsHTML('tarjeta_video', tarjetaVideoSlots); // NUEVO
                    }
                    
                    cargarComponentesEnSlots(data.slots);
                } else {
                    // Defaults para PC
                    document.getElementById('slots_cpu').value = 1;
                    document.getElementById('slots_ram').value = 4;
                    document.getElementById('slots_almacenamiento').value = 2;
                    document.getElementById('slots_tarjeta_video').value = 1;
                    actualizarVistaSlots();
                }
            })
            .catch(error => {
                console.error('Error obteniendo slots PC:', error);
                document.getElementById('slots_cpu').value = 1;
                document.getElementById('slots_ram').value = 4;
                document.getElementById('slots_almacenamiento').value = 2;
                document.getElementById('slots_tarjeta_video').value = 1;
                actualizarVistaSlots();
            });
    }

    // --- cargar componentes en slots específicos - ACTUALIZADA para tarjeta video ---
    function cargarComponentesEnSlots(slotsData) {
        // Limpiar selecciones permanentes antes de cargar desde BD
        limpiarSeleccionesPermanentes();
        
        setTimeout(() => {
            // Cargar CPU
            if (slotsData.cpu_slots && slotsData.cpu_slots.length > 0) {
                slotsData.cpu_slots.forEach((slot, index) => {
                    const cpuSelect = document.querySelector(`select[data-tipo="cpu"][data-slot="${index}"]`);
                    if (cpuSelect && slot.componente) {
                        const optionExists = Array.from(cpuSelect.options).some(option => option.value === slot.componente);
                        if (optionExists) {
                            cpuSelect.value = slot.componente;
                            // Guardar en selecciones permanentes
                            const valorData = {
                                valor: slot.componente,
                                texto: cpuSelect.options[cpuSelect.selectedIndex]?.text || '',
                                tipo: cpuSelect.options[cpuSelect.selectedIndex]?.getAttribute('data-tipo') || ''
                            };
                            seleccionesPermanentes.cpu[index] = valorData;
                        }
                    }
                });
            }
            
            // Cargar RAM
            if (slotsData.ram_slots && slotsData.ram_slots.length > 0) {
                slotsData.ram_slots.forEach((slot, index) => {
                    const ramSelect = document.querySelector(`select[data-tipo="ram"][data-slot="${index}"]`);
                    if (ramSelect && slot.componente) {
                        const optionExists = Array.from(ramSelect.options).some(option => option.value === slot.componente);
                        if (optionExists) {
                            ramSelect.value = slot.componente;
                            // Guardar en selecciones permanentes
                            const valorData = {
                                valor: slot.componente,
                                texto: ramSelect.options[ramSelect.selectedIndex]?.text || '',
                                tipo: ramSelect.options[ramSelect.selectedIndex]?.getAttribute('data-tipo') || ''
                            };
                            seleccionesPermanentes.ram[index] = valorData;
                        }
                    }
                });
            }
            
            // Cargar Almacenamiento
            if (slotsData.almacenamiento_slots && slotsData.almacenamiento_slots.length > 0) {
                slotsData.almacenamiento_slots.forEach((slot, index) => {
                    const almacenamientoSelect = document.querySelector(`select[data-tipo="almacenamiento"][data-slot="${index}"]`);
                    if (almacenamientoSelect && slot.componente) {
                        const optionExists = Array.from(almacenamientoSelect.options).some(option => option.value === slot.componente);
                        if (optionExists) {
                            almacenamientoSelect.value = slot.componente;
                            // Guardar en selecciones permanentes
                            const valorData = {
                                valor: slot.componente,
                                texto: almacenamientoSelect.options[almacenamientoSelect.selectedIndex]?.text || '',
                                tipo: almacenamientoSelect.options[almacenamientoSelect.selectedIndex]?.getAttribute('data-tipo') || ''
                            };
                            seleccionesPermanentes.almacenamiento[index] = valorData;
                        }
                    }
                });
            }
            
            // NUEVO: Cargar tarjetas de video
            if (slotsData.tarjeta_video_slots && slotsData.tarjeta_video_slots.length > 0) {
                slotsData.tarjeta_video_slots.forEach((slot, index) => {
                    const tvSelect = document.querySelector(`select[data-tipo="tarjeta_video"][data-slot="${index}"]`);
                    if (tvSelect && slot.componente) {
                        const optionExists = Array.from(tvSelect.options).some(option => option.value === slot.componente);
                        if (optionExists) {
                            tvSelect.value = slot.componente;
                            // Guardar en selecciones permanentes
                            const valorData = {
                                valor: slot.componente,
                                texto: tvSelect.options[tvSelect.selectedIndex]?.text || '',
                                tipo: tvSelect.options[tvSelect.selectedIndex]?.getAttribute('data-tipo') || ''
                            };
                            seleccionesPermanentes.tarjeta_video[index] = valorData;
                        }
                    }
                });
            }
            
            console.log("💾 Selecciones permanentes PC cargadas desde BD:", seleccionesPermanentes);
        }, 100);
    }

    // --- editar activo - ACTUALIZADO para cargar slots de edición ---
    document.querySelectorAll(".btn-editar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modal) return;
            modal.style.display = "block";
            
            // Limpiar selecciones permanentes al abrir modal de edición
            limpiarSeleccionesPermanentes();
            
            document.getElementById("modal-title").textContent = "Editar PC";
            document.getElementById("accion").value = "editar";
            document.getElementById("id_activo").value = this.dataset.id;
            
            // Rellenar inputs básicos con los valores correctos
            document.getElementById("nombreEquipo").value = this.dataset.nombreequipo || '';
            document.getElementById("modelo").value = this.dataset.modelo || '';
            document.getElementById("mac").value = this.dataset.mac || '';
            document.getElementById("numberSerial").value = this.dataset.serial || '';
            document.getElementById("fechaCompra").value = this.dataset.fechacompra || '';
            document.getElementById("garantia").value = this.dataset.garantia || '';
            document.getElementById("precioCompra").value = this.dataset.precio || '';
            document.getElementById("antiguedad").value = this.dataset.antiguedad || '';
            document.getElementById("ordenCompra").value = this.dataset.orden || '';
            document.getElementById("estadoGarantia").value = this.dataset.estadogarantia || '';
            document.getElementById("numeroIP").value = this.dataset.ip || '';
            document.getElementById("link").value = this.dataset.link || '';
            document.getElementById("observaciones").value = this.dataset.observaciones || '';
            
            // Establecer los selects
            if (this.dataset.marca) {
                const selectMarca = document.getElementById("id_marca");
                if (selectMarca) selectMarca.value = this.dataset.marca;
            }
            
            if (this.dataset.estadoactivo) {
                const selectEstado = document.getElementById("id_estado_activo");
                if (selectEstado) selectEstado.value = this.dataset.estadoactivo;
            }
            
            if (this.dataset.empresa) {
                const selectEmpresa = document.getElementById("id_empresa");
                if (selectEmpresa) selectEmpresa.value = this.dataset.empresa;
            }
            
            // Verificar si el activo está asignado antes de permitir edición
            verificarAsignacion(this.dataset.id);
            
            // Configurar observaciones
            const contenedorObs = document.getElementById("contenedorObservaciones");
            const btnToggleObs = document.getElementById("toggleObservaciones");
            
            if (this.dataset.observaciones) {
                if (contenedorObs) contenedorObs.style.display = "block";
                if (btnToggleObs) btnToggleObs.textContent = "Ocultar";
            } else {
                if (contenedorObs) contenedorObs.style.display = "none";
                if (btnToggleObs) btnToggleObs.textContent = "Mostrar";
            }
            
            // Actualizar etiquetas
            calcularAntiguedad();
            calcularEstadoGarantia();
            
            // Limpiar y cargar componentes (solo RAM y Almacenamiento del sistema anterior)
            ['RAM', 'Almacenamiento'].forEach(tipo => {
                const contenedor = document.getElementById(`${tipo.toLowerCase()}Seleccionados`);
                if (contenedor) contenedor.innerHTML = '';
                componentesSeleccionados[tipo].clear();
            });
            
            // Cargar componentes desde los datos (excluir CPU ya que ahora se maneja por slots)
            cargarComponentes('RAM', this.dataset.rams);
            cargarComponentes('Almacenamiento', this.dataset.almacenamientos);
            
            // Resetear filtro de componentes
            tipoFiltroActual = 'todos';
            const btnToggle = document.getElementById('toggleTipoComponente');
            const estadoFiltro = document.getElementById('estadoFiltro');
            if (btnToggle) {
                btnToggle.textContent = 'Mostrar Todos';
                btnToggle.className = 'btn-toggle-tipo';
                btnToggle.setAttribute('data-tipo', 'todos');
            }
            if (estadoFiltro) {
                estadoFiltro.textContent = '(Genéricos y Detallados)';
            }
            
            // Cargar información de slots desde el servidor
            cargarSlotsExistentes(this.dataset.id);
            
            // IMPORTANTE: Configurar event listeners DESPUÉS de cargar slots
            setTimeout(() => {
                configurarEventListenersSlots();
                configurarToggleObservaciones();
            }, 300);
        });
    });

    // --- validación del formulario - CORREGIDA para múltiples CPUs ---
    if (form) {
        form.addEventListener("submit", function(event) {
            event.preventDefault();
            
            console.log("📝 === INICIANDO VALIDACIÓN DE FORMULARIO PC ===");
            
            // Validaciones básicas primero
            const nombreEquipo = document.getElementById("nombreEquipo");
            const modelo = document.getElementById("modelo");
            const serial = document.getElementById("numberSerial");
            const empresa = document.getElementById("id_empresa"); // NUEVO: Validar empresa
            
            if (!nombreEquipo.value.trim()) {
                alert("El nombre del equipo es obligatorio");
                nombreEquipo.focus();
                return false;
            }
            
            if (!modelo.value.trim()) {
                alert("El modelo es obligatorio");
                modelo.focus();
                return false;
            }
            
            if (!serial.value.trim()) {
                alert("El número de serie es obligatorio");
                serial.focus();
                return false;
            }
            
            // NUEVO: Validar empresa obligatoria
            if (!empresa.value || empresa.value === '') {
                alert("Debe seleccionar una empresa");
                empresa.focus();
                return false;
            }
            
            const datosSlots = recopilarDatosSlots();
            
            // Validar que hay slots configurados
            const cpuSlots = parseInt(document.getElementById('slots_cpu').value) || 0;
            const ramSlots = parseInt(document.getElementById('slots_ram').value) || 0;
            const almacenamientoSlots = parseInt(document.getElementById('slots_almacenamiento').value) || 0;
            
            if (cpuSlots === 0) {
                alert("Debe configurar al menos 1 slot de CPU");
                return false;
            }
            
            if (ramSlots === 0) {
                alert("Debe configurar al menos 1 slot de RAM");
                return false;
            }
            
            if (almacenamientoSlots === 0) {
                alert("Debe configurar al menos 1 slot de almacenamiento");
                return false;
            }
            
            // Validar asignación de componentes
            if (!datosSlots.cpu && datosSlots.cpus.length === 0) {
                alert("Debe asignar al menos un procesador (CPU) a un slot");
                return false;
            }
            
            if (datosSlots.rams.length === 0) {
                alert("Debe asignar al menos una memoria RAM a un slot");
                return false;
            }
            
            if (datosSlots.almacenamientos.length === 0) {
                alert("Debe asignar al menos un dispositivo de almacenamiento a un slot");
                return false;
            }
            
            // No validamos tarjetas de video obligatorias para PC, pueden ser 0
            
            console.log("📋 Validación PC pasada. Datos a enviar:", datosSlots);
            
            const slotsDataInput = document.getElementById('slotsDataHidden');
            if (slotsDataInput) {
                slotsDataInput.value = JSON.stringify(datosSlots);
            } else {
                alert("Error: No se pudo configurar los datos de slots");
                return false;
            }
            
            // Deshabilitar el botón de envío para evitar múltiples envíos
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';
            
            // Crear FormData
            const formData = new FormData(form);
            
            // Asegurar que los valores de slots se envíen para PC
            formData.set('slots_cpu', document.getElementById('slots_cpu').value);
            formData.set('slots_ram', document.getElementById('slots_ram').value);
            formData.set('slots_almacenamiento', document.getElementById('slots_almacenamiento').value);
            formData.set('slots_tarjeta_video', document.getElementById('slots_tarjeta_video').value);
            
            console.log("📤 === ENVIANDO DATOS PC AL SERVIDOR ===");
            console.log("📋 Configuración de slots PC:", {
                slots_cpu: document.getElementById('slots_cpu').value,
                slots_ram: document.getElementById('slots_ram').value,
                slots_almacenamiento: document.getElementById('slots_almacenamiento').value,
                slots_tarjeta_video: document.getElementById('slots_tarjeta_video').value
            });
            console.log("🎯 Componentes asignados PC:", datosSlots);
            
            // Enviar vía AJAX con headers apropiados
            fetch('../controllers/procesar_pc.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                console.log("📥 Respuesta del servidor PC recibida:");
                console.log("  - Status:", response.status);
                console.log("  - Status Text:", response.statusText);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                return response.text();
            })
            .then(text => {
                console.log("📄 Contenido de respuesta PC (primeros 500 caracteres):");
                console.log(text.substring(0, 500));
                
                if (text.trim() === '') {
                    throw new Error("El servidor devolvió una respuesta vacía");
                }
                
                try {
                    // Intentar parsear como JSON
                    const data = JSON.parse(text);
                    console.log("✅ Respuesta JSON PC parseada:", data);
                    
                    if (data.success) {
                        alert('✅ ' + (data.message || 'PC procesada exitosamente'));
                        modal.style.display = "none";
                        window.location.href = '../views/crud_pc.php?success=1';
                    } else {
                        alert('❌ Error: ' + (data.error || 'Error desconocido'));
                    }
                } catch (parseError) {
                    console.log("⚠️ La respuesta PC no es JSON válido, analizando como texto...");
                    console.log("Parse error:", parseError.message);
                    
                    // Buscar patrones de error en el texto
                    if (text.includes('❌ Error:')) {
                        const errorMatch = text.match(/❌ Error: (.+?)(?:\.|<|$)/);
                        const errorMessage = errorMatch ? errorMatch[1] : 'Error desconocido';
                        alert('❌ Error: ' + errorMessage);
                    } else if (text.includes('Error:')) {
                        const errorMatch = text.match(/Error: (.+?)(?:\.|<|$)/);
                        const errorMessage = errorMatch ? errorMatch[1] : 'Error desconocido';
                        alert('Error: ' + errorMessage);
                    } else if (text.includes('Fatal error:')) {
                        const errorMatch = text.match(/Fatal error: (.+?)(?:\n|<|$)/);
                        const errorMessage = errorMatch ? errorMatch[1] : 'Error fatal del servidor';
                        alert('Error fatal del servidor: ' + errorMessage);
                    } else if (text.includes('success=1') || text.includes('Location:')) {
                        // Redirección exitosa
                        console.log("✅ Redirección PC exitosa detectada");
                        // NUEVO: Mostrar alerta según la acción
                        const accion = document.getElementById("accion").value;
                        if (accion === "editar") {
                            alert('✅ PC editada exitosamente');
                        } else if (accion === "crear") {
                            alert('✅ PC creada exitosamente');
                        }
                        modal.style.display = "none";
                        window.location.href = '../views/crud_pc.php?success=1';
                    } else if (text.includes('<!DOCTYPE') || text.includes('<html')) {
                        // Respuesta HTML - posible error de PHP
                        console.error("Respuesta HTML PC recibida (posible error de PHP):", text);
                        alert("Error del servidor: El servidor devolvió una página HTML en lugar de la respuesta esperada. Esto sugiere un error de PHP.");
                    } else {
                        console.error('Respuesta inesperada del servidor PC:', text);
                        alert("Respuesta inesperada del servidor: El servidor devolvió una respuesta que no se pudo procesar.");
                    }
                }
            })
            .catch(error => {
                console.error('💥 Error en la petición PC:', error);
                alert('Error al comunicarse con el servidor: ' + error.message);
            })
            .finally(() => {
                // Rehabilitar el botón
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                console.log("🔄 Botón de envío PC restaurado");
            });
            
            return false;
        });
    }

    // --- Botones de generación de QR ---
    document.querySelectorAll('.btn-qr-generate, .btn-qr-regenerate').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const isRegenerate = this.classList.contains('btn-qr-regenerate');
            const idActivo = this.getAttribute('data-id') || this.dataset.id;
            
            if (!idActivo) {
                alert('No se pudo identificar el ID de la PC');
                return;
            }

            if (isRegenerate) {
                if (!confirm('¿Está seguro de que desea regenerar el código QR? Esto reemplazará el QR actual.')) {
                    return;
                }
            }

            this.disabled = true;
            const originalText = this.textContent;
            this.textContent = isRegenerate ? 'Regenerando...' : 'Generando...';

            fetch('../controllers/procesar_pc.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id_activo=${idActivo}&generar_qr=1`
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        alert(`QR ${isRegenerate ? 'regenerado' : 'generado'} correctamente`);
                        window.location.reload();
                    } else {
                        alert(`Error al ${isRegenerate ? 'regenerar' : 'generar'} el QR: ` + (data.error || 'Error desconocido'));
                    }
                } catch (parseError) {
                    alert('Error en la respuesta del servidor.');
                }
            })
            .catch(error => {
                alert('Error al comunicarse con el servidor: ' + error.message);
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = originalText;
            });
        });
    });

    // --- inicialización - CORREGIDA ---
    configurarToggleObservaciones();
    configurarFiltroTipoComponente();
    
    // Configurar event listeners iniciales
    configurarEventListenersSlots();
    
    window.addEventListener('load', function() {
        debugTabla();
        console.log("✅ Sistema de gestión de PCs cargado correctamente");
    });
    
    // --- funciones de filtro - ACTUALIZADAS para tarjeta de video ---
    function aplicarFiltroASlots() {
        console.log("🔄 === APLICANDO FILTRO A SLOTS PC ===");
        console.log("Filtro actual:", tipoFiltroActual);
        
        // GUARDAR valores actuales ANTES de aplicar cualquier cambio
        const valoresActuales = guardarValoresSlots();
        console.log("Valores guardados antes del filtro:", valoresActuales);
        
        // Aplicar filtro a todos los selects de slots existentes
        document.querySelectorAll('.slot-select').forEach(select => {
            const tipo = select.getAttribute('data-tipo');
            if (tipo) {
                llenarOpcionesSlotConFiltro(tipo, select);
            }
        });
        
        // RESTAURAR valores después de aplicar el filtro
        // Usar setTimeout para asegurar que las opciones se han cargado completamente
        setTimeout(() => {
            restaurarValoresSlots(valoresActuales);
            console.log("✅ === FILTRO PC APLICADO Y VALORES RESTAURADOS ===");
        }, 50);
    }

    function llenarOpcionesSlotConFiltro(tipo, selectElement) {
        let sourceSelect = null;
        
        switch(tipo) {
            case 'cpu':
                sourceSelect = document.getElementById('source-cpu');
                break;
            case 'ram':
                sourceSelect = document.getElementById('source-ram');
                break;
            case 'almacenamiento':
                sourceSelect = document.getElementById('source-almacenamiento');
                break;
            case 'tarjeta_video': // NUEVO
                sourceSelect = document.getElementById('source-tarjeta_video');
                break;
        }
        
        if (!sourceSelect) {
            console.error(`No se encontró el select fuente para ${tipo} en PC`);
            return;
        }
        
        // Guardar el valor actual del select antes de limpiar
        const valorActual = selectElement.value;
        const textoActual = selectElement.options[selectElement.selectedIndex]?.text || '';
        
        const todasLasOpciones = Array.from(sourceSelect.options).slice(1); // Excluir primera opción vacía
        let opcionesFiltradas = todasLasOpciones;
        
        if (tipoFiltroActual !== 'todos') {
            opcionesFiltradas = todasLasOpciones.filter(option => {
                const tipoOpcion = option.getAttribute('data-tipo');
                return tipoOpcion === tipoFiltroActual;
            });
        }
        
        // Limpiar y llenar el select manteniendo el valor si es posible
        selectElement.innerHTML = '<option value="">Libre</option>';
        
        opcionesFiltradas.forEach(option => {
            const newOption = option.cloneNode(true);
            selectElement.appendChild(newOption);
        });
        
        // Intentar restaurar el valor inmediatamente si está disponible
        if (valorActual && valorActual !== '') {
            const optionExists = Array.from(selectElement.options).some(option => option.value === valorActual);
            if (optionExists) {
                selectElement.value = valorActual;
                console.log(`🔄 Valor preservado inmediatamente en ${tipo} PC:`, valorActual);
            } else {
                console.log(`⚠️ Valor ${valorActual} no disponible en filtro ${tipoFiltroActual} para ${tipo} PC`);
            }
        }
        
        console.log(`📋 Opciones filtradas para ${tipo} PC:`, {
            total: todasLasOpciones.length,
            filtradas: opcionesFiltradas.length,
            filtro: tipoFiltroActual,
            valorActual: valorActual,
            valorRestaurado: selectElement.value
        });
    }

    function actualizarVistaSlots() {
        console.log("🔄 === ACTUALIZANDO VISTA DE SLOTS PC ===");
        
        // GUARDAR valores actuales antes de regenerar
        const valoresActuales = guardarValoresSlots();
        
        const cpuSlots = parseInt(document.getElementById('slots_cpu').value) || 1;
        const ramSlots = parseInt(document.getElementById('slots_ram').value) || 4; // PC default
        const almacenamientoSlots = parseInt(document.getElementById('slots_almacenamiento').value) || 2; // PC default
        const tarjeta_videoSlots = parseInt(document.getElementById('slots_tarjeta_video').value) || 0; // CORREGIDO: default 0
        
        console.log("📊 Nueva configuración de slots PC:", { 
            cpuSlots, 
            ramSlots, 
            almacenamientoSlots, 
            tarjeta_videoSlots
        });
        
        slotsData.cpu = cpuSlots;
        slotsData.ram = ramSlots;
        slotsData.almacenamiento = almacenamientoSlots;
        slotsData.tarjeta_video = tarjeta_videoSlots;
        
        const container = document.getElementById('slots-container');
        if (container) {
            container.style.display = 'block';
            
            // Regenerar slots HTML - ahora maneja correctamente cantidad 0
            generarSlotsHTML('cpu', cpuSlots);
            generarSlotsHTML('ram', ramSlots);
            generarSlotsHTML('almacenamiento', almacenamientoSlots);
            generarSlotsHTML('tarjeta_video', tarjeta_videoSlots);
        }
        
        // RESTAURAR valores después de regenerar
        setTimeout(() => {
            console.log("🔄 Restaurando valores PC después de actualizar vista...");
            restaurarValoresSlots(valoresActuales);
            console.log("✅ === VISTA DE SLOTS PC ACTUALIZADA Y VALORES RESTAURADOS ===");
        }, 100);
    }

    // --- cálculo antigüedad ---
    function calcularAntiguedad() {
        if (!fechaCompraInput || !antiguedadInput) return;
        const fechaCompra = new Date(fechaCompraInput.value);
        const hoy = new Date();

        if (isNaN(fechaCompra)) {
            antiguedadInput.value = "";
            safeSetText(labelAntiguedad, "(No calculado)");
            return;
        }

        const ms = hoy - fechaCompra;
        const dias = Math.floor(ms / (1000 * 60 * 60 * 24));
        antiguedadInput.value = dias;

        const años = Math.floor(dias / 365);
        const restoDias = dias % 365;
        const meses = Math.floor(restoDias / 30);
        const diasFinales = restoDias % 30;

        const partes = [];
        if (años > 0) partes.push(`${años} ${años === 1 ? "año" : "años"}`);
        if (meses > 0) partes.push(`${meses} ${meses === 1 ? "mes" : "meses"}`);
        if (diasFinales > 0 || partes.length === 0) partes.push(`${diasFinales} ${diasFinales === 1 ? "día" : "días"}`);

        safeSetText(labelAntiguedad, `(${partes.join(", ")})`);
    }

    // --- cálculo estado garantía ---
    function calcularEstadoGarantia() {
        if (!garantiaInput || !estadoGarantiaInput) return;
        const hoy = new Date().toISOString().split("T")[0];
        const garantia = garantiaInput.value;

        if (garantia) {
            if (garantia >= hoy) {
            }
        } else {
            estadoGarantiaInput.value = "Sin garantía";
            safeSetText(labelGarantia, "(Sin garantía)");
        }
    }

    // --- listeners seguros ---
    if (fechaCompraInput) fechaCompraInput.addEventListener("change", calcularAntiguedad);
    if (garantiaInput) garantiaInput.addEventListener("change", calcularEstadoGarantia);
});
