/*
 * hardware.js - Gestión de cambios de hardware para reparaciones
 * Solo permite usar componentes existentes
 * Versión limpia corregida
 */

console.log('✅ hardware.js cargando...');

// Variables globales para el filtro de componentes
let tipoFiltroComponentesActual = 'todos'; // 'todos', 'generico', 'detallado'
console.log('🔧 Variable de filtro inicializada:', tipoFiltroComponentesActual);

function mostrarFormCambioHardware() {
    console.log('Mostrando formulario de cambio de hardware');
    const formSection = document.getElementById('formCambioHardware');
    if (formSection) {
        formSection.style.display = 'block';
        
        // Limpiar el formulario
        const form = document.getElementById('formCambio');
        if (form) {
            form.reset();
        }
        
        // Ocultar sección de componentes al inicio
        const seccionExistente = document.getElementById('seccionComponenteExistente');
        if (seccionExistente) {
            seccionExistente.style.display = 'none';
        }
        
        // Ocultar sección de componente actual al inicio
        const componenteActualDiv = document.getElementById('componenteActualDiv');
        if (componenteActualDiv) {
            componenteActualDiv.style.display = 'none';
        }
        
        // Cargar tipos de cambio si no están cargados
        cargarTiposCambioFormulario();
    }
}

// Función para cargar tipos de cambio en el formulario
async function cargarTiposCambioFormulario() {
    const select = document.getElementById('idTipoCambio');
    
    if (select && select.options.length <= 1) { // Solo cargar si está vacío
        console.log('📡 Cargando tipos de cambio en formulario...');
        try {
            const response = await fetch('../controllers/procesar_reparacion.php?action=get_tipos_cambio');
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const tipos = await response.json();
            console.log('📋 Tipos recibidos:', tipos);
            
            if (Array.isArray(tipos)) {
                tipos.forEach(tipo => {
                    const option = document.createElement('option');
                    option.value = tipo.id_tipo_cambio;
                    option.textContent = tipo.nombre_tipo_cambio;
                    select.appendChild(option);
                });
                console.log(`✅ ${tipos.length} tipos de cambio cargados en formulario`);
            }
        } catch (error) {
            console.error('❌ Error cargando tipos de cambio:', error);
        }
    }
}

// Función para configurar el filtro de tipos de componentes
function configurarFiltroTipoComponenteReparacion() {
    const btnToggle = document.getElementById('toggleTipoComponenteReparacion');
    const estadoFiltro = document.getElementById('estadoFiltroReparacion');
    
    if (!btnToggle || !estadoFiltro) {
        console.warn('Elementos de filtro de reparaciones no encontrados');
        return;
    }
    
    console.log('✅ Configurando filtro de componentes');
    
    btnToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log("=== CLICK EN FILTRO ===");
        console.log("Filtro actual ANTES:", tipoFiltroComponentesActual);
        
        // Cambiar el filtro en secuencia
        if (tipoFiltroComponentesActual === 'todos') {
            tipoFiltroComponentesActual = 'generico';
            btnToggle.textContent = 'Solo Genéricos';
            btnToggle.className = 'btn-toggle-tipo filtro-generico';
            estadoFiltro.textContent = '(Solo componentes genéricos)';
            
        } else if (tipoFiltroComponentesActual === 'generico') {
            tipoFiltroComponentesActual = 'detallado';
            btnToggle.textContent = 'Solo Detallados';
            btnToggle.className = 'btn-toggle-tipo filtro-detallado';
            estadoFiltro.textContent = '(Solo componentes detallados)';
            
        } else if (tipoFiltroComponentesActual === 'detallado') {
            tipoFiltroComponentesActual = 'todos';
            btnToggle.textContent = 'Mostrar Todos';
            btnToggle.className = 'btn-toggle-tipo';
            estadoFiltro.textContent = '(Genéricos y Detallados)';
        }
        
        console.log("Filtro actual DESPUÉS:", tipoFiltroComponentesActual);
        
        // APLICAR EL FILTRO
        aplicarFiltroASelectComponentes();
    });
}

