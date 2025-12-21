/*
 * eventos.js - Event listeners para el sistema de reparaciones
 * Versión simplificada sin importaciones problemáticas
 */

// Event listeners para el modal de cambios de hardware
function inicializarEventosHardware() {
    console.log('Inicializando eventos de hardware...');
    
    // Botón para agregar nuevo cambio
    const btnNuevoCambio = document.getElementById('btnNuevoCambio');
    if (btnNuevoCambio) {
        btnNuevoCambio.addEventListener('click', function() {
            console.log('Mostrando formulario de cambio de hardware');
            mostrarFormCambioHardware();
        });
        console.log('✅ Event listener para btnNuevoCambio agregado');
    } else {
        console.warn('⚠️ No se encontró btnNuevoCambio');
    }

    // NUEVO: Configurar filtro de componentes para reparaciones (solo una vez)
    if (typeof configurarFiltroTipoComponenteReparacion === 'function') {
        const btnFiltro = document.getElementById('toggleTipoComponenteReparacion');
        if (btnFiltro && !btnFiltro.dataset.configurado) {
            configurarFiltroTipoComponenteReparacion();
            btnFiltro.dataset.configurado = 'true';
            console.log('✅ Filtro de componentes de reparaciones configurado');
        } else if (btnFiltro && btnFiltro.dataset.configurado) {
            console.log('⚠️ Filtro ya estaba configurado, saltando...');
        }
    } else {
        console.warn('⚠️ Función configurarFiltroTipoComponenteReparacion no encontrada');
    }

    // Select de tipo de componente
    const tipoComponente = document.getElementById('tipoComponente');
    if (tipoComponente) {
        tipoComponente.addEventListener('change', function() {
            console.log('Cambio en tipo de componente:', this.value);
            manejarCambioTipoComponente();
        });
        console.log('✅ Event listener para tipoComponente agregado');
    } else {
        console.warn('⚠️ No se encontró tipoComponente');
    }

    // Select de tipo de cambio - NUEVO
    const tipoCambio = document.getElementById('idTipoCambio');
    if (tipoCambio) {
        tipoCambio.addEventListener('change', function() {
            console.log('Cambio en tipo de cambio:', this.value);
            manejarCambioTipoCambio();
        });
        console.log('✅ Event listener para tipoCambio agregado');
    } else {
        console.warn('⚠️ No se encontró idTipoCambio');
    }

    // Cerrar modales
    const closeButtons = document.querySelectorAll('.close, .close-hardware');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });

    console.log('✅ Eventos de hardware inicializados correctamente');
}

// Event listeners para botones de la tabla de reparaciones
function inicializarEventosTabla() {
    console.log('Inicializando eventos de tabla...');
    
    // Verificar que las funciones estén disponibles
    if (typeof mostrarDetallesReparacion !== 'function') {
        console.error('❌ mostrarDetallesReparacion no está definida');
        return;
    }
    if (typeof editarReparacion !== 'function') {
        console.error('❌ editarReparacion no está definida');
        return;
    }
    if (typeof abrirModalCambiosHardware !== 'function') {
        console.error('❌ abrirModalCambiosHardware no está definida');
        return;
    }
    
    // Botones de ver detalles
    const botonesVer = document.querySelectorAll('.btn-ver');
    botonesVer.forEach(btn => {
        btn.addEventListener('click', function() {
            mostrarDetallesReparacion(this);
        });
    });
    console.log(`✅ ${botonesVer.length} botones de ver configurados`);
    
    // Botones de editar
    const botonesEditar = document.querySelectorAll('.btn-editar');
    botonesEditar.forEach(btn => {
        btn.addEventListener('click', function() {
            editarReparacion(this);
        });
    });
    console.log(`✅ ${botonesEditar.length} botones de editar configurados`);
    
    // Botones de cambios de hardware
    const botonesHardware = document.querySelectorAll('.btn-hardware');
    botonesHardware.forEach(btn => {
        btn.addEventListener('click', function() {
            abrirModalCambiosHardware(this);
        });
    });
    console.log(`✅ ${botonesHardware.length} botones de hardware configurados`);
    
    // Botón nueva reparación
    const btnNuevo = document.getElementById('btnNuevo');
    if (btnNuevo) {
        if (typeof nuevaReparacion === 'function') {
            btnNuevo.addEventListener('click', function() {
                nuevaReparacion();
            });
            console.log('✅ Botón nueva reparación configurado');
        } else {
            console.error('❌ nuevaReparacion no está definida');
        }
    }
}

// === FUNCIONALIDAD MEJORADA: CERRAR MODALES ===
// Agregar funcionalidad para cerrar modales con tecla Escape

// Función para cerrar todos los modales con tecla Escape
function configurarCierreConEscape() {
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
            // Buscar todos los modales visibles y cerrarlos
            const modalesVisibles = document.querySelectorAll('.modal[style*="block"], .modal.show');
            modalesVisibles.forEach(modal => {
                modal.style.display = "none";
                if (modal.classList.contains('show')) {
                    modal.classList.remove('show');
                }
            });
            
            // Buscar modales específicos de reparación
            const modalReparacion = document.getElementById("modalReparacion");
            const modalDetalles = document.getElementById("modalDetalles");
            const modalCambios = document.getElementById("modalCambios");
            
            if (modalReparacion && modalReparacion.style.display === "block") {
                modalReparacion.style.display = "none";
            }
            if (modalDetalles && modalDetalles.style.display === "block") {
                modalDetalles.style.display = "none";
            }
            if (modalCambios && modalCambios.style.display === "block") {
                modalCambios.style.display = "none";
            }
        }
    });
}

// Ejecutar al cargar el documento
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', configurarCierreConEscape);
} else {
    configurarCierreConEscape();
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM cargado, inicializando eventos de reparaciones');
    
    // Aumentar el delay para asegurar que todos los scripts estén cargados
    setTimeout(() => {
        console.log('🔄 Iniciando configuración de eventos...');
        inicializarEventosHardware();
        
        // Esperar un poco más para la tabla
        setTimeout(() => {
            inicializarEventosTabla();
        }, 200);
    }, 500);
});

console.log('✅ eventos.js cargado correctamente');