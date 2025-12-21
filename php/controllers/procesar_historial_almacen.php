<?php
session_start();
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';
    $id_usuario = $_SESSION["id_usuario"] ?? null;

    try {
        if ($accion === "ingresar_activo") {
            $id_activo = $_POST["id_activo"] ?? '';
            $id_almacen = $_POST["id_almacen"] ?? '';
            $fecha_ingreso = $_POST["fecha_ingreso"] ?? date('Y-m-d');
            $observaciones = trim($_POST["observaciones"] ?? '');

            // Validaciones
            if (empty($id_activo) || empty($id_almacen)) {
                throw new Exception("Debe seleccionar un activo y un almacén.");
            }

            // Verificar que el activo no esté ya en almacén (sin fecha de salida)
            $sql_check = "SELECT COUNT(*) as count FROM historial_almacen 
                         WHERE id_activo = ? AND fecha_salida IS NULL";
            $stmt_check = sqlsrv_query($conn, $sql_check, [$id_activo]);
            $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
            
            if ($row['count'] > 0) {
                throw new Exception("Este activo ya está en almacén.");
            }

            // Verificar que el activo no esté asignado
            $sql_check_asig = "SELECT COUNT(*) as count FROM asignacion 
                              WHERE id_activo = ? AND fecha_retorno IS NULL";
            $stmt_check_asig = sqlsrv_query($conn, $sql_check_asig, [$id_activo]);
            $row_asig = sqlsrv_fetch_array($stmt_check_asig, SQLSRV_FETCH_ASSOC);
            
            if ($row_asig['count'] > 0) {
                throw new Exception("No se puede ingresar al almacén un activo que está asignado.");
            }

            sqlsrv_begin_transaction($conn);
            
            // Insertar en historial_almacen
            $sql_historial = "INSERT INTO historial_almacen (id_activo, id_almacen, fecha_ingreso, observaciones) 
                             VALUES (?, ?, ?, ?)";
            $stmt_historial = sqlsrv_query($conn, $sql_historial, [$id_activo, $id_almacen, $fecha_ingreso, $observaciones]);

            if ($stmt_historial === false) {
                throw new Exception("Error al registrar ingreso al almacén: " . print_r(sqlsrv_errors(), true));
            }

            // Actualizar estado del activo a "Almacen"
            $sql_update_estado = "
                UPDATE laptop SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Almacen') 
                WHERE id_laptop = (SELECT id_laptop FROM activo WHERE id_activo = ? AND tipo_activo = 'Laptop');
                
                UPDATE pc SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Almacen') 
                WHERE id_pc = (SELECT id_pc FROM activo WHERE id_activo = ? AND tipo_activo = 'PC');
                
                UPDATE servidor SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Almacen') 
                WHERE id_servidor = (SELECT id_servidor FROM activo WHERE id_activo = ? AND tipo_activo = 'Servidor');
            ";
            
            $stmts = explode(';', $sql_update_estado);
            foreach ($stmts as $stmt_sql) {
                if (trim($stmt_sql)) {
                    sqlsrv_query($conn, trim($stmt_sql), [$id_activo]);
                }
            }

            sqlsrv_commit($conn);
            header("Location: ../views/crud_historial_almacen.php?success=ingreso");
            exit;

        } elseif ($accion === "salida_activo") {
            $id_historial = $_POST["id_historial"] ?? '';
            $fecha_salida = $_POST["fecha_salida"] ?? date('Y-m-d');
            $observaciones_salida = trim($_POST["observaciones_salida"] ?? '');

            if (empty($id_historial)) {
                throw new Exception("ID de historial no válido.");
            }

            // Obtener información del historial
            $sql_get_historial = "SELECT h.id_activo, h.fecha_ingreso, h.observaciones 
                                 FROM historial_almacen h 
                                 WHERE h.id_historial = ? AND h.fecha_salida IS NULL";
            $stmt_get_historial = sqlsrv_query($conn, $sql_get_historial, [$id_historial]);
            $historial_info = sqlsrv_fetch_array($stmt_get_historial);
            
            if (!$historial_info) {
                throw new Exception("El activo no está en almacén o ya fue retirado.");
            }

            // Verificar que la fecha de salida no sea anterior a la de ingreso
            if ($fecha_salida < $historial_info['fecha_ingreso']->format('Y-m-d')) {
                throw new Exception("La fecha de salida no puede ser anterior a la fecha de ingreso.");
            }

            sqlsrv_begin_transaction($conn);
            
            // Actualizar historial con fecha de salida
            $observaciones_completas = $historial_info['observaciones'];
            if (!empty($observaciones_salida)) {
                $observaciones_completas .= (!empty($observaciones_completas) ? ' | ' : '') . 'SALIDA: ' . $observaciones_salida;
            }
            
            $sql_salida = "UPDATE historial_almacen SET fecha_salida = ?, observaciones = ? 
                          WHERE id_historial = ?";
            $stmt_salida = sqlsrv_query($conn, $sql_salida, [$fecha_salida, $observaciones_completas, $id_historial]);
            
            if ($stmt_salida === false) {
                throw new Exception("Error al registrar salida del almacén");
            }

            // Actualizar estado del activo a "Disponible"
            $id_activo = $historial_info['id_activo'];
            $sql_update_estado = "
                UPDATE laptop SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') 
                WHERE id_laptop = (SELECT id_laptop FROM activo WHERE id_activo = ? AND tipo_activo = 'Laptop');
                
                UPDATE pc SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') 
                WHERE id_pc = (SELECT id_pc FROM activo WHERE id_activo = ? AND tipo_activo = 'PC');
                
                UPDATE servidor SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') 
                WHERE id_servidor = (SELECT id_servidor FROM activo WHERE id_activo = ? AND tipo_activo = 'Servidor');
            ";
            
            $stmts = explode(';', $sql_update_estado);
            foreach ($stmts as $stmt_sql) {
                if (trim($stmt_sql)) {
                    sqlsrv_query($conn, trim($stmt_sql), [$id_activo]);
                }
            }

            sqlsrv_commit($conn);
            header("Location: ../views/crud_historial_almacen.php?success=salida");
            exit;

        } elseif ($accion === "editar") {
            $id_historial = $_POST["id_historial"] ?? '';
            $id_activo = $_POST["id_activo"] ?? '';
            $id_almacen = $_POST["id_almacen"] ?? '';
            $fecha_ingreso = $_POST["fecha_ingreso"] ?? '';
            $fecha_salida = $_POST["fecha_salida"] ?? null;
            $observaciones = trim($_POST["observaciones"] ?? '');

            // Validaciones
            if (empty($id_historial) || empty($id_activo) || empty($id_almacen) || empty($fecha_ingreso)) {
                throw new Exception("Todos los campos obligatorios deben ser completados.");
            }

            // Validar fechas
            if ($fecha_salida && $fecha_salida < $fecha_ingreso) {
                throw new Exception("La fecha de salida no puede ser anterior a la fecha de ingreso.");
            }

            // Limpiar fecha_salida si está vacía
            if (empty($fecha_salida)) {
                $fecha_salida = null;
            }

            // Obtener información actual del registro
            $sql_get_current = "SELECT id_activo, fecha_salida FROM historial_almacen WHERE id_historial = ?";
            $stmt_get_current = sqlsrv_query($conn, $sql_get_current, [$id_historial]);
            $current_data = sqlsrv_fetch_array($stmt_get_current);
            
            if (!$current_data) {
                throw new Exception("Registro de historial no encontrado.");
            }

            $activo_anterior = $current_data['id_activo'];
            $tenia_fecha_salida = $current_data['fecha_salida'] !== null;

            sqlsrv_begin_transaction($conn);
            
            // Actualizar el registro
            $sql_update = "UPDATE historial_almacen SET 
                          id_activo = ?, 
                          id_almacen = ?, 
                          fecha_ingreso = ?, 
                          fecha_salida = ?, 
                          observaciones = ? 
                          WHERE id_historial = ?";
            
            $stmt_update = sqlsrv_query($conn, $sql_update, [
                $id_activo, 
                $id_almacen, 
                $fecha_ingreso, 
                $fecha_salida, 
                $observaciones, 
                $id_historial
            ]);

            if ($stmt_update === false) {
                throw new Exception("Error al actualizar registro del historial");
            }

            // Actualizar estados de activos si es necesario
            
            // Si cambió el activo, restaurar el estado del activo anterior
            if ($activo_anterior != $id_activo) {
                if (!$tenia_fecha_salida) {
                    // El activo anterior estaba en almacén, ponerlo disponible
                    $sql_restore_anterior = "
                        UPDATE laptop SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') 
                        WHERE id_laptop = (SELECT id_laptop FROM activo WHERE id_activo = ? AND tipo_activo = 'Laptop');
                        
                        UPDATE pc SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') 
                        WHERE id_pc = (SELECT id_pc FROM activo WHERE id_activo = ? AND tipo_activo = 'PC');
                        
                        UPDATE servidor SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') 
                        WHERE id_servidor = (SELECT id_servidor FROM activo WHERE id_activo = ? AND tipo_activo = 'Servidor');
                    ";
                    
                    $stmts = explode(';', $sql_restore_anterior);
                    foreach ($stmts as $stmt_sql) {
                        if (trim($stmt_sql)) {
                            sqlsrv_query($conn, trim($stmt_sql), [$activo_anterior]);
                        }
                    }
                }
            }

            // Actualizar estado del activo actual según si tiene fecha de salida o no
            $estado_activo = ($fecha_salida === null) ? 'Almacen' : 'Disponible';
            
            $sql_update_actual = "
                UPDATE laptop SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = ?) 
                WHERE id_laptop = (SELECT id_laptop FROM activo WHERE id_activo = ? AND tipo_activo = 'Laptop');
                
                UPDATE pc SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = ?) 
                WHERE id_pc = (SELECT id_pc FROM activo WHERE id_activo = ? AND tipo_activo = 'PC');
                
                UPDATE servidor SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = ?) 
                WHERE id_servidor = (SELECT id_servidor FROM activo WHERE id_activo = ? AND tipo_activo = 'Servidor');
            ";
            
            $stmts = explode(';', $sql_update_actual);
            foreach ($stmts as $stmt_sql) {
                if (trim($stmt_sql)) {
                    sqlsrv_query($conn, trim($stmt_sql), [$estado_activo, $id_activo]);
                }
            }

            sqlsrv_commit($conn);
            header("Location: ../views/crud_historial_almacen.php?success=editado");
            exit;

        } elseif ($accion === "eliminar") {
            $id_historial = $_POST["id_historial"] ?? '';

            if (empty($id_historial)) {
                throw new Exception("ID de historial no válido.");
            }

            // Obtener información antes de eliminar
            $sql_get_info = "SELECT id_activo, fecha_salida FROM historial_almacen WHERE id_historial = ?";
            $stmt_get_info = sqlsrv_query($conn, $sql_get_info, [$id_historial]);
            $info = sqlsrv_fetch_array($stmt_get_info);
            
            if (!$info) {
                throw new Exception("Registro de historial no encontrado.");
            }

            sqlsrv_begin_transaction($conn);
            
            // Eliminar registro
            $sql_delete = "DELETE FROM historial_almacen WHERE id_historial = ?";
            $stmt_delete = sqlsrv_query($conn, $sql_delete, [$id_historial]);
            
            if ($stmt_delete === false) {
                throw new Exception("Error al eliminar registro del historial");
            }

            // Si el activo estaba activo en almacén (sin fecha de salida), actualizar su estado a Disponible
            if ($info['fecha_salida'] === null) {
                $sql_update_estado = "
                    UPDATE laptop SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') 
                    WHERE id_laptop = (SELECT id_laptop FROM activo WHERE id_activo = ? AND tipo_activo = 'Laptop');
                    
                    UPDATE pc SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') 
                    WHERE id_pc = (SELECT id_pc FROM activo WHERE id_activo = ? AND tipo_activo = 'PC');
                    
                    UPDATE servidor SET id_estado_activo = (SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible') 
                    WHERE id_servidor = (SELECT id_servidor FROM activo WHERE id_activo = ? AND tipo_activo = 'Servidor');
                ";
                
                $stmts = explode(';', $sql_update_estado);
                foreach ($stmts as $stmt_sql) {
                    if (trim($stmt_sql)) {
                        sqlsrv_query($conn, trim($stmt_sql), [$info['id_activo']]);
                    }
                }
            }

            sqlsrv_commit($conn);
            header("Location: ../views/crud_historial_almacen.php?success=eliminado");
            exit;

        } else {
            throw new Exception("Acción no válida.");
        }

    } catch (Exception $e) {
        if (sqlsrv_begin_transaction($conn)) {
            sqlsrv_rollback($conn);
        }
        header("Location: ../views/crud_historial_almacen.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

header('Location: ../views/crud_historial_almacen.php');
exit;
?>