// Función específica para aplicar el filtro
function aplicarFiltroASelectComponentes() {
    const selectComponente = document.getElementById('idComponenteExistente');
    const tipoComponente = document.getElementById('tipoComponente')?.value;
    
    if (!selectComponente || !tipoComponente) {
        console.warn('No se puede aplicar filtro: faltan elementos');
        return;
    }
    
    console.log(`🔍 Aplicando filtro: ${tipoFiltroComponentesActual}`);
    
    // Obtener datos del servidor
    fetch(`../controllers/procesar_reparacion.php?action=get_componentes&tipo=${tipoComponente}`)
        .then(response => response.json())
        .then(componentes => {
            console.log(`📦 Total componentes recibidos: ${componentes.length}`);
            
            // Limpiar select
            selectComponente.innerHTML = '<option value="">Seleccionar componente...</option>';
            
            // Filtrar según el tipo seleccionado
            let componentesMostrar = componentes;
            
            if (tipoFiltroComponentesActual !== 'todos') {
                componentesMostrar = componentes.filter(comp => comp.tipo === tipoFiltroComponentesActual);
            }
            
            console.log(`🔍 Componentes después del filtro: ${componentesMostrar.length}`);
            
            if (componentesMostrar.length === 0) {
                const optionVacio = document.createElement('option');
                optionVacio.value = '';
                optionVacio.textContent = `Sin componentes ${tipoFiltroComponentesActual}s disponibles`;
                optionVacio.disabled = true;
                selectComponente.appendChild(optionVacio);
                return;
            }
            
            // Agregar componentes filtrados
            componentesMostrar.forEach(comp => {
                const option = document.createElement('option');
                option.value = `${comp.tipo}_${comp.id}`;
                option.textContent = `${comp.descripcion} (${comp.tipo === 'generico' ? 'Genérico' : 'Detallado'})`;
                selectComponente.appendChild(option);
            });
            
            console.log(`✅ Select poblado con ${componentesMostrar.length} componentes`);
        })
        .catch(error => {
            console.error('❌ Error aplicando filtro:', error);
            selectComponente.innerHTML = '<option value="">Error cargando componentes</option>';
        });
}

// Función para manejar cambios en el tipo de componente
function manejarCambioTipoComponente() {
    const tipoComponente = document.getElementById('tipoComponente')?.value;
    
    console.log('=== CAMBIO EN TIPO DE COMPONENTE ===');
    console.log('Tipo de componente seleccionado:', tipoComponente);
    
    // Reset del filtro cuando cambia el tipo de componente
    tipoFiltroComponentesActual = 'todos';
    const btnToggle = document.getElementById('toggleTipoComponenteReparacion');
    const estadoFiltro = document.getElementById('estadoFiltroReparacion');
    if (btnToggle && estadoFiltro) {
        btnToggle.textContent = 'Mostrar Todos';
        btnToggle.className = 'btn-toggle-tipo';
        estadoFiltro.textContent = '(Genéricos y Detallados)';
    }
    
    // Mostrar sección de componentes existentes si hay tipo seleccionado
    const seccionExistente = document.getElementById('seccionComponenteExistente');
    const tipoCambio = document.getElementById('idTipoCambio')?.value;
    
    if (seccionExistente) {
        // Solo mostrar sección de componentes si NO es "Retirar" (tipo 3)
        if (tipoComponente && tipoCambio !== '3') {
            seccionExistente.style.display = 'block';
            configurarFiltroTipoComponenteReparacion();
            cargarComponentesNuevos();
            console.log('✅ Mostrando sección de componentes (no es Retirar)');
        } else {
            seccionExistente.style.display = 'none';
            if (tipoCambio === '3') {
                console.log('✅ Ocultando sección de componentes (es Retirar)');
            }
        }
    }
    
    // También cargar componentes actuales si se seleccionó tipo de cambio
    if (tipoCambio && tipoComponente) {
        console.log('🔄 Cargando componentes/slots para tipo de cambio:', tipoCambio);
        cargarComponentesActuales();
    }
    
    console.log('=== FIN CAMBIO TIPO COMPONENTE ===');
}

