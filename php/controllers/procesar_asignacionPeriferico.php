<?php
session_start();
include("../includes/conexion.php");

$respuesta = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';
    $id_usuario = $_SESSION["id_usuario"] ?? null;

    try {
        switch ($accion) {
            case "crear":
                $id_persona = $_POST["persona"] ?? '';
                $id_periferico = $_POST["periferico"] ?? '';
                $fecha_asignacion = $_POST["fecha_asignacion"] ?? '';
                $observaciones = trim($_POST["observaciones"] ?? '');

                // Validaciones
                if (empty($id_persona) || empty($id_periferico) || empty($fecha_asignacion)) {
                    throw new Exception("Todos los campos obligatorios deben ser completados.");
                }

                if ($fecha_asignacion > date('Y-m-d')) {
                    throw new Exception("La fecha de asignación no puede ser posterior a hoy.");
                }

                // Verificar que el periférico esté disponible
                $sql_check = "SELECT COUNT(*) as count FROM asignacion_periferico WHERE id_periferico = ? AND fecha_retorno IS NULL";
                $stmt_check = sqlsrv_query($conn, $sql_check, [$id_periferico]);
                $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                
                if ($row['count'] > 0) {
                    throw new Exception("Este periférico ya está asignado a otra persona.");
                }

                sqlsrv_begin_transaction($conn);
                
                // Insertar asignación
                $sql_asignacion = "INSERT INTO asignacion_periferico (id_persona, id_periferico, fecha_asignacion, observaciones) VALUES (?, ?, ?, ?)";
                $stmt_asignacion = sqlsrv_query($conn, $sql_asignacion, [$id_persona, $id_periferico, $fecha_asignacion, $observaciones]);

                if ($stmt_asignacion === false) {
                    throw new Exception("Error al crear asignación de periférico: " . print_r(sqlsrv_errors(), true));
                }

                // Actualizar estado del periférico a "Asignado"
                $sql_update_estado = "UPDATE periferico SET id_estado_periferico = (SELECT id_estado_periferico FROM estado_periferico WHERE vestado_periferico = 'Asignado') WHERE id_periferico = ?";
                $stmt_update = sqlsrv_query($conn, $sql_update_estado, [$id_periferico]);
                
                if ($stmt_update === false) {
                    throw new Exception("Error al actualizar estado del periférico");
                }

                sqlsrv_commit($conn);
                $respuesta['success'] = true;
                $respuesta['message'] = 'Asignación de periférico creada exitosamente';
                break;

            case "editar":
                $id_asignacion_periferico = $_POST["id_asignacion_periferico"] ?? '';
                $id_persona = $_POST["persona"] ?? '';
                $id_periferico = $_POST["periferico"] ?? '';
                $fecha_asignacion = $_POST["fecha_asignacion"] ?? '';
                $observaciones = trim($_POST["observaciones"] ?? '');

                // Validaciones
                if (empty($id_asignacion_periferico) || empty($id_persona) || empty($id_periferico) || empty($fecha_asignacion)) {
                    throw new Exception("Todos los campos obligatorios deben ser completados.");
                }

                if ($fecha_asignacion > date('Y-m-d')) {
                    throw new Exception("La fecha de asignación no puede ser posterior a hoy.");
                }

                // Verificar que la asignación existe y está activa
                $sql_check = "SELECT id_periferico FROM asignacion_periferico WHERE id_asignacion_periferico = ? AND fecha_retorno IS NULL";
                $stmt_check = sqlsrv_query($conn, $sql_check, [$id_asignacion_periferico]);
                $asignacion_actual = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                
                if (!$asignacion_actual) {
                    throw new Exception("Solo se pueden editar asignaciones activas.");
                }

                // Si se cambió el periférico, verificar disponibilidad
                if ($asignacion_actual['id_periferico'] != $id_periferico) {
                    $sql_check_periferico = "SELECT COUNT(*) as count FROM asignacion_periferico WHERE id_periferico = ? AND fecha_retorno IS NULL";
                    $stmt_check_periferico = sqlsrv_query($conn, $sql_check_periferico, [$id_periferico]);
                    $row = sqlsrv_fetch_array($stmt_check_periferico, SQLSRV_FETCH_ASSOC);
                    
                    if ($row['count'] > 0) {
                        throw new Exception("El periférico seleccionado ya está asignado a otra persona.");
                    }
                }

                sqlsrv_begin_transaction($conn);
                
                // Actualizar asignación
                $sql_update = "UPDATE asignacion_periferico SET id_persona = ?, id_periferico = ?, fecha_asignacion = ?, observaciones = ? WHERE id_asignacion_periferico = ?";
                $stmt_update = sqlsrv_query($conn, $sql_update, [$id_persona, $id_periferico, $fecha_asignacion, $observaciones, $id_asignacion_periferico]);

                if ($stmt_update === false) {
                    throw new Exception("Error al actualizar asignación de periférico: " . print_r(sqlsrv_errors(), true));
                }

                // Si cambió el periférico, actualizar estados
                if ($asignacion_actual['id_periferico'] != $id_periferico) {
                    // Liberar periférico anterior
                    $sql_free_old = "UPDATE periferico SET id_estado_periferico = (SELECT id_estado_periferico FROM estado_periferico WHERE vestado_periferico = 'Disponible') WHERE id_periferico = ?";
                    sqlsrv_query($conn, $sql_free_old, [$asignacion_actual['id_periferico']]);

                    // Asignar nuevo periférico
                    $sql_assign_new = "UPDATE periferico SET id_estado_periferico = (SELECT id_estado_periferico FROM estado_periferico WHERE vestado_periferico = 'Asignado') WHERE id_periferico = ?";
                    sqlsrv_query($conn, $sql_assign_new, [$id_periferico]);
                }

                sqlsrv_commit($conn);
                $respuesta['success'] = true;
                $respuesta['message'] = 'Asignación de periférico actualizada exitosamente';
                break;

            case "retornar":
                $id_asignacion_periferico = $_POST["id_asignacion_periferico"] ?? '';
                $fecha_retorno = $_POST["fecha_retorno"] ?? '';
                $observaciones_retorno = trim($_POST["observaciones_retorno"] ?? '');

                // Validaciones
                if (empty($id_asignacion_periferico) || empty($fecha_retorno)) {
                    throw new Exception("Todos los campos obligatorios deben ser completados.");
                }

                if ($fecha_retorno > date('Y-m-d')) {
                    throw new Exception("La fecha de retorno no puede ser posterior a hoy.");
                }

                // Verificar que la asignación existe y está activa
                $sql_check = "SELECT id_periferico, fecha_asignacion FROM asignacion_periferico WHERE id_asignacion_periferico = ? AND fecha_retorno IS NULL";
                $stmt_check = sqlsrv_query($conn, $sql_check, [$id_asignacion_periferico]);
                $asignacion = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                
                if (!$asignacion) {
                    throw new Exception("La asignación no existe o ya fue retornada.");
                }

                // Verificar que la fecha de retorno sea posterior a la de asignación
                $fecha_asig = $asignacion['fecha_asignacion'];
                if ($fecha_asig instanceof DateTime) {
                    $fecha_asig = $fecha_asig->format('Y-m-d');
                }
                
                if ($fecha_retorno < $fecha_asig) {
                    throw new Exception("La fecha de retorno no puede ser anterior a la fecha de asignación.");
                }

                sqlsrv_begin_transaction($conn);
                
                // Actualizar la asignación con fecha de retorno
                $sql_update = "UPDATE asignacion_periferico SET fecha_retorno = ?, observaciones = CASE WHEN observaciones IS NULL OR observaciones = '' THEN ? ELSE observaciones + ' | RETORNO: ' + ? END WHERE id_asignacion_periferico = ?";
                $stmt_update = sqlsrv_query($conn, $sql_update, [$fecha_retorno, $observaciones_retorno, $observaciones_retorno, $id_asignacion_periferico]);

                if ($stmt_update === false) {
                    throw new Exception("Error al registrar retorno: " . print_r(sqlsrv_errors(), true));
                }

                // Actualizar estado del periférico a "Disponible"
                $sql_free = "UPDATE periferico SET id_estado_periferico = (SELECT id_estado_periferico FROM estado_periferico WHERE vestado_periferico = 'Disponible') WHERE id_periferico = ?";
                $stmt_free = sqlsrv_query($conn, $sql_free, [$asignacion['id_periferico']]);
                
                if ($stmt_free === false) {
                    throw new Exception("Error al liberar estado del periférico");
                }

                sqlsrv_commit($conn);
                $respuesta['success'] = true;
                $respuesta['message'] = 'Retorno registrado exitosamente';
                break;

            case "eliminar":
                $id_asignacion_periferico = $_POST["id_asignacion_periferico"] ?? '';

                if (empty($id_asignacion_periferico)) {
                    throw new Exception("ID de asignación no proporcionado.");
                }

                // Obtener información de la asignación
                $sql_check = "SELECT id_periferico, fecha_retorno FROM asignacion_periferico WHERE id_asignacion_periferico = ?";
                $stmt_check = sqlsrv_query($conn, $sql_check, [$id_asignacion_periferico]);
                $asignacion = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                
                if (!$asignacion) {
                    throw new Exception("La asignación no existe.");
                }

                sqlsrv_begin_transaction($conn);
                
                // Si la asignación está activa (sin fecha de retorno), liberar el periférico
                if ($asignacion['fecha_retorno'] === null) {
                    $sql_free = "UPDATE periferico SET id_estado_periferico = (SELECT id_estado_periferico FROM estado_periferico WHERE vestado_periferico = 'Disponible') WHERE id_periferico = ?";
                    sqlsrv_query($conn, $sql_free, [$asignacion['id_periferico']]);
                }

                // Eliminar asignación
                $stmt_delete = sqlsrv_query($conn, "DELETE FROM asignacion_periferico WHERE id_asignacion_periferico = ?", [$id_asignacion_periferico]);
                
                if ($stmt_delete === false) {
                    throw new Exception("Error al eliminar: " . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_commit($conn);
                $respuesta['success'] = true;
                $respuesta['message'] = 'Asignación eliminada exitosamente';
                break;

            default:
                throw new Exception("Acción no válida.");
        }
    } catch (Exception $e) {
        if (sqlsrv_begin_transaction($conn)) {
            sqlsrv_rollback($conn);
        }
        $respuesta['message'] = $e->getMessage();
    }

    // Si es una petición AJAX, devolver JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($respuesta);
        exit;
    }
    
    // Si no es AJAX, redirigir con parámetros
    if ($respuesta['success']) {
        header('Location: ../views/crud_asignacionPeriferico.php?success=1');
    } else {
        header('Location: ../views/crud_asignacionPeriferico.php?error=' . urlencode($respuesta['message']));
    }
    exit;
}

header('Location: ../views/crud_asignacionPeriferico.php');
exit;
?>
