document.addEventListener("DOMContentLoaded", function () {
    const modalAsignacion = document.getElementById("modalAsignacion");
    const modalRetorno = document.getElementById("modalRetorno");
    const modalView = document.getElementById('modalVisualizacion');
    const btnNuevo = document.getElementById("btnNuevo");
    const formAsignacion = document.getElementById("formAsignacion");
    const formRetorno = document.getElementById("formRetorno");

    console.log("🚀 Iniciando sistema de gestión de asignaciones...");

    // --- abrir modal nueva asignación ---
    if (btnNuevo) {
        btnNuevo.addEventListener("click", function () {
            console.log("📝 Abriendo modal nueva asignación");
            document.getElementById("modal-title").textContent = "Nueva Asignación";
            document.getElementById("accion").value = "crear";
            document.getElementById("id_asignacion").value = "";
            document.getElementById("btn-submit").textContent = "Asignar";
            
            if (formAsignacion) formAsignacion.reset();
            
            // Filter activos for new assignment - only show available ones
            const activoSelect = document.getElementById("id_activo");
            if (activoSelect) {
                for (let i = 0; i < activoSelect.options.length; i++) {
                    const option = activoSelect.options[i];
                    if (option.value && option.dataset.estadoAsignacion === 'Asignado') {
                        option.style.display = 'none';
                    } else {
                        option.style.display = '';
                    }
                }
            }
            
            modalAsignacion.style.display = "block";
        });
    }

    // --- cerrar modales ---
    document.querySelectorAll(".close").forEach(closeBtn => {
        closeBtn.addEventListener("click", function() {
            console.log("❌ Cerrando modal");
            if (modalAsignacion) modalAsignacion.style.display = "none";
            if (modalRetorno) modalRetorno.style.display = "none";
            if (modalView) modalView.style.display = "none";
        });
    });

    // --- cerrar modales con tecla Escape ---
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            if (modalAsignacion && modalAsignacion.style.display === "block") {
                modalAsignacion.style.display = "none";
            }
            if (modalRetorno && modalRetorno.style.display === "block") {
                modalRetorno.style.display = "none";
            }
            if (modalView && modalView.style.display === "block") {
                modalView.style.display = "none";
            }
            // Cerrar modal del visor de documentos
            const modalVisor = document.getElementById('modalVisorDocumento');
            if (modalVisor && modalVisor.style.display === 'block') {
                cerrarVisorDocumento();
            }
        }
    });

    // --- botones ver ---
    document.querySelectorAll(".btn-ver").forEach(function (btn) {
        btn.addEventListener("click", function () {
            console.log("👀 Abriendo modal de visualización");
            if (!modalView) return;

            const data = this.dataset;
            const idAsignacion = data.id;
            
            // Mapeo de datos para el modal de visualización
            const mapeoElementos = {
                'view-persona': data.persona,
                'view-email': data.email || 'No especificado',
                'view-telefono': data.telefono || 'No especificado',
                'view-localidad': data.localidad || 'No especificado',
                'view-area': data.area || 'No especificado',
                'view-empresa': data.empresa || 'No especificado',
                'view-situacion': data.situacion || 'No especificado',
                'view-tipo-persona': data.tipoPersona || 'No especificado',
                'view-jefe': data.jefe || 'No especificado',
                'view-activo': data.activo,
                'view-tipo-activo': data.tipoActivo,
                'view-serial': data.serial || 'No especificado',
                'view-ip': data.ip || 'No especificado',
                'view-mac': data.mac || 'No especificado',
                'view-fecha-asignacion': data.fechaAsignacion,
                'view-fecha-retorno': data.fechaRetorno || 'Pendiente',
                'view-estado': data.estado,
                'view-usuario': data.usuario || 'No especificado',
                'view-observaciones': data.observaciones || 'Sin observaciones'
            };

            // Llenar elementos del modal
            Object.keys(mapeoElementos).forEach(elementId => {
                const element = document.getElementById(elementId);
                if (element) {
                    let valor = mapeoElementos[elementId];
                    
                    if (elementId === 'view-observaciones') {
                        const observacionesContainer = document.getElementById('view-observaciones');
                        if (observacionesContainer) {
                            if (!valor || valor === 'Sin observaciones' || valor === 'No especificado') {
                                observacionesContainer.textContent = 'Sin observaciones adicionales';
                                observacionesContainer.className = 'observaciones-texto sin-contenido';
                            } else {
                                observacionesContainer.textContent = valor;
                                observacionesContainer.className = 'observaciones-texto con-contenido';
                            }
                        }
                        return;
                    }
                    
                    element.textContent = valor;
                }
            });

            // Calcular duración de la asignación
            calcularDuracionAsignacion(data.fechaAsignacion, data.fechaRetorno);

            // Aplicar colores según el estado
            const estadoElement = document.getElementById('view-estado');
            if (estadoElement && data.estado) {
                estadoElement.style.color = data.estado === 'Activo' ? '#f39c12' : '#27ae60';
                estadoElement.style.fontWeight = 'bold';
            }

            // Configurar botones de PDF usando el módulo externo
            configurarBotonesPDF(modalView, idAsignacion);

            // Verificar documentos existentes
            verificarDocumentosExistentes(idAsignacion);

            modalView.style.display = 'block';
        });
    });

    // --- botones retornar ---
    document.querySelectorAll(".btn-retornar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modalRetorno) return;

            const idAsignacion = this.dataset.id;
            const persona = this.dataset.persona;
            const activo = this.dataset.activo;

            document.getElementById("retorno_id_asignacion").value = idAsignacion;
            document.getElementById("retorno_persona").textContent = persona;
            document.getElementById("retorno_activo").textContent = activo;

            modalRetorno.style.display = "block";
        });
    });

    // --- buscador ---
    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaAsignaciones tbody tr");

    if (buscador) {
        buscador.addEventListener("input", function () {
            const valor = buscador.value.toLowerCase();
            filas.forEach(function (fila) {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(valor) ? "" : "none";
            });
        });
    }

    // --- botones editar ---
    document.querySelectorAll(".btn-editar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modalAsignacion) return;

            // Configurar para edición
            document.getElementById("modal-title").textContent = "Editar Asignación";
            document.getElementById("accion").value = "editar";
            document.getElementById("id_asignacion").value = this.dataset.id;
            document.getElementById("btn-submit").textContent = "Actualizar";

            // Llenar formulario con datos existentes
            const personaSelect = document.getElementById("id_persona");
            const activoSelect = document.getElementById("id_activo");
            const fechaInput = document.getElementById("fecha_asignacion");
            const observacionesTextarea = document.getElementById("observaciones");

            if (personaSelect) {
                personaSelect.value = this.dataset.idPersona || '';
            }

            // Para edición, habilitar todas las opciones primero
            if (activoSelect) {
                for (let i = 0; i < activoSelect.options.length; i++) {
                    const option = activoSelect.options[i];
                    if (option.dataset.estadoAsignacion === 'Asignado' && option.value !== this.dataset.idActivo) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                }
                activoSelect.value = this.dataset.idActivo || '';
            }

            if (fechaInput) {
                fechaInput.value = this.dataset.fechaAsignacion || '';
            }

            if (observacionesTextarea) {
                observacionesTextarea.value = this.dataset.observaciones || '';
            }

            modalAsignacion.style.display = "block";
        });
    });

    // --- botones eliminar ---
    document.querySelectorAll(".btn-eliminar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            const id = this.dataset.id;
            const persona = this.dataset.persona;
            const activo = this.dataset.activo;
            const estado = this.dataset.estado;

            let mensaje = `⚠️ ELIMINACIÓN DE ASIGNACIÓN\n\n`;
            mensaje += `¿Está seguro de eliminar permanentemente la asignación de:\n`;
            mensaje += `👤 Persona: "${persona}"\n`;
            mensaje += `💻 Activo: "${activo}"?\n\n`;
            mensaje += `📋 IMPORTANTE:\n`;
            mensaje += `• Se eliminará el registro de asignación del sistema\n`;
            mensaje += `• Se eliminarán TODOS los documentos PDF relacionados\n`;
            mensaje += `• Esta acción forma parte del ciclo de vida del activo\n`;
            mensaje += `• Esta operación NO se puede deshacer\n\n`;
            
            if (estado === "Activo") {
                mensaje += `🔄 NOTA ADICIONAL: Esta asignación está activa.\n`;
                mensaje += `Al eliminarla, el activo quedará disponible para nuevas asignaciones.\n\n`;
            }
            
            mensaje += `¿Desea continuar con la eliminación?`;

            if (confirm(mensaje)) {
                procesarEliminacion(id);
            }
        });
    });

    // --- validación formulario asignación ---
    if (formAsignacion) {
        formAsignacion.addEventListener("submit", function(event) {
            event.preventDefault();
            
            const persona = document.getElementById("id_persona").value;
            const activo = document.getElementById("id_activo").value;
            const fechaAsignacion = document.getElementById("fecha_asignacion").value;

            if (!persona || !activo || !fechaAsignacion) {
                alert("Por favor complete todos los campos obligatorios.");
                return false;
            }

            const hoy = new Date().toISOString().split('T')[0];
            if (fechaAsignacion > hoy) {
                alert("La fecha de asignación no puede ser posterior a hoy.");
                return false;
            }

            // Client-side validation for already assigned assets (for new assignments only)
            const accion = document.getElementById("accion").value;
            if (accion === "crear") {
                const activoSelect = document.getElementById("id_activo");
                const selectedOption = activoSelect.options[activoSelect.selectedIndex];
                
                if (selectedOption && selectedOption.dataset.estadoAsignacion === 'Asignado') {
                    alert("Este activo ya está asignado a otra persona. Por favor seleccione un activo disponible.");
                    return false;
                }
            }
            
            procesarFormulario(this, accion);
        });
    }

    // --- validación formulario retorno ---
    if (formRetorno) {
        formRetorno.addEventListener("submit", function(event) {
            event.preventDefault();
            
            const fechaRetorno = document.getElementById("fecha_retorno").value;

            if (!fechaRetorno) {
                alert("La fecha de retorno es obligatoria.");
                return false;
            }

            const hoy = new Date().toISOString().split('T')[0];
            if (fechaRetorno > hoy) {
                alert("La fecha de retorno no puede ser posterior a hoy.");
                return false;
            }

            if (!confirm("¿Está seguro de registrar el retorno de este activo?")) {
                return false;
            }

            procesarFormulario(this, 'retornar');
        });
    }

    console.log("✅ Sistema de gestión de asignaciones cargado correctamente");
});