// Función para manejar cambio de tipo de cambio
function manejarCambioTipoCambio() {
    const tipoCambio = document.getElementById('idTipoCambio')?.value;
    const componenteActualDiv = document.getElementById('componenteActualDiv');
    const seccionComponenteExistente = document.getElementById('seccionComponenteExistente');
    const costoCambioDiv = document.querySelector('#costoCambio')?.closest('.form-group');
    
    console.log('=== CAMBIO EN TIPO DE CAMBIO ===');
    console.log('Tipo de cambio seleccionado:', tipoCambio);
    
    if (componenteActualDiv) {
        // Para todos los tipos de cambio mostrar la sección, pero cambiar el label
        if (tipoCambio === '1' || tipoCambio === '2' || tipoCambio === '3') {
            componenteActualDiv.style.display = 'block';
            
            // Cambiar el label según el tipo de cambio
            const label = componenteActualDiv.querySelector('label');
            if (label) {
                if (tipoCambio === '1') {
                    label.textContent = 'Componente a Reemplazar:';
                    console.log('✅ Mostrando sección: Componente a Reemplazar');
                } else if (tipoCambio === '2') {
                    label.textContent = 'Slot Disponible:';
                    console.log('✅ Mostrando sección: Slot Disponible');
                } else if (tipoCambio === '3') {
                    label.textContent = 'Componente a Retirar:';
                    console.log('✅ Mostrando sección: Componente a Retirar');
                }
            }
            
            // Cargar datos apropiados si ya se seleccionó tipo de componente
            const tipoComponente = document.getElementById('tipoComponente')?.value;
            if (tipoComponente) {
                cargarComponentesActuales();
            }
        } else {
            // Sin tipo de cambio seleccionado, ocultar
            componenteActualDiv.style.display = 'none';
            console.log('✅ Ocultando sección (sin tipo de cambio)');
        }
    }
    
    // Manejar visibilidad de secciones según el tipo de cambio
    if (tipoCambio === '3') {
        // Para RETIRAR: ocultar solo la sección de componentes nuevos
        if (seccionComponenteExistente) {
            seccionComponenteExistente.style.display = 'none';
            console.log('✅ Ocultando sección de componentes para Retirar');
        }
        // NO ocultar el costo - puede haber costo de servicio de retiro
        console.log('✅ Manteniendo campo de costo para Retirar (puede haber costo de servicio)');
    } else if (tipoCambio === '1' || tipoCambio === '2') {
        // Para REEMPLAZO/INSTALACIÓN: mostrar todas las secciones necesarias
        if (costoCambioDiv) {
            costoCambioDiv.style.display = 'block';
            console.log('✅ Mostrando campo de costo para Reemplazo/Instalación');
        }
        // La sección de componentes se maneja en manejarCambioTipoComponente()
    }
}

