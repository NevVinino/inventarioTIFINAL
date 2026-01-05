document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ JS de Procesador cargado");
    
    const modal = document.getElementById("modalProcesador");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formProcesador");
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
    console.log('=== INFORMACIÓN DE DEBUG PROCESADOR ===');
    console.log('Marcas de procesador disponibles:', window.marcasProcesador);
    console.log('Opciones en el select:', marcaSelect ? marcaSelect.options.length : 'Select no encontrado');

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar Procesador";
        document.getElementById("accion").value = "crear";
        form.reset();
        
        // Verificar que las marcas estén cargadas
        if (window.marcasProcesador && window.marcasProcesador.length === 0) {
            console.warn('⚠️ No hay marcas de procesador disponibles');
            alert('⚠️ No hay marcas de tipo "Procesador" disponibles. Por favor, cree primero una marca de este tipo.');
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
            console.log("✏️ Editando procesador:", btn.dataset);
            
            document.getElementById("modal-title").textContent = "Editar Procesador";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_procesador").value = btn.dataset.id;
            document.getElementById("modelo").value = btn.dataset.modelo;
            document.getElementById("generacion").value = btn.dataset.generacion;
            document.getElementById("nucleos").value = btn.dataset.nucleos;
            document.getElementById("hilos").value = btn.dataset.hilos;
            document.getElementById("part_number").value = btn.dataset.partnumber;
            
            // Seleccionar la marca
            const marcaId = btn.dataset.idMarca;
            console.log('Seleccionando marca ID:', marcaId);
            document.getElementById("id_marca").value = marcaId;

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaProcesadores tbody tr");

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
            console.warn('⚠️ No hay marcas de procesador disponibles');
            console.log('💡 Para solucionar esto:');
            console.log('1. Ve a "Gestión de Tipos de Marca" y crea un tipo llamado "procesador"');
            console.log('2. Ve a "Gestión de Marcas" y crea marcas asociadas al tipo "procesador"');
        }
        
        return marcasValidas > 0;
    }

    // Verificar marcas al cargar
    if (marcaSelect) {
        verificarMarcasDisponibles();
    }
});