// Función para calcular duración de asignación
function calcularDuracionAsignacion(fechaAsignacion, fechaRetorno) {
    const duracionElement = document.getElementById('view-duracion');
    if (!duracionElement) return;

    let duracionTexto = 'No calculable';

    if (fechaAsignacion && fechaAsignacion !== 'Sin fecha') {
        const partesAsignacion = fechaAsignacion.split('/');
        if (partesAsignacion.length === 3) {
            const fechaInicio = new Date(partesAsignacion[2], partesAsignacion[1] - 1, partesAsignacion[0]);
            let fechaFin;

            if (fechaRetorno && fechaRetorno !== 'Pendiente' && fechaRetorno !== 'Sin fecha') {
                const partesRetorno = fechaRetorno.split('/');
                if (partesRetorno.length === 3) {
                    fechaFin = new Date(partesRetorno[2], partesRetorno[1] - 1, partesRetorno[0]);
                }
            } else {
                fechaFin = new Date();
            }

            if (fechaFin) {
                const diferenciaTiempo = fechaFin.getTime() - fechaInicio.getTime();
                const dias = Math.floor(diferenciaTiempo / (1000 * 3600 * 24));
                
                if (dias < 0) {
                    duracionTexto = 'Fecha inválida';
                } else if (dias === 0) {
                    duracionTexto = 'Mismo día';
                } else if (dias === 1) {
                    duracionTexto = '1 día';
                } else if (dias < 30) {
                    duracionTexto = `${dias} días`;
                } else if (dias < 365) {
                    const meses = Math.floor(dias / 30);
                    const diasRestantes = dias % 30;
                    duracionTexto = diasRestantes === 0 ? 
                        `${meses} ${meses === 1 ? 'mes' : 'meses'}` : 
                        `${meses} ${meses === 1 ? 'mes' : 'meses'} y ${diasRestantes} ${diasRestantes === 1 ? 'día' : 'días'}`;
                } else {
                    const años = Math.floor(dias / 365);
                    const diasRestantes = dias % 365;
                    duracionTexto = diasRestantes === 0 ? 
                        `${años} ${años === 1 ? 'año' : 'años'}` : 
                        `${años} ${años === 1 ? 'año' : 'años'} y ${diasRestantes} ${diasRestantes === 1 ? 'día' : 'días'}`;
                }

                if (!fechaRetorno || fechaRetorno === 'Pendiente') {
                    duracionTexto += ' (en curso)';
                }
            }
        }
    }

    duracionElement.textContent = duracionTexto;
}

