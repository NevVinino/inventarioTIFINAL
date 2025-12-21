// PATCH COMPLETO: Función para cargar componentes correctamente con filtro mejorado
function aplicarFiltroComponentesSimple() {
    console.log('🔧 === APLICANDO FILTRO SIMPLE (MEJORADO) ===');
    
    const tipoComponente = document.getElementById('tipoComponente')?.value;
    const selectComponente = document.getElementById('idComponenteExistente');
    
    if (!tipoComponente || !selectComponente) {
        console.warn('❌ Faltan elementos para cargar componentes');
        return;
    }
    
    // Resetear filtro si es necesario
    if (typeof tipoFiltroComponentesActual === 'undefined') {
        window.tipoFiltroComponentesActual = 'todos';
    }
    
    const url = `../controllers/procesar_reparacion.php?action=get_componentes&tipo=${tipoComponente}`;
    console.log('🌐 Cargando desde:', url);
    console.log('🔍 Filtro actual:', window.tipoFiltroComponentesActual);
    
    fetch(url, { method: 'GET' })
        .then(response => {
            console.log('📊 Response status:', response.status);
            return response.json();
        })
        .then(componentes => {
            console.log('📦 Componentes recibidos:', componentes);
            
            // Limpiar select
            selectComponente.innerHTML = '<option value="">Seleccionar componente...</option>';
            
            if (!Array.isArray(componentes) || componentes.length === 0) {
                selectComponente.innerHTML = '<option value="">No hay componentes disponibles</option>';
                return;
            }
            
            // Aplicar filtro actual
            let componentesMostrar = componentes;
            if (window.tipoFiltroComponentesActual && window.tipoFiltroComponentesActual !== 'todos') {
                componentesMostrar = componentes.filter(comp => comp.tipo === window.tipoFiltroComponentesActual);
                console.log(`� Filtrado por '${window.tipoFiltroComponentesActual}': ${componentesMostrar.length} de ${componentes.length}`);
            }
            
            if (componentesMostrar.length === 0) {
                selectComponente.innerHTML = '<option value="">Sin componentes para este filtro</option>';
                return;
            }
            
            // Llenar select
            componentesMostrar.forEach(comp => {
                const option = document.createElement('option');
                option.value = `${comp.tipo}_${comp.id}`;
                option.textContent = `${comp.descripcion} (${comp.tipo === 'generico' ? 'Genérico' : 'Detallado'})`;
                selectComponente.appendChild(option);
            });
            
            console.log('✅ Select poblado correctamente');
            
            // Actualizar estado del filtro en la interfaz
            actualizarEstadoFiltroUI();
        })
        .catch(error => {
            console.error('❌ Error cargando componentes:', error);
            selectComponente.innerHTML = '<option value="">Error cargando componentes</option>';
        });
}

// Función para actualizar la UI del filtro
function actualizarEstadoFiltroUI() {
    const btnToggle = document.getElementById('toggleTipoComponenteReparacion');
    const estadoFiltro = document.getElementById('estadoFiltroReparacion');
    
    if (!btnToggle || !estadoFiltro) return;
    
    const filtro = window.tipoFiltroComponentesActual || 'todos';
    
    console.log('🎨 Actualizando UI del filtro a:', filtro);
    
    switch (filtro) {
        case 'generico':
            btnToggle.textContent = 'Solo Genéricos';
            btnToggle.className = 'btn-toggle-tipo filtro-generico';
            estadoFiltro.textContent = '(Solo componentes genéricos)';
            break;
        case 'detallado':
            btnToggle.textContent = 'Solo Detallados';
            btnToggle.className = 'btn-toggle-tipo filtro-detallado';
            estadoFiltro.textContent = '(Solo componentes detallados)';
            break;
        default:
            btnToggle.textContent = 'Mostrar Todos';
            btnToggle.className = 'btn-toggle-tipo';
            estadoFiltro.textContent = '(Genéricos y Detallados)';
            window.tipoFiltroComponentesActual = 'todos';
            break;
    }
}

// Función para configurar el filtro con manejo mejorado
function configurarFiltroMejorado() {
    const btnToggle = document.getElementById('toggleTipoComponenteReparacion');
    
    if (!btnToggle) {
        console.warn('❌ Botón de filtro no encontrado');
        return;
    }
    
    // Remover listeners anteriores
    const nuevoBtn = btnToggle.cloneNode(true);
    btnToggle.parentNode.replaceChild(nuevoBtn, btnToggle);
    
    // Configurar nuevo listener
    nuevoBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log("🎯 === CLICK EN FILTRO MEJORADO ===");
        console.log("Filtro ANTES:", window.tipoFiltroComponentesActual);
        
        // Ciclar entre los filtros
        switch (window.tipoFiltroComponentesActual) {
            case 'todos':
                window.tipoFiltroComponentesActual = 'generico';
                break;
            case 'generico':
                window.tipoFiltroComponentesActual = 'detallado';
                break;
            case 'detallado':
                window.tipoFiltroComponentesActual = 'todos';
                break;
            default:
                window.tipoFiltroComponentesActual = 'todos';
                break;
        }
        
        console.log("Filtro DESPUÉS:", window.tipoFiltroComponentesActual);
        
        // Aplicar filtro inmediatamente
        aplicarFiltroComponentesSimple();
    });
    
    console.log('✅ Filtro mejorado configurado');
}

// Sobrescribir la función problemática
if (typeof aplicarFiltroASelectComponentes === 'undefined') {
    window.aplicarFiltroASelectComponentes = aplicarFiltroComponentesSimple;
}

// Event listener mejorado para el tipo de componente
document.addEventListener('DOMContentLoaded', function() {
    const tipoSelect = document.getElementById('tipoComponente');
    if (tipoSelect) {
        tipoSelect.addEventListener('change', function() {
            console.log('🎯 Tipo cambiado a:', this.value);
            
            // Resetear filtro al cambiar tipo de componente
            window.tipoFiltroComponentesActual = 'todos';
            
            if (this.value) {
                const seccion = document.getElementById('seccionComponenteExistente');
                if (seccion) {
                    seccion.style.display = 'block';
                    
                    // Configurar filtro y cargar componentes
                    setTimeout(() => {
                        configurarFiltroMejorado();
                        aplicarFiltroComponentesSimple();
                    }, 100);
                }
            }
        });
    }
});

console.log('🔧 Patch de componentes mejorado aplicado');