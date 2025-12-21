// crud_reparacion.js
// ✅ Archivo principal de Reparaciones - Versión simplificada
// Este es el único archivo que debes importar en crud_reparacion.php

console.log("✅ Iniciando sistema de reparaciones...");

// Arranque del sistema cuando el DOM está listo
document.addEventListener("DOMContentLoaded", () => {
    console.log("✅ JS de Reparaciones cargado");
    
    // Configurar funcionalidad de modales mejorada
    configurarModalMejorado();
    
    // Inicializar sistema básico
    if (typeof inicializarSistema === 'function') {
        inicializarSistema();
    } else {
        console.log("Sistema de reparaciones listo");
    }
});

// Función para configurar funcionalidad mejorada de modales
function configurarModalMejorado() {
    // Cerrar modales con tecla Escape
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
            // Buscar todos los modales visibles
            const modalesVisibles = document.querySelectorAll('.modal[style*="block"], .modal.show');
            modalesVisibles.forEach(modal => {
                modal.style.display = "none";
                if (modal.classList.contains('show')) {
                    modal.classList.remove('show');
                }
            });
            
            // Modales específicos de reparación
            const modalIds = ['modalReparacion', 'modalDetalles', 'modalCambios', 'modalComponentes'];
            modalIds.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && modal.style.display === "block") {
                    modal.style.display = "none";
                }
            });
        }
    });
    
    // Configurar botones de cierre
    document.querySelectorAll('.close, .btn-close, [data-dismiss="modal"]').forEach(closeBtn => {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
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
    
    console.log("✅ Funcionalidad de modales mejorada configurada");
}