// Función para procesar eliminación
function procesarEliminacion(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../controllers/procesar_asignacion.php';

    const inputAccion = document.createElement('input');
    inputAccion.type = 'hidden';
    inputAccion.name = 'accion';
    inputAccion.value = 'eliminar';

    const inputId = document.createElement('input');
    inputId.type = 'hidden';
    inputId.name = 'id_asignacion';
    inputId.value = id;

    form.appendChild(inputAccion);
    form.appendChild(inputId);

    console.log("🗑️ Eliminando asignación y documentos relacionados...");

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new FormData(form)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let successMessage = '✅ Asignación eliminada exitosamente\n\n';
            if (data.archivos_eliminados && data.archivos_eliminados > 0) {
                successMessage += `📄 Archivos de documentos eliminados: ${data.archivos_eliminados}\n`;
            } else {
                successMessage += `📄 No se encontraron documentos para eliminar\n`;
            }
            
            // Mostrar información de debug en consola
            if (data.debug_info && data.debug_info.length > 0) {
                console.log("🔍 DEBUG INFO - ELIMINACIÓN DE ARCHIVOS:");
                data.debug_info.forEach(info => console.log(info));
            }
            
            alert(successMessage);
            window.location.reload();
        } else {
            alert(`❌ Error: ${data.message || 'Error al eliminar la asignación'}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Ocurrió un error al procesar la solicitud');
    });
}

// Función para procesar formularios
function procesarFormulario(form, accion) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = accion === "crear" ? 'Procesando...' : 
                                accion === "retornar" ? 'Procesando...' : 'Actualizando...';
    }

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new FormData(form)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Operación realizada exitosamente');
            
            if (accion === 'retornar') {
                document.getElementById('modalRetorno').style.display = "none";
            } else {
                document.getElementById('modalAsignacion').style.display = "none";
            }
            
            window.location.reload();
        } else {
            alert(data.message || 'Error al procesar la solicitud');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ocurrió un error al procesar la solicitud');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

// Función para generar PDF de asignación
function generarPDFAsignacion(idAsignacion) {
    fetch(`../controllers/pdf_asignacion/generar_pdf_asignacion.php?id_asignacion=${idAsignacion}&accion=ver_modal`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Documento generado exitosamente');
            verificarDocumentosExistentes(idAsignacion);
        } else {
            alert(data.message || 'Error al generar el documento');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}

// Función para subir documento firmado
function subirDocumentoFirmado(idAsignacion, archivo) {
    const formData = new FormData();
    formData.append('id_asignacion', idAsignacion);
    formData.append('documento_firmado', archivo);
    formData.append('accion', 'subir_firmado');
    
    fetch('../controllers/pdf_asignacion/procesar_documento_firmado.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Documento firmado subido exitosamente');
            const btnVerFirmado = document.querySelector('.btn-ver-pdf[data-tipo="conFirma"]');
            if (btnVerFirmado) {
                btnVerFirmado.style.display = 'inline-block';
                btnVerFirmado.onclick = function() {
                    window.open(data.url_documento, '_blank');
                };
            }
        } else {
            alert(data.message || 'Error al subir el documento');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}

// Función para verificar documentos existentes
function verificarDocumentosExistentes(idAsignacion) {
    fetch(`../controllers/pdf_asignacion/verificar_documentos.php?id_asignacion=${idAsignacion}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btnVerSinFirma = document.querySelector('.btn-ver-pdf[data-tipo="sinFirma"]');
            const btnVerConFirma = document.querySelector('.btn-ver-pdf[data-tipo="conFirma"]');
            
            if (data.documentos.sin_firma.existe) {
                if (btnVerSinFirma) {
                    btnVerSinFirma.style.display = 'inline-block';
                    btnVerSinFirma.onclick = function() {
                        window.open('../../' + data.documentos.sin_firma.url, '_blank');
                    };
                }
            }
            
            if (data.documentos.con_firma.existe && btnVerConFirma) {
                btnVerConFirma.style.display = 'inline-block';
                btnVerConFirma.onclick = function() {
                    window.open('../../' + data.documentos.con_firma.url, '_blank');
                };
            }
        }
    })
    .catch(error => {
        console.error('Error al verificar documentos:', error);
    });
}