// Función para cargar componentes actuales del activo
async function cargarComponentesActuales() {
    const tipoComponente = document.getElementById('tipoComponente')?.value;
    const idActivo = document.getElementById('idActivoCambio')?.value;
    const tipoCambio = document.getElementById('idTipoCambio')?.value;
    const selectComponenteActual = document.getElementById('componenteActual');
    
    console.log('=== CARGANDO COMPONENTES ACTUALES ===');
    console.log('Tipo componente:', tipoComponente);
    console.log('ID activo:', idActivo);
    console.log('Tipo cambio:', tipoCambio);
    
    if (!tipoComponente || !idActivo || !selectComponenteActual) {
        console.warn('Faltan datos para cargar componentes actuales');
        return;
    }
    
    try {
        let url = '';
        let labelTexto = '';
        
        // Determinar qué endpoint usar según el tipo de cambio
        if (tipoCambio === '2') {
            // Para Instalación/Adición: mostrar slots disponibles
            url = `../controllers/procesar_reparacion.php?action=get_slots_disponibles&id_activo=${idActivo}&tipo=${tipoComponente}`;
            labelTexto = 'Seleccionar slot disponible...';
            console.log('🔍 Cargando slots disponibles para instalación');
        } else {
            // Para Reemplazo/Retiro: mostrar componentes ocupados
            url = `../controllers/procesar_reparacion.php?action=get_componentes_activo&id_activo=${idActivo}&tipo=${tipoComponente}`;
            labelTexto = 'Seleccionar componente actual...';
            console.log('🔍 Cargando componentes ocupados para reemplazo/retiro');
        }
        
        const response = await fetch(url);
        const componentes = await response.json();
        
        console.log('📊 Respuesta del servidor:', componentes);
        
        selectComponenteActual.innerHTML = `<option value="">${labelTexto}</option>`;
        
        if (Array.isArray(componentes)) {
            if (tipoCambio === '2') {
                // Para instalación: mostrar slots disponibles
                const slotsDisponibles = componentes.filter(slot => slot.estado === 'disponible');
                
                if (slotsDisponibles.length > 0) {
                    slotsDisponibles.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot.id_slot;
                        option.textContent = `Slot ${slot.id_slot} (Disponible)`;
                        selectComponenteActual.appendChild(option);
                    });
                    console.log(`✅ ${slotsDisponibles.length} slots disponibles cargados`);
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No hay slots disponibles para este tipo de componente';
                    option.disabled = true;
                    selectComponenteActual.appendChild(option);
                    console.log('⚠️ No hay slots disponibles');
                }
            } else {
                // Para reemplazo/retiro: mostrar componentes ocupados
                const componentesOcupados = componentes.filter(comp => comp.estado === 'ocupado');
                
                if (componentesOcupados.length > 0) {
                    componentesOcupados.forEach(comp => {
                        const option = document.createElement('option');
                        option.value = comp.id_slot;
                        option.textContent = comp.descripcion;
                        selectComponenteActual.appendChild(option);
                    });
                    console.log(`✅ ${componentesOcupados.length} componentes ocupados cargados`);
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No hay componentes de este tipo instalados';
                    option.disabled = true;
                    selectComponenteActual.appendChild(option);
                    console.log('⚠️ No hay componentes ocupados de este tipo');
                }
            }
        } else {
            console.error('Respuesta inválida del servidor:', componentes);
        }
        
    } catch (error) {
        console.error('Error cargando componentes actuales:', error);
        selectComponenteActual.innerHTML = '<option value="">Error de conexión</option>';
    }
}

// Función para cargar componentes nuevos
async function cargarComponentesNuevos() {
    const tipoComponente = document.getElementById('tipoComponente')?.value;
    const selectComponente = document.getElementById('idComponenteExistente');
    
    if (!tipoComponente || !selectComponente) {
        if (selectComponente) {
            selectComponente.innerHTML = '<option value="">Seleccionar componente...</option>';
        }
        return;
    }
    
    try {
        const response = await fetch(`../controllers/procesar_reparacion.php?action=get_componentes&tipo=${tipoComponente}`);
        const componentes = await response.json();
        
        if (Array.isArray(componentes)) {
            aplicarFiltroASelectComponentes();
        } else {
            selectComponente.innerHTML = '<option value="">Sin componentes disponibles</option>';
        }
    } catch (error) {
        console.error('Error cargando componentes nuevos:', error);
        selectComponente.innerHTML = '<option value="">Error de conexión</option>';
    }
}

// Event listeners para los formularios
document.addEventListener('DOMContentLoaded', function() {
    // Event listener para tipo de cambio
    const tipoCambioSelect = document.getElementById('idTipoCambio');
    if (tipoCambioSelect) {
        tipoCambioSelect.addEventListener('change', manejarCambioTipoCambio);
        console.log('✅ Event listener configurado para tipo de cambio');
    }
    
    // Event listener para tipo de componente
    const tipoComponenteSelect = document.getElementById('tipoComponente');
    if (tipoComponenteSelect) {
        tipoComponenteSelect.addEventListener('change', manejarCambioTipoComponente);
        console.log('✅ Event listener configurado para tipo de componente');
    }
});

console.log('✅ hardware.js cargado correctamente');