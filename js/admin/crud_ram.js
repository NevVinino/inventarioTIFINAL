document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ JS de RAM cargado");
    
    const modal = document.getElementById("modalRam");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formRam");
    const marcaSelect = document.getElementById("id_marca");

    // Asegurar que el modal quede cerrado al cargar, evitando que el overlay oscurezca la vista
    if (modal) modal.style.display = "none";

    // Verificar si hay un mensaje de error en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const errorMsg = urlParams.get('error');
    if (errorMsg) {
        alert(errorMsg);
        if (modal) modal.style.display = "none"; // evitar overlay visible tras la alerta
        // Limpiar el parámetro de la URL sin recargar la página
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Verificar si los elementos existen
    if (!modal) console.error("❌ Modal no encontrado");
    if (!marcaSelect) console.error("❌ Select de marca no encontrado");

    // Debug inicial
    console.log('=== INFORMACIÓN DE DEBUG RAM ===');
    console.log('Marcas de RAM disponibles:', window.marcasRAM);
    console.log('Opciones en el select:', marcaSelect ? marcaSelect.options.length : 'Select no encontrado');
    
    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar RAM";
        document.getElementById("accion").value = "crear";
        form.reset();
        
        // Verificar que las marcas estén cargadas
        if (window.marcasRAM && window.marcasRAM.length === 0) {
            console.warn('⚠️ No hay marcas de RAM disponibles');
            alert('⚠️ No hay marcas de tipo "RAM" disponibles. Por favor, cree primero una marca de este tipo.');
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
            console.log("✏️ Editando RAM:", btn.dataset);
            
            document.getElementById("modal-title").textContent = "Editar RAM";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_ram").value = btn.dataset.id;
            document.getElementById("capacidad").value = btn.dataset.capacidad;
            document.getElementById("tipo").value = btn.dataset.tipo;
            document.getElementById("frecuencia").value = btn.dataset.frecuencia;
            document.getElementById("serial_number").value = btn.dataset.serial;
            
            // Seleccionar la marca
            const marcaId = btn.dataset.idMarca;
            console.log('Seleccionando marca ID:', marcaId);
            document.getElementById("id_marca").value = marcaId;

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaRams tbody tr");

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
            console.warn('⚠️ No hay marcas de RAM disponibles');
            console.log('💡 Para solucionar esto:');
            console.log('1. Ve a "Gestión de Tipos de Marca" y crea un tipo llamado "ram"');
            console.log('2. Ve a "Gestión de Marcas" y crea marcas asociadas al tipo "ram"');
        }
        
        return marcasValidas > 0;
    }

    // Verificar marcas al cargar
    if (marcaSelect) {
        verificarMarcasDisponibles();
    }
});