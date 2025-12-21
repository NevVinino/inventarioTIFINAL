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
                $id_persona = $_POST["id_persona"] ?? '';
                $id_activo = $_POST["id_activo"] ?? '';
                $fecha_asignacion = $_POST["fecha_asignacion"] ?? '';
                $observaciones = trim($_POST["observaciones"] ?? '');

                // Validaciones
                if (empty($id_persona) || empty($id_activo) || empty($fecha_asignacion)) {
                    throw new Exception("Todos los campos obligatorios deben ser completados.");
                }

                if ($fecha_asignacion > date('Y-m-d')) {
                    throw new Exception("La fecha de asignación no puede ser posterior a hoy.");
                }

                // Verificar que el activo esté disponible
                $sql_check = "SELECT COUNT(*) as count FROM asignacion WHERE id_activo = ? AND fecha_retorno IS NULL";
                $stmt_check = sqlsrv_query($conn, $sql_check, [$id_activo]);
                $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                
                if ($row['count'] > 0) {
                    throw new Exception("Este activo ya está asignado a otra persona.");
                }

                sqlsrv_begin_transaction($conn);
                
                // Insertar asignación
                $sql_asignacion = "INSERT INTO asignacion (id_activo, id_persona, fecha_asignacion, observaciones, id_usuario) VALUES (?, ?, ?, ?, ?)";
                $stmt_asignacion = sqlsrv_query($conn, $sql_asignacion, [$id_activo, $id_persona, $fecha_asignacion, $observaciones, $id_usuario]);

                if ($stmt_asignacion === false) {
                    throw new Exception("Error al crear asignación: " . print_r(sqlsrv_errors(), true));
                }

                // Actualizar estado del activo a "Asignado"
                $sql_update_laptop = "UPDATE laptop SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Asignado') WHERE id_laptop IN (SELECT id_laptop FROM activo WHERE id_activo = ?)";

                $sql_update_pc = "UPDATE pc SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Asignado') WHERE id_pc IN (SELECT id_pc FROM activo WHERE id_activo = ?)";

                sqlsrv_query($conn, $sql_update_laptop, [$id_activo]);
                sqlsrv_query($conn, $sql_update_pc, [$id_activo]);

                sqlsrv_commit($conn);
                $respuesta['success'] = true;
                $respuesta['message'] = 'Asignación creada exitosamente';
                break;

            case "editar":
                $id_asignacion = $_POST["id_asignacion"] ?? '';
                $id_persona = $_POST["id_persona"] ?? '';
                $id_activo = $_POST["id_activo"] ?? '';
                $fecha_asignacion = $_POST["fecha_asignacion"] ?? '';
                $observaciones = trim($_POST["observaciones"] ?? '');

                // Validaciones
                if (empty($id_asignacion) || empty($id_persona) || empty($id_activo) || empty($fecha_asignacion)) {
                    throw new Exception("Todos los campos obligatorios deben ser completados.");
                }

                if ($fecha_asignacion > date('Y-m-d')) {
                    throw new Exception("La fecha de asignación no puede ser posterior a hoy.");
                }

                // Verificar que la asignación existe y está activa
                $sql_check = "SELECT id_activo FROM asignacion WHERE id_asignacion = ? AND fecha_retorno IS NULL";
                $stmt_check = sqlsrv_query($conn, $sql_check, [$id_asignacion]);
                $asignacion_actual = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                
                if (!$asignacion_actual) {
                    throw new Exception("Solo se pueden editar asignaciones activas.");
                }

                // Si se cambió el activo, verificar disponibilidad
                if ($asignacion_actual['id_activo'] != $id_activo) {
                    $sql_check_activo = "SELECT COUNT(*) as count FROM asignacion WHERE id_activo = ? AND fecha_retorno IS NULL";
                    $stmt_check_activo = sqlsrv_query($conn, $sql_check_activo, [$id_activo]);
                    $row = sqlsrv_fetch_array($stmt_check_activo, SQLSRV_FETCH_ASSOC);
                    
                    if ($row['count'] > 0) {
                        throw new Exception("El activo seleccionado ya está asignado a otra persona.");
                    }
                }

                sqlsrv_begin_transaction($conn);
                
                // Actualizar asignación
                $sql_update = "UPDATE asignacion SET id_persona = ?, id_activo = ?, fecha_asignacion = ?, observaciones = ? WHERE id_asignacion = ?";
                $stmt_update = sqlsrv_query($conn, $sql_update, [$id_persona, $id_activo, $fecha_asignacion, $observaciones, $id_asignacion]);

                if ($stmt_update === false) {
                    throw new Exception("Error al actualizar asignación: " . print_r(sqlsrv_errors(), true));
                }

                // Si cambió el activo, actualizar estados
                if ($asignacion_actual['id_activo'] != $id_activo) {
                    // Liberar activo anterior
                    $sql_update_old_laptop = "UPDATE laptop SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') WHERE id_laptop IN (SELECT id_laptop FROM activo WHERE id_activo = ?)";

                    $sql_update_old_pc = "UPDATE pc SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') WHERE id_pc IN (SELECT id_pc FROM activo WHERE id_activo = ?)";

                    sqlsrv_query($conn, $sql_update_old_laptop, [$asignacion_actual['id_activo']]);
                    sqlsrv_query($conn, $sql_update_old_pc, [$asignacion_actual['id_activo']]);

                    // Asignar nuevo activo
                    $sql_update_new_laptop = "UPDATE laptop SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Asignado') WHERE id_laptop IN (SELECT id_laptop FROM activo WHERE id_activo = ?)";

                    $sql_update_new_pc = "UPDATE pc SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Asignado') WHERE id_pc IN (SELECT id_pc FROM activo WHERE id_activo = ?)";

                    sqlsrv_query($conn, $sql_update_new_laptop, [$id_activo]);
                    sqlsrv_query($conn, $sql_update_new_pc, [$id_activo]);
                }

                sqlsrv_commit($conn);
                $respuesta['success'] = true;
                $respuesta['message'] = 'Asignación actualizada exitosamente';
                break;

            case "retornar":
                $id_asignacion = $_POST["id_asignacion"] ?? '';
                $fecha_retorno = $_POST["fecha_retorno"] ?? '';
                $observaciones_retorno = trim($_POST["observaciones_retorno"] ?? '');

                // Validaciones
                if (empty($id_asignacion) || empty($fecha_retorno)) {
                    throw new Exception("Todos los campos obligatorios deben ser completados.");
                }

                if ($fecha_retorno > date('Y-m-d')) {
                    throw new Exception("La fecha de retorno no puede ser posterior a hoy.");
                }

                // Verificar que la asignación existe y está activa
                $sql_check = "SELECT id_activo, fecha_asignacion FROM asignacion WHERE id_asignacion = ? AND fecha_retorno IS NULL";
                $stmt_check = sqlsrv_query($conn, $sql_check, [$id_asignacion]);
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
                $sql_update = "UPDATE asignacion SET fecha_retorno = ?, observaciones = CASE WHEN observaciones IS NULL OR observaciones = '' THEN ? ELSE observaciones + ' | RETORNO ' + ? END WHERE id_asignacion = ?";
                $stmt_update = sqlsrv_query($conn, $sql_update, [$fecha_retorno, $observaciones_retorno, $observaciones_retorno, $id_asignacion]);

                if ($stmt_update === false) {
                    throw new Exception("Error al registrar retorno: " . print_r(sqlsrv_errors(), true));
                }

                // Actualizar estado del activo a "Disponible"
                $id_activo = $asignacion['id_activo'];
                $sql_update_laptop = "UPDATE laptop SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') WHERE id_laptop IN (SELECT id_laptop FROM activo WHERE id_activo = ?)";
                $sql_update_pc = "UPDATE pc SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') WHERE id_pc IN (SELECT id_pc FROM activo WHERE id_activo = ?)";


                sqlsrv_query($conn, $sql_update_laptop, [$id_activo]);
                sqlsrv_query($conn, $sql_update_pc, [$id_activo]);

                sqlsrv_commit($conn);
                $respuesta['success'] = true;
                $respuesta['message'] = 'Retorno registrado exitosamente';
                break;

            case "eliminar":
                $id_asignacion = $_POST["id_asignacion"] ?? '';

                if (empty($id_asignacion)) {
                    throw new Exception("ID de asignación no proporcionado.");
                }

                // Obtener información de la asignación y datos del activo
                $sql_check = "
                SELECT 
                    asig.id_activo, 
                    asig.fecha_retorno,
                    act.tipo_activo,
                    CASE 
                        WHEN act.tipo_activo = 'Laptop' THEN lap.id_laptop
                        WHEN act.tipo_activo = 'PC' THEN pc.id_pc
                        WHEN act.tipo_activo = 'Servidor' THEN srv.id_servidor
                    END as id_equipo,
                    CONCAT(p.nombre, ' ', p.apellido) as persona_nombre
                FROM asignacion asig
                INNER JOIN activo act ON asig.id_activo = act.id_activo
                INNER JOIN persona p ON asig.id_persona = p.id_persona
                LEFT JOIN laptop lap ON act.id_laptop = lap.id_laptop
                LEFT JOIN pc pc ON act.id_pc = pc.id_pc
                LEFT JOIN servidor srv ON act.id_servidor = srv.id_servidor
                WHERE asig.id_asignacion = ?";
                
                $stmt_check = sqlsrv_query($conn, $sql_check, [$id_asignacion]);
                $asignacion = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                
                if (!$asignacion) {
                    throw new Exception("La asignación no existe.");
                }

                sqlsrv_begin_transaction($conn);
                
                // Si la asignación está activa (sin fecha de retorno), liberar el activo
                if ($asignacion['fecha_retorno'] === null) {
                    $sql_update_laptop = "UPDATE laptop SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') WHERE id_laptop IN (SELECT id_laptop FROM activo WHERE id_activo = ?)";
                    $sql_update_pc = "UPDATE pc SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') WHERE id_pc IN (SELECT id_pc FROM activo WHERE id_activo = ?)";
                    
                    sqlsrv_query($conn, $sql_update_laptop, [$asignacion['id_activo']]);
                    sqlsrv_query($conn, $sql_update_pc, [$asignacion['id_activo']]);
                }

                // Eliminar asignación de la base de datos
                $stmt_delete = sqlsrv_query($conn, "DELETE FROM asignacion WHERE id_asignacion = ?", [$id_asignacion]);
                
                if ($stmt_delete === false) {
                    throw new Exception("Error al eliminar: " . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_commit($conn);

                // Eliminar SOLO los archivos relacionadas (NO las carpetas)
                $archivos_eliminados = 0;
                $debug_info = [];
                
                try {
                    // Log información de debug
                    $debug_info[] = "ID Asignación: " . $id_asignacion;
                    $debug_info[] = "ID Activo: " . $asignacion['id_activo'];
                    $debug_info[] = "Tipo Activo: " . $asignacion['tipo_activo'];
                    $debug_info[] = "ID Equipo: " . $asignacion['id_equipo'];
                    $debug_info[] = "Persona: " . $asignacion['persona_nombre'];
                    
                    // Construir ruta de la carpeta del activo
                    $carpeta_base = "../../pdf";
                    $carpeta_asignacion = $carpeta_base . "/asignacion";
                    $carpeta_activo = $carpeta_asignacion . "/activo_" . $asignacion['id_activo'] . "_" . strtolower($asignacion['tipo_activo']) . "_" . $asignacion['id_equipo'];
                    
                    $debug_info[] = "Carpeta base: " . realpath($carpeta_base);
                    $debug_info[] = "Carpeta activo: " . $carpeta_activo;
                    $debug_info[] = "Carpeta activo real: " . realpath($carpeta_activo);
                    $debug_info[] = "Carpeta activo existe: " . (is_dir($carpeta_activo) ? 'SI' : 'NO');
                    
                    if (is_dir($carpeta_activo)) {
                        // Verificar subcarpetas
                        $subcarpeta_sin_firma = $carpeta_activo . "/sinFirma";
                        $subcarpeta_con_firma = $carpeta_activo . "/conFirma";
                        
                        $debug_info[] = "Subcarpeta sinFirma existe: " . (is_dir($subcarpeta_sin_firma) ? 'SI' : 'NO');
                        $debug_info[] = "Subcarpeta conFirma existe: " . (is_dir($subcarpeta_con_firma) ? 'SI' : 'NO');
                        
                        // ELIMINAR SOLO ARCHIVOS EN SUBCARPETA SIN FIRMA
                        if (is_dir($subcarpeta_sin_firma)) {
                            $archivos_sin_firma = array_diff(scandir($subcarpeta_sin_firma), ['.', '..']);
                            $debug_info[] = "Archivos en sinFirma: " . implode(', ', $archivos_sin_firma);
                            
                            foreach ($archivos_sin_firma as $archivo) {
                                // Buscar archivos que empiecen con "asignacion_{id}_"
                                if (strpos($archivo, "asignacion_" . $id_asignacion . "_") === 0) {
                                    $ruta_completa = $subcarpeta_sin_firma . "/" . $archivo;
                                    if (file_exists($ruta_completa) && unlink($ruta_completa)) {
                                        $archivos_eliminados++;
                                        $debug_info[] = "✅ Eliminado sinFirma: " . $archivo;
                                    } else {
                                        $debug_info[] = "❌ No se pudo eliminar sinFirma: " . $archivo;
                                    }
                                }
                            }
                        }
                        
                        // ELIMINAR SOLO ARCHIVOS EN SUBCARPETA CON FIRMA
                        if (is_dir($subcarpeta_con_firma)) {
                            $archivos_con_firma = array_diff(scandir($subcarpeta_con_firma), ['.', '..']);
                            $debug_info[] = "Archivos en conFirma: " . implode(', ', $archivos_con_firma);
                            
                            foreach ($archivos_con_firma as $archivo) {
                                // Buscar archivos que empiecen con "asignacion_{id}_"
                                if (strpos($archivo, "asignacion_" . $id_asignacion . "_") === 0) {
                                    $ruta_completa = $subcarpeta_con_firma . "/" . $archivo;
                                    if (file_exists($ruta_completa) && unlink($ruta_completa)) {
                                        $archivos_eliminados++;
                                        $debug_info[] = "✅ Eliminado conFirma: " . $archivo;
                                    } else {
                                        $debug_info[] = "❌ No se pudo eliminar conFirma: " . $archivo;
                                    }
                                }
                            }
                        }
                    } else {
                        $debug_info[] = "❌ La carpeta del activo no existe";
                    }
                    
                    // NO ELIMINAR CARPETAS - Solo reportar cuántos archivos se eliminaron
                    $debug_info[] = "🔄 Total de archivos eliminados: " . $archivos_eliminados;
                    
                } catch (Exception $e) {
                    $debug_info[] = "❌ Error en eliminación: " . $e->getMessage();
                    error_log("Error al eliminar archivos de asignación $id_asignacion: " . $e->getMessage());
                }

                $respuesta['success'] = true;
                $respuesta['message'] = 'Asignación eliminada exitosamente';
                $respuesta['archivos_eliminados'] = $archivos_eliminados;
                $respuesta['carpetas_eliminadas'] = 0; // Ya no eliminamos carpetas
                $respuesta['debug_info'] = $debug_info;
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
        header('Location: ../views/crud_asignacion.php?success=1');
    } else {
        header('Location: ../views/crud_asignacion.php?error=' . urlencode($respuesta['message']));
    }
    exit;
}

header('Location: ../views/crud_asignacion.php');
exit;
?>
 