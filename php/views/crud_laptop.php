<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Define la URL base del sistema
define('BASE_URL', 'http://localhost:8000');

// Recuperar datos de sesión
$id_usuario_sesion = $_SESSION['id_usuario'] ?? '';
$nombre_usuario_sesion = $_SESSION['username'] ?? '';

// Consultas para selects - MARCAS FILTRADAS PARA LAPTOP
$empresas = sqlsrv_query($conn, "SELECT id_empresa, nombre FROM empresa");

// Filtrar marcas solo para tipo "Laptop"
$sql_marcas = "SELECT m.id_marca, m.nombre 
               FROM marca m 
               INNER JOIN tipo_marca tm ON m.id_tipo_marca = tm.id_tipo_marca 
               WHERE tm.nombre = 'Laptop'
               ORDER BY m.nombre";
$marcas = sqlsrv_query($conn, $sql_marcas);

$estados = sqlsrv_query($conn, "SELECT id_estado_activo, vestado_activo FROM estado_activo");

// Agregar consultas para componentes - CORREGIDO para incluir genéricos y detallados
$sql_cpu = "
    SELECT id_procesador as id, CONCAT(m.nombre, ' ', modelo, ISNULL(' Gen ' + generacion, '')) as descripcion, 'detallado' as tipo
    FROM procesador p 
    LEFT JOIN marca m ON p.id_marca = m.id_marca
    UNION ALL
    SELECT id_procesador_generico as id, CONCAT(modelo, ISNULL(' Gen ' + generacion, '')) as descripcion, 'generico' as tipo
    FROM procesador_generico
    ORDER BY descripcion";

$sql_ram = "
    SELECT id_ram as id, CONCAT(r.capacidad, ISNULL(' ' + r.tipo, ''), ISNULL(' ' + m.nombre, '')) as descripcion, 'detallado' as tipo
    FROM RAM r 
    LEFT JOIN marca m ON r.id_marca = m.id_marca
    UNION ALL
    SELECT id_ram_generico as id, capacidad as descripcion, 'generico' as tipo
    FROM RAM_generico
    ORDER BY descripcion";

$sql_almacenamiento = "
    SELECT id_almacenamiento as id, CONCAT(s.capacidad, ISNULL(' ' + s.tipo, ''), ISNULL(' ' + m.nombre, '')) as descripcion, 'detallado' as tipo
    FROM almacenamiento s 
    LEFT JOIN marca m ON s.id_marca = m.id_marca
    UNION ALL
    SELECT id_almacenamiento_generico as id, CONCAT(capacidad, ISNULL(' ' + tipo, '')) as descripcion, 'generico' as tipo
    FROM almacenamiento_generico
    ORDER BY descripcion";

// NUEVA consulta para tarjetas de video
$sql_tarjeta_video = "
    SELECT id_tarjeta_video as id, 
           CONCAT(m.nombre, ' ', tv.modelo, ISNULL(' ' + tv.memoria, ''), ISNULL(' ' + tv.tipo_memoria, '')) as descripcion, 
           'detallado' as tipo
    FROM tarjeta_video tv
    LEFT JOIN marca m ON tv.id_marca = m.id_marca
    UNION ALL
    SELECT id_tarjeta_video_generico as id, 
           CONCAT(modelo, ISNULL(' ' + memoria, '')) as descripcion, 
           'generico' as tipo
    FROM tarjeta_video_generico
    ORDER BY descripcion";

$cpus = sqlsrv_query($conn, $sql_cpu);
$rams = sqlsrv_query($conn, $sql_ram);
$almacenamientos = sqlsrv_query($conn, $sql_almacenamiento);
$tarjetas_video = sqlsrv_query($conn, $sql_tarjeta_video); // NUEVO