// Función para configurar botones de PDF en el modal de visualización
function configurarBotonesPDF(modal, idAsignacion) {
    const btnGenerarPdf = modal.querySelector('.btn-generar-pdf');
    const btnSubirFirma = modal.querySelector('.btn-subir-firma');
    const inputFirma = modal.querySelector('.input-firma');
    
    if (btnGenerarPdf) {
        btnGenerarPdf.onclick = function() {
            generarPDFAsignacion(idAsignacion);
        };
    }
    
    if (btnSubirFirma) {
        btnSubirFirma.onclick = function() {
            inputFirma.click();
        };
    }
    
    if (inputFirma) {
        inputFirma.onchange = function() {
            if (this.files.length > 0) {
                subirDocumentoFirmado(idAsignacion, this.files[0]);
            }
        };
    }
}

// Función para cerrar el visor de documentos
function cerrarVisorDocumento() {
    console.log("🔄 Cerrando visor de documento...");
    const modalVisor = document.getElementById('modalVisorDocumento');
    const iframe = document.getElementById('documento-iframe');
    
    if (!modalVisor) {
        console.log("❌ Modal visor no encontrado");
        return;
    }
    
    // Limpiar el blob URL si existe
    if (modalVisor.dataset.blobUrl) {
        URL.revokeObjectURL(modalVisor.dataset.blobUrl);
        delete modalVisor.dataset.blobUrl;
        console.log("🗑️ Blob URL limpiado");
    }
    
    // Limpiar iframe
    if (iframe) {
        iframe.src = '';
    }
    
    // Cerrar modal
    modalVisor.style.display = 'none';
    console.log("✅ Modal visor cerrado");
}

