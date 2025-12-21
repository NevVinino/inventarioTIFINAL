/*
 * utils.js - Funciones de utilidad para el sistema de reparaciones
 * Versión sin exports para scripts tradicionales
 */

// Función para mostrar mensajes al usuario
function mostrarMensaje(mensaje, tipo = 'info') {
    // Crear elemento de mensaje si no existe
    let contenedorMensajes = document.getElementById('contenedor-mensajes');
    if (!contenedorMensajes) {
        contenedorMensajes = document.createElement('div');
        contenedorMensajes.id = 'contenedor-mensajes';
        contenedorMensajes.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
        `;
        document.body.appendChild(contenedorMensajes);
    }
    
    // Crear mensaje
    const elementoMensaje = document.createElement('div');
    elementoMensaje.style.cssText = `
        margin-bottom: 10px;
        padding: 12px 16px;
        border-radius: 4px;
        color: white;
        font-weight: bold;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    // Aplicar estilos según el tipo
    switch (tipo) {
        case 'success':
            elementoMensaje.style.backgroundColor = '#4CAF50';
            break;
        case 'error':
            elementoMensaje.style.backgroundColor = '#f44336';
            break;
        case 'warning':
            elementoMensaje.style.backgroundColor = '#ff9800';
            break;
        default:
            elementoMensaje.style.backgroundColor = '#2196F3';
    }
    
    elementoMensaje.textContent = mensaje;
    contenedorMensajes.appendChild(elementoMensaje);
    
    // Mostrar mensaje
    setTimeout(() => {
        elementoMensaje.style.opacity = '1';
    }, 100);
    
    // Ocultar mensaje después de 5 segundos
    setTimeout(() => {
        elementoMensaje.style.opacity = '0';
        setTimeout(() => {
            if (elementoMensaje.parentNode) {
                elementoMensaje.parentNode.removeChild(elementoMensaje);
            }
        }, 300);
    }, 5000);
}

// Validación del formulario de cambio de hardware
function validarFormularioCambioHardware() {
    const tipoNuevo = document.getElementById('tipoNuevoComponente')?.value;
    
    if (!tipoNuevo) {
        mostrarMensaje('Debe seleccionar el tipo de nuevo componente', 'error');
        return false;
    }
    
    if (tipoNuevo === 'existente') {
        const componenteExistente = document.getElementById('idComponenteExistente')?.value;
        if (!componenteExistente) {
            mostrarMensaje('Debe seleccionar un componente existente', 'error');
            return false;
        }
    } else {
        mostrarMensaje('Solo se permite usar componentes existentes', 'error');
        return false;
    }
    
    // Validar campos obligatorios
    const tipoComponente = document.getElementById('tipoComponente')?.value;
    const idTipoCambio = document.getElementById('idTipoCambio')?.value;
    
    if (!tipoComponente) {
        mostrarMensaje('Debe seleccionar el tipo de componente', 'error');
        return false;
    }
    
    if (!idTipoCambio) {
        mostrarMensaje('Debe seleccionar el tipo de cambio', 'error');
        return false;
    }
    
    return true;
}

// Función para formatear fecha
function formatearFecha(fecha) {
    if (!fecha) return '';
    
    if (fecha instanceof Date) {
        return fecha.toLocaleDateString('es-ES');
    }
    
    if (typeof fecha === 'string') {
        const fechaObj = new Date(fecha);
        return fechaObj.toLocaleDateString('es-ES');
    }
    
    return fecha.toString();
}

// Función para formatear moneda
function formatearMoneda(cantidad) {
    if (cantidad === null || cantidad === undefined || cantidad === '') {
        return '-';
    }
    
    const numero = parseFloat(cantidad);
    if (isNaN(numero)) {
        return '-';
    }
    
    return `$ ${numero.toFixed(2)}`;
}

// Función para limpiar formulario
function limpiarFormulario(idFormulario) {
    const formulario = document.getElementById(idFormulario);
    if (formulario) {
        formulario.reset();
        
        // Limpiar selects específicos
        const selects = formulario.querySelectorAll('select');
        selects.forEach(select => {
            if (select.id === 'idComponenteExistente') {
                select.innerHTML = '<option value="">Seleccionar componente...</option>';
            }
        });
    }
}

// Función para mostrar detalles de reparación
function mostrarDetallesReparacion(boton) {
    console.log('Mostrando detalles de reparación');
    
    // Obtener datos del botón
    const idReparacion = boton.dataset.idReparacion;
    const fecha = boton.dataset.fecha;
    const nombreEstado = boton.dataset.nombreEstado;
    const nombreLugar = boton.dataset.nombreLugar;
    const costo = boton.dataset.costo;
    const tiempoInactividad = boton.dataset.tiempoInactividad;
    const nombreEquipo = boton.dataset.nombreEquipo;
    const tipoActivo = boton.dataset.tipoActivo;
    const idActivo = boton.dataset.idActivo;
    const personaAsignada = boton.dataset.personaAsignada;
    const descripcion = boton.dataset.descripcion;
    
    // Llenar modal de visualización
    document.getElementById('view-id-reparacion').textContent = idReparacion;
    document.getElementById('view-fecha').textContent = fecha;
    document.getElementById('view-estado').textContent = nombreEstado;
    document.getElementById('view-lugar').textContent = nombreLugar;
    document.getElementById('view-costo').textContent = costo ? formatearMoneda(costo) : '-';
    document.getElementById('view-tiempo-inactividad').textContent = tiempoInactividad ? `${tiempoInactividad} días` : '-';
    document.getElementById('view-nombre-equipo').textContent = nombreEquipo;
    document.getElementById('view-tipo-activo').textContent = tipoActivo;
    document.getElementById('view-id-activo').textContent = idActivo;
    document.getElementById('view-persona-asignada').textContent = personaAsignada;
    document.getElementById('view-descripcion').textContent = descripcion || 'Sin descripción';
    
    // Mostrar modal
    const modal = document.getElementById('modalVisualizacion');
    if (modal) {
        modal.style.display = 'block';
    }
}

// Función para editar reparación
function editarReparacion(boton) {
    console.log('Editando reparación');
    
    // Obtener datos del botón
    const id = boton.dataset.id;
    const idActivo = boton.dataset.idActivo;
    const idLugar = boton.dataset.idLugar;
    const idEstado = boton.dataset.idEstado;
    const fecha = boton.dataset.fecha;
    const descripcion = boton.dataset.descripcion;
    const costo = boton.dataset.costo;
    const tiempo = boton.dataset.tiempo;
    
    // Configurar modal para edición
    document.getElementById('modal-title').textContent = 'Editar Reparación';
    document.getElementById('accion').value = 'editar';
    document.getElementById('id_reparacion').value = id;
    
    // Llenar formulario
    document.getElementById('id_activo').value = idActivo;
    document.getElementById('id_lugar_reparacion').value = idLugar;
    document.getElementById('id_estado_reparacion').value = idEstado;
    document.getElementById('fecha').value = fecha;
    document.getElementById('descripcion').value = descripcion || '';
    document.getElementById('costo').value = costo || '';
    document.getElementById('tiempo_inactividad').value = tiempo || '';
    
    // Mostrar modal
    const modal = document.getElementById('modalReparacion');
    if (modal) {
        modal.style.display = 'block';
    }
}

// Función para nueva reparación
function nuevaReparacion() {
    console.log('Nueva reparación');
    
    // Configurar modal para creación
    document.getElementById('modal-title').textContent = 'Nueva Reparación';
    document.getElementById('accion').value = 'crear';
    document.getElementById('id_reparacion').value = '';
    
    // Limpiar formulario
    document.getElementById('formReparacion').reset();
    
    // Mostrar modal
    const modal = document.getElementById('modalReparacion');
    if (modal) {
        modal.style.display = 'block';
    }
}

// Función para abrir modal de cambios de hardware
function abrirModalCambiosHardware(boton) {
    console.log('Abriendo modal de cambios de hardware');
    
    const idReparacion = boton.dataset.id;
    const idActivo = boton.dataset.activo;
    
    // Configurar modal
    document.getElementById('numReparacion').textContent = idReparacion;
    document.getElementById('idReparacionCambio').value = idReparacion;
    document.getElementById('idActivoCambio').value = idActivo;
    
    // Limpiar tabla de cambios
    const tbody = document.querySelector('#tablaCambiosHardware tbody');
    if (tbody) {
        tbody.innerHTML = '';
    }
    
    // Ocultar formulario de cambio
    const formCambio = document.getElementById('formCambioHardware');
    if (formCambio) {
        formCambio.style.display = 'none';
    }
    
    // Mostrar modal
    const modal = document.getElementById('modalCambiosHardware');
    if (modal) {
        modal.style.display = 'block';
        
        // Cargar datos necesarios
        cargarCambiosHardware(idReparacion);
        cargarTiposCambio(); // NUEVO: Cargar tipos de cambio
        cargarLugaresReparacion(); // Cargar lugares
        cargarEstadosReparacion(); // Cargar estados
    }
}

// NUEVA: Función para cargar tipos de cambio
async function cargarTiposCambio() {
    console.log('🔄 Iniciando carga de tipos de cambio...');
    
    try {
        const url = '../controllers/procesar_reparacion.php?action=get_tipos_cambio';
        console.log('📡 Haciendo petición a:', url);
        
        const response = await fetch(url);
        console.log('📊 Response status:', response.status);
        console.log('📊 Response ok:', response.ok);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const textResponse = await response.text();
        console.log('📄 Raw response:', textResponse);
        
        let tipos;
        try {
            tipos = JSON.parse(textResponse);
        } catch (parseError) {
            console.error('❌ Error parsing JSON:', parseError);
            console.error('📄 Raw text was:', textResponse);
            throw new Error('Respuesta no es JSON válido');
        }
        
        console.log('📋 Tipos de cambio recibidos:', tipos);
        
        const select = document.getElementById('idTipoCambio');
        if (select) {
            select.innerHTML = '<option value="">Seleccionar tipo...</option>';
            
            if (Array.isArray(tipos)) {
                tipos.forEach(tipo => {
                    const option = document.createElement('option');
                    option.value = tipo.id_tipo_cambio;
                    option.textContent = tipo.nombre_tipo_cambio;
                    select.appendChild(option);
                });
                console.log(`✅ ${tipos.length} tipos de cambio cargados exitosamente`);
            } else if (tipos.error) {
                console.error('❌ Error del servidor:', tipos.error);
                mostrarMensaje('Error del servidor: ' + tipos.error, 'error');
            } else {
                console.error('❌ Respuesta inesperada:', tipos);
                mostrarMensaje('Respuesta inesperada del servidor', 'error');
            }
        } else {
            console.error('❌ No se encontró el select idTipoCambio');
            mostrarMensaje('Error: select de tipo de cambio no encontrado', 'error');
        }
    } catch (error) {
        console.error('❌ Error completo cargando tipos de cambio:', error);
        mostrarMensaje('Error cargando tipos de cambio: ' + error.message, 'error');
    }
}

// NUEVA: Función para cargar lugares de reparación
async function cargarLugaresReparacion() {
    try {
        const response = await fetch('../controllers/procesar_reparacion.php?action=get_lugares');
        const lugares = await response.json();
        
        const select = document.getElementById('id_lugar_reparacion');
        if (select) {
            // Mantener la opción actual si existe
            const valorActual = select.value;
            select.innerHTML = '<option value="">Seleccionar lugar...</option>';
            
            if (Array.isArray(lugares)) {
                lugares.forEach(lugar => {
                    const option = document.createElement('option');
                    option.value = lugar.id_lugar;
                    option.textContent = lugar.nombre_lugar;
                    if (lugar.id_lugar == valorActual) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error cargando lugares:', error);
    }
}

// NUEVA: Función para cargar estados de reparación
async function cargarEstadosReparacion() {
    try {
        const response = await fetch('../controllers/procesar_reparacion.php?action=get_estados');
        const estados = await response.json();
        
        const select = document.getElementById('id_estado_reparacion');
        if (select) {
            // Mantener la opción actual si existe
            const valorActual = select.value;
            select.innerHTML = '<option value="">Seleccionar estado...</option>';
            
            if (Array.isArray(estados)) {
                estados.forEach(estado => {
                    const option = document.createElement('option');
                    option.value = estado.id_estado_reparacion;
                    option.textContent = estado.nombre_estado;
                    if (estado.id_estado_reparacion == valorActual) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error cargando estados:', error);
    }
}

// Función para cargar cambios de hardware existentes
async function cargarCambiosHardware(idReparacion) {
    try {
        const response = await fetch(`../controllers/procesar_reparacion.php?action=get_cambios_hardware&id_reparacion=${idReparacion}`);
        const cambios = await response.json();
        
        const tbody = document.querySelector('#tablaCambiosHardware tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (Array.isArray(cambios) && cambios.length > 0) {
            cambios.forEach(cambio => {
                const fila = document.createElement('tr');
                fila.innerHTML = `
                    <td>${cambio.nombre_tipo_cambio || 'N/A'}</td>
                    <td>${cambio.tipo_componente || 'N/A'}</td>
                    <td>${cambio.componente_nuevo || 'N/A'}</td>
                    <td>${cambio.componente_retirado || 'N/A'}</td>
                    <td>${cambio.costo ? formatearMoneda(cambio.costo) : '-'}</td>
                    <td>${cambio.motivo || '-'}</td>
                    <td>
                        <button type="button" class="btn-icon btn-eliminar" 
                                onclick="eliminarCambioHardware(${cambio.id_cambio_hardware})"
                                title="Eliminar cambio">
                            <img src="../../img/eliminar.png" alt="Eliminar">
                        </button>
                    </td>
                `;
                tbody.appendChild(fila);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No hay cambios de hardware registrados</td></tr>';
        }
        
    } catch (error) {
        console.error('Error cargando cambios de hardware:', error);
        mostrarMensaje('Error cargando cambios de hardware', 'error');
    }
}

// Función para eliminar cambio de hardware
async function eliminarCambioHardware(idCambio) {
    if (!confirm('¿Está seguro de eliminar este cambio de hardware?')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('accion', 'eliminar_cambio_hardware');
        formData.append('id_cambio_hardware', idCambio);
        
        const response = await fetch('../controllers/procesar_reparacion.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarMensaje('Cambio de hardware eliminado correctamente', 'success');
            // Recargar la tabla
            const idReparacion = document.getElementById('idReparacionCambio').value;
            if (idReparacion) {
                cargarCambiosHardware(idReparacion);
            }
        } else {
            mostrarMensaje(result.error || 'Error eliminando cambio', 'error');
        }
        
    } catch (error) {
        console.error('Error eliminando cambio de hardware:', error);
        mostrarMensaje('Error de conexión', 'error');
    }
}

// === UTILIDADES PARA MODALES ===
// Funciones auxiliares para manejo de modales en reparaciones

// Función para cerrar modal específico
function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "none";
        if (modal.classList.contains('show')) {
            modal.classList.remove('show');
        }
    }
}

// Función para abrir modal específico
function abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "block";
    }
}

// Configurar eventos de cierre para botones X
function configurarBotonesCerrar() {
    document.querySelectorAll('.close, .btn-close, [data-dismiss="modal"]').forEach(closeBtn => {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Buscar el modal padre más cercano
            const modal = this.closest('.modal') || 
                         this.closest('[id*="modal"]') ||
                         document.querySelector('.modal[style*="block"]');
            if (modal) {
                modal.style.display = "none";
                if (modal.classList.contains('show')) {
                    modal.classList.remove('show');
                }
            }
        });
    });
}

// Ejecutar configuración al cargar
if (typeof window !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', configurarBotonesCerrar);
    } else {
        configurarBotonesCerrar();
    }
}

console.log('✅ utils.js cargado correctamente');
console.log('📋 Funciones disponibles:', {
    mostrarMensaje: typeof mostrarMensaje,
    mostrarDetallesReparacion: typeof mostrarDetallesReparacion,
    editarReparacion: typeof editarReparacion,
    abrirModalCambiosHardware: typeof abrirModalCambiosHardware,
    nuevaReparacion: typeof nuevaReparacion,
    cargarTiposCambio: typeof cargarTiposCambio,
    cargarLugaresReparacion: typeof cargarLugaresReparacion,
    cargarEstadosReparacion: typeof cargarEstadosReparacion
});

// Verificar funciones de hardware cuando estén disponibles
setTimeout(() => {
    console.log('🔧 Funciones de hardware disponibles:', {
        manejarTipoNuevoComponente: typeof manejarTipoNuevoComponente,
        cargarComponentesNuevos: typeof cargarComponentesNuevos,
        manejarCambioTipoComponente: typeof manejarCambioTipoComponente,
        cargarComponentesActuales: typeof cargarComponentesActuales,
        configurarFiltroTipoComponenteReparacion: typeof configurarFiltroTipoComponenteReparacion
    });
}, 1000);