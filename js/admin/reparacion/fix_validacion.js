// SOLUCIÓN DEFINITIVA: Validación y envío simplificado
console.log('🔧 === FIX VALIDACIÓN CARGADO ===');

// NUEVO: Función para cargar componentes automáticamente
function cargarComponentesAutomaticamente() {
    console.log('🔄 Cargando componentes automáticamente...');
    
    const tipoComponente = document.getElementById('tipoComponente')?.value;
    const seccion = document.getElementById('seccionComponenteExistente');
    
    if (tipoComponente && seccion) {
        seccion.style.display = 'block';
        
        // Usar la función existente
        if (typeof aplicarFiltroASelectComponentes === 'function') {
            aplicarFiltroASelectComponentes();
        }
    }
}

// Configurar event listener para tipo de componente
document.addEventListener('DOMContentLoaded', function() {
    const tipoComponenteSelect = document.getElementById('tipoComponente');
    if (tipoComponenteSelect) {
        tipoComponenteSelect.addEventListener('change', function() {
            console.log('🎯 Cambio en tipo de componente detectado:', this.value);
            cargarComponentesAutomaticamente();
        });
        console.log('✅ Event listener configurado para tipo de componente');
    }
});

// Sobrescribir completamente la función de guardado
function guardarCambioHardware() {
    console.log('🚀 === GUARDAR CAMBIO HARDWARE (FIX) ===');
    
    const form = document.getElementById('formCambio');
    if (!form) {
        console.error('❌ Formulario no encontrado');
        mostrarMensaje('Error: Formulario no encontrado', 'error');
        return;
    }
    
    // Validación simple
    const tipoCambio = form.querySelector('#idTipoCambio').value;
    const tipoComponente = form.querySelector('#tipoComponente').value;
    const componenteExistente = form.querySelector('#idComponenteExistente').value;
    const fechaCambio = form.querySelector('#fechaCambio').value;
    
    console.log('🔍 Validando campos:');
    console.log('  tipoCambio:', tipoCambio);
    console.log('  tipoComponente:', tipoComponente);
    console.log('  componenteExistente:', componenteExistente);
    
    if (!tipoCambio) {
        mostrarMensaje('Debe seleccionar el tipo de cambio', 'error');
        return;
    }
    
    if (!tipoComponente) {
        mostrarMensaje('Debe seleccionar el tipo de componente', 'error');
        return;
    }

    if (!fechaCambio) {
        mostrarMensaje('Debe seleccionar la fecha del cambio', 'error');
        return;
    }

    // Validar que la fecha no sea futura
    const hoyISO = new Date().toISOString().split('T')[0];
    if (fechaCambio > hoyISO) {
        mostrarMensaje('La fecha del cambio no puede ser futura', 'error');
        return;
    }
    
    // Solo validar componente existente si NO es retirar (tipo 3)
    if (tipoCambio !== '3' && !componenteExistente) {
        mostrarMensaje('Debe seleccionar un componente del catálogo', 'error');
        return;
    }
    
    // Preparar datos para envío directo
    const formData = new FormData();
    
    // Datos básicos del formulario
    formData.set('id_reparacion', document.getElementById('idReparacionCambio')?.value || '');
    formData.set('id_activo', document.getElementById('idActivoCambio')?.value || '');
    formData.set('id_tipo_cambio', tipoCambio);
    formData.set('tipo_componente', tipoComponente);
    formData.set('fecha_cambio', fechaCambio);
    
    // Solo enviar componente existente si NO es retirar
    if (tipoCambio !== '3') {
        formData.set('id_componente_existente', componenteExistente);
        formData.set('costo', form.querySelector('#costoCambio')?.value || '0');
    } else {
        // Para retirar, puede haber costo de servicio pero no componente nuevo
        formData.set('costo', form.querySelector('#costoCambio')?.value || '0');
        console.log('📌 Tipo Retirar: no enviando componente existente pero sí costo');
    }
    
    formData.set('motivo', form.querySelector('#motivoCambio')?.value || '');
    
    // Componente actual si está presente (para reemplazo/retiro) o slot seleccionado (para instalación)
    const componenteActual = form.querySelector('#componenteActual')?.value;
    if (componenteActual) {
        formData.set('componente_actual', componenteActual);
        console.log('📌 Campo componente_actual/slot enviado:', componenteActual);
    } else if (tipoCambio === '1' || tipoCambio === '3') {
        // Para reemplazo y retiro es obligatorio
        mostrarMensaje('Debe seleccionar un componente actual', 'error');
        return;
    }
    
    // Usar la acción que funciona con reparaciones
    formData.set('accion', 'crear');
    
    console.log('📤 Datos a enviar:');
    console.log('📋 === RESUMEN COMPLETO DE DATOS ===');
    console.log(`  🎯 Tipo cambio: ${tipoCambio} (${tipoCambio === '1' ? 'Reemplazo' : tipoCambio === '2' ? 'Instalación' : tipoCambio === '3' ? 'Retiro' : 'Desconocido'})`);
    console.log(`  🔧 Tipo componente: ${tipoComponente}`);
    console.log(`  📦 Componente existente: ${componenteExistente || 'N/A (Retiro)'}`);
    console.log(`  🎰 Componente actual/slot: ${componenteActual || 'N/A'}`);
    
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: "${value}"`);
    }
    console.log('📋 === FIN RESUMEN ===');
    
    // Intentar primero con controlador específico de hardware
    console.log('🎯 Intentando con controlador de hardware...');
    
    fetch('../controllers/procesar_cambio_hardware.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📊 Hardware controller status:', response.status);
        if (response.status === 404) {
            console.log('❌ Controlador de hardware no existe, usando reparaciones...');
            throw new Error('Hardware controller not found');
        }
        return response.text();
    })
    .catch(error => {
        console.log('🔄 Usando controlador de reparaciones...');
        
        // Si el controlador específico no existe, usar el de reparaciones
        formData.set('accion', 'guardar_cambio_hardware');
        
        return fetch('../controllers/procesar_reparacion.php', {
            method: 'POST',
            body: formData
        }).then(r => r.text());
    })
    .then(text => {
        console.log('📄 Response text completo:', text);
        
        // Verificar si la respuesta es JSON válido
        if (text.trim().startsWith('{') || text.trim().startsWith('[')) {
            try {
                const data = JSON.parse(text);
                console.log('📨 Response JSON:', data);
                
                if (data.success) {
                    mostrarMensaje('Cambio de hardware guardado correctamente', 'success');
                    
                    // Recargar la tabla de cambios
                    if (typeof cargarCambiosHardware === 'function') {
                        cargarCambiosHardware();
                    }
                    
                    // Limpiar y ocultar formulario
                    form.reset();
                    document.getElementById('formCambioHardware').style.display = 'none';
                    
                } else {
                    console.error('❌ Error del servidor:', data.error);
                    mostrarMensaje(data.error || 'Error al guardar el cambio', 'error');
                }
                
            } catch (parseError) {
                console.error('❌ Error parsing JSON:', parseError);
                console.error('Response text:', text);
                mostrarMensaje('Error: Respuesta inválida del servidor', 'error');
            }
        } else {
            // La respuesta no es JSON, es un mensaje de error directo
            console.error('❌ Respuesta no es JSON:', text);
            
            if (text.includes('Acción no válida')) {
                // Intentar con diferentes nombres de acción
                console.log('🔄 Intentando con acción diferente...');
                const nuevaFormData = new FormData(form);
                nuevaFormData.set('accion', 'guardar_cambio_hardware');
                nuevaFormData.set('tipo_nuevo_componente', 'usar_existente');
                
                return fetch('../controllers/procesar_reparacion.php', {
                    method: 'POST',
                    body: nuevaFormData
                }).then(r => r.text());
            } else {
                mostrarMensaje('Error del servidor: ' + text, 'error');
            }
        }
    })
    .then(text2 => {
        if (text2) {
            console.log('📄 Segunda respuesta:', text2);
            try {
                const data = JSON.parse(text2);
                if (data.success) {
                    mostrarMensaje('Cambio de hardware guardado correctamente', 'success');
                    form.reset();
                    document.getElementById('formCambioHardware').style.display = 'none';
                } else {
                    mostrarMensaje(data.error || 'Error al guardar', 'error');
                }
            } catch (e) {
                mostrarMensaje('Error: ' + text2, 'error');
            }
        }
    })
    .catch(error => {
        console.error('❌ Error en fetch:', error);
        mostrarMensaje('Error de conexión al guardar', 'error');
    });
}

console.log('✅ Fix de validación aplicado - función guardarCambioHardware sobrescrita');