// Mejorar la consulta para incluir información de slots - CORREGIDO para incluir componentes genéricos
$sql = "
SELECT DISTINCT
    a.id_activo,
    l.id_laptop,
    l.nombreEquipo,
    l.modelo,
    l.numeroSerial,
    l.mac,
    l.numeroIP,
    l.link,
    l.fechaCompra,
    l.garantia,
    l.precioCompra,
    l.antiguedad,
    l.ordenCompra,
    l.estadoGarantia,
    l.observaciones,
    m.nombre AS marca,
    m.id_marca,
    ea.vestado_activo AS estado,
    ea.id_estado_activo,
    e.nombre AS empresa,
    e.id_empresa,
    q.ruta_qr,
    -- Información de slots de CPU (incluye genéricos)
    (
        SELECT STRING_AGG(
            CASE 
                WHEN sap.id_procesador IS NOT NULL THEN CONCAT('Slot ', sa.id_slot, ': ', ISNULL(mp.nombre + ' ', '') + p.modelo + ISNULL(' ' + p.generacion, ''))
                WHEN sap.id_procesador_generico IS NOT NULL THEN CONCAT('Slot ', sa.id_slot, ': ', pg.modelo + ISNULL(' ' + pg.generacion, ''))
                ELSE CONCAT('Slot ', sa.id_slot, ': Libre')
            END, 
            ', '
        )
        FROM slot_activo sa 
        LEFT JOIN slot_activo_procesador sap ON sa.id_slot = sap.id_slot
        LEFT JOIN procesador p ON sap.id_procesador = p.id_procesador
        LEFT JOIN marca mp ON p.id_marca = mp.id_marca
        LEFT JOIN procesador_generico pg ON sap.id_procesador_generico = pg.id_procesador_generico
        WHERE sa.id_activo = a.id_activo AND sa.tipo_slot = 'PROCESADOR'
    ) as slots_cpu_texto,
    -- Información de slots de RAM (incluye genéricos)
    (
        SELECT STRING_AGG(
            CASE 
                WHEN sar.id_ram IS NOT NULL THEN CONCAT('Slot ', sa.id_slot, ': ', r.capacidad + ISNULL(' ' + r.tipo, '') + ISNULL(' ' + mr.nombre, ''))
                WHEN sar.id_ram_generico IS NOT NULL THEN CONCAT('Slot ', sa.id_slot, ': ', rg.capacidad)
                ELSE CONCAT('Slot ', sa.id_slot, ': Libre')
            END, 
            ', '
        )
        FROM slot_activo sa 
        LEFT JOIN slot_activo_ram sar ON sa.id_slot = sar.id_slot
        LEFT JOIN RAM r ON sar.id_ram = r.id_ram
        LEFT JOIN marca mr ON r.id_marca = mr.id_marca
        LEFT JOIN RAM_generico rg ON sar.id_ram_generico = rg.id_ram_generico
        WHERE sa.id_activo = a.id_activo AND sa.tipo_slot = 'RAM'
    ) as slots_ram_texto,
    -- Información de slots de almacenamiento (incluye genéricos)
    (
        SELECT STRING_AGG(
            CASE 
                WHEN saa.id_almacenamiento IS NOT NULL THEN CONCAT('Slot ', sa.id_slot, ': ', s.capacidad + ISNULL(' ' + s.tipo, '') + ISNULL(' ' + ms.nombre, ''))
                WHEN saa.id_almacenamiento_generico IS NOT NULL THEN CONCAT('Slot ', sa.id_slot, ': ', sg.capacidad + ISNULL(' ' + sg.tipo, ''))
                ELSE CONCAT('Slot ', sa.id_slot, ': Libre')
            END, 
            ', '
        )
        FROM slot_activo sa 
        LEFT JOIN slot_activo_almacenamiento saa ON sa.id_slot = saa.id_slot
        LEFT JOIN almacenamiento s ON saa.id_almacenamiento = s.id_almacenamiento
        LEFT JOIN marca ms ON s.id_marca = ms.id_marca
        LEFT JOIN almacenamiento_generico sg ON saa.id_almacenamiento_generico = sg.id_almacenamiento_generico
        WHERE sa.id_activo = a.id_activo AND sa.tipo_slot = 'ALMACENAMIENTO'
    ) as slots_almacenamiento_texto,
    -- Información de slots de tarjeta de video (incluye genéricos)
    (
        SELECT STRING_AGG(
            CASE 
                WHEN satv.id_tarjeta_video IS NOT NULL THEN CONCAT('Slot ', sa.id_slot, ': ', ISNULL(mtv.nombre + ' ', '') + tv.modelo + ISNULL(' ' + tv.memoria, '') + ISNULL(' ' + tv.tipo_memoria, ''))
                WHEN satv.id_tarjeta_video_generico IS NOT NULL THEN CONCAT('Slot ', sa.id_slot, ': ', tvg.modelo + ISNULL(' ' + tvg.memoria, ''))
                ELSE CONCAT('Slot ', sa.id_slot, ': Libre')
            END, 
            ', '
        )
        FROM slot_activo sa 
        LEFT JOIN slot_activo_tarjeta_video satv ON sa.id_slot = satv.id_slot
        LEFT JOIN tarjeta_video tv ON satv.id_tarjeta_video = tv.id_tarjeta_video
        LEFT JOIN marca mtv ON tv.id_marca = mtv.id_marca
        LEFT JOIN tarjeta_video_generico tvg ON satv.id_tarjeta_video_generico = tvg.id_tarjeta_video_generico
        WHERE sa.id_activo = a.id_activo AND sa.tipo_slot = 'TARJETA_VIDEO'
    ) as slots_tarjeta_video_texto
