// Función para recargar la tabla de cambios de hardware
function cargarCambiosHardware() {
    console.log('🔄 === RECARGANDO TABLA DE CAMBIOS DE HARDWARE ===');
    
    const idReparacion = document.getElementById('idReparacionCambio')?.value;
    const tablaCuerpo = document.querySelector('#tablaCambiosHardware tbody');
    
    if (!idReparacion) {
        console.warn('❌ No hay ID de reparación para cargar cambios');
        return;
    }
    
    if (!tablaCuerpo) {
        console.warn('❌ Tabla de cambios no encontrada');
        return;
    }
    
    console.log('📊 Cargando cambios para reparación:', idReparacion);
    
    // Mostrar indicador de carga
    tablaCuerpo.innerHTML = '<tr><td colspan="7" style="text-align: center;">🔄 Cargando cambios...</td></tr>';
    
    // Consultar cambios de hardware
    fetch(`../controllers/procesar_reparacion.php?action=get_cambios_hardware&id_reparacion=${idReparacion}`)
        .then(response => {
            console.log('📊 Response status:', response.status);
            return response.json();
        })
        .then(cambios => {
            console.log('📦 Cambios recibidos:', cambios);
            
            // Limpiar tabla
            tablaCuerpo.innerHTML = '';
            
            if (!Array.isArray(cambios) || cambios.length === 0) {
                tablaCuerpo.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #666;">No hay cambios de hardware registrados</td></tr>';
                console.log('ℹ️ No hay cambios para mostrar');
                return;
            }
            
            // Llenar tabla con cambios
            cambios.forEach(cambio => {
                const fila = crearFilaCambioHardware(cambio);
                tablaCuerpo.appendChild(fila);
            });
            
            console.log(`✅ Tabla actualizada con ${cambios.length} cambios`);
        })
        .catch(error => {
            console.error('❌ Error cargando cambios:', error);
            tablaCuerpo.innerHTML = '<tr><td colspan="7" style="text-align: center; color: red;">❌ Error cargando cambios de hardware</td></tr>';
        });
}

// Event listener para cargar cambios cuando se abre el modal
document.addEventListener('DOMContentLoaded', function() {
    // Observar cuando se abre el modal de cambios de hardware
    const modalHardware = document.getElementById('modalCambiosHardware');
    if (modalHardware) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const modal = mutation.target;
                    if (modal.style.display === 'block' || modal.style.display === '') {
                        // Modal se abrió, cargar cambios
                        setTimeout(() => {
                            cargarCambiosHardware();
                        }, 100);
                    }
                }
            });
        });
        
        observer.observe(modalHardware, {
            attributes: true,
            attributeFilter: ['style']
        });
    }
});

// Función para crear una fila de la tabla de cambios
function crearFilaCambioHardware(cambio) {
    const fila = document.createElement('tr');
    
    fila.innerHTML = `
        <td>${cambio.tipo_cambio || 'N/A'}</td>
        <td>${cambio.tipo_componente || 'N/A'}</td>
        <td>${cambio.componente_nuevo || 'N/A'}</td>
        <td>${cambio.componente_retirado || 'N/A'}</td>
        <td>${cambio.costo ? '$ ' + parseFloat(cambio.costo).toFixed(2) : '$ 0.00'}</td>
        <td>${cambio.motivo || 'N/A'}</td>
        <td>${cambio.fecha_formateada || cambio.fecha || '-'}</td>
        <td>
            <button type="button" class="btn-icon btn-eliminar" 
                    onclick="eliminarCambioHardware(${cambio.id_cambio_hardware})" 
                    title="Eliminar cambio">
                <img src="../../img/eliminar.png" alt="Eliminar">
            </button>
        </td>
    `;
    
    return fila;
}

// Función para eliminar un cambio de hardware
function eliminarCambioHardware(idCambio) {
    if (!confirm('¿Está seguro de eliminar este cambio de hardware?')) {
        return;
    }
    
    console.log('🗑️ Eliminando cambio de hardware:', idCambio);
    
    const formData = new FormData();
    formData.set('action', 'eliminar_cambio_hardware');
    formData.set('id_cambio_hardware', idCambio);
    
    // Intentar primero con el controlador específico de hardware
    fetch('../controllers/procesar_cambio_hardware.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📊 Hardware controller status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('📄 Response text:', text);
        
        // Verificar si la respuesta es JSON válido
        if (text.trim().startsWith('{')) {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    mostrarMensaje('Cambio de hardware eliminado correctamente', 'success');
                    cargarCambiosHardware(); // Recargar tabla
                } else {
                    mostrarMensaje(data.error || 'Error al eliminar el cambio', 'error');
                }
            } catch (parseError) {
                console.error('❌ Error parsing JSON:', parseError);
                mostrarMensaje('Error: Respuesta inválida del servidor', 'error');
            }
        } else {
            // Si no es JSON válido, usar el controlador de reparaciones como fallback
            console.log('🔄 Usando controlador de reparaciones como fallback...');
            
            return fetch('../controllers/procesar_reparacion.php', {
                method: 'POST',
                body: formData
            }).then(r => r.text());
        }
    })
    .then(text2 => {
        if (text2) {
            console.log('📄 Fallback response:', text2);
            try {
                const data = JSON.parse(text2);
                if (data.success) {
                    mostrarMensaje('Cambio de hardware eliminado correctamente', 'success');
                    cargarCambiosHardware(); // Recargar tabla
                } else {
                    mostrarMensaje(data.error || 'Error al eliminar el cambio', 'error');
                }
            } catch (e) {
                console.error('❌ Error en fallback:', e);
                mostrarMensaje('Error: ' + text2, 'error');
            }
        }
    })
    .catch(error => {
        console.error('❌ Error eliminando cambio:', error);
        mostrarMensaje('Error de conexión al eliminar', 'error');
    });
}

// Hacer las funciones disponibles globalmente
window.cargarCambiosHardware = cargarCambiosHardware;
window.eliminarCambioHardware = eliminarCambioHardware;

console.log('✅ Funciones de tabla de cambios de hardware cargadas');