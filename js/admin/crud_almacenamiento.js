console.log("✅ JS de Almacenamiento cargado");

document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ JS de Almacenamiento cargado");
    
    const modal = document.getElementById("modalAlmacenamiento");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formAlmacenamiento");
    const marcaSelect = document.getElementById("id_marca");

    // Verificar si los elementos existen
    if (!modal) console.error("❌ Modal no encontrado");
    if (!marcaSelect) console.error("❌ Select de marca no encontrado");

    // Debug inicial
    console.log('=== INFORMACIÓN DE DEBUG ALMACENAMIENTO ===');
    console.log('Marcas de almacenamiento disponibles:', window.marcasAlmacenamiento);
    console.log('Opciones en el select:', marcaSelect ? marcaSelect.options.length : 'Select no encontrado');

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar Almacenamiento";
        document.getElementById("accion").value = "crear";
        form.reset();
        
        // Verificar que las marcas estén cargadas
        if (window.marcasAlmacenamiento && window.marcasAlmacenamiento.length === 0) {
            console.warn('⚠️ No hay marcas de almacenamiento disponibles');
            alert('⚠️ No hay marcas de tipo "Almacenamiento" disponibles. Por favor, cree primero una marca de este tipo.');
        }
        
        modal.style.display = "block";
    });

    // Cerrar modal con clic esc o en la X
    spanClose.onclick = () => modal.style.display = "none";
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && modal.style.display === "block") {
            modal.style.display = "none";
        }
    });
    
    document.querySelectorAll(".btn-editar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            console.log("✏️ Editando almacenamiento:", btn.dataset);
            
            document.getElementById("modal-title").textContent = "Editar Almacenamiento";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_almacenamiento").value = btn.dataset.id;
            document.getElementById("tipo").value = btn.dataset.tipo;
            document.getElementById("interfaz").value = btn.dataset.interfaz;
            document.getElementById("capacidad").value = btn.dataset.capacidad;
            document.getElementById("modelo").value = btn.dataset.modelo;
            document.getElementById("serial_number").value = btn.dataset.serial;
            
            // Seleccionar la marca
            const marcaId = btn.dataset.idMarca;
            console.log('Seleccionando marca ID:', marcaId);
            document.getElementById("id_marca").value = marcaId;

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaAlmacenamientos tbody tr");

    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });

    // Función para verificar marcas disponibles
    function verificarMarcasDisponibles() {
        const opciones = marcaSelect.options;
        let marcasValidas = 0;
        
        for (let i = 0; i < opciones.length; i++) {
            if (opciones[i].value !== "") {
                marcasValidas++;
            }
        }
        
        console.log(`📊 Marcas válidas encontradas: ${marcasValidas}`);
        
        if (marcasValidas === 0) {
            console.warn('⚠️ No hay marcas de almacenamiento disponibles');
            console.log('💡 Para solucionar esto:');
            console.log('1. Ve a "Gestión de Tipos de Marca" y crea un tipo llamado "almacenamiento"');
            console.log('2. Ve a "Gestión de Marcas" y crea marcas asociadas al tipo "almacenamiento"');
        }
        
        return marcasValidas > 0;
    }

    // Verificar marcas al cargar
    if (marcaSelect) {
        verificarMarcasDisponibles();
    }
});
