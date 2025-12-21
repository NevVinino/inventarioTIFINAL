document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalAsignacionPeriferico");
    const modalRetorno = document.getElementById("modalRetorno");
    const modalView = document.getElementById('modalVisualizacion');
    const btnNuevo = document.getElementById("btnNuevo");
    const form = document.getElementById("formAsignacionPeriferico");
    const formRetorno = document.getElementById("formRetorno");

    console.log("🚀 Iniciando sistema de asignaciones de periféricos...");

    // --- abrir modal nueva asignación ---
    if (btnNuevo) {
        btnNuevo.addEventListener("click", function () {
            console.log("📝 Abriendo modal nueva asignación");
            document.getElementById("modal-title").textContent = "Registrar Asignación de Periférico";
            document.getElementById("accion").value = "crear";
            document.getElementById("id_asignacion_periferico").value = "";
            if (form) form.reset();
            modal.style.display = "block";
        });
    }

    // --- cerrar modales ---
    document.querySelectorAll(".close").forEach(closeBtn => {
        closeBtn.addEventListener("click", function() {
            console.log("❌ Cerrando modal");
            if (modal) modal.style.display = "none";
            if (modalRetorno) modalRetorno.style.display = "none";
            if (modalView) modalView.style.display = "none";
        });
    });

    // --- cerrar modales con tecla Escape ---
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            if (modal && modal.style.display === "block") {
                modal.style.display = "none";
            }
            if (modalRetorno && modalRetorno.style.display === "block") {
                modalRetorno.style.display = "none";
            }
            if (modalView && modalView.style.display === "block") {
                modalView.style.display = "none";
            }
        }
    });

    // --- botones ver ---
    document.querySelectorAll(".btn-ver").forEach(function (btn) {
        btn.addEventListener("click", function () {
            console.log("👀 Abriendo modal de visualización");
            if (!modalView) return;

            const data = this.dataset;
            
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
                'view-tipo-periferico': data.tipoPeriferico,
                'view-marca': data.marca,
                'view-modelo': data.modelo || 'No especificado',
                'view-nombre-periferico': data.nombrePeriferico || 'No especificado',
                'view-numero-serie': data.numeroSerie || 'No especificado',
                'view-estado-periferico': data.estadoPeriferico,
                'view-condicion': data.condicion || 'No especificado',
                'view-fecha-asignacion': data.fechaAsignacion,
                'view-fecha-retorno': data.fechaRetorno || 'Pendiente',
                'view-estado': data.estado,
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
            const duracionElement = document.getElementById('view-duracion');
            if (duracionElement) {
                const fechaAsignacion = data.fechaAsignacion;
                const fechaRetorno = data.fechaRetorno;
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

            // Aplicar colores según el estado
            const estadoElement = document.getElementById('view-estado');
            if (estadoElement && data.estado) {
                estadoElement.style.color = data.estado === 'Activo' ? '#f39c12' : '#27ae60';
                estadoElement.style.fontWeight = 'bold';
            }

            modalView.style.display = 'block';
        });
    });

    // --- botones editar ---
    document.querySelectorAll(".btn-editar").forEach(function (btn, index) {
        console.log(`🔧 Configurando botón editar ${index + 1}`);
        
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log("✏️ EDITANDO ASIGNACIÓN DE PERIFÉRICO");
            console.log("Dataset:", this.dataset);
            
            if (!modal) {
                console.error("❌ Modal no encontrado");
                return;
            }

            // Configurar modal para edición
            document.getElementById("modal-title").textContent = "Editar Asignación de Periférico";
            document.getElementById("accion").value = "editar";

            // Obtener datos del dataset
            const idAsignacion = this.dataset.idAsignacionPeriferico;
            const idPersona = this.dataset.idPersona;
            const idPeriferico = this.dataset.idPeriferico;
            const fechaAsignacion = this.dataset.fechaAsignacion;
            const observaciones = this.dataset.observaciones;

            console.log("Datos a cargar:", {
                idAsignacion,
                idPersona,
                idPeriferico,
                fechaAsignacion,
                observaciones
            });

            // Llenar campos del formulario
            document.getElementById("id_asignacion_periferico").value = idAsignacion || '';
            
            // Seleccionar persona
            const personaSelect = document.getElementById("persona");
            if (personaSelect && idPersona) {
                personaSelect.value = idPersona;
                console.log("✅ Persona seleccionada:", personaSelect.value);
            }

            // Seleccionar periférico
            const perifericoSelect = document.getElementById("periferico");
            if (perifericoSelect && idPeriferico) {
                // Buscar si el periférico actual está en la lista
                let optionExists = false;
                for (let i = 0; i < perifericoSelect.options.length; i++) {
                    if (perifericoSelect.options[i].value === idPeriferico) {
                        optionExists = true;
                        break;
                    }
                }
                
                // Si no existe, agregarlo temporalmente
                if (!optionExists) {
                    const tempOption = document.createElement('option');
                    tempOption.value = idPeriferico;
                    tempOption.textContent = '(Periférico actual) - ID: ' + idPeriferico;
                    tempOption.setAttribute('data-temp', 'true');
                    perifericoSelect.insertBefore(tempOption, perifericoSelect.firstChild.nextSibling);
                }
                
                perifericoSelect.value = idPeriferico;
                console.log("✅ Periférico seleccionado:", perifericoSelect.value);
            }

            // Fecha de asignación
            const fechaInput = document.getElementById("fecha_asignacion");
            if (fechaInput && fechaAsignacion) {
                fechaInput.value = fechaAsignacion;
                console.log("✅ Fecha asignada:", fechaInput.value);
            }

            // Observaciones
            const observacionesTextarea = document.getElementById("observaciones");
            if (observacionesTextarea) {
                observacionesTextarea.value = observaciones || '';
                console.log("✅ Observaciones cargadas");
            }

            // Mostrar modal
            modal.style.display = "block";
            console.log("✅ Modal de edición mostrado");
        });
    });

    // --- botones retornar ---
    document.querySelectorAll(".btn-retornar").forEach(function (btn, index) {
        console.log(`🔄 Configurando botón retorno ${index + 1}`);
        
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log("🔄 REGISTRANDO RETORNO");
            console.log("Dataset:", this.dataset);
            
            if (!modalRetorno) {
                console.error("❌ Modal retorno no encontrado");
                return;
            }

            const idAsignacion = this.dataset.id;
            const persona = this.dataset.persona;
            const periferico = this.dataset.periferico;

            console.log("Datos retorno:", { idAsignacion, persona, periferico });

            document.getElementById("retorno_id_asignacion").value = idAsignacion;
            document.getElementById("retorno_persona").textContent = persona;
            document.getElementById("retorno_periferico").textContent = periferico;

            modalRetorno.style.display = "block";
            console.log("✅ Modal de retorno mostrado");
        });
    });

    // --- botones eliminar ---
    document.querySelectorAll(".btn-eliminar").forEach(function (btn, index) {
        console.log(`🗑️ Configurando botón eliminar ${index + 1}`);
        
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log("🗑️ ELIMINANDO ASIGNACIÓN");
            
            const id = this.dataset.id;
            const persona = this.dataset.persona;
            const periferico = this.dataset.periferico;
            const estado = this.dataset.estado;

            let mensaje = `¿Está seguro de eliminar la asignación del periférico "${periferico}" a "${persona}"?`;
            
            if (estado === "Activo") {
                mensaje += "\n\nNOTA: Esta asignación está activa. Al eliminarla, el periférico quedará disponible.";
            }

            if (confirm(mensaje)) {
                // Crear form data para envío
                const formData = new FormData();
                formData.append('accion', 'eliminar');
                formData.append('id_asignacion_periferico', id);

                // Enviar usando fetch
                fetch('../controllers/procesar_asignacionPeriferico.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Asignación de periférico eliminada exitosamente');
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error al eliminar la asignación');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ocurrió un error al procesar la solicitud');
                });
            }
        });
    });

    // --- buscador ---
    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaAsignacionesPerifericos tbody tr");

    if (buscador) {
        buscador.addEventListener("input", function () {
            const valor = buscador.value.toLowerCase();
            filas.forEach(function (fila) {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(valor) ? "" : "none";
            });
        });
    }

    // --- validación formulario retorno ---
    if (formRetorno) {
        formRetorno.addEventListener("submit", function(event) {
            const fechaRetorno = document.getElementById("fecha_retorno").value;

            if (!fechaRetorno) {
                event.preventDefault();
                alert("La fecha de retorno es obligatoria.");
                return false;
            }

            const hoy = new Date().toISOString().split('T')[0];
            if (fechaRetorno > hoy) {
                event.preventDefault();
                alert("La fecha de retorno no puede ser posterior a hoy.");
                return false;
            }

            if (!confirm("¿Está seguro de registrar el retorno de este periférico?")) {
                event.preventDefault();
                return false;
            }
        });
    }

    // --- validación formulario principal ---
    if (form) {
        form.addEventListener("submit", function(event) {
            const persona = document.getElementById("persona").value;
            const periferico = document.getElementById("periferico").value;
            const fechaAsignacion = document.getElementById("fecha_asignacion").value;

            if (!persona || !periferico || !fechaAsignacion) {
                event.preventDefault();
                alert("Por favor complete todos los campos obligatorios.");
                return false;
            }

            const hoy = new Date().toISOString().split('T')[0];
            if (fechaAsignacion > hoy) {
                event.preventDefault();
                alert("La fecha de asignación no puede ser posterior a hoy.");
                return false;
            }

            // Limpiar opciones temporales antes de enviar
            const perifericoSelect = document.getElementById("periferico");
            if (perifericoSelect) {
                const tempOptions = perifericoSelect.querySelectorAll('option[data-temp="true"]');
                tempOptions.forEach(option => option.remove());
            }
        });
    }

    // --- auto-hide mensajes ---
    const mensajeError = document.getElementById("mensajeError");
    const mensajeExito = document.getElementById("mensajeExito");
    
    if (mensajeError) {
        setTimeout(() => {
            mensajeError.style.display = "none";
        }, 10000);
    }
    
    if (mensajeExito) {
        setTimeout(() => {
            mensajeExito.style.display = "none";
        }, 5000);
    }

    // --- debug final ---
    console.log("✅ Sistema de asignaciones de periféricos configurado");
    console.log("📊 Estadísticas:");
    console.log("- Botones ver:", document.querySelectorAll(".btn-ver").length);
    console.log("- Botones editar:", document.querySelectorAll(".btn-editar").length);
    console.log("- Botones retornar:", document.querySelectorAll(".btn-retornar").length);
    console.log("- Botones eliminar:", document.querySelectorAll(".btn-eliminar").length);
});
