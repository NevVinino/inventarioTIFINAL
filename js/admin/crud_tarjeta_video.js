document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ JS de Tarjeta de Video cargado");
    
    const modal = document.getElementById("modalTarjeta");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formTarjeta");
    const marcaSelect = document.getElementById("id_marca");

    // Verificar si los elementos existen
    if (!modal) console.error("❌ Modal no encontrado");
    if (!marcaSelect) console.error("❌ Select de marca no encontrado");

    // Debug inicial
    console.log('=== INFORMACIÓN DE DEBUG TARJETA DE VIDEO ===');
    console.log('Marcas de Tarjeta de Video disponibles:', window.marcasTarjeta);
    console.log('Opciones en el select:', marcaSelect ? marcaSelect.options.length : 'Select no encontrado');
    
    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar Tarjeta de Video";
        document.getElementById("accion").value = "crear";
        form.reset();
        
        // Verificar que las marcas estén cargadas
        if (window.marcasTarjeta && window.marcasTarjeta.length === 0) {
            console.warn('⚠️ No hay marcas de Tarjeta de Video disponibles');
            alert('⚠️ No hay marcas de tipo "Tarjeta de Video" disponibles. Por favor, cree primero una marca de este tipo.');
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
            console.log("✏️ Editando Tarjeta de Video:", btn.dataset);
            
            document.getElementById("modal-title").textContent = "Editar Tarjeta de Video";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_tarjeta_video").value = btn.dataset.id;
            document.getElementById("modelo").value = btn.dataset.modelo;
            document.getElementById("memoria").value = btn.dataset.memoria;
            document.getElementById("tipo_memoria").value = btn.dataset.tipoMemoria;
            document.getElementById("interfaz").value = btn.dataset.interfaz;
            document.getElementById("puertos").value = btn.dataset.puertos;
            document.getElementById("serial_number").value = btn.dataset.serial;
            
            // Seleccionar la marca
            const marcaId = btn.dataset.idMarca;
            console.log('Seleccionando marca ID:', marcaId);
            document.getElementById("id_marca").value = marcaId;

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaTarjetas tbody tr");

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
            console.warn('⚠️ No hay marcas de Tarjeta de Video disponibles');
            console.log('💡 Para solucionar esto:');
            console.log('1. Ve a "Gestión de Tipos de Marca" y crea un tipo llamado "tarjeta de video", "tarjeta video" o "gpu"');
            console.log('2. Ve a "Gestión de Marcas" y crea marcas asociadas al tipo correspondiente');
        }
        
        return marcasValidas > 0;
    }

    // Verificar marcas al cargar
    if (marcaSelect) {
        verificarMarcasDisponibles();
    }
});