FROM activo a
INNER JOIN laptop l ON a.id_laptop = l.id_laptop
LEFT JOIN marca m ON l.id_marca = m.id_marca
LEFT JOIN estado_activo ea ON l.id_estado_activo = ea.id_estado_activo
LEFT JOIN empresa e ON l.id_empresa = e.id_empresa
LEFT JOIN qr_activo q ON a.id_activo = q.id_activo
WHERE a.tipo_activo = 'Laptop'
";

$activos = sqlsrv_query($conn, $sql);
if ($activos === false) {
    die("Error en consulta de activos: " . print_r(sqlsrv_errors(), true));
}

// Comprobar y mostrar el número de filas encontradas
$hay_resultados = false;
$num_filas = 0;

// Contar filas manualmente ya que sqlsrv_has_rows puede ser inconsistente
$filas_temp = [];
while ($fila = sqlsrv_fetch_array($activos, SQLSRV_FETCH_ASSOC)) {
    $hay_resultados = true;
    $num_filas++;
    $filas_temp[] = $fila;
}

// Restaurar el cursor para el bucle principal
$activos = $filas_temp;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Laptops</title>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">

</head>
<body>

    <!-- Incluir Header -->
    <?php include('includes/header.php'); ?>

    <!-- Incluir Sidebar -->
    <?php include('includes/sidebar.php'); ?>

    <main class="main-content" id="mainContent">

        <a href="vista_admin.php" class="back-button">
            <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
        </a>

        <div class="main-container">
            <div class="top-bar">
                <h2>Crear nueva Laptop</h2>
                <input type="text" id="buscador" placeholder="Buscar laptop">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <!-- Tabla de laptops -->
            <table id="tablaLaptops">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Nombre Equipo</th>
                        <th>Modelo</th>
                        <th>Serial</th>
                        <th>MAC</th>
                        <th>IP</th>
                        <th>Estado</th>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>Asistente TI</th>
                        <th>Opciones QR</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    
                    // Si no hay resultados, mostrar una fila vacía indicando que no hay datos
                    if (count($activos) === 0) {
                        echo "<tr><td colspan='12' style='text-align:center;'>No se encontraron laptops registradas</td></tr>";
                    }
                    
                    foreach ($activos as $a) { 
                        $estado_clase = '';
                        // Determinar la clase CSS según el estado
                        if (isset($a['estado'])) {
                            switch(strtolower($a['estado'])) {
                                case 'disponible':
                                    $estado_clase = 'estado-disponible';
                                    break;
                                case 'asignado':
                                    $estado_clase = 'estado-asignado';
                                    break;
                                case 'malogrado':
                                    $estado_clase = 'estado-malogrado';
                                    break;
                                case 'almacen':
                                    $estado_clase = 'estado-almacen';
                                    break;
                            }
                        }
                        
                        // Manejar fechas
                        $fecha_compra = "";
                        $fecha_garantia = "";
                        
                        if (isset($a['fechaCompra']) && $a['fechaCompra'] !== null) {
                            if ($a['fechaCompra'] instanceof DateTime) {
                                $fecha_compra = $a['fechaCompra']->format('Y-m-d');
                            } elseif (is_array($a['fechaCompra']) && isset($a['fechaCompra']['date'])) {
                                $fecha_compra = date('Y-m-d', strtotime($a['fechaCompra']['date']));
                            }
                        }
                        
                        if (isset($a['garantia']) && $a['garantia'] !== null) {
                            if ($a['garantia'] instanceof DateTime) {
                                $fecha_garantia = $a['garantia']->format('Y-m-d');
                            } elseif (is_array($a['garantia']) && isset($a['garantia']['date'])) {
                                $fecha_garantia = date('Y-m-d', strtotime($a['garantia']['date']));
                            }
                        }
                    ?>
                    <tr class="<?= $estado_clase ?>">
                        <td><?= $counter++ ?></td>
                        <td><?= htmlspecialchars($a['nombreEquipo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['modelo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['numeroSerial'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['mac'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['numeroIP'] ?? '') ?></td>
                        <td class="estado-celda"><?= htmlspecialchars($a['estado'] ?? '') ?></td>
                        <td>Laptop</td>
                        <td><?= htmlspecialchars($a['marca'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['empresa'] ?? '') ?></td>
                        
                        <!-- Nueva columna Opciones QR -->
                        <td>
                            <div class="opciones-qr">
                                <?php if(isset($a['ruta_qr']) && !empty($a['ruta_qr'])): ?>
                                    <!-- QR ya existe, mostrar botón de descarga Y regenerar -->
                                    <a href="../../<?= htmlspecialchars($a['ruta_qr']) ?>" download class="btn-text btn-qr-download" data-id="<?= htmlspecialchars($a['id_activo']) ?>" title="Descargar código QR existente">
                                        Descargar QR
                                    </a>
                                    <button type="button" class="btn-text btn-qr-regenerate" data-id="<?= htmlspecialchars($a['id_activo']) ?>" title="Regenerar código QR">
                                        Regenerar QR
                                    </button>
                                <?php else: ?>
                                    <!-- QR no existe, mostrar botón para generar -->
                                    <button type="button" class="btn-text btn-qr-generate" data-id="<?= htmlspecialchars($a['id_activo']) ?>" title="Generar nuevo código QR">
                                        Generar QR
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        
                        <td>
                            <div class="acciones">
                                <!-- Botón ver -->
                                <button type="button" class="btn-icon btn-ver" 
                                    data-id="<?= htmlspecialchars($a['id_activo']) ?>"
                                    data-nombreequipo="<?= htmlspecialchars($a['nombreEquipo'] ?? '') ?>"
                                    data-modelo="<?= htmlspecialchars($a['modelo'] ?? '') ?>"
                                    data-mac="<?= htmlspecialchars($a['mac'] ?? '') ?>"
                                    data-serial="<?= htmlspecialchars($a['numeroSerial'] ?? '') ?>"
                                    data-ip="<?= htmlspecialchars($a['numeroIP'] ?? '') ?>"
                                    data-link="<?= htmlspecialchars($a['link'] ?? '') ?>"
                                    data-estado="<?= htmlspecialchars($a['estado'] ?? '') ?>"
                                    data-tipo="Laptop"
                                    data-marca="<?= htmlspecialchars($a['marca'] ?? '') ?>"
                                    data-empresa="<?= htmlspecialchars($a['empresa'] ?? 'No asignado') ?>"
                                    data-fechacompra="<?= htmlspecialchars($fecha_compra) ?>"
                                    data-garantia="<?= htmlspecialchars($fecha_garantia) ?>"
                                    data-preciocompra="<?= htmlspecialchars($a['precioCompra'] ?? '') ?>"
                                    data-antiguedad="<?= htmlspecialchars($a['antiguedad'] ?? '') ?>"
                                    data-ordencompra="<?= htmlspecialchars($a['ordenCompra'] ?? '') ?>"
                                    data-estadogarantia="<?= htmlspecialchars($a['estadoGarantia'] ?? '') ?>"
                                    data-observaciones="<?= htmlspecialchars($a['observaciones'] ?? '') ?>"
                                    data-asistente="<?= htmlspecialchars($nombre_usuario_sesion) ?>"
                                    data-cpu="<?= htmlspecialchars($a['slots_cpu_texto'] ?? 'No especificado') ?>"
                                    data-ram="<?= htmlspecialchars($a['slots_ram_texto'] ?? 'No especificado') ?>"
                                    data-almacenamiento="<?= htmlspecialchars($a['slots_almacenamiento_texto'] ?? 'No especificado') ?>"
                                    data-tarjeta_video="<?= htmlspecialchars($a['slots_tarjeta_video_texto'] ?? 'No especificado') ?>"
                                    data-qr-link="<?= htmlspecialchars(BASE_URL . '/php/views/user/detalle_activo_publico.php?id=' . ($a['id_activo'] ?? '')) ?>"
                                    <?php if(isset($a['ruta_qr']) && !empty($a['ruta_qr'])): ?>
                                    data-qr="<?= htmlspecialchars($a['ruta_qr']) ?>"
                                    <?php endif; ?>
                                >
                                    <img src="../../img/ojo.png" alt="Ver">
                                </button>

                                <!-- Botón editar -->
                                <button type="button" class="btn-icon btn-editar"
                                    data-id="<?= htmlspecialchars($a['id_activo']) ?>"
                                    data-nombreequipo="<?= htmlspecialchars($a['nombreEquipo'] ?? '') ?>"
                                    data-modelo="<?= htmlspecialchars($a['modelo'] ?? '') ?>"
                                    data-mac="<?= htmlspecialchars($a['mac'] ?? '') ?>"
                                    data-serial="<?= htmlspecialchars($a['numeroSerial'] ?? '') ?>"
                                    data-fechacompra="<?= htmlspecialchars($fecha_compra) ?>"
                                    data-garantia="<?= htmlspecialchars($fecha_garantia) ?>"
                                    data-precio="<?= htmlspecialchars($a['precioCompra'] ?? '') ?>"
                                    data-antiguedad="<?= htmlspecialchars($a['antiguedad'] ?? '') ?>"
                                    data-orden="<?= htmlspecialchars($a['ordenCompra'] ?? '') ?>"
                                    data-estadogarantia="<?= htmlspecialchars($a['estadoGarantia'] ?? '') ?>"
                                    data-ip="<?= htmlspecialchars($a['numeroIP'] ?? '') ?>"
                                    data-link="<?= htmlspecialchars($a['link'] ?? '') ?>"
                                    data-observaciones="<?= htmlspecialchars($a['observaciones'] ?? '') ?>"
                                    data-marca="<?= htmlspecialchars($a['id_marca'] ?? '') ?>"
                                    data-estadoactivo="<?= htmlspecialchars($a['id_estado_activo'] ?? '') ?>"
                                    data-empresa="<?= htmlspecialchars($a['id_empresa'] ?? '') ?>"
                                >
                                    <img src="../../img/editar.png" alt="Editar">
                                </button>

                                <?php if (!isset($a['estado']) || strtolower($a['estado']) !== 'asignado'): ?>
                                    <!-- Botón eliminar (solo visible si no está asignado) -->
                                    <form method="POST" action="../controllers/procesar_laptop.php" onsubmit="return confirm('¿Eliminar este activo?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_activo" value="<?= htmlspecialchars($a['id_activo']) ?>">
                                        <button type="submit" class="btn-icon">
                                            <img src="../../img/eliminar.png" alt="Eliminar">
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </main>

<!-- Modal para registrar/editar activo -->
<div id="modalActivo" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 id="modal-title">Registrar Laptop</h3>
        
        <form id="formActivo" method="POST" action="../controllers/procesar_laptop.php">
            <input type="hidden" name="accion" id="accion" value="crear">
            <input type="hidden" name="id_activo" id="id_activo">
            
            <label>Usuario TI Responsable:</label>
            <input type="text" value="<?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '' ?>" readonly>
            <input type="hidden" name="id_usuario" value="<?= isset($_SESSION['id_usuario']) ? htmlspecialchars($_SESSION['id_usuario']) : '' ?>">

            <label>Nombre Equipo:</label>
            <input type="text" name="nombreEquipo" id="nombreEquipo" required>

            <label>Modelo:</label>
            <input type="text" name="modelo" id="modelo" required>

            <label>MAC:</label>
            <input type="text" name="mac" id="mac">

            <label>Serial:</label>
            <input type="text" name="numberSerial" id="numberSerial" required>

            <label>Fecha Compra:</label>
            <input type="date" name="fechaCompra" id="fechaCompra">

            <label>Garantía Hasta:</label>
            <input type="date" name="garantia" id="garantia">

            <label>Precio Compra:</label>
            <input type="number" name="precioCompra" id="precioCompra" step="0.01">

            <label>
                Antigüedad en días: 
                <span id="antiguedadLegible" class="antiguedad-label">(No calculado)</span>
            </label>
            <input type="text" name="antiguedad" id="antiguedad" readonly>

            <label>Orden de Compra:</label>
            <input type="text" name="ordenCompra" id="ordenCompra">

            <div class="estado-garantia-container">
                <label>Estado Garantía:</label>
                <input type="hidden" name="estadoGarantia" id="estadoGarantia">
                <div id="estadoGarantiaLabel" class="estado-garantia-label">(No calculado)</div>
            </div>

            <label>IP:</label>
            <input type="text" name="numeroIP" id="numeroIP">

            <label>Link de Modelo:</label>
            <input type="url" name="link" id="link" placeholder="https://ejemplo.com">

            <label>Observaciones:</label>
            <button type="button" id="toggleObservaciones" class="btn-toggle">Mostrar</button>
            <div id="contenedorObservaciones" style="display: none;">
                <textarea name="observaciones" id="observaciones" rows="3" cols="50"></textarea>
            </div>
            <br>
            <?php
            // Función select simplificada
            function select($name, $dataset, $id_field, $desc_field, $customLabel = null) {
                if ($dataset === false) return;
                
                $labelText = $customLabel ?? ucfirst(str_replace('_', ' ', $name));
                echo "<label>$labelText:</label>";
                echo "<select name='$name' id='$name' required>";
                echo "<option value=''>Seleccione...</option>";
                while ($r = sqlsrv_fetch_array($dataset, SQLSRV_FETCH_ASSOC)) {
                    $val = $r[$id_field] ?? '';
                    $txt = $r[$desc_field] ?? '';
                    echo "<option value='" . htmlspecialchars($val) . "'>" . htmlspecialchars($txt) . "</option>";
                }
                echo "</select>";
            }

            // Solo mostrar los selects necesarios para laptop
            select("id_empresa", $empresas, "id_empresa", "nombre", "Empresa");
            select("id_marca", $marcas, "id_marca", "nombre", "Marca");
            select("id_estado_activo", $estados, "id_estado_activo", "vestado_activo", "Estado");
            ?>

            <div class="componentes-section">
                <h4>Configuración de Slots</h4>
                
                <!-- Toggle para filtrar tipos de componentes -->
                <div class="filtro-componentes">
                    <label>Filtrar componentes:</label>
                    <button type="button" id="toggleTipoComponente" class="btn-toggle-tipo" data-tipo="todos">
                        Mostrar Todos
                    </button>
                    <span id="estadoFiltro" class="estado-filtro">(Genéricos y Detallados)</span>
                </div>
                
                <div class="slots-config">
                    <label>Cantidad de slots de CPU:</label>
                    <input type="number" name="slots_cpu" id="slots_cpu" min="1" max="2" value="1" required>
                    
                    <label>Cantidad de slots de RAM:</label>
                    <input type="number" name="slots_ram" id="slots_ram" min="1" max="4" value="2" required>
                    
                    <label>Cantidad de slots de Almacenamiento:</label>
                    <input type="number" name="slots_almacenamiento" id="slots_almacenamiento" min="1" max="2" value="1" required>
                    
                    <!-- NUEVO: Agregar campo para tarjetas de video en laptops -->
                    <label>Cantidad de slots de Tarjeta de Video:</label>
                    <input type="number" name="slots_tarjeta_video" id="slots_tarjeta_video" min="0" max="2" value="0">
                </div>

                <div id="slots-container" style="display: none;">
                    <h5>Asignar Componentes a Slots</h5>
                    <div id="slots-cpu-container"></div>
                    <div id="slots-ram-container"></div>
                    <div id="slots-almacenamiento-container"></div>
                    <div id="slots-tarjeta_video-container"></div> <!-- NUEVO -->
                </div>
                
                <!-- Componentes disponibles para los slots - ACTUALIZADO con tarjetas de video -->
                <div style="display: none;">
                    <select id="source-cpu" data-tipo-actual="todos">
                        <option value="">Seleccione un procesador...</option>
                        <?php 
                        $cpus = sqlsrv_query($conn, $sql_cpu);
                        while ($cpu = sqlsrv_fetch_array($cpus, SQLSRV_FETCH_ASSOC)): 
                        ?>
                            <option value="<?= $cpu['tipo'] ?>_<?= $cpu['id'] ?>" 
                                    data-tipo="<?= $cpu['tipo'] ?>"
                                    data-descripcion="<?= htmlspecialchars($cpu['descripcion']) ?>">
                                <?= htmlspecialchars($cpu['descripcion']) ?>
                                <?= $cpu['tipo'] == 'generico' ? ' (Genérico)' : ' (Detallado)' ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <select id="source-ram" data-tipo-actual="todos">
                        <option value="">Seleccione memoria RAM...</option>
                        <?php 
                        $rams = sqlsrv_query($conn, $sql_ram);
                        while ($ram = sqlsrv_fetch_array($rams, SQLSRV_FETCH_ASSOC)): 
                        ?>
                            <option value="<?= $ram['tipo'] ?>_<?= $ram['id'] ?>" 
                                    data-tipo="<?= $ram['tipo'] ?>"
                                    data-descripcion="<?= htmlspecialchars($ram['descripcion']) ?>">
                                <?= htmlspecialchars($ram['descripcion']) ?>
                                <?= $ram['tipo'] == 'generico' ? ' (Genérico)' : ' (Detallado)' ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <select id="source-almacenamiento" data-tipo-actual="todos">
                        <option value="">Seleccione almacenamiento...</option>
                        <?php 
                        $almacenamientos = sqlsrv_query($conn, $sql_almacenamiento);
                        while ($almacenamiento = sqlsrv_fetch_array($almacenamientos, SQLSRV_FETCH_ASSOC)): 
                        ?>
                            <option value="<?= $almacenamiento['tipo'] ?>_<?= $almacenamiento['id'] ?>" 
                                    data-tipo="<?= $almacenamiento['tipo'] ?>"
                                    data-descripcion="<?= htmlspecialchars($almacenamiento['descripcion']) ?>">
                                <?= htmlspecialchars($almacenamiento['descripcion']) ?>
                                <?= $almacenamiento['tipo'] == 'generico' ? ' (Genérico)' : ' (Detallado)' ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <!-- NUEVO: Select para tarjetas de video -->
                    <select id="source-tarjeta_video" data-tipo-actual="todos">
                        <option value="">Seleccione tarjeta de video...</option>
                        <?php 
                        while ($tarjeta = sqlsrv_fetch_array($tarjetas_video, SQLSRV_FETCH_ASSOC)): 
                        ?>
                            <option value="<?= $tarjeta['tipo'] ?>_<?= $tarjeta['id'] ?>" 
                                    data-tipo="<?= $tarjeta['tipo'] ?>"
                                    data-descripcion="<?= htmlspecialchars($tarjeta['descripcion']) ?>">
                                <?= htmlspecialchars($tarjeta['descripcion']) ?>
                                <?= $tarjeta['tipo'] == 'generico' ? ' (Genérico)' : ' (Detallado)' ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <!-- Update hidden inputs para slots -->
            <input type="hidden" name="slots_data" id="slotsDataHidden">

            <br>
            <button type="submit">Guardar</button>
        </form>
    </div>
</div>

<!-- Modal para ver detalles (agregar antes del script) -->
<div id="modalVisualizacion" class="modal">
    <div class="modal-content detalles">
        <span class="close close-view">&times;</span>
        <h3>Detalles del Activo - Laptop</h3>
        
        <div class="detalles-grid">
            <!-- Información básica del equipo -->
            <div class="seccion-detalles">
                <h4>Información Básica</h4>
                <div class="detalle-item">
                    <strong>Nombre del Equipo:</strong>
                    <span id="view-nombreequipo"></span>
                </div>
                <div class="detalle-item">
                    <strong>Modelo:</strong>
                    <span id="view-modelo"></span>
                </div>
                <div class="detalle-item">
                    <strong>Marca:</strong>
                    <span id="view-marca"></span>
                </div>
                <div class="detalle-item">
                    <strong>Número de Serie:</strong>
                    <span id="view-serial"></span>
                </div>
                <div class="detalle-item">
                    <strong>Tipo de Activo:</strong>
                    <span id="view-tipo"></span>
                </div>
            </div>

            <!-- Información de red -->
            <div class="seccion-detalles">
                <h4>Configuración de Red</h4>
                <div class="detalle-item">
                    <strong>Dirección MAC:</strong>
                    <span id="view-mac"></span>
                </div>
                <div class="detalle-item">
                    <strong>Dirección IP:</strong>
                    <span id="view-ip"></span>
                </div>
                <div class="detalle-item">
                    <strong>Link de Modelo:</strong>
                    <span id="view-link"></span>
                </div>
            </div>

            <!-- Información administrativa -->
            <div class="seccion-detalles">
                <h4>Información Administrativa</h4>
                <div class="detalle-item">
                    <strong>Estado:</strong>
                    <span id="view-estado"></span>
                </div>
                <div class="detalle-item">
                    <strong>Empresa:</strong>
                    <span id="view-empresa"></span>
                </div>
                <div class="detalle-item">
                    <strong>Asistente TI (Registró el activo):</strong>
                    <span id="view-asistente"></span>
                </div>
                <div class="detalle-item">
                    <strong>Fecha de Compra:</strong>
                    <span id="view-fechacompra"></span>
                </div>
                <div class="detalle-item">
                    <strong>Precio de Compra:</strong>
                    <span id="view-preciocompra"></span>
                </div>
                <div class="detalle-item">
                    <strong>Orden de Compra:</strong>
                    <span id="view-ordencompra"></span>
                </div>
            </div>

            <!-- Información de garantía -->
            <div class="seccion-detalles">
                <h4>Información de Garantía</h4>
                <div class="detalle-item">
                    <strong>Garantía Hasta:</strong>
                    <span id="view-garantia"></span>
                </div>
                <div class="detalle-item">
                    <strong>Estado de Garantía:</strong>
                    <span id="view-estadogarantia"></span>
                </div>
                <div class="detalle-item">
                    <strong>Antigüedad:</strong>
                    <span id="view-antiguedad"></span>
                </div>
            </div>

            <!-- Especificaciones técnicas -->
            <div class="seccion-detalles">
                <h4>Especificaciones Técnicas</h4>
                <div class="detalle-item">
                    <strong>Procesador (CPU):</strong>
                    <span id="view-cpu"></span>
                </div>
                <div class="detalle-item">
                    <strong>Memoria RAM:</strong>
                    <span id="view-ram"></span>
                </div>
                <div class="detalle-item">
                    <strong>Almacenamiento:</strong>
                    <span id="view-almacenamiento"></span>
                </div>
                <div class="detalle-item">
                    <strong>Tarjeta de Video:</strong>
                    <span id="view-tarjeta_video"></span>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="seccion-detalles">
                <h4>Observaciones</h4>
                <div class="detalle-item observaciones-item">
                    <div id="view-observaciones" class="observaciones-texto"></div>
                </div>
            </div>

            <!-- Código QR -->
            <div class="seccion-detalles qr-section">
                <h4>Código QR</h4>
                <div class="detalle-item qr-container">
                    <div id="view-qr"></div>
                    <a id="download-qr" href="#" download class="btn-download">
                        Descargar QR
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="../../js/admin/crud_laptop.js"></script>
    <!-- Incluir JavaScript del Sidebar -->
    <script src="../../js/admin/sidebar.js"></script>

</body>
</html>