// Cerrar modal del visor con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modalVisor = document.getElementById('modalVisorDocumento');
        if (modalVisor && modalVisor.style.display === 'block') {
            cerrarVisorDocumento();
        }
    }
});
   

// Función para cerrar el visor de documentos
function cerrarVisorDocumento() {
    console.log("🔄 Cerrando visor de documento...");
    const modalVisor = document.getElementById('modalVisorDocumento');
    const iframe = document.getElementById('documento-iframe');
    
    if (!modalVisor) {
        console.log("❌ Modal visor no encontrado");
        return;
    }
    
    // Limpiar el blob URL si existe
    if (modalVisor.dataset.blobUrl) {
        URL.revokeObjectURL(modalVisor.dataset.blobUrl);
        delete modalVisor.dataset.blobUrl;
        console.log("🗑️ Blob URL limpiado");
    }
    
    // Limpiar iframe
    if (iframe) {
        iframe.src = '';
    }
    
    // Cerrar modal
    modalVisor.style.display = 'none';
    console.log("✅ Modal visor cerrado");
}



// Función para configurar botones de PDF en el modal de visualización
function configurarBotonesPDF(modal, idAsignacion) {
    const btnGenerarPdf = modal.querySelector('.btn-generar-pdf');
    const btnSubirFirma = modal.querySelector('.btn-subir-firma');
    const inputFirma = modal.querySelector('.input-firma');
    
    if (btnGenerarPdf) {
        btnGenerarPdf.onclick = function() {
            generarPDFAsignacion(idAsignacion);
        };
    }
    
    if (btnSubirFirma) {
        btnSubirFirma.onclick = function() {
            inputFirma.click();
        };
    }
    
    if (inputFirma) {
        inputFirma.onchange = function() {
            if (this.files.length > 0) {
                subirDocumentoFirmado(idAsignacion, this.files[0]);
            }
        };
    }
}
  
// Función para cerrar el visor de documentos
function cerrarVisorDocumento() {
    console.log("🔄 Cerrando visor de documento...");
    const modalVisor = document.getElementById('modalVisorDocumento');
    const iframe = document.getElementById('documento-iframe');
    
    if (!modalVisor) {
        console.log("❌ Modal visor no encontrado");
        return;
    }
    
    // Limpiar el blob URL si existe
    if (modalVisor.dataset.blobUrl) {
        URL.revokeObjectURL(modalVisor.dataset.blobUrl);
        delete modalVisor.dataset.blobUrl;
        console.log("🗑️ Blob URL limpiado");
    }
    
    // Limpiar iframe
    if (iframe) {
        iframe.src = '';
    }
    
    // Cerrar modal
    modalVisor.style.display = 'none';
    console.log("✅ Modal visor cerrado");
}


