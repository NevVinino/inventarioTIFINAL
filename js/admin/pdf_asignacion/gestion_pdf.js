/**
 * Módulo para gestión de documentos PDF de asignaciones
 * Contiene todas las funciones relacionadas con generación y manejo de PDFs
 */

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

// Función para configurar los event listeners de los botones PDF
function configurarBotonesPDF(modalView, idAsignacion) {
    const btnGenerarPdf = modalView.querySelector('.btn-generar-pdf');
    const btnSubirFirma = modalView.querySelector('.btn-subir-firma');
    const inputFirma = modalView.querySelector('.input-firma');
    
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

console.log("📄 Módulo de gestión PDF cargado correctamente");
function configurarBotonesPDF(modalView, idAsignacion) {
    const btnGenerarPdf = modalView.querySelector('.btn-generar-pdf');
    const btnDescargarPdf = modalView.querySelector('.btn-descargar-pdf');
    const btnSubirFirma = modalView.querySelector('.btn-subir-firma');
    const inputFirma = modalView.querySelector('.input-firma');
    
    if (btnGenerarPdf) {
        btnGenerarPdf.onclick = function() {
            generarPDFAsignacion(idAsignacion);
        };
    }
    
    if (btnDescargarPdf) {
        btnDescargarPdf.onclick = function() {
            descargarPDFAsignacion(idAsignacion);
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

console.log("📄 Módulo de gestión PDF cargado correctamente");
