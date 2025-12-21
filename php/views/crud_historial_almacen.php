<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Recuperar datos de sesión
$id_usuario_sesion = $_SESSION['id_usuario'] ?? '';
$nombre_usuario_sesion = $_SESSION['username'] ?? '';

// Consulta para obtener almacenes
$almacenes = sqlsrv_query($conn, "SELECT id_almacen, nombre FROM almacen ORDER BY nombre");

// Consulta para obtener activos disponibles (no en almacén y no asignados)
$sql_activos_disponibles = "
SELECT DISTINCT
    a.id_activo,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN CONCAT('Laptop - ', l.nombreEquipo, ' (', l.numeroSerial, ')')
        WHEN a.tipo_activo = 'PC' THEN CONCAT('PC - ', p.nombreEquipo, ' (', p.numeroSerial, ')')
        WHEN a.tipo_activo = 'Servidor' THEN CONCAT('Servidor - ', s.nombreEquipo, ' (', s.numeroSerial, ')')
    END as descripcion_activo,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN ea_l.vestado_activo
        WHEN a.tipo_activo = 'PC' THEN ea_p.vestado_activo
        WHEN a.tipo_activo = 'Servidor' THEN ea_s.vestado_activo
    END as estado_actual
FROM activo a
LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
LEFT JOIN pc p ON a.id_pc = p.id_pc
LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
LEFT JOIN estado_activo ea_l ON l.id_estado_activo = ea_l.id_estado_activo
LEFT JOIN estado_activo ea_p ON p.id_estado_activo = ea_p.id_estado_activo
LEFT JOIN estado_activo ea_s ON s.id_estado_activo = ea_s.id_estado_activo
WHERE a.id_activo NOT IN (
    -- No debe estar en almacén actualmente
    SELECT id_activo FROM historial_almacen WHERE fecha_salida IS NULL
    UNION
    -- No debe estar asignado actualmente
    SELECT id_activo FROM asignacion WHERE fecha_retorno IS NULL
)
AND (
    (a.tipo_activo = 'Laptop' AND ea_l.vestado_activo IN ('Disponible', 'Malogrado')) OR
    (a.tipo_activo = 'PC' AND ea_p.vestado_activo IN ('Disponible', 'Malogrado')) OR
    (a.tipo_activo = 'Servidor' AND ea_s.vestado_activo IN ('Disponible', 'Malogrado'))
)
ORDER BY descripcion_activo";

$activos_disponibles = sqlsrv_query($conn, $sql_activos_disponibles);

// Consulta principal para historial de almacén
$sql_historial = "
SELECT 
    h.id_historial,
    h.id_activo,
    h.fecha_ingreso,
    h.fecha_salida,
    h.observaciones,
    alm.nombre as almacen_nombre,
    alm.id_almacen,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN CONCAT('Laptop - ', l.nombreEquipo, ' (', l.numeroSerial, ')')
        WHEN a.tipo_activo = 'PC' THEN CONCAT('PC - ', p.nombreEquipo, ' (', p.numeroSerial, ')')
        WHEN a.tipo_activo = 'Servidor' THEN CONCAT('Servidor - ', s.nombreEquipo, ' (', s.numeroSerial, ')')
    END as descripcion_activo,
    a.tipo_activo,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN l.numeroSerial
        WHEN a.tipo_activo = 'PC' THEN p.numeroSerial
        WHEN a.tipo_activo = 'Servidor' THEN s.numeroSerial
    END as numero_serial,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN ea_l.vestado_activo
        WHEN a.tipo_activo = 'PC' THEN ea_p.vestado_activo
        WHEN a.tipo_activo = 'Servidor' THEN ea_s.vestado_activo
    END as estado_actual
FROM historial_almacen h
INNER JOIN almacen alm ON h.id_almacen = alm.id_almacen
INNER JOIN activo a ON h.id_activo = a.id_activo
LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
LEFT JOIN pc p ON a.id_pc = p.id_pc
LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
LEFT JOIN estado_activo ea_l ON l.id_estado_activo = ea_l.id_estado_activo
LEFT JOIN estado_activo ea_p ON p.id_estado_activo = ea_p.id_estado_activo
LEFT JOIN estado_activo ea_s ON s.id_estado_activo = ea_s.id_estado_activo
ORDER BY h.fecha_ingreso DESC";

$historial = sqlsrv_query($conn, $sql_historial);
if ($historial === false) {
    die("Error en consulta de historial: " . print_r(sqlsrv_errors(), true));
}

$filas_historial = [];
while ($fila = sqlsrv_fetch_array($historial, SQLSRV_FETCH_ASSOC)) {
    $filas_historial[] = $fila;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Almacén</title>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
</head>
<body>

    <!-- Incluir Header -->
    <?php include('includes/header.php'); ?>

    <!-- Incluir Sidebar -->
    <?php include('includes/sidebar.php'); ?>

<!-- Mensajes de estado -->
<?php if (isset($_GET['success'])): ?>
    <div class="alerta-exito">
        <?php 
        switch($_GET['success']) {
            case 'ingreso': echo 'Activo ingresado al almacén correctamente.'; break;
            case 'salida': echo 'Salida del almacén registrada correctamente.'; break;
            case 'editado': echo 'Registro actualizado correctamente.'; break;
            case 'eliminado': echo 'Registro eliminado correctamente.'; break;
            default: echo 'Operación realizada correctamente.'; break;
        }
        ?>
    </div>

<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="mensaje-error">
        Error: <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<main class="main-content" id="mainContent">
    <a href="vista_admin.php" class="back-button">
        <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
    </a>

    <div class="main-container">
        <div class="top-bar">
            <h2>Historial de Almacén</h2>
            <input type="text" id="buscador" placeholder="Buscar en historial">
            <button id="btnIngresar">+ INGRESAR ACTIVO</button>
        </div>

        <!-- Tabla de historial -->
        <table id="tablaHistorial">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Activo</th>
                    <th>Tipo</th>
                    <th>Número de Serie</th>
                    <th>Almacén</th>
                    <th>Fecha Ingreso</th>
                    <th>Fecha Salida</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                
                if (count($filas_historial) === 0) {
                    echo "<tr><td colspan='9' style='text-align:center;'>No se encontraron registros en el historial</td></tr>";
                }
                
                foreach ($filas_historial as $h) { 
                    $estado_movimiento = $h['fecha_salida'] ? 'Finalizado' : 'En Almacén';
                    $estado_clase = $estado_movimiento === 'En Almacén' ? 'estado-almacen' : 'estado-disponible';
                    
                    // Formatear fechas
                    $fecha_ingreso = $h['fecha_ingreso'] ? $h['fecha_ingreso']->format('d/m/Y') : '-';
                    $fecha_salida = $h['fecha_salida'] ? $h['fecha_salida']->format('d/m/Y') : 'En almacén';
                ?>
                <tr class="<?= $estado_clase ?>">
                    <td><?= $counter++ ?></td>
                    <td><?= htmlspecialchars($h['descripcion_activo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($h['tipo_activo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($h['numero_serial'] ?? '') ?></td>
                    <td><?= htmlspecialchars($h['almacen_nombre'] ?? '') ?></td>
                    <td><?= $fecha_ingreso ?></td>
                    <td><?= $fecha_salida ?></td>
                    <td class="estado-celda"><?= $estado_movimiento ?></td>
                    <td>
                        <div class="acciones">
                            <!-- Botón editar -->
                            <button type="button" class="btn-icon btn-editar"
                                data-id="<?= htmlspecialchars($h['id_historial']) ?>"
                                data-activo="<?= htmlspecialchars($h['id_activo']) ?>"
                                data-almacen="<?= htmlspecialchars($h['id_almacen']) ?>"
                                data-fecha-ingreso="<?= $h['fecha_ingreso'] ? $h['fecha_ingreso']->format('Y-m-d') : '' ?>"
                                data-fecha-salida="<?= $h['fecha_salida'] ? $h['fecha_salida']->format('Y-m-d') : '' ?>"
                                data-observaciones="<?= htmlspecialchars($h['observaciones'] ?? '') ?>"
                                title="Editar registro">
                                <img src="../../img/editar.png" alt="Editar">
                            </button>

                            <?php if ($estado_movimiento === 'En Almacén'): ?>
                                <!-- Botón registrar salida -->
                                <button type="button" class="btn-icon btn-salida"
                                    data-id="<?= htmlspecialchars($h['id_historial']) ?>"
                                    data-activo="<?= htmlspecialchars($h['descripcion_activo']) ?>"
                                    data-almacen="<?= htmlspecialchars($h['almacen_nombre']) ?>"
                                    title="Registrar salida">
                                    <img src="../../img/salida.png" alt="Salida">
                                </button>
                            <?php endif; ?>

                            <!-- Botón eliminar -->
                            <form method="POST" action="../controllers/procesar_historial_almacen.php" 
                                style="display:inline;" 
                                onsubmit="return confirm('¿Eliminar este registro del historial?');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_historial" value="<?= htmlspecialchars($h['id_historial']) ?>">
                                <button type="submit" class="btn-icon">
                                    <img src="../../img/eliminar.png" alt="Eliminar">
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Modal para ingresar activo al almacén -->
<div id="modalIngreso" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Ingresar Activo al Almacén</h3>
        
        <form id="formIngreso" method="POST" action="../controllers/procesar_historial_almacen.php">
            <input type="hidden" name="accion" value="ingresar_activo">
            
            <label>Activo:</label>
            <select name="id_activo" id="id_activo" required>
                <option value="">Seleccione un activo...</option>
                <?php while ($activo = sqlsrv_fetch_array($activos_disponibles, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?= $activo['id_activo'] ?>">
                        <?= htmlspecialchars($activo['descripcion_activo']) ?>
                        (<?= htmlspecialchars($activo['estado_actual']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Almacén:</label>
            <select name="id_almacen" id="id_almacen" required>
                <option value="">Seleccione un almacén...</option>
                <?php 
                // Reset pointer para almacenes
                $almacenes = sqlsrv_query($conn, "SELECT id_almacen, nombre FROM almacen ORDER BY nombre");
                while ($almacen = sqlsrv_fetch_array($almacenes, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?= $almacen['id_almacen'] ?>">
                        <?= htmlspecialchars($almacen['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Fecha de Ingreso:</label>
            <input type="date" name="fecha_ingreso" id="fecha_ingreso" value="<?= date('Y-m-d') ?>" required>

            <label>Observaciones:</label>
            <textarea name="observaciones" id="observaciones" rows="3" 
                      placeholder="Motivo del ingreso, estado del equipo, etc."></textarea>

            <br>
            <button type="submit">Ingresar al Almacén</button>
        </form>
    </div>
</div>

<!-- Modal para registrar salida -->
<div id="modalSalida" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close close-salida">&times;</span>
        <h3>Registrar Salida del Almacén</h3>
        
        <form id="formSalida" method="POST" action="../controllers/procesar_historial_almacen.php">
            <input type="hidden" name="accion" value="salida_activo">
            <input type="hidden" name="id_historial" id="salida_id_historial">
            
            <div class="info-movimiento">
                <p><strong>Activo:</strong> <span id="salida_activo"></span></p>
                <p><strong>Almacén:</strong> <span id="salida_almacen"></span></p>
            </div>

            <label>Fecha de Salida:</label>
            <input type="date" name="fecha_salida" id="fecha_salida" value="<?= date('Y-m-d') ?>" required>

            <label>Observaciones de Salida:</label>
            <textarea name="observaciones_salida" id="observaciones_salida" rows="3" 
                      placeholder="Motivo de salida, destino, estado del equipo..."></textarea>

            <br>
            <button type="submit">Registrar Salida</button>
        </form>
    </div>
</div>

<!-- Modal para editar registro -->
<div id="modalEditar" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Editar Registro de Almacén</h3>
        
        <form id="formEditar" method="POST" action="../controllers/procesar_historial_almacen.php">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id_historial" id="editar_id_historial">
            
            <label>Activo:</label>
            <select name="id_activo" id="editar_id_activo" required>
                <option value="">Seleccione un activo...</option>
                <?php 
                // Consulta para todos los activos para el modal de edición
                $sql_todos_activos = "
                SELECT DISTINCT
                    a.id_activo,
                    CASE 
                        WHEN a.tipo_activo = 'Laptop' THEN CONCAT('Laptop - ', l.nombreEquipo, ' (', l.numeroSerial, ')')
                        WHEN a.tipo_activo = 'PC' THEN CONCAT('PC - ', p.nombreEquipo, ' (', p.numeroSerial, ')')
                        WHEN a.tipo_activo = 'Servidor' THEN CONCAT('Servidor - ', s.nombreEquipo, ' (', s.numeroSerial, ')')
                    END as descripcion_activo
                FROM activo a
                LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
                LEFT JOIN pc p ON a.id_pc = p.id_pc
                LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
                ORDER BY descripcion_activo";
                
                $todos_activos = sqlsrv_query($conn, $sql_todos_activos);
                while ($activo = sqlsrv_fetch_array($todos_activos, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?= $activo['id_activo'] ?>">
                        <?= htmlspecialchars($activo['descripcion_activo']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Almacén:</label>
            <select name="id_almacen" id="editar_id_almacen" required>
                <option value="">Seleccione un almacén...</option>
                <?php 
                $almacenes_edit = sqlsrv_query($conn, "SELECT id_almacen, nombre FROM almacen ORDER BY nombre");
                while ($almacen = sqlsrv_fetch_array($almacenes_edit, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?= $almacen['id_almacen'] ?>">
                        <?= htmlspecialchars($almacen['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Fecha de Ingreso:</label>
            <input type="date" name="fecha_ingreso" id="editar_fecha_ingreso" required>

            <label>Fecha de Salida:</label>
            <input type="date" name="fecha_salida" id="editar_fecha_salida">

            <label>Observaciones:</label>
            <textarea name="observaciones" id="editar_observaciones" rows="4" 
                      placeholder="Observaciones del registro..."></textarea>

            <br>
            <button type="submit">Actualizar Registro</button>
        </form>
    </div>
</div>

    <script src="../../js/admin/crud_historial_almacen.js"></script>
    <!-- Incluir JavaScript del Sidebar -->
    <script src="../../js/admin/sidebar.js"></script>

</body>
</